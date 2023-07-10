<?php

spl_autoload_register(function($class) {
    if (file_exists('../classes/'.$class.'.class.php'))
        require_once('../classes/'.$class.'.class.php');
});

/**
 * Class CustomDBI add facilites for database usage
 */
class CustomDBI {
    /**
     * Send an SQL command to the database
     *
     * @param string $sql
     * @param array $pdoParams
     * @param null $retData
     * @return boolean $res
     */
    private function request(string $sql, array $pdoParams, &$retData=null): bool
    {
        global $config;
        $res = false;
        try {
            $dbh = new PDO('mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['database'],
                            $config['db']['username'], $config['db']['password'],
                            array(
                                PDO::ATTR_TIMEOUT => $config['db']['connexion_timeout'], // in seconds
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                            ));

            $sth = $dbh->prepare($sql);

            foreach ($pdoParams as $param) {
                $sth->bindValue(':'.$param['name'], $param['value'], $param['type']);
            }
            $res = $sth->execute();

            if (strpos($sql, 'SELECT') !== false) {
                if (isset($retData)) {
                    $retData = $sth->fetchAll();
                }
                if($retData) {
                    $res = true;
                }
            }
            return $res;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return $res;
        }
    }


    /**
     * generate a memcached key from a room name
     *
     * @param string $name
     * @return string $key
     * @throws Exception
     */
    private function getKeyFromName(string $name): ?string
    {
        $validKey = '@'.base64_encode($name);
        if (strlen($validKey) < 250) {
            return $validKey;
        }
            return null;
    }


    /**
     * Encode an input number with N digits length
     *
     * @param $inNumber
     * @param $maxDigits
     * @return string
     */
    private function encodeNumberForNDigits($inNumber,$maxDigits): string
    {

        $crypto = new Cryptomute(
            'aes-128-cbc',      // cipher
            '0123456789zxcvbn', // base key
            7                  // number of rounds
        );
        $max = pow(10,$maxDigits)-1;
        $crypto->setValueRange(1, $max);
        $password = '0123456789qwerty';
        $iv = '0123456789abcdef';

        return $crypto->encrypt($inNumber, 10, true, $password, $iv);
    }


    /**
     * Set room number in database
     *
     * @param $roomName
     * @return string
     * @throws Exception
     */
    public function setRoomNumber($roomName): ?string
    {

        global $config;
        $room = CustomDBI::getRoom($roomName, null, null);
        if (!$room) {
            return null;
        }
        $roomNum = CustomDBI::encodeNumberForNDigits($room['db_id'], $config['conf_mapper']['pin_digit_number']);
        $sql = "UPDATE rooms
        SET room_number= :in_num
        WHERE db_id= :id";
        $params = array(
            array('name' => 'in_num', 'value' => $roomNum, 'type' => PDO::PARAM_STR),
            array('name' => 'id', 'value' => $room['db_id'], 'type' => PDO::PARAM_INT),
        );

        if (CustomDBI::request($sql, $params)) {
            return $roomNum;
        }
        else {
            return null;
        }
    }


    /**
     * Create a new entry in room table
     *
     * @param $roomName
     * @param $longTerm
     * @return string
     * @throws Exception
     */
    public function setRoom($roomName, $longTerm): ?string
    {

        $dateNow = new DateTime("now", new DateTimeZone('UTC'));
        $sql = "INSERT INTO rooms (room_name,creation_time,long_term) 
                VALUES (:in_name,:in_time,:long_term)";

        $params = array(
            array('name' => 'in_name',   'value' => $roomName, 'type' => PDO::PARAM_STR),
            array('name' => 'in_time',   'value' => $dateNow->format('Y-m-d H:i:s'), 'type' => PDO::PARAM_STR),
            array('name' => 'long_term', 'value' => $longTerm, 'type' => PDO::PARAM_BOOL),
        );

        if(!CustomDBI::request($sql, $params)) {
            return null;
        }

        return CustomDBI::setRoomNumber($roomName);

    }


    /**
     * Retrieve room from database
     *
     * @param $roomName
     * @param $roomNum
     * @param $cache
     * @return array
     * @throws Exception
     */
    public function getRoom($roomName, $roomNum, $cache): ?array
    {

        global $config;
        $dateNow = new DateTime("now", new DateTimeZone('UTC'));

        if ($cache) {
            $room = false;
            if (isset($roomNum)) {
                $room = $cache->get($roomNum, null);
            }
            elseif (isset($roomName)) {
                $room = $cache->get(CustomDBI::getKeyFromName($roomName), null);
            }
            if ($room !== false) {
                /** Check lifetime **/
                $lifetime = $room['long_term'] ? $config['conf_mapper']['lifetime']['long']:
                                                 $config['conf_mapper']['lifetime']['short'];
                $dateEnd = new DateTime($room['creation_time'], new DateTimeZone('UTC'));
                if ($dateEnd->modify('+'.$lifetime.' hours') > $dateNow) {
                    return $room;
                }
            }
        }

        $sql = 'SELECT db_id,room_name,room_number,room_pin,meet_instance,creation_time,long_term
        FROM rooms
        WHERE   room_number = :in_num OR room_name = :in_name
            AND (((DATE_ADD(creation_time, INTERVAL :long_tl HOUR) > :in_time) AND long_term = 1)
                 OR ((DATE_ADD(creation_time, INTERVAL :short_tl HOUR) > :in_time) AND long_term = 0))
        ORDER BY creation_time DESC';

        $params = array(
            array('name' => 'in_num', 'value' => $roomNum, 'type' => PDO::PARAM_STR),
            array('name' => 'in_name', 'value' => $roomName, 'type' => PDO::PARAM_STR),
            array('name' => 'in_time', 'value' => $dateNow->format('Y-m-d H:i:s'), 'type' => PDO::PARAM_STR),
            array('name' => 'short_tl', 'value' => $config['conf_mapper']['lifetime_hours']['short'],
                  'type' => PDO::PARAM_INT),
            array('name' => 'long_tl', 'value' => $config['conf_mapper']['lifetime_hours']['long'],
                  'type' => PDO::PARAM_INT),
        );

        $room = New ArrayObject();
        if(!CustomDBI::request($sql, $params, $room)) {
            return NULL;
        }

        if (is_array($room) && count($room)) {
            if ($cache) {
                $cache->add($roomNum, $room[0], $config['memcached']['time_to_leave']);
                $cache->add(CustomDBI::getKeyFromName($roomName), $room[0], $config['memcached']['time_to_leave']);
            }
            return $room[0];
        }
        else {
            return null;
        }
    }
}
