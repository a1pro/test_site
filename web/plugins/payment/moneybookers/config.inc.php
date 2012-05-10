<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'MoneyBookers';
config_set_notebook_comment($notebook_page, 'MoneyBookers plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.moneybookers.business', 'MoneyBookers account email',
    'text', "your email address registered in MoneyBooks",
    $notebook_page, 
    'validate_email_address');
add_config_field('payment.moneybookers.password', 'Secret word',
    'password_c', "
    lostpassword answer to question 1 of the merchant's<br />
    moneybookers account.
    ",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));
add_config_field('payment.moneybookers.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'MoneyBookers'));
add_config_field('payment.moneybookers.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
