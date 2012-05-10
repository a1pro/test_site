<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

$notebook_page = 'E-Gold';
config_set_notebook_comment($notebook_page, 'E-Gold plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.egold.merchant_id', 'Your E-Gold account number',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.egold.merchant_name', 'Account Name',
    'text', "displayed to client",
    $notebook_page,
    '');
add_config_field('payment.egold.secret_id', 'Alternate passphrase in your e-gold account',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.egold.units', 'Processing Currency',
    'text', "1 - USD",
    $notebook_page,
    '');
add_config_field('payment.egold.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'E-Gold'));
add_config_field('payment.egold.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay with e-gold'));
?>
