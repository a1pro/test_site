<?php 

$notebook_page = 'PayPal Pro';
config_set_notebook_comment($notebook_page, 'paypal plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.paypal_pro.business', 'Merchant ID',
    'text', "your PayPal account PRIMARY email address",
    $notebook_page, 
    '');
add_config_field('payment.paypal_pro.api_user', 'API Username',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.paypal_pro.api_pass', 'API Password',
    'password_c', "your API Password (it is different<br />
                  from your PayPal account password)",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));
add_config_field('payment.paypal_pro.api_sig', 'API Signature',
    'textarea', "it is a long string of characters from PayPal, copy&paste - it is one-line!",
    $notebook_page, 
    '', '', '', array('size' => 40));
add_config_field('payment.paypal_pro.testing', 'Sandbox testing',
    'select', "you have to signup here <a href='http://developer.paypal.com/'>developer.paypal.com</a><br />
    to use this feature",
    $notebook_page, 
    '','','',
    array('default'=>"", 'options' => array('' => 'No', '1' => 'Yes'))
    );

add_config_field('payment.paypal_pro.locale', 'PayPal Language Code',
    'text', nl2br("This field allows you to configure PayPal page language
    that will be displayed when customer is redirected from your website
    to PayPal for payment. By default, this value is empty, then PayPal
    will automatically choose which language to use. Or, alternatively,
    you can specify for example: en (for english language), or fr
    (for french Language) and so on. In this case, PayPal will not choose
    language automatically. <br />
    Default value for this field is empty string"),
    $notebook_page,
    '','','',
    array('default'=>"", 'size' => 10)
    );


if (class_exists('payment_paypal_pro')) {
    $pl = & instantiate_plugin('payment', 'paypal_pro');
    $pl->add_config_items($notebook_page);
}

cc_core_add_config_items('paypal_pro_cc', $notebook_page);