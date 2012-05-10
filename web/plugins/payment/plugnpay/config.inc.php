<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'PlugNPay';
config_set_notebook_comment($notebook_page, 'plugnpay configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.plugnpay.login', 'plugnpay login',
    'text', "your PlugNPay Username",
    $notebook_page, 
    '');
/*
add_config_field('payment.plugnpay.domain', 'plugnpay Payment Domain',
    'text', "your secure payment server domain - assigned by PlugNPay",
    $notebook_page, 
    '');
*/
add_config_field('payment.plugnpay.app_level', 'AVS approval Level',
    'select', " Please see AVS Specifications in PlugNPay for level details.",
    $notebook_page, 
    '','','',
    array('options' => array(-1=>'-1',0=>0,1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6),
          'default' => '1'));
add_config_field('payment.plugnpay.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'PlugNPay'));
add_config_field('payment.plugnpay.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));

add_config_field('payment.plugnpay.currency', 'plugnpay Currency',
    'select', 'default plugnpay Currency',
    $notebook_page, 
    '', '', '',
    array('options' => array(
        'aud' => 'Australian dollars',
        'cad' => 'Canadian dollars',
        'chf' => 'Swiss francs',
        'eur' => 'Euro',
        'gbp' => 'Pounds',
        'jpy' => 'Yen',
        'usd' => 'US Dollars',
        'jmd' => 'Jamaican Dollar'
    ), 'default' => 'usd')
    );
?>
