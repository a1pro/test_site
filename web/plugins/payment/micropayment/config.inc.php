<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Micropayment';
config_set_notebook_comment($notebook_page, 'Micropayment configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;

add_config_field('payment.micropayment.key', 'Access Key',
    'text', "You'll find your AccessKey in <br />
	ControlCenter --> My Configuration",
    $notebook_page, 
    '');
/*add_config_field('payment.micropayment.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));*/
?>
