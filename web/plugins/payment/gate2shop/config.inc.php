<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'Gate2Shop';
config_set_notebook_comment($notebook_page, 'Gate2Shop Configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.gate2shop.merchant_id', 'Gate2Shop Merchant ID',
    'text', "The Merchant's unique identification number as provided by Gate2Shop",
    $notebook_page,
    '');
add_config_field('payment.gate2shop.site_id', 'Gate2Shop Site ID',
    'text', "The Merchant's web site's unique identification number as provided by Gate2Shop",
    $notebook_page,
    '');
add_config_field('payment.gate2shop.secret', 'Gate2Shop Secret Key',
    'password_c', "secret key from your Gate2Shop account for the checksum calculation",
    $notebook_page,
    '');

add_config_field('payment.gate2shop.method', 'Use this request method',
    'select', "plugin will use an HTTP GET or POST request",
    $notebook_page,
    '','','',
    array('options' => array('GET' => 'GET', 'POST' => 'POST'), 'default' => 'GET'));

add_config_field('payment.gate2shop.testing', 'Use this API version',
    'select', "use 1.0.0 for testing",
    $notebook_page,
    '','','',
    array('options' => array(0 => '3.0.0', 1 => '1.0.0')));

?>
