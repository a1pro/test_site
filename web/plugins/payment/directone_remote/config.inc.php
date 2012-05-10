<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'DirectOne (remote)';
config_set_notebook_comment($notebook_page, 'directone_remote plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.directone_remote.account_name', 'Your DirectOne Merchant Name',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.directone_remote.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));
add_config_field('payment.directone_remote.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card payment (DirectOne)'));
add_config_field('payment.directone_remote.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
