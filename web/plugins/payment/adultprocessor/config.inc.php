<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'AdultProcessor';
config_set_notebook_comment($notebook_page, 'AdultProcessor plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.adultprocessor.url', 'AdultProcessor Post Url',
    'text', "for example https://secure2.adultprocessor.com/",
    $notebook_page, 
    '', '', '',
    array('default' => 'https://secure2.adultprocessor.com/'));

add_config_field('payment.adultprocessor.merchantid', 'AdultProcessor Merchant ID',
    'text', "",
    $notebook_page, 
    '', '', '');
add_config_field('payment.adultprocessor.merchant_password', 'AdultProcessor Merchant Password',
    'text', "",
    $notebook_page, 
    '','','');
add_config_field('payment.adultprocessor.serverid', 'AdultProcessor Remote Server ID',
    'text', "",
    $notebook_page, 
    '', '', '');
add_config_field('payment.adultprocessor.method', 'AdultProcessor Request method',
    'select', "request method for initial payment",
    $notebook_page, 
    '', '', '',
	array('options' => array(0 => 'Hosted forms', 1 => 'POST method')));




add_config_field('payment.adultprocessor.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'AdultProcessor'));
add_config_field('payment.adultprocessor.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay by credit card/debit card - Visa/Mastercard'));
?>
