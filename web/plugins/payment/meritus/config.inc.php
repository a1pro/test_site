<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Meritus';
config_set_notebook_comment($notebook_page, 'Meritus configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;

add_config_field('payment.meritus.id', 'MerchantID',
    'text', "The identification that is assigned to the merchant",
    $notebook_page, 
    '');
add_config_field('payment.meritus.key', 'MerchantKey',
    'text', 
    "A unique identification that is assigned to the merchant id",
    $notebook_page, 
    '');
?>
