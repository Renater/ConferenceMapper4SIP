<?php
$config['version'] = '0.1';

// ---------------------------------------------------------------------------
// DB config
// ---------------------------------------------------------------------------
$config['db'] = array(
    'type' => 'mysql',
    'host' => getenv("DB_HOST"),//'localhost',
    'port' => '3306',
    'database' => 'rendezvous',
    'username' => getenv("DB_USER"),
    'password' => getenv("DB_PASSWORD"),
    'charset' => 'utf8',
    'connexion_timeout' => 5, //in seconds
);

$config['memcached'] = array(
	'enabled' => false,
);

$config['conf_mapper'] = array(
    'pin_digit_number' => 10,
    'meet_domain' => "conference.rdv42.rendez-vous.renater.fr",
    'lifetime_hours' => array(
            'long' => 1440,
            'short' => 6
        )
);

$config['phone_number_list'] = array(
    '0978080962'
);

$config['jigasi_extension_list'] = array(
    '666'
);

$config['syslog'] = array(
    'enabled' => false,
    'debug' => true,
    'identifier' => 'policyServerLog'
);
