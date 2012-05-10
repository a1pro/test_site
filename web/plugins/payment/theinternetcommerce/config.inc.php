<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'TheInternetCommerce';
config_set_notebook_comment($notebook_page, 'TheInternetCommerce configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.theinternetcommerce.login', 'TheInternetCommerce Merchant ID',
    'text', "your TheInternetCommerce username",
    $notebook_page, 
    '');
add_config_field('payment.theinternetcommerce.pass', 'Transaction Password',
    'text', 
    "",
    $notebook_page, 
    '');
add_config_field('payment.theinternetcommerce.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'TheInternetCommerce'));
add_config_field('payment.theinternetcommerce.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
