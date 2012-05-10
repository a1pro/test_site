<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'AlertPay';
config_set_notebook_comment($notebook_page, 'AlertPay plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.alertpay.merchant', 'Your AlertPay Merchant Email',
    'text', "The email that the payment for this purchase
should be sent to",
    $notebook_page, 
    '');
add_config_field('payment.alertpay.secret', 'Security Code',
    'text', "Securyty Code that you set in your AlerPay account",
    $notebook_page, 
    '', '', '');

add_config_field('payment.alertpay.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));
add_config_field('payment.alertpay.currency', 'The transaction currency',
    'text', "as the 3-letter ISO 4217 alphabetical code",
    $notebook_page, 
    '');




add_config_field('payment.alertpay.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'AlertPay'));
add_config_field('payment.alertpay.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay by credit card/debit card - Visa/Mastercard'));
?>
