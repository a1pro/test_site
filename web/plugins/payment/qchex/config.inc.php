<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'QChex';
config_set_notebook_comment($notebook_page, 'QChex plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.qchex.merchant_id', 'QChex Merchant ID',
    'text', "your QChex Merchant ID",
    $notebook_page, 
    '');
add_config_field('payment.qchex.country', 'QChex Merchant Country',
    'select', "registered country",
    $notebook_page, 
    '','','',
    array('options'=>array('USA'=>'USA','CANADA'=>'CANADA')));
add_config_field('payment.qchex.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'QChex'));
add_config_field('payment.qchex.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay with check'));
?>
