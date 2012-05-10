<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'NetworkMerchants';
config_set_notebook_comment($notebook_page, 'NetworkMerchants configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.networkmerchants.login', 'networkmerchants login',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.networkmerchants.pass', 'networkmerchants password',
    'text', "",
    $notebook_page, 
    '');

cc_core_add_config_items('networkmerchants', $notebook_page);
?>
