<?php 
$notebook_page = 'Verotel';
config_set_notebook_comment($notebook_page, 'Verotel plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.verotel.merchant_id', 'Verotel Id',
    'text', "",
    $notebook_page, 
    'validate_integer', '','',
    array('size' => 16));
add_config_field('payment.verotel.site_id', 'Verotel Site Id',
    'text', "",
    $notebook_page,
    'validate_integer', '','',
    array('size' => 8));
/*
add_config_field('payment.verotel.secret', 'Your Secret Code for ipn script',
    'text', "",
    $notebook_page,
    '');
*/
add_config_field('payment.verotel.testing', 'Test Mode Enabled',                                                                                                      
    'select', "set to No after you complete testing",                                                                                                                 
    $notebook_page,                                                                                                                                                   
    '','','',                                                                                                                                                         
    array('options' => array(0 => 'No', 1 => 'Yes')));

add_config_field('payment.verotel.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'VeroTel'));
add_config_field('payment.verotel.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
