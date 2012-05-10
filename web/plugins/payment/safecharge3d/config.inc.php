<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'SafeCharge 3D';
config_set_notebook_comment($notebook_page, 'SafeCharge 3D Configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.safecharge3d.login', 'SafeCharge login',
    'text', "your SafeCharge Username",
    $notebook_page,
    '');
add_config_field('payment.safecharge3d.password', 'SafeCharge password',
    'password_c', "your SafeCharge Password",
    $notebook_page,
    '');
add_config_field('payment.safecharge3d.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page,
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));
add_config_field('payment.safecharge3d.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'SafeCharge'));
add_config_field('payment.safecharge3d.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>