<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'WebAffair';
config_set_notebook_comment($notebook_page, 'WebAffair plugin configuration');

add_config_field('payment.webaffair.merchant', 'Your WebAffair Merchant ID',
    'text', "The email that the payment for this purchase
should be sent to",
    $notebook_page, 
    '');
add_config_field('payment.webaffair.pathfile', 'Your WebAffair Pathfile',
    'text', "unix path to file like /home/user/pathfile",
    $notebook_page, 
    '', '', '');

add_config_field('payment.webaffair.path_bin', 'Your WebAffair Request file',
    'text', "unix path to file like /home/user/request",
    $notebook_page, 
    '', '', '');
add_config_field('payment.webaffair.response', 'Your WebAffair Response file',
    'text', "unix path to file like /home/user/response",
    $notebook_page, 
    '', '', '');

add_config_field('payment.webaffair.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'WebAffair'));
add_config_field('payment.webaffair.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay by credit card/debit card - Visa/Mastercard'));
?>
