<?php
$config['version'] = '0.1';

// ---------------------------------------------------------------------------
// DB config
// ---------------------------------------------------------------------------
$config['db'] = array(
    'type' => 'mysql',
    'host' => getenv("DB_HOST"),//'localhost',
    'port' => '3306',
    'database' => getenv("DB_DATABASE"),
    'username' => getenv("DB_USER"),
    'password' => getenv("DB_PASSWORD"),
    'charset' => 'utf8',
    'connexion_timeout' => 5, //in seconds
);

$config['memcached'] = array(
	'enabled' => false,
);

$domains = explode(',',getenv("JITSI_DOMAIN"));

$config['conf_mapper'] = array(
    'pin_digit_number' => 10,
    'meet_domain' => $domains,    
    'lifetime_hours' => array(
            'long' => getenv("LIFETIME_LONG"),
            'short' => getenv("LIFETIME_SHORT")
        )
);

$numberList = explode(',',getenv("NUMBER_LIST"));
$config['number_list'] = $numberList ;

$numberLabel = explode(',',getenv("NUMBER_LABEL_LIST"));
$config['number_label'] = $numberLabel ;

$extensionList = explode(',',getenv("JIGASI_EXTANSION_LIST"));
$config['jigasi_extension_list'] = $extensionList;

$config['syslog'] = array(
    'enabled' => false,
    'debug' => true,
    'identifier' => 'policyServerLog'
);
