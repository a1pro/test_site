<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Centipaid';
config_set_notebook_comment($notebook_page, 'Centipaid authorize.Net API configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.centipaid.login', 'Centipaid ID',
    'text', "your Centipaid Merchant ID",
    $notebook_page, 
    '');
add_config_field('payment.centipaid.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Centipaid'));
add_config_field('payment.centipaid.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Digital Stored Value Cards'));
?>
