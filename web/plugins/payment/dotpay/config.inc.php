<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'DotPay';
config_set_notebook_comment($notebook_page, 'DotPay plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.dotpay.seller_id', 'Your DotPay Seller ID',
    'integer', "your DotPay installation ID",
    $notebook_page, 
    'validate_integer');
add_config_field('payment.dotpay.lang', 'Payment Language',
    'select', "this defines the language which will be used during the payment process",
    $notebook_page,
    '', '', '',
    array('options' => array(
        'pl' => 'Polish',
        'en' => 'English',
        'de' => 'German',
        'it' => 'Italian',
        'fr' => 'Frencz',
        'es' => 'Spanish',
        'cz' => 'Czech',
        'ru' => 'Russian')
        )
    );
add_config_field('payment.dotpay.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'DotPay'));
add_config_field('payment.dotpay.description', 'Payment Method Description',
    'textarea', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay by credit card/debit card - Visa, Diners Club, MasterCard/EuroCard, JCB, PBK Styl, PolCard, VISA ELECTRON of the banks: Inteligo, Deutsche Bank PBC and Millenium'));
?>
