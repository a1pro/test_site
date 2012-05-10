<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'FastSpring';
config_set_notebook_comment($notebook_page, 'FastSpring plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.fastspring.company', 'Company Name',
    'text', "your Company name as registered at FastSpring",
    $notebook_page, 
    '');
add_config_field('payment.fastspring.private_key', 'Private Key Security',
    'text', "FastSpring -> Account -> Notification Configuration -> Order Notification -> Security",
    $notebook_page, 
    '');

add_config_field('payment.fastspring.testmode', 'Test Mode', 'select', 
    "Set to No after you complete testing.", $notebook_page, '','','',
    array('options' => array(0 => 'No', 1 => 'Yes'))
);


add_config_field('payment.fastspring.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'FastSpring'));
add_config_field('payment.fastspring.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
