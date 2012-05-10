<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'GTBill';
config_set_notebook_comment($notebook_page, 'GTBill Configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.gtbill.merchant_id', 'GTBill Merchant ID',
    'text', "Merchant reference ID supplied by GTBill",
    $notebook_page,
    '');
add_config_field('payment.gtbill.site_id', 'GTBill Site ID',
    'text', "A site reference ID supplied by GTBill<br />for each of your websites",
    $notebook_page,
    '');

add_config_field('payment.gtbill.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'GTBill'));
add_config_field('payment.gtbill.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => 'Redirect'));
?>