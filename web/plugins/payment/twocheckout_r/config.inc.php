<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = '2Checkout Rec.';
config_set_notebook_comment($notebook_page, '2Checkout plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.twocheckout_r.seller_id', 'Your 2Checkout account ID',
    'text', "your 2Checkout account ID (a number)",
    $notebook_page, 
    'validate_integer');
add_config_field('payment.twocheckout_r.secret', 'Your 2Checkout secret phrase',
    'text', "your 2Checkout secret phrase<br />
    please set it to the same value 
    <a href='https://www2.2checkout.com/2co/admin/look_and_feel' target=_blank>here</a>
    
    ",
    $notebook_page, 
    '');
add_config_field('payment.twocheckout_r.api_username', 'Your 2Checkout API username',
    'text', "fill it if you want that users be able to cancel recurring subscriptions from aMember,<br>
            you can find instruction how create API user here<br>
            http://www.2checkout.com/documentation/api/",
    $notebook_page);
add_config_field('payment.twocheckout_r.api_password', 'Your 2Checkout API password',
    'text', "fill it if you want that users be able to cancel recurring subscriptions from aMember,<br>
            you can find instruction how create API user here<br>
            http://www.2checkout.com/documentation/api/",
    $notebook_page);
add_config_field('payment.twocheckout_r.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => '2Checkout'));
add_config_field('payment.twocheckout_r.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'purchase from 2Checkout'));
    /*
add_config_field('payment.twocheckout_r.use_v2', 'Use 2CO version 2',
    'select', "
If your 2CO seller id is greater than 200000 please IGNORE this option.<br />
If your id is smaller than 200000, then you need to do the following:<br />
  - contact 2Checkout: http://support.2co.com/deskpro/newticket.php
  to find out how to convert your account to version 2;<br />
  - once you get this, have a look below for instructions how to configure NEW type of account,<br />
    and follow these instructions;<br />
  - when you are done, set this option to YES;<br />
It is necessary to make this change until April, 2005, because 2CO will<br />
shut down old (version 1) payment interface.
",
    $notebook_page, 
    '','','',
    array('options' => array('' => 'No', '1' => 'Yes')));

add_config_field('payment.twocheckout_r.demo', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array('N' => 'No', 'Y' => 'Yes')));
*/
?>
