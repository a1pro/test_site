<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Internetsecure';
config_set_notebook_comment($notebook_page, 'Internetsecure plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.internetsecure.merchant_id', 'Your Internetsecure Merhcant Number',
    'text', "it must be a numeric value",
    $notebook_page, 
    '');
add_config_field('payment.internetsecure.testing', 'Test Mode',
    'select', "",
    $notebook_page, 
    '', '', '',
    array('options' => array('' => 'No' , 1 => 'Yes'),
          'default' => '' )
);
add_config_field('payment.internetsecure.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'InternetSecure'));
add_config_field('payment.internetsecure.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
