<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

$notebook_page = 'Vanco Services';
config_set_notebook_comment($notebook_page, 'Vanco Configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.vanco.company_code', 'Vanco company code',
    'text', "Company code assigned by Vanco Services",
    $notebook_page, 
    '');
add_config_field('payment.vanco.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Vanco Services'));
add_config_field('payment.vanco.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Vanco Services Credit Card Processing'));

?>
