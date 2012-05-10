<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'NoChex';
config_set_notebook_comment($notebook_page, 'NoChex plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.nochex.business', 'NoChex Email',
    'text', "your registered in NoChex email address",
    $notebook_page, 
    'validate_email_address');
add_config_field('payment.nochex.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'NoChex'));
add_config_field('payment.nochex.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Secure credit card payment'));
?>
