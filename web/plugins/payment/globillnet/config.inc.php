<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'Globill.NET';
config_set_notebook_comment($notebook_page, 'Globill.NET plugin configuration');


add_config_field('payment.globillnet.mewid', 'MEWid',
    'text', "This value is provided by globill.net",
    $notebook_page);
    
add_config_field('payment.globillnet.mewkey', 'MEWkey',
    'text', "This value is provided by globill.net",
    $notebook_page);
    
add_config_field('payment.globillnet.mewkey_trial', 'MEWkey TRIAL',
    'text', "This value is provided by globill.net",
    $notebook_page);

add_config_field('payment.globillnet.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'GloBill'));

add_config_field('payment.globillnet.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => ''));


?>
