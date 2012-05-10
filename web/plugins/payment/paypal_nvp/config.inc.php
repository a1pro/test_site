<?php 

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");


$notebook_page = 'PayPal NVP';
config_set_notebook_comment($notebook_page, 'Paypal NVP configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.paypal_nvp.business', 'Merchant ID',
    'text', "your PayPal account PRIMARY email address",
    $notebook_page, 
    '');
add_config_field('payment.paypal_nvp.api_user', 'API Username',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.paypal_nvp.api_pass', 'API Password',
    'password_c', "your API Password (it is different<br />
                  from your PayPal account password)",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));
add_config_field('payment.paypal_nvp.api_sig', 'API Signature',
    'textarea', "it is a long string of characters from PayPal, copy&paste - it is one-line!",
    $notebook_page, 
    '', '', '', array('size' => 40));
add_config_field('payment.paypal_nvp.testing', 'Sandbox testing',
    'select', "you have to signup here <a href='http://developer.paypal.com/'>developer.paypal.com</a><br />
    to use this feature",
    $notebook_page, 
    '','','',
    array('default'=>"", 'options' => array('' => 'No', '1' => 'Yes'))
    );

cc_core_add_config_items('paypal_nvp', $notebook_page);
?>
