<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Paymate';
config_set_notebook_comment($notebook_page, 'Paymate plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.paymate.login', 'Your Paymate Login',
    'text', "",
    $notebook_page, 
    '');


add_config_field('payment.paymate.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Paymate'));
add_config_field('payment.paymate.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay by credit card/debit card - Visa/Mastercard'));
?>
