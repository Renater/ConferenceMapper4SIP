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
    public function setRoomNumber($roomName,$domain): ?string
    {

        global $config;
        $room = CustomDBI::getMapping($roomName, null, $domain);
        if (!$room) {
            return null;
        }
        $roomNum = CustomDBI::encodeNumberForNDigits($room['db_id'], $config['conf_mapper']['pin_digit_number']);
        $sql = "UPDATE conference_mapping
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
    public function setMapping($roomName, $domain, $longTerm, $mail): ?string
    {

        $dateNow = new DateTime("now", new DateTimeZone('UTC'));
        $sql = "INSERT INTO conference_mapping (room_name,creation_time,long_term,mail_owner,meet_instance) 
                VALUES (:in_name,:in_time,:long_term,:in_mail,:in_domain)";

        $params = array(
            array('name' => 'in_name',   'value' => $roomName, 'type' => PDO::PARAM_STR),
            array('name' => 'in_time',   'value' => $dateNow->format('Y-m-d H:i:s'), 'type' => PDO::PARAM_STR),
            array('name' => 'long_term', 'value' => $longTerm, 'type' => PDO::PARAM_BOOL),
            array('name' => 'in_domain', 'value' => $domain, 'type' => PDO::PARAM_STR),
            array('name' => 'in_mail',   'value' => $mail, 'type' => PDO::PARAM_STR),
        );

        if(!CustomDBI::request($sql, $params)) {
            return null;
        }

        return CustomDBI::setRoomNumber($roomName, $domain);

    }

    /**
     * Update the value longTerm and mail_owner of the last entry roomName in room table
     *
     * @param $roomName
     * @param $longTerm
     * @param $mail
     * @return Boolean
     * @throws Exception
     */
    public function updateMapping($roomName, $mail): ?bool
    {
        $sql = 'UPDATE conference_mapping
        SET mail_owner= :in_mail
        WHERE room_name = :in_name
        ORDER BY creation_time DESC
        LIMIT 1' ; 

        $params = array(
            array('name' => 'in_name', 'value' => $roomName, 'type' => PDO::PARAM_STR),
            array('name' => 'in_mail',   'value' => $mail, 'type' => PDO::PARAM_STR),
        );
        return CustomDBI::request($sql, $params);
    }

    /**
     * Retrieve room from database
     *
     * @param $roomName
     * @param $roomNum
     * @param $domain
     * @return array
     * @throws Exception
     */
    public function getMapping($roomName, $roomNum, $domain): ?array
    {

        global $config;
        $dateNow = new DateTime("now", new DateTimeZone('UTC'));

        $sql = 'SELECT db_id,room_name,room_number,room_pin,meet_instance,creation_time,long_term,mail_owner
        FROM conference_mapping
        WHERE    (room_number = :in_num OR (room_name = :in_name AND meet_instance = :in_domain) )
            AND (((DATE_ADD(creation_time, INTERVAL :long_tl HOUR) > :in_time) AND long_term = 1)
                 OR ((DATE_ADD(creation_time, INTERVAL :short_tl HOUR) > :in_time) AND long_term = 0))
        ORDER BY creation_time DESC';

        $params = array(
            array('name' => 'in_num', 'value' => $roomNum, 'type' => PDO::PARAM_STR),
            array('name' => 'in_name', 'value' => $roomName, 'type' => PDO::PARAM_STR),
            array('name' => 'in_domain', 'value' => $domain, 'type' => PDO::PARAM_STR),
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
            return $room[0];
        }
        else {
            return null;
        }
    }
}
