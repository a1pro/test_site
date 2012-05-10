<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'PagSeguro';
config_set_notebook_comment($notebook_page, 'PagSeguro plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.pagseguro.merchant_email', 'Your PagSeguro Merchant Email',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.pagseguro.token', 'Security Token',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.pagseguro.currency', 'Currency',
    'text', "",
    $notebook_page, 
    '', '', '');
add_config_field('payment.pagseguro.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'PagSeguro'));
add_config_field('payment.pagseguro.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay by credit card/debit card - Visa/Mastercard'));
?>
