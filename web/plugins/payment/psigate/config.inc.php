<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'psigate';
config_set_notebook_comment($notebook_page, 'PSiGate configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.psigate.storeid', 'StoreID',
    'text', "PSiGate provides the StoreID within the PSiGate Welcome Email",
    $notebook_page, 
    '');
add_config_field('payment.psigate.passphrase', 'Passphrase',
    'password_c', "PSiGate provides the Passphrase within the PSiGate Welcome Email",
    $notebook_page, 
    '');
add_config_field('payment.psigate.testmode', 'Test Mode Enabled', 'select', 
    "Set to No after you complete testing.", $notebook_page, '','','',
    array('options' => array(0 => 'No', 1 => 'Yes'))
);

add_config_field('payment.psigate.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'PSiGate'));
add_config_field('payment.psigate.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));

?>
