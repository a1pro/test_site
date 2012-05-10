<?php 

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");


$notebook_page = 'PayPal Mobile';
config_set_notebook_comment($notebook_page, 'Paypal Mobile configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;

add_config_field('payment.paypal_mobile.api_user', 'API Username',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.paypal_mobile.api_pass', 'API Password',
    'password_c', "your API Password (it is different<br />
                  from your PayPal account password)",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));
add_config_field('payment.paypal_mobile.api_sig', 'API Signature',
    'textarea', "it is a long string of characters from PayPal, copy&paste - it is one-line!",
    $notebook_page, 
    '', '', '', array('size' => 40));
add_config_field('payment.paypal_mobile.testing', 'Sandbox testing',
    'select', "you have to signup here <a href='http://developer.paypal.com/'>developer.paypal.com</a><br />
    to use this feature",
    $notebook_page, 
    '','','',
    array('default'=>"", 'options' => array('' => 'No', '1' => 'Yes'))
    );

add_config_field('payment.paypal_mobile.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'PayPal'));
add_config_field('payment.paypal_mobile.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'PayPal Mobile Checkout'));

?>
