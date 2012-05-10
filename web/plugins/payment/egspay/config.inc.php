<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'EgsPay';
config_set_notebook_comment($notebook_page, 'EgsPay plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.egspay.site_id', 'EgsPay Site Id',
    'integer', "your EgsPay site id#",
    $notebook_page, 
    'validate_integer', '','',
    array('size' => 10));
add_config_field('payment.egspay.ip', 'PostBack IP address',
    'text', "egspay will post from the specified network<br />don't change this value",
    $notebook_page, 
    '', '', '',
    array('default' => '209.76.160.'));
add_config_field('payment.egspay.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'AC Pay'));
add_config_field('payment.egspay.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
