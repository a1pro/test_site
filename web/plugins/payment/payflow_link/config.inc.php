<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'PayFlow Link';
config_set_notebook_comment($notebook_page, 'PayFlow Link plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.payflow_link.login', 'Your PayFlow username',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.payflow_link.partner', 'Your PayFlow Partner Name',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.payflow_link.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'PayFlow Link'));
add_config_field('payment.payflow_link.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
