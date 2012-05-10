<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'PayFlow Pro';
config_set_notebook_comment($notebook_page, 'PayFlow Pro configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.payflow_pro.login', 'PayflowPro login',
    'text', "your PayFlow Pro login",
    $notebook_page, 
    '');
add_config_field('payment.payflow_pro.user', 'PayflowPro user',
    'text', "same as login unless a Payflow Pro USER was created",
    $notebook_page, 
    '');
add_config_field('payment.payflow_pro.partner', 'PayflowPro partner',
    'text', "your PayFlow Pro Partner",
    $notebook_page, 
    '');
add_config_field('payment.payflow_pro.password', 'PayFlow Pro Password',
    'password_c', "your PayFlow Pro Password",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));
add_config_field('payment.payflow_pro.certification_id', 'Certification ID',
    'text', "X-VPS-VIT-CLIENT-CERTIFICATION-ID - any alpha-numeric ID<br />
	note: will be removed in the near future",
    $notebook_page, 
    '');
add_config_field('payment.payflow_pro.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

cc_core_add_config_items('payflow_pro', $notebook_page);
