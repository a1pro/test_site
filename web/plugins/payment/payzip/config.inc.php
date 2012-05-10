<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

$notebook_page = 'PayZIP';
config_set_notebook_comment($notebook_page, 'PayZIP plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.payzip.account', 'Your PayZIP account ID',
    'text', "your PayZIP account ID (numeric)",
    $notebook_page,
    'validate_integer');
add_config_field('payment.payzip.pin', 'Your PIN-code issued by PayZip',
    'text', "(numeric)",
    $notebook_page,
    '');
add_config_field('payment.payzip.test', 'Test Mode Enabled',
    'select', "set to No when you complete testing",
    $notebook_page,
    '','','',
    array('options' => array('' => 'No', '1' => 'Yes')));
add_config_field('payment.payzip.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'PayZIP'));
add_config_field('payment.payzip.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Secure Credit Card Payment'));
?>
