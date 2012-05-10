<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 

$notebook_page = 'ABN-AMRO';
config_set_notebook_comment($notebook_page, 'abnamro.nl plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.abnamro.pspid', 'Your affiliation name in ABN-AMRO system',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.abnamro.userid', 'Name of your application (API) user',
    'text', "Please refer to the ABN-AMRO User Manager documentation<br/>
for information on how to create an API user",
    $notebook_page, 
    '');
add_config_field('payment.abnamro.pswd', 'Password of the API user (USERID)',
    'password_c', "",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));

add_config_field('payment.abnamro.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

add_config_field('payment.abnamro.currency', 'Currency',
    'text', "ISO alpha order currency code, <br/>
	for example: EUR, USD, GBP, CHF, …",
    $notebook_page);

cc_core_add_config_items('abnamro', $notebook_page);
?>
