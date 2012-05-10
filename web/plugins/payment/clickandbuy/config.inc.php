<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'ClickAndBuy';
config_set_notebook_comment($notebook_page, 'ClickAndBuy plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);


add_config_field('payment.clickandbuy.purchase_code', 'Your ClickAndBuy Purchase Link code',
    'text', "your ClickAndBuy Purchase Link code <b><i>pe2lstz7ep13r0</i></b> from URL like<br />
    ( http://premium-<b>pe2lstz7ep13r0</b>.eu.clickandbuy.com/clickandbuy.php )<br />
    It is set up by your Account Support Manager, who will notify you of the exact URL.",
    $notebook_page);

add_config_field('payment.clickandbuy.seller_id', 'Your ClickAndBuy Seller ID',
    'text', "your ClickAndBuy Seller ID",
    $notebook_page,
    'validate_integer');
add_config_field('payment.clickandbuy.tm_password', 'Your ClickAndBuy TM Password',
    'password_c', "your ClickAndBuy Transaction Manager Password",
    $notebook_page);

add_config_field('payment.clickandbuy.disable_second_confirmation', 'Disable Second Confirmation',
    'checkbox', "For testing only!!! Enable it in case of 'Soap Request Fault' error.<br />
    The Second Confirmation procedure enables you to ensure that a transaction is only evaluated<br />
    as successful if the transaction was actually able to be created in the ClickandBuy system.",
    $notebook_page
    );


add_config_field('payment.clickandbuy.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'ClickAndBuy'));
add_config_field('payment.clickandbuy.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => 'Pay by ClickandBuy'));
?>
