<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Protx VSP direct';
config_set_notebook_comment($notebook_page, 'Protx VSP direct configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.protxdirect.login', 'Protx  login',
    'text', "your protx username",
    $notebook_page, 
    '');
add_config_field('payment.protxdirect.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

cc_core_add_config_items('protxdirect', $notebook_page);
?>
