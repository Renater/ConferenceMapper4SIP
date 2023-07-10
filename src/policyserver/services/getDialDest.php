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

    $randKey = array_rand($config['jigasi_extension_list'], 1);
    $response['jigasi_ext'] = $config['jigasi_extension_list'][$randKey];

    $jsonResp = json_encode($response);

    echo $jsonResp;

    return;

} catch (Exception $e){
    error_log($e->getMessage());
    return;
}
