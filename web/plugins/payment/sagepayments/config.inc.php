<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Sagepayments';
config_set_notebook_comment($notebook_page, 'Sagepayments configuration');
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.sagepayments.id', '12 Digit Merchant Identification',
    'text', "", $notebook_page,'');
add_config_field('payment.sagepayments.key', '12 Digit Merchant Key',
    'text', "", $notebook_page,'');
?>
