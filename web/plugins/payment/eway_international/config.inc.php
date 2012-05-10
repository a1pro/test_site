<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'EWAY International';
config_set_notebook_comment($notebook_page, 'EWAY International plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.eway_international.customer_id', 'Your EWAY  customerID',
    'integer', "",
    $notebook_page,
    'validate_integer');
add_config_field('payment.eway_international.username', 'Your eWAY Customer User Name',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.eway_international.company_name', 'Company Name',
    'text', "This will be displayed as the company the<br/>
                       customer is purchasing from, including this<br/>
                       is highly recommended.",
    $notebook_page,
    '');
add_config_field('payment.eway_international.language', 'Language',
    'select', "",
    $notebook_page,
    '','','',
    array('options' => array(
                                'EN'    =>  'English',
                                'ES'    =>  'Spanish',
                                'FR'    =>  'French',
                                'DE'    =>  'German',
                                'NL'    =>  'Dutch'
                            )));

add_config_field('payment.eway_international.country', 'Country',
    'select', "Different gateway will be used depending on this setting",
    $notebook_page,
    '','','',
    array('options' => array(
                                'UK'    =>  'United Kingdom',
                                'AU'    =>  'Australia',
                                'NZ'    =>  'New Zeland'
                            )));


add_config_field('payment.eway_international.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => _PLUG_PAY_EWAY_TITLE));
add_config_field('payment.eway_international.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => _PLUG_PAY_EWAY_DESC));
?>
