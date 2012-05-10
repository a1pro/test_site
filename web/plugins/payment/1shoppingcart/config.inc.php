<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

$notebook_page = '1ShoppingCart/MCSSL';
config_set_notebook_comment($notebook_page, '1ShoppingCart/MCSSL Integration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.1shoppingcart.merchant_id', 'Merchant ID',
    'text', "your 1ShoppingCart merchant ID#",
    $notebook_page, 
    '');
add_config_field('payment.1shoppingcart.postback_password', 'PostBack Password',
    'password_c', "you must set the same value in 1ShoppingCart control panel",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));

add_config_field('payment.1shoppingcart.api_key', 'API Key',
    'text', "Required only for 1SC in from of aMember",
    $notebook_page,
    '');

if (class_exists('payment_1shoppingcart')) {
    $pl = & instantiate_plugin('payment', '1shoppingcart');
    $pl->add_config_items($notebook_page);
}

    
?>
