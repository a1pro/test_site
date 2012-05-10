<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Saferpay';
config_set_notebook_comment($notebook_page, 'Saferpay plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.saferpay.merchant', 'Your Saferpay Account Id',
    'text', "like 12345-12312345",
    $notebook_page, 
    '');
add_config_field('payment.saferpay.password', 'Your Saferpay Account Password',
    'password_c', "",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));

add_config_field('payment.saferpay.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Saferpay'));
add_config_field('payment.saferpay.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay by credit card/debit card - Visa/Mastercard'));
add_config_field('payment.saferpay.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

?>
