<?php
include "../../../config.inc.php";
$_product_id=array('ONLY_LOGIN');
include "../../protect/php_include/check.inc.php";
global $db,$config;

$vars = get_input_vars();

if(!$vars['payment_id'])
    fatal_error(_PLUG_PAY_CC_CORE_FERROR3);

$payment = $db->get_payment(intval($vars['payment_id']));

if(!$payment)
    fatal_error(sprintf(_SIGNUP_PAYMENT_NOT_FOUND, $vars['payment_id']));

if($payment['member_id'] != $_SESSION['_amember_id'])
    fatal_error(_PLUG_PAY_CC_CORE_FERROR4);

if(!$payment['data']['PAYPAL_PROFILE_ID'])
    fatal_error(sprintf(_PLUG_PAY_PAYPALR_IDEMPTY,$vars['payment_id']));

// Now all checks are correct let's cancel subscription.
global $paypal_pro_pl;
$vars = array(
    'METHOD'    =>  'ManageRecurringPaymentsProfileStatus',
    'PROFILEID' =>  $payment['data']['PAYPAL_PROFILE_ID'],
    'ACTION'    =>  'Cancel',
    'NOTE'      =>  'Cancelled by customer IP: '.$_SERVER['REMOTE_ADDR']."  date=".date("Y-m-d H:i:s")
);
$resp = $paypal_pro_pl->paypalAPIRequest($payment['payment_id'], $vars);
header("Location: ".$config['root_url']."/member.php?action=cancel_recurring&payment_id=".$payment['payment_id']);
exit;
?>