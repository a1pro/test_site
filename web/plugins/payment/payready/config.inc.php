<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'PayReady';
config_set_notebook_comment($notebook_page, 'PayReady configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.payready.login', 'PayReady ID',
    'text', 
     "Your actual PayReady ID will be assigned to you<br />
      when your account is initiated. One common problem<br />
      while developing a PayReady solution is to use the<br /> 
      12-digit numeric Merchant ID instead of the character<br />
      based PayReady ID<br />
      <b>please enter \"PayReady.net Test Account\" for testing<br />",
    $notebook_page, 
    '');
add_config_field('payment.payready.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'PayReady'));
add_config_field('payment.payready.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit Card Payment'));
?>
