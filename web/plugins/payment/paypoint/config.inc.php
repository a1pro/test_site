<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Paypoint';
config_set_notebook_comment($notebook_page, 'Paypoint plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.paypoint.intinstid', 'Your Paypoint ID',
    'text', "your preferred MCPE Fast Track<br>
	installation number",
    $notebook_page);
add_config_field('payment.paypoint.currency', 'Currency',
    'text', "ISO alpha order currency code, <br/>
	for example: EUR, USD, GBP, CHF, :",
    $notebook_page);
add_config_field('payment.paypoint.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

?>
