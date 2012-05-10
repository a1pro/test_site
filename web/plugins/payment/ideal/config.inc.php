<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

$notebook_page = 'iDeal';
config_set_notebook_comment($notebook_page, 'iDeal plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.ideal.merchant_id', 'iDeal Merchant ID',
    'text', "your Merchant ID in iDeal",
    $notebook_page);

add_config_field('payment.ideal.sub_id', 'iDeal subID',
    'text', "do not change subID unless you have specific reasons to do so",
    $notebook_page, 
    '','','',
    array('default'=>"0"));

add_config_field('payment.ideal.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'iDeal'));
add_config_field('payment.ideal.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));

add_config_field('payment.ideal.private_key', 'Private Key File Location',
    'text', "if file is uploaded into ".$config['root_dir']."/plugins/payment/ideal/security/ folder<br />
    you may specify just filename. If file is located somewhere else,<br />
    specify full UNIX path to file",
    $notebook_page, 
    '');
add_config_field('payment.ideal.private_pass', 'Private Key Password',
    'password_c', "your private password",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));
add_config_field('payment.ideal.cert_file', 'Certificate File Location',
    'text', "if file is uploaded into ".$config['root_dir']."/plugins/payment/ideal/security/ folder<br />
    you may specify just filename. If file is located somewhere else,<br />
    specify full UNIX path to file",
    $notebook_page, 
    '');

add_config_field('payment.ideal.currency', 'iDeal Currency',
    'text', "do not change currenty unless you have specific reasons to do so",
    $notebook_page, 
    '','','',
    array('default'=>"EUR"));

add_config_field('payment.ideal.language', 'Language',
    'select', "is only used for showing errormessages in the prefered language",
    $notebook_page, 
    '','','',
    array('default'=>"", 'options' => array('en' => 'English', 'nl' => 'Netherlands'))
    );

add_config_field('payment.ideal.testing', 'Sandbox testing',
    'select', "",
    $notebook_page, 
    '','','',
    array('default'=>"", 'options' => array('' => 'No', '1' => 'Yes'))
    );

?>
