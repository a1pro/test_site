<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'WebMoney';
config_set_notebook_comment($notebook_page, 'WebMoney plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.webmoney.purse', 'Your WebMoney Purse ID',
    'text', "for example: R111111111111",
    $notebook_page);

add_config_field('payment.webmoney.password', 'Your WebMoney Transfer MerchantPassword',
    'password_c', "",
    $notebook_page);


add_config_field('payment.webmoney.interface', 'WebMoney Interface',
    'select', 'An interface of payment page',
    $notebook_page,
    '', '', '',
    array('options' => array(
        'rus'    => 'WMKeeper Light (rus)',
        'eng'    => 'WMKeeper Light (eng)',
        'keeper' => 'WMKeeper Classic'
    ))
);

add_config_field('payment.webmoney.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page,
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));


add_config_field('payment.webmoney.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'WebMoney'));
add_config_field('payment.webmoney.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => "Make payments with WebMoney - it's fast, free and secure!")
    );
?>
