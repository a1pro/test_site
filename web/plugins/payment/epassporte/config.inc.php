<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");


//      @version $Id: config.inc.php 1640 2006-06-07 19:29:19Z avp $

$notebook_page = 'Epassporte';
config_set_notebook_comment($notebook_page, 'Epassporte Configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.epassporte.account', 'Account Number',
    'text', "Numeric value assigned by Epassporte",
    $notebook_page, 
    '');
add_config_field('payment.epassporte.product', 'Product Code',
    'text', "Alphanumeric value assigned by Epassporte",
    $notebook_page, 
    '');
add_config_field('payment.epassporte.IPN_pass', 'Password for the IPN call',
    'text', "when seting up your IPN callback, use " . $config['root_url'] . "/plugins/payment/epassporte/ipn.php?cred=XXXX where XXXX is the password you set up.",
    $notebook_page, 
    '');
add_config_field('payment.epassporte.ip', 'Epassporte Server IP',
    'text', "where postback comes from - keep default value",
    $notebook_page, 
    '', '', '',
    array('default' => '204.118.97.*'));
add_config_field('payment.epassporte.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Epassporte'));
add_config_field('payment.epassporte.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Epassporte Payment Processing'));
?>
