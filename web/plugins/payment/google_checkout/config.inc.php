<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: google checkout payment plugin config
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5000 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/

global $config;

$notebook_page = "Google Checkout";

config_set_notebook_comment($notebook_page, $notebook_page . ' configuration');
if (file_exists($rm = dirname(__FILE__) . "/readme.txt"))
    config_set_readme($notebook_page, $rm);

$features = array('title' => '', 'description' => '');
$pl = & instantiate_plugin('payment', 'google_checkout');
if (is_object($pl))
    $features = $pl->get_plugin_features();


add_config_field('payment.google_checkout.merchant_id', 'Google Checkout Default Merchant Identifier', 'text', 
    "Your default Google Checkout Merchant Identifier (usually a number, e.g. 123456).", $notebook_page, ''
);

add_config_field('payment.google_checkout.merchant_key', 'Google Checkout Default Merchant Key', 'text', 
    "Your default Google Checkout Merchant Key.", $notebook_page, ''
);

add_config_field('payment.google_checkout.currency', 'Currency', 'text', 
    "Your default Google Checkout Currency.<br />Technical limitations of the current service: USD (US dollars) is the only valid currency.", $notebook_page, '', '', '',
    array( 'default' => 'USD' )
);

add_config_field('payment.google_checkout.sandbox', 'Sandbox testing', 'select', 
    "Set to No after you complete testing.", $notebook_page, '','','',
    array('options' => array(0 => 'No', 1 => 'Yes'))
);

add_config_field('payment.google_checkout.debug', 'Test Mode Enabled', 'select', 
    'will log all IPN postback messages to aMember CP -> Error/Debug Log', $notebook_page, '','','',
    array('options' => array(0 => 'No', 1 => 'Yes'))
);

add_config_field('payment.google_checkout.allow_create', 'Allow create new accounts', 'select', 
    'aMember will create member (if not exists) when &lt;new-order-notification&gt; received', $notebook_page, '','','',
    array('options' => array(0 => 'No', 1 => 'Yes'))
);

add_config_field("payment.google_checkout.title", "Payment system title",
    'text', "to be displayed on signup.php and member.php pages",
    $notebook_page, 
    '','','',
    array('default' => $features['title']));
add_config_field("payment.google_checkout.description", "Payment system description",
    'text', "to be displayed on signup page",
    $notebook_page, 
    '','','',
    array('default' => $features['description']));

add_config_field("payment.google_checkout.reattempt", 'Reattempt on Failure',
    'text', 
"Enter list of days to reattempt failed credit card charge, for example: 3,8<br />
 <br />
 The reattempting failed payments option allows you to reattempt failed<br />
 payments before cancelling the subscription. Scheduled payments may fail<br />    
 due to several reasons, including insufficient funds. Payments will be<br />
 reattempted 3 days after the failure date. If it fails again, we will try once<br />
 more 5 days later (it is for sample above: 3,8). Failure on this last attempt<br />
 leads to cancellation of the subscription.<br />
 <br />
 NOTE: this time user will have FREE access to your site. If it is not acceptable<br />
 for your site, please don't enable this feature",
    $notebook_page, 
    '','','',
    array('options' => array(
        '' => 'No',
        1  => 'Yes'
    )));


add_config_field('payment.google_checkout.resend_postback', 'Resend Postback',
    'textarea', "all IPN posts will be resent to specified URL,<br />
     you may need it for third-party affiliate script, for example<br />
     DON'T ENTER URL OF aMember Google checkout script HERE!<br />
     KEEP IT BLANK IF YOU DON'T UNDERSTAND WHAT IT MEANS",
    $notebook_page, 
    '','','',
    array('default'=>"", 'cols' => 40)
    );

?>
