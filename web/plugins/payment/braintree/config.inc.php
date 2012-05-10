<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Braintree';
config_set_notebook_comment($notebook_page, 'Braintree configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.braintree.key', 'Transaction Key',
    'text', "This is the value obtained from the gateway.",
    $notebook_page, 
    '');
add_config_field('payment.braintree.key_id', 'Transaction Key ID',
    'text', "This is the value obtained from the gateway.",
    $notebook_page, 
    '');
/*add_config_field('payment.braintree.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));
*/
?>
