<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'MIGS';
config_set_notebook_comment($notebook_page, 'MIGS plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.migs_r.merchant_id', 'Merchant ID',
    'text', "your MIGS Merchant ID",
    $notebook_page);
add_config_field('payment.migs_r.access_code', 'Access Code',
    'password_c', "your MIGS access code",
    $notebook_page, 
    '');
add_config_field('payment.migs_r.secure_secret', 'Secure Secret',
    'password_c', "Secure hash is only optional if you have,<br />the mayOmitHash privilege provided to you by your Payment Provider<br />The Secure Hash Secret can be accessed using Merchant Administration",
    $notebook_page, 
    '');
add_config_field('payment.migs_r.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'MIGS'));
add_config_field('payment.migs_r.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'MasterCard Internet Gateway Service'));
?>
