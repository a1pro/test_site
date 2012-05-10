<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 

$notebook_page = 'ikobo.Com';
config_set_notebook_comment($notebook_page, 'ikobo.Com plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.ikobo.account_id', 'iKobo account ID',
    'text', "you may find it on the first page when you login to<br />
    iKobo merchant interface, example:<br />
    iKobo account :  SA353157EN",
    $notebook_page, 
    '');
add_config_field('payment.ikobo.postback_pass', 'iKobo postback password',
    'text', "you have to set it into the same value here and at <br />
    iKobo.Com -> Login -> Sell -> Instant Payment Notification<br />
    see readme below for details",
    $notebook_page, 
    '');

if (class_exists('payment_ikobo')) {
    $pl = & instantiate_plugin('payment', 'ikobo');
    $pl->add_config_items($notebook_page);
}

add_config_field('payment.ikobo.resend_postback', 'Resend Postback',
    'textarea', "URL List to resend PostBack<br />
    please discuss your situation with CGI-Central support<br />
    before use this feature. It is better to keep this field blank",
    $notebook_page, 
    '','','',
    array('default'=>"", 'cols' => 40)
    );
?>
