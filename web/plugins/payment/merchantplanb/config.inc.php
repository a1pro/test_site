<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'MerchantPlanb';
config_set_notebook_comment($notebook_page, 'MerchantPlanb configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.merchantplanb.merchant_id', 'MerchantPlanb ID',
    'text', "your MerchantPlanb Username",
    $notebook_page, 
    '');
add_config_field('payment.merchantplanb.merchant_pass', 'MerchantPlanb password',
    'password_c', "your MerchantPlanb Password",
    $notebook_page, 
    '');

add_config_field('payment.merchantplanb.currency_code', 'MerchantPlanb CurrencyCode',
    'text', "used if you want you are processing in non USD",
    $notebook_page, 
    '', '', '',
    array('default' => 'USD'));

/*
add_config_field('payment.merchantplanb.testmode', 'Test Mode Enabled', 'select', 
    "Set to No after you complete testing.", $notebook_page, '','','',
    array('options' => array(0 => 'No', 1 => 'Yes'))
);
*/

add_config_field('payment.merchantplanb.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'MerchantPlanb'));
add_config_field('payment.merchantplanb.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));

?>
