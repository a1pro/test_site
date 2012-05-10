<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Moneris';
config_set_notebook_comment($notebook_page, 'Moneris configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.moneris.api_token', 'Moneris Api Token',
    'text', "your Moneris Api Token",
    $notebook_page, 
    '');
add_config_field('payment.moneris.store_id', 'Moneris Store ID',
    'text', "your Moneris Store ID",
    $notebook_page, 
    '');
add_config_field('payment.moneris.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes'), 'default' => '1'));

cc_core_add_config_items('moneris', $notebook_page);
?>
