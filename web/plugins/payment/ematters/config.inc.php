<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'eMatters.com.au';
config_set_notebook_comment($notebook_page, 'E-Matters payment plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.ematters.company_name', 'Company Name',
    'text', "this is the name that appers on the top of the reciept",
    $notebook_page, 
    '');
add_config_field('payment.ematters.merchant_id', 'Merchant ID',
    'integer', "-IMPORTANT - This is the number issued by eMatters <br />
        - It must correspond to the last 2 or 3 digits <br />
        of your Login Name",
    $notebook_page, 
    'validate_integer');
add_config_field('payment.ematters.send_email', 'Send Email from eMatters',
    'select', "Do you want your customer to receive an email from us.",
    $notebook_page, 
    '', '', '',
    array('options'  => array('Yes'=>'Yes','No'=>'No')));
add_config_field('payment.ematters.abn', 'Your ABN Number',
    'text', "Displays on your receipt.",
    $notebook_page, 
    '');
add_config_field('payment.ematters.bank', 'Bank',
    'text', "Must be National or StGeorge or BankSA",
    $notebook_page, 
    '');
add_config_field('payment.ematters.testing', 'Test Mode',
    'select', "Do you want to run test transactions? Use CC numbers: <br />
        4557 0130 0031 4262 = Approved<br />
        4557 0130 0031 6242 = Declined<br />
    ",
    $notebook_page, 
    '', '', '',
    array('options'  => array(0=>'No',1=>'Yes')));
add_config_field('payment.ematters.readers', 'eMatters Login',
    'text', "see the Merchant Kit for this code.<br />
       The last numbers will match your Merchant ID",
    $notebook_page, 
    '');
add_config_field('payment.ematters.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'eMatters.com.au'));
add_config_field('payment.ematters.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
