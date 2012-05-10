<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Virtual Merchant';
config_set_notebook_comment($notebook_page, 'Vertual Merchant configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.vmerchant.merchant_id', 'Virtual Merchant account ID',
    'text', "Set the value to the Virtual Merchant account ID.",
    $notebook_page, 
    '');
add_config_field('payment.vmerchant.pin', 'PIN',
    'text', 
    "Set the value to the merchant PIN associated with the
Virtual Merchant ID.",
    $notebook_page, 
    '');

add_config_field('payment.vmerchant.user_id', 'User ID',
    'text', 
    "",
    $notebook_page, 
    '');     
 
add_config_field('payment.vmerchant.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array('0' => 'No', '1' => 'Yes')));

cc_core_add_config_items('vmerchant', $notebook_page);
?>
