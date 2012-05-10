<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 

$notebook_page = 'Payvision';
config_set_notebook_comment($notebook_page, 'payvision plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.payvision.memberid', 'Your memberId in Payvision system',
    'text', "this value is provided by Payvision
and is used to authenticate a
merchant.",
    $notebook_page, 
    '');
add_config_field('payment.payvision.memberguid', 'Your memberGuid in Payvision system',
    'text', "This value is provided by Payvision
and is used to authenticate a
merchant.",
    $notebook_page, 
    '');

add_config_field('payment.payvision.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));
//ISO 4217
add_config_field('payment.payvision.currency', 'Currency',
    'select', "",
    $notebook_page,
    '','','',
    array('options' => array("840"=>"USD","978"=>"EUR")));

cc_core_add_config_items('payvision', $notebook_page);
?>
