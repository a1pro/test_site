<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'GSpay';
config_set_notebook_comment($notebook_page, 'GSpay plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.gspay.siteid', 'Your GSpay Site ID',
    'text', "",
    $notebook_page);
add_config_field('payment.gspay.key', 'Your GSpay Callback script key',
    'text', "",
    $notebook_page);
add_config_field('payment.gspay.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));
?>
