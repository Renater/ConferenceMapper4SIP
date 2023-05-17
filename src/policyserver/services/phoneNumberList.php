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

    $response['message'] = "Phone numbers available.";
    $response['numbers']["FR"] = $config['phone_number_list'];
    $response['numbers']["Other"] = ["more"];
    $response['numbersEnabled'] = true;

    $jsonResp = json_encode($response);

    echo $jsonResp;

    return;

} catch (Exception $e){
    error_log($e->getMessage());
    return;
}
