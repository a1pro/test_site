<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Icepay';
config_set_notebook_comment($notebook_page, 'Icepay plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.icepay.merchkey', 'Your Icepay Merchant number',
    'text', "A unique assigned code identifying the<br />
	merchant under which transactions will<br />
	be processed",
    $notebook_page);
add_config_field('payment.icepay.secret', 'Your Icepay Encryption code',
    'text', "",
    $notebook_page);
?>
