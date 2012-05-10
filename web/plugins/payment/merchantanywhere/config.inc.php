<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'MerchantAnyWhere';
config_set_notebook_comment($notebook_page, 'MerchantAnyWhere configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.merchantanywhere.merchantID', 'Your Assigned Merchant Number',
    'text', "Your Assigned Merchant Number",
    $notebook_page, 
    '');
add_config_field('payment.merchantanywhere.tkey', 'PRI Security Key',
    'text', 
    "",
    $notebook_page, 
    '');
add_config_field('payment.merchantanywhere.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing. <br/>Do not use your live CC number for tests",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

cc_core_add_config_items('merchantanywhere', $notebook_page);
?>
