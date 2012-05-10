<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Protx';
config_set_notebook_comment($notebook_page, 'Protx plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.protx.login', 'Your Protx login',
    'text', "your Protx username",
    $notebook_page, 
    '');
add_config_field('payment.protx.pass', 'Your Protx password',
    'password_c', "your Protx password",
    $notebook_page, 
    'validate_password', '', '', 
    array('store_type' => 3));
add_config_field('payment.protx.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));
add_config_field('payment.protx.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Protx'));
add_config_field('payment.protx.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay by credit card/debit card - Visa/Mastercard/Eurocard/Delta/Solo/Switch/JCB'));
?>
