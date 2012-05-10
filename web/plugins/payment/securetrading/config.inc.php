<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'SecureTrading';
config_set_notebook_comment($notebook_page, 'securetrading plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.securetrading.merchant', 'SecureTrading merchant ID',
    'text', "Merchant ID",
    $notebook_page, 
    '');
add_config_field('payment.securetraiding.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'SecureTrading'));
add_config_field('payment.securetraiding.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
