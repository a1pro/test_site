<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

$notebook_page = 'PayPal';
config_set_notebook_comment($notebook_page, 'paypal plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.paypal_r.business', 'Merchant ID',
    'text', "your PayPal account PRIMARY email address",
    $notebook_page, 
    '');
add_config_field('payment.paypal_r.alt_business', 'Alternate PayPal account emails (one per line)',
    'textarea', "",
    $notebook_page, 
    '','','',
    array('default'=>"", 'cols' => 20)
    );
add_config_field('payment.paypal_r.testing', 'Sandbox testing',
    'select', "you have to signup here <a href='http://developer.paypal.com/'>developer.paypal.com</a><br />
    to use this feature",
    $notebook_page, 
    '','','',
    array('default'=>"", 'options' => array('' => 'No', '1' => 'Yes'))
    );

add_config_field('payment.paypal_r.other_account', 'Assign different account to product', 'select', 
    "you can assign an other PayPal account to each product<br />
    at aMember CP -> Manage Products -> Edit<br />
    KEEP IT DISABLED IF YOU DON'T UNDERSTAND WHAT IT MEANS", $notebook_page, '','','',
    array('options' => array(0 => 'No', 1 => 'Yes'))
);

add_config_field('payment.paypal_r.dont_verify', 'Disable IPN verification',
    'select', nl2br("You usually DO NOT NEED to enable this option.\n
     However, on some webhostings PHP scripts are not allowed to contact external
     web sites. It breaks functionality of the PayPal payment integration plugin,
     and aMember Pro then is unable to contact PayPal to verify that incoming
     IPN post is genuine. In this case, AS TEMPORARY SOLUTION, you can enable
     this option to don't contact PayPal server for verification. However,
     in this case \"hackers\" can signup on your site without actual payment.
     \n
     So if you have enabled this option, contact your webhost and ask them to 
     open outgoing connections to www.paypal.com port 80 ASAP, then disable
     this option to make your site secure again. 
     "),
     $notebook_page, 
     '','','',
     array('default'=>"", 'options' => array('' => 'Verification enabled (default)', 1 => 'Verification DISABLED (dangerous!)'))
);

add_config_field('payment.paypal_r.lc', 'PayPal Language Code',
    'text', nl2br("This field allows you to configure PayPal page language
    that will be displayed when customer is redirected from your website
    to PayPal for payment. By default, this value is empty, then PayPal
    will automatically choose which language to use. Or, alternatively,
    you can specify for example: en (for english language), or fr
    (for french Language) and so on. In this case, PayPal will not choose
    language automatically. <br />
    Default value for this field is empty string"),
    $notebook_page, 
    '','','',
    array('default'=>"", 'size' => 10)
    );

if (class_exists('payment_paypal_r')) {
    $pl = & instantiate_plugin('payment', 'paypal_r');
    $pl->add_config_items($notebook_page);
}

add_config_field('payment.paypal_r.rewrite_email', 'Set user E-Mail to paypal payer e-mail',
    'checkbox', "This only works if you enable username and/or password generation<br>
    in signup form, then it may help to stop some fraud attempts via PayPal",
    $notebook_page, 
    '');

add_config_field('payment.paypal_r.resend_postback', 'Resend Postback',
    'textarea', "all IPN posts will be resent to specified URL,<br />
     you may need it for third-party affiliate script, for example<br />
     DON'T ENTER URL OF aMember PayPal script HERE! KEEP IT BLANK IF<br />
     YOU DON'T UNDERSTAND WHAT IT MEANS",
    $notebook_page, 
    '','','',
    array('default'=>"", 'cols' => 40)
    );

