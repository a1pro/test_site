<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'FirstPay.Net';
config_set_notebook_comment($notebook_page, 'FirstPay  configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.firstpay.login', 'FirstPay.Net  merchant',
    'text', "The Visa/Mastercard merchant number assigned <br/>to each individual merchant, starting with '4154...'",
    $notebook_page, 
    '');
add_config_field('payment.firstpay.secret', 'Secret ID',
    'text', 
    "A secret merchant identifier that will be issued <br />by FirstPay.Net to each merchant.",
    $notebook_page, 
    '');

cc_core_add_config_items('firstpay', $notebook_page);
?>
