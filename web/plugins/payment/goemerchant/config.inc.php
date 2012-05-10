<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'GoEmerchant';
config_set_notebook_comment($notebook_page, 'GoEmerchant configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.goemerchant.merchant_id', 'GoEmerchant ID',
    'text', "your GoEmerchant Username",
    $notebook_page, 
    '');
add_config_field('payment.goemerchant.merchant_pass', 'GoEmerchant password',
    'password_c', "your GoEmerchant Password",
    $notebook_page, 
    '');

/*
add_config_field('payment.goemerchant.testmode', 'Test Mode Enabled', 'select', 
    "Set to No after you complete testing.", $notebook_page, '','','',
    array('options' => array(0 => 'No', 1 => 'Yes'))
);
*/

add_config_field('payment.goemerchant.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'GoEmerchant'));
add_config_field('payment.goemerchant.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));

?>
