<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 

$notebook_page = 'Ogone.NL';
config_set_notebook_comment($notebook_page, 'ogone.Com plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.ogone.company_id', 'Merchant ID',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.ogone.sha_id', 'SHA1 Signature',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.ogone.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 1 => 'Yes')));
add_config_field('payment.ogone.hashing_method', 'Hashing Method',
    'select', "the same as you have at Ogone -> Configuration -> Technical Information",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'Main parameters only', 1 => 'Each parameter followed by the pass phrase')));

if (class_exists('payment_ogone')) {
    $pl = & instantiate_plugin('payment', 'ogone');
    $pl->add_config_items($notebook_page);
}


?>
