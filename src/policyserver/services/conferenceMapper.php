<?php


require_once('../config/config.php');

spl_autoload_register(function($class) {
    if (file_exists('../classes/'.$class.'.class.php'))
        require_once('../classes/'.$class.'.class.php');
});

/**
 * @var array $config
 */


try {
    
    if ($config['syslog']['enabled']) {
        openlog($config['syslog']['identifier'], LOG_PID, LOG_LOCAL0);
    }
    
    /*** init header ***/
    header_remove();
    header('Content-Type: application/json');
    header("Access-Control-Allow-Origin: *");
    

    /***  Conference Mapper ***/
    $roomNumReq = key_exists('id', $_GET) ? $_GET['id'] : null;
    $roomNameReq = key_exists('conference', $_GET) ? $_GET['conference'] : null;

    $mail = key_exists('mail', $_GET) ? $_GET['mail'] : null;

    $longTerm = filter_input(INPUT_GET, 'suffix',  FILTER_VALIDATE_BOOLEAN,
                                  array( 'options' => array('default'=> 0) ));


    /*** Log request ****/
    error_log("Request NUM:$roomNumReq NAME:$roomNameReq LT:$longTerm MAIL:$mail");


    if ($roomNumReq || $roomNameReq) {

        http_response_code(200);

        /** get room number/name **/
        preg_match_all('/(\w+:)?([^@]+)(@.*)?/', $roomNumReq, $roomNumReq);
        preg_match_all('/(\w+:)?([^@]+)(@.*)?/', $roomNameReq, $roomNameReq);
        $roomNum = $roomNumReq[2][0];
        $roomName = $roomNameReq[2][0];
        $roomDomain = substr($roomNameReq[3][0],1);

        if ($roomName && $roomDomain!=$config['conf_mapper']['meet_domain']) {
            $response['error'] = "Expected domain is @".$config['conf_mapper']['meet_domain'];
            echo json_encode($response);

            return;
        }
        
        /** configure cache **/
        $cache = NULL;
        if ($config['memcached']['enabled'] && class_exists('Memcached')) {
            $cache = new Memcached();
            $cache->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
            foreach ($config['memcached']['servers'] as $server) {
                $cacheTmp = new Memcached();
                $cacheTmp->addServer($server['host'], $server['port']);
                if ($cacheTmp->getStats()) {
                    $cache->addServer($server['host'], $server['port']);
                }
            }
        }
        $response = array();
        $myDB = new CustomDBI();
        /** search conference **/
        $conferenceDb = $myDB->getRoom($roomName, $roomNum, $cache);

        /* Conference name requested for a given number */
        if (!$roomName) {
            if ($conferenceDb && $conferenceDb['room_name']) {
                $response['message'] = "Successfully retrieved conference mapping";
                $response['id'] = $roomNum;
                $response['conference'] = $conferenceDb['room_name'];
            }
            else {
                /*  The provided number is not valid  */
                $response['error'] = "Provided number is not valid";
            }
        }

        /*  Conference number requested for a given name */
        if (!$roomNum) {
            /* This conference name does not exist in DB */
            if (!$conferenceDb) {
                $roomNum = $myDB->setRoom($roomName, $longTerm, $mail);
            }
            /* Maybe the conference insertion in DB is not finalized (due to others calls...) */
            elseif( !isset($conferenceDb['room_number']) ) {
                $roomNum = $myDB->setRoomNUmber($roomName);
            }
            else {
                $roomNum = $conferenceDb['room_number'];
                // Update conference attribute if changed
                if ( ( isset($mail) && $conferenceDb['mail_owner']!= $mail ) || $conferenceDb['long_term']!=$longTerm )
                    $myDB->updateRoom($roomName,$longTerm,$mail);
            }

            if( !$roomNum ) {
                $response['error'] = "Conference Mapper internal error";
            }
            else {
                $response['message'] = "Successfully retrieved conference mapping";
                $response['id'] = $roomNum;
                $response['conference'] = $roomName.'@'.$config['conf_mapper']['meet_domain'];
            }
        }

        $jsonResp = json_encode($response);

        if ($config['syslog']['enabled'] && $config['syslog']['debug'] && key_exists('error', $response)) {
            syslog(LOG_DEBUG, 'error: ' . json_encode($_GET));
            syslog(LOG_DEBUG, 'error: ' . $jsonResp);
        }
        echo  $jsonResp;

        return;
    }
    
    return;
    
} catch (Exception $e){
    error_log($e->getMessage());
    return;
}
