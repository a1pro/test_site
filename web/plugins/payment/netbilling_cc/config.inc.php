<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'NetBilling CC';
config_set_notebook_comment($notebook_page, 'NetBilling CC Configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.netbilling_cc.account_id', 'NetBilling Account ID',
    'text', "your NetBilling Account ID",
    $notebook_page, 
    '');
/*
add_config_field('payment.netbilling_cc.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));
*/
add_config_field('payment.netbilling_cc.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'NetBilling'));
add_config_field('payment.netbilling_cc.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>