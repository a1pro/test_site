<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Quickpay_CC';
config_set_notebook_comment($notebook_page, 'Quickpay_CC configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;

add_config_field('payment.quickpay_cc.merchant_id', 'Quickpay Merchant ID',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.quickpay_cc.secret', 'Quickpay Secret Key',
    'text', 
    "",
    $notebook_page, 
    '');
add_config_field('payment.quickpay_cc.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));
add_config_field('payment.quickpay_cc.currency', 'The transaction currency',
    'text', "as the 3-letter ISO 4217 alphabetical code",
    $notebook_page, 
    '');
cc_core_add_config_items('quickpay_cc', $notebook_page);
?>
