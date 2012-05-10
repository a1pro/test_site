<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'PaySbuy';
config_set_notebook_comment($notebook_page, 'PaySbuy plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.paysbuy.merchant_id', 'Your PaySbuy Merchant ID',
    'text', "Username or Login email",
    $notebook_page);

add_config_field('payment.paysbuy.currency', 'PaySbuy Currency',
    'select', 'currency for PaySbuy gateway',
    $notebook_page,
    '', '', '',
    array('options' => array(
        ''         => '- use default -',
        '840'      => 'US Dollar',
        '036'      => 'Australian Dollar',
        '826'      => 'POUND STERLING',
        '124'      => 'Canadian Dollar',
        '208'      => 'Danish Krone',
        '978'      => 'EURO',
        '344'      => 'HongKong Dollar',
        '356'      => 'Indian Rupee',
        '392'      => 'YEN(100)',
        '554'      => 'Newzealand Dollar',
        '578'      => 'Norwegien Krone',
        '702'      => 'Singapore Dollar',
        '752'      => 'Swedish Krone',
        '756'      => 'Swiss Franc'
    ))
);

add_config_field('payment.paysbuy.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'PaySbuy'));
add_config_field('payment.paysbuy.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => "Make payments with PaySbuy - it's fast, free and secure!")
    );
/*
    array('options' => array(
        'USD'      => 'US Dollar',
        'AUD'      => 'Australian Dollar',
        'GBP'      => 'POUND STERLING',
        'CAD'      => 'Canadian Dollar',
        'DKK'      => 'Danish Krone',
        'EUR'      => 'EURO',
        'HKD'      => 'HongKong Dollar',
        'INR'      => 'Indian Rupee',
        'JPY'      => 'YEN(100)',
        'NZD'      => 'Newzealand Dollar',
        'NOK'      => 'Norwegien Krone',
        'SGD'      => 'Singapore Dollar',
        'SEK'      => 'Swedish Krone',
        'CHF'      => 'Swiss Franc'
    ))

*/
?>
