<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'CashU';
config_set_notebook_comment($notebook_page, 'CashU plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.cashu.merchant_id', 'Your CashU merchant ID',
    'text', "",
    $notebook_page);
add_config_field('payment.cashu.currency', 'Currency',
    'text', "ISO alpha order currency code, <br/>
	for example: EUR, USD, GBP, CHF, :",
    $notebook_page);
add_config_field('payment.cashu.secret', 'Encryption Keyword',
    'password_c', "The merchant enters the Encryption Keyword<br /> 
in the \"Payment Security\" tab of<br />
the cashU merchant account.",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));
add_config_field('payment.cashu.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

