<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'MSCS';
config_set_notebook_comment($notebook_page, 'MSCS configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.mscs.login', 'MSCS login',
    'text', "Your MSCS Login ID", $notebook_page, '');
add_config_field('payment.mscs.password', 'MSCS password',
    'text', "Your MSCS Password ID", $notebook_page, 
    '');
add_config_field('payment.mscs.mid', 'Merchant ID',
    'text', "Your MSCS Merchant ID Number", $notebook_page, '');
add_config_field('payment.mscs.did', 'Device ID',
    'text', "Your MSCS Device ID For this Gateway", $notebook_page, 
    '');
add_config_field('payment.mscs.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

cc_core_add_config_items('mscs', $notebook_page);
?>
