<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Merchant One';
config_set_notebook_comment($notebook_page, 'Merchant One configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.merchantone.login', 'Merchant One username',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.merchantone.pass', 'Merchant One password',
    'text', 
    "",
    $notebook_page, 
    '');
add_config_field('payment.merchantone.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));

?>
