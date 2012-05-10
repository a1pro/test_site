<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'RealEx';
config_set_notebook_comment($notebook_page, 'RealEx Configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.realex.merchant_id', 'RealEx Merchant ID',
    'text', "your RealEx Merchant ID",
    $notebook_page,
    '');
add_config_field('payment.realex.account', 'RealEx Sub-account',
    'text', "This is the realex payments sub-account to use.<br />If you omit this element then we will use your default account.",
    $notebook_page,
    '');

add_config_field('payment.realex.secret', 'RealEx secret key',
    'password_c', "your RealEx secret key",
    $notebook_page,
    '');


add_config_field('payment.realex.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'RealEx'));
add_config_field('payment.realex.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>