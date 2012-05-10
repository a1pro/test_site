<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Epay.eu';
config_set_notebook_comment($notebook_page, 'Epay.eu plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.epayeu.merchantnumber', 'Your Epay.eu merchant number',
    'text', "A unique assigned number identifying the<br />
	merchant under which transactions will<br />
	be processed",
    $notebook_page);
add_config_field('payment.epayeu.secret', 'Your Epay.eu secret key',
    'password_c', "",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));
add_config_field('payment.epayeu.currency', 'Currency',
    'select', "",
    $notebook_page, 
    '','','',
    array('options' => array(
		208 => 'DKK',
		978 => 'EUR',
		840 => 'USD',
		578 => 'NOK',
		752 => 'SEK',
		826 => 'GBP'
		)));
add_config_field('payment.epayeu.windowstate', 'Payment Window',
    'select', "The payment window works on 2 different manners. <br>
	Either as popup or in the same window as the internet shop. <br>
	The parameter windowstate controls this behaviour. ",
    $notebook_page, 
    '','','',
    array('options' => array(1 => 'Popup', 2 => 'Same window')));

?>
