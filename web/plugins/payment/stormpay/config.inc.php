<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

$notebook_page = 'StormPay';
config_set_notebook_comment($notebook_page, 'stormpay plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.stormpay.business', 'StormPay Account E-Mail',
    'text', "your email address registered in StormPay",
    $notebook_page,
    'validate_email_address');

add_config_field('payment.stormpay.secret_id', 'Your Secret Code (Profile/IPN configuration)',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.stormpay.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'StormPay'));
add_config_field('payment.stormpay.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
