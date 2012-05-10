<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'WorldPay';
config_set_notebook_comment($notebook_page, 'WorldPay plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.worldpay.installation_id', 'Your WorldPay installation ID',
    'integer', "your WorldPay installation ID",
    $notebook_page, 
    'validate_integer');
add_config_field('payment.worldpay.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));
add_config_field('payment.worldpay.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'WorldPay'));
add_config_field('payment.worldpay.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay by credit card/debit card - Visa/Mastercard/Eurocard/Delta/Solo/Switch/JCB'));
?>
