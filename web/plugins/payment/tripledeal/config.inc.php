<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'TripleDeal';
config_set_notebook_comment($notebook_page, 'TripleDeal plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.tripledeal.merchant_name', 'Your TripleDeal Merchant Name',
    'text', "your TripleDeal merchant name",
    $notebook_page,
    '');
add_config_field('payment.tripledeal.merchant_password', 'Your TripleDeal Merchant Password',
    'password_c', "your TripleDeal merchant password",
    $notebook_page,
    '');

add_config_field('payment.tripledeal.profile', 'Your TripleDeal Profile',
    'text', "your TripleDeal profile",
    $notebook_page,
    '', '', '',
    array('default' => 'OnlyCreditCards'));

add_config_field('payment.tripledeal.debugmode', 'Debug Mode Enabled', 'select',
    "Set to No after you complete testing.", $notebook_page, '','','',
    array('options' => array(0 => 'No', 1 => 'Yes'))
);


add_config_field('payment.tripledeal.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'TripleDeal'));
add_config_field('payment.tripledeal.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
