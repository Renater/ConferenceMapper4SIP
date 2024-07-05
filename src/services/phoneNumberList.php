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

    $response['message'] = "Phone numbers available.";

    $numbers = $config['number_list'];
    $numbersLabel = $config['number_label'];
    for ($i=0;$i<sizeof($numbers);$i++){
        $response['numbers'][$numbersLabel[$i]] = [$numbers[$i]];    
    }
   
    $response['numbersEnabled'] = true;
    $jsonResp = json_encode($response);

    RestResponse::send($jsonResp);

    return;

} catch (Exception $e){
    error_log($e->getMessage());
    RestResponse::send($e->getMessage(), 500);
    return;
}
