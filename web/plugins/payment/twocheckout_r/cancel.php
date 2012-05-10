<?php
include_once("../../../config.inc.php");
include_once (dirname(__FILE__) . "/TwocheckoutAPI.inc.php");

$this_config = amConfig('payment.twocheckout_r');

if (!$this_config['api_username']) {
    fatal_error("Feature is not enabled");
}

$vars = get_input_vars();
$t = &new_smarty();

//Validate info that was submited

settype($vars['payment_id'], 'integer');
if (!$vars['payment_id']) {
    fatal_error("Payment_id empty");
}

$payment = $db->get_payment($vars['payment_id']);

if ($payment['member_id'] != $vars['member_id']) {
    fatal_error(_PLUG_PAY_CC_CORE_FERROR4);
}

if ($payment['paysys_id'] != 'twocheckout_r') {
    fatal_error('Incorrect paysys_id');
}

$member = $db->get_user($vars['member_id']);

//Validate hash
if (md5($member['pass'].$vars['action'].($member['member_id'] * 12)) != $vars['v']) {
    fatal_error(_PLUG_PAY_CC_CORE_FERROR1);
}

$twocheckoutAPI = new TwocheckoutAPI($this_config['api_username'], $this_config['api_password']);

//get info about invoice
$params = array(
    'sale_id'=>$payment['receipt_id']
);

$resp  = $twocheckoutAPI->detail_sale($params);
if (!$resp) {
    $GLOBALS['db']->log_error($twocheckoutAPI->getLastError());
    fatal_error('Internal error');
}

$invoices = $resp->sale->invoices;

$lineitem_id = $invoices[0]->lineitems[0]->lineitem_id;

$params = array(
    'vendor_id'   => $this_config['seller_id'],
    'lineitem_id' => $lineitem_id
);
//stop reccuring, $payment will be canceled throw IPN
$resp = $twocheckoutAPI->stop_lineitem_recurring($params);
if ($resp && $resp->response_code == 'OK') {
    // email to member if configured
    if ($config['mail_cancel_admin']){
        $t->assign('user', $member);
        $t->assign('payment', $p);
        $t->assign('product', $db->get_product($p['product_id']));
        $et = & new aMemberEmailTemplate();
        $et->name = "mail_cancel_admin";
        mail_template_admin($t, $et);
    }
    if ($config['mail_cancel_member']){
        $t->assign('user', $member);
        $t->assign('payment', $p);
        $t->assign('product', $db->get_product($p['product_id']));
        $et = & new aMemberEmailTemplate();
        $et->name = "mail_cancel_member";
        mail_template_user($t, $et, $member);
    }

    $t->assign('title', _PLUG_PAY_CC_CORE_SBSCNCL);
    $t->assign('msg', _PLUG_PAY_CC_CORE_SBSCNCL2);
    $t->display("msg_close.html");
} else {
    $GLOBALS['db']->log_error($twocheckoutAPI->getLastError());
    fatal_error('Internal error');
}

        



