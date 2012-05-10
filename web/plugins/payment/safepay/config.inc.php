<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

$notebook_page = 'SafePay';
config_set_notebook_comment($notebook_page, 'safepay plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.safepay.owner', 'SafePay Account',
    'text', "your account name registered in SafePay",
    $notebook_page,
    '');

add_config_field('payment.safepay.notifyEml', 'Product Purchase Notification E-mail',
    'text', "The e-mail address where you will be emailed<br />that a user has just made a purchase of your Product",
    $notebook_page,
    '');

add_config_field('payment.safepay.testmode', 'Test Mode Enabled', 'select', 
    "Set to No after you complete testing.", $notebook_page, '','','',
    array('options' => array(0 => 'No', 1 => 'Yes'))
);

add_config_field('payment.safepay.secret_id', 'Your Secret Passphrase',
    'password_c', "(Profile/IPN configuration)",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3)
);
add_config_field('payment.safepay.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'SafePay'));
add_config_field('payment.safepay.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
