<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");


global $config;
require_once(dirname(__FILE__)."/wirecard.inc.php");
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

$notebook_page = "wirecard";

config_set_notebook_comment($notebook_page, $notebook_page . ' configuration');
if (file_exists($rm = dirname(__FILE__) . "/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.wirecard.gateway', 'wirecard gateway',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.wirecard.user', 'wirecard gateway login',
    'text', "",
    $notebook_page, 
    '');

add_config_field('payment.wirecard.pass', 'wirecard gateway Password',
    'password_c', "",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));

add_config_field('payment.wirecard.signature', 'Business Case Signature',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.wirecard.country', 'Country Code',
    'text', "This is the ISO 3166-1 code of the country where<br/>
the transaction takes place.",
    $notebook_page, 
    '');
add_config_field('payment.wirecard.mode', 'Mode',
    'select', "",
    $notebook_page, 
    '','','',
    array('options' => array('demo' => 'demo', 'live' => 'live')));

cc_core_add_config_items('wirecard', $notebook_page);

?>
