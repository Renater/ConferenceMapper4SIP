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

    $randKey = array_rand($config['jigasi_extension_list'], 1);
    $response['jigasi_ext'] = $config['jigasi_extension_list'][$randKey];

    $jsonResp = json_encode($response);

    RestResponse::send($jsonResp);

    echo $jsonResp;

    return;

} catch (Exception $e){
    error_log($e->getMessage());
    RestResponse::send($e->getMessage(), 500);
    return;
}
