<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Netregistry';
config_set_notebook_comment($notebook_page, 'Netregistry configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.netregistry.login', 'Netregistry Merchant ID',
    'text', "your netregistry merchant id",
    $notebook_page, 
    '');
add_config_field('payment.netregistry.password', 'Netregistry Merchant password',
    'text', "your netregistry merchant password",
    $notebook_page, 
    '');
cc_core_add_config_items('netregistry', $notebook_page);
?>
