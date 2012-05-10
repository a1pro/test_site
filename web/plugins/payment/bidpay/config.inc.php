<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'BidPay';
config_set_notebook_comment($notebook_page, 'BidPay plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.bidpay.username', 'API Username',
    'text', "your BidPay API username",
    $notebook_page,
    '');
add_config_field('payment.bidpay.password', 'API Password',
    'text', "your BidPay API password",
    $notebook_page,
    '');

add_config_field('payment.bidpay.token', 'Seller Token',
    'text', "your BidPay Seller Token<br /><a href=\"".$config['root_url']."/plugins/payment/bidpay/seller_token.php\" target=\"_blank\">Click here</a> to generate it",
    $notebook_page,
    '');

add_config_field('payment.bidpay.testmode', 'Sandbox Testing',
    'select', "set to No after you complete testing",
    $notebook_page,
    '','','',
    array('options' => array('N' => 'No', 'Y' => 'Yes')));

add_config_field('payment.bidpay.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'BidPay'));
add_config_field('payment.bidpay.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
