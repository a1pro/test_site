<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Locaweb';
config_set_notebook_comment($notebook_page, 'Locaweb plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.locaweb.identificacao', 'Identificacao',
    'text', "Code for the Locaweb e-commerce service",
    $notebook_page, 
    '');
add_config_field('payment.locaweb.secret', 'Security Code',
    'text', "Securyty Code that you set in your AlerPay account",
    $notebook_page, 
    '', '', '');

add_config_field('payment.locaweb.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));




add_config_field('payment.locaweb.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Locaweb'));
add_config_field('payment.locaweb.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay by credit card/debit card - Visa/Mastercard'));
?>
