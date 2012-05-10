<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'CCBill';
config_set_notebook_comment($notebook_page, 'ccbill plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.ccbill.account', 'Your Account Id in ccbill',
    'text', "your account number on ccBill, like 112233",
    $notebook_page, 
    '', '','',
    array('size' => 12));
add_config_field('payment.ccbill.sub_account', 'Subaccount number',
    'text', "like 0001 or 0002",
    $notebook_page
);
add_config_field('payment.ccbill.datalink_user', 'ccBill DataLink Username',
    'text', "read ccBill plugin readme (11) about",
    $notebook_page
);
add_config_field('payment.ccbill.datalink_pass', 'ccBill DataLink Password',
    'password_c', "read ccBill plugin readme (11) about",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));
add_config_field('payment.ccbill.use_cheques', 'Accept online cheques',
    'select', "",
    $notebook_page, 
    '','','',
    array('options' => array('' => 'No', 1 => 'Yes')));
add_config_field('payment.ccbill.use_900', 'Accept CCBill900 payments',
    'select', "",
    $notebook_page, 
    '','','',
    array('options' => array('' => 'No', 1 => 'Yes')));
add_config_field('payment.ccbill.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'CCBill'));
add_config_field('payment.ccbill.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit card, online check,  telephone bill payments'));
?>
