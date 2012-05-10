<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Metacharge';
config_set_notebook_comment($notebook_page, 'Metacharge plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.metacharge.installation_id', 'Your Metacharge installation ID',
    'integer', "refer to Merchant Extranet: Account Management > Installations",
    $notebook_page, 
    'validate_integer');
add_config_field('payment.metacharge.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

add_config_field('payment.metacharge.auth_username', 'Response HTTP Auth Username',
    'text', "Metacharge PRN response authorisation username",
    $notebook_page, 
    '', '', '');
add_config_field('payment.metacharge.auth_password', 'Response HTTP Auth Password',
    'password_c', "Metacharge PRN response authorisation password",
    $notebook_page, 
    '', '', '',
    array('store_type' => 3));


add_config_field('payment.metacharge.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Metacharge'));
add_config_field('payment.metacharge.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay by credit card/debit card - VISA/MC/AMEX/DELTA/SOLO/SWITCH/UKE'));
?>
