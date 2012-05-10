<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'Skypay';
config_set_notebook_comment($notebook_page, 'Skypay Configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.skypay.username', 'Skypay Username',
    'text', "Username for payment API.",
    $notebook_page,
    '');
add_config_field('payment.skypay.password', 'Skypay Password',
    'text', "Password for payment API.",
    $notebook_page,
    '');

add_config_field('payment.skypay.testMode', 'Test Mode Enabled',
    'select', "If set to 'Yes' then only test card details will operate.",
    $notebook_page,
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

add_config_field('payment.skypay.dispatch', 'Dispatch',
    'select', "Whether the payment should be taken immediately.",
    $notebook_page,
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

add_config_field('payment.skypay.dispatchAmount', 'Dispatch Amount',
    'text', "When Dispatch set to 'No' this determines the value that will be pre-authenticated.<br />Whole amount if zero or not set.",
    $notebook_page,
    '');

add_config_field('payment.skypay.avscv2', 'AVSCV2 Check',
    'select', "Perform checks on the Address and CVV number on the card.",
    $notebook_page,
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

add_config_field('payment.skypay.threeDSecure', '3D Secure Check',
    'select', "Perform 3DSecure validation on the order if the cardholder is enrolled.<br />You must have this feature enabled on your account for it to work.",
    $notebook_page,
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

add_config_field('payment.skypay.formTemplate', 'Form Template',
    'text', "Name of a customised payment template on the Skypay site, uses default if not set.<br />You will need to register to use this service.",
    $notebook_page,
    '');

add_config_field('payment.skypay.vendorEmail', 'Vendor Email',
    'text', "Comma separated list of emails to receive payment success/failure emails.",
    $notebook_page,
    '');

add_config_field('payment.skypay.debug', 'Debug',
    'select', "Logs debugging messages into a file (/plugins/payment/skypay/skypay_debug_log.php).<br />
				Keep it off unless you need to debug the payment process,<br />as it creates lots of output and the file could easily become unmanagable.",
    $notebook_page,
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

add_config_field('payment.skypay.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'Skypay'));
add_config_field('payment.skypay.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => 'Pay by credit/debit card'));

// we need an extra table for the payment stuff
include_once(dirname(__FILE__) . '/skypay.php');
$skypay = new Skypay('');
$skypay->createTransactionTable();