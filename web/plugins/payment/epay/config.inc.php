<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'ePay';
config_set_notebook_comment($notebook_page, 'ePay plugin configuration');


add_config_field('payment.epay.min', 'MIN',
    'text', "This value is provided by ePay.bg",
    $notebook_page);
    
add_config_field('payment.epay.secret', 'Secret',
    'text', "This value is provided by ePay.bg",
    $notebook_page);
    
add_config_field('payment.epay.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page,
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

add_config_field('payment.epay.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'ePay'));

add_config_field('payment.epay.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => ''));


?>
