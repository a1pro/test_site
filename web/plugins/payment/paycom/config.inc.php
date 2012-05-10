<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

global $config;
$notebook_page = 'PayCom';
config_set_notebook_comment($notebook_page, 'Paycom Configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.paycom.co_code', 'Company Code',
    'text', "alphanumeric value assigned by PayCom",
    $notebook_page, 
    '');
add_config_field('payment.paycom.ip', 'PayCom Server IP',
    'text', "where postback comes from - keep default value",
    $notebook_page, 
    '', '', '',
    array('default' => '208.236.105.*'));
add_config_field('payment.paycom.IPN_pass', 'Password for the IPN call',
    'text', "when seting up your IPN callback, use " . $config['root_url'] . "/plugins/payment/paycom/ipn.php?cred=XXXX where XXXX is the password you set up.",
    $notebook_page, 
    '');
add_config_field('payment.paycom.testing', 'Testing',
    'select', "enable/disable payments with test credit cars<br />
     ask PayCom support for test credit card numbers",
    $notebook_page, 
    '','','',
    array('default'=>"", 'options' => array('' => 'No', '1' => 'Yes'))
    );
add_config_field('payment.paycom.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'PayCom'));
add_config_field('payment.paycom.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'PayCom Credit Card Processing'));
?>
