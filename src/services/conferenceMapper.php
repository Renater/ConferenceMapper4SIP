<?php


require_once('../config/config.php');

spl_autoload_register(function($class) {
    if (file_exists('../classes/'.$class.'.php'))
        require_once('../classes/'.$class.'.php');
});

/**
 * @var array $config
 */


try {
    
    if ($config['syslog']['enabled']) {
        openlog($config['syslog']['identifier'], LOG_PID, LOG_LOCAL0);
    }
    

    /***  Conference Mapper ***/
    $roomNumReq = key_exists('id', $_GET) ? $_GET['id'] : null;
    $roomNameReq = key_exists('conference', $_GET) ? $_GET['conference'] : null;

    $mail = key_exists('mail', $_GET) ? $_GET['mail'] : null;

    $longTerm = filter_input(INPUT_GET, 'suffix',  FILTER_VALIDATE_BOOLEAN,
                                  array( 'options' => array('default'=> 0) ));


    /*** Log request ****/
    error_log("Request NUM:$roomNumReq NAME:$roomNameReq LT:$longTerm MAIL:$mail");


    if ($roomNumReq || $roomNameReq) {

        /** get room number/name **/
        preg_match_all('/(\w+:)?([^@]+)(@.*)?/', $roomNumReq, $roomNumReq);
        preg_match_all('/(\w+:)?([^@]+)(@.*)?/', $roomNameReq, $roomNameReq);
        $roomNum = null;
        $roomName = null;
        $roomDomain = null;

        if (!empty($roomNumReq[2])) {
            $roomNum = $roomNumReq[2][0];
        }
        if (!empty($roomNameReq[2])) {
            $roomName = $roomNameReq[2][0];
        }
        if (!empty($roomNameReq[3])) {
            $roomDomain = substr($roomNameReq[3][0], 1);
        }

        // look for tenant presence in request
        $tenant = Utils::extractTenant($roomNameReq[0][0],$config['conf_mapper']['meet_domain']);
        error_log("Tenant $tenant $roomName $roomDomain");

        // Copy tenant information from domain to Roomname
        if (!empty($tenant) && (Utils::getTenantFromRoom($roomName)=="") ){
            $roomName=$tenant.'/'.$roomName;
        }

        if (!is_null($roomName) && !Utils::isValidDomain($roomDomain, $config['conf_mapper']['meet_domain'])) {
            $response['error'] = "Expected domain is: ".implode(' or ',$config['conf_mapper']['meet_domain']);
            RestResponse::send($response, 400, [] );
            return;
        }
        
        $response = array();
        $myDB = new CustomDBI();

        /** look for a valid mapping **/
        $meetInstance = Utils::extractMeetingInstance($roomDomain, $config['conf_mapper']['meet_domain']);
        
        $mappingDb = $myDB->getMapping($roomName, $roomNum, $meetInstance);


        /* Conference name requested for a given number */
        if (!$roomName) {
            if ($mappingDb && $mappingDb['room_name']) {
                $response['message'] = "Successfully retrieved conference mapping";
                $response['id'] = $roomNum;
                $meetInstance = $mappingDb['meet_instance'];
                $response['conference'] = Utils::formatIntoJid($mappingDb['room_name'],$meetInstance);
                $response['url'] = $mappingDb['room_name'];
                $response['meeting_instance'] = $meetInstance;
                $response['mail_owner'] = $mappingDb['mail_owner'];
            }
            else {
                /*  The provided number is not valid  */
                $response['error'] = "Provided number is not valid";
            }
        }

        /*  Conference number requested for a given name */
        if (!$roomNum) {
            /* This conference name does not exist in DB */
            if (!$mappingDb) {
                $roomNum = $myDB->setMapping($roomName, $meetInstance, $longTerm, $mail);
                $mappingDb['mail_owner']=$mail;
            }
            /* Maybe the conference insertion in DB is not finalized (due to others calls...) */
            elseif( !isset($mappingDb['room_number']) ) {
                $roomNum = $myDB->setRoomNUmber($roomName,$meetInstance);
            }
            else {
                $roomNum = $mappingDb['room_number'];
                // Update conference mail attribute if changed
                if ( ( isset($mail) && $mappingDb['mail_owner']!= $mail )  )
                    $myDB->updateMapping($roomName,$mail);
            }

            if( !$roomNum ) {
                $response['error'] = "Conference Mapper internal error";
            }
            else {
                $response['message'] = "Successfully retrieved conference mapping";
                $response['id'] = $roomNum;
                $response['conference'] = Utils::formatIntoJid($roomName,$meetInstance);
                $response['url'] = $roomName;
                $response['meeting_instance'] = $meetInstance;
                $response['mail_owner'] = $mappingDb['mail_owner'];
            }
        }

        $jsonResp = json_encode($response);

        if ($config['syslog']['enabled'] && $config['syslog']['debug'] && key_exists('error', $response)) {
            syslog(LOG_DEBUG, 'error: ' . json_encode($_GET));
            syslog(LOG_DEBUG, 'error: ' . $jsonResp);
        }
        RestResponse::send($jsonResp);

        return;
    }
    
    return;
    
} catch (Exception $e){
    RestResponse::send($e->getMessage(), 500);
    error_log($e->getMessage());
    return;
}
