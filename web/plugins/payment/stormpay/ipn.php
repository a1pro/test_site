<?php

include "../../../config.inc.php";

$this_config = $plugin_config['payment']['stormpay'];

function get_dump($var){
//dump of array
    $s = "";
    foreach ((array)$var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function stormpay_error($msg){
    global $txn_id, $invoice;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_STORMPAY_FERROR, $msg, $txn_id, $invoice, '<br />')."\n".get_dump($vars));
}

// read post from Stormpay system and add 'cmd'
$vars = $_POST ? $_POST : $_GET;
$req = 'cmd=_notify-validate';
foreach ($vars as $k => $v) {
    if (get_magic_quotes_gpc())
        $vars[$k] = $v = stripslashes($v);
    $req .= "&" . urlencode($k) . "=" . urlencode ($v);
}

// assign posted variables to local variables
// note: additional IPN variables also available -- see IPN documentation
// https://www.stormpay.com/stormpay/user/glc_integration_manual.html#ipn_variables
$receiver_email = $vars['payee_email'];
$payer_email    = $vars['payer_email'];
$invoice        = $vars['transaction_ref'];
$payment_status = $vars['status'];
$payment_gross  = $vars['amount'];
$txn_id         = $vars['transaction_id'];
$secret_code    = $vars['secret_code'];

$db->log_error("stormpay DEBUG<br />\n".get_dump($vars));

if ($secret_code != $this_config['secret_id'])
    stormpay_error(sprintf(_PLUG_PAY_STORMPAY_ERROR, $secret_code));

// check that receiver_email is an email address
// in your stormpay account
if ($receiver_email != $this_config['business'])
    stormpay_error(
    sprintf(_PLUG_PAY_STORMPAY_ERROR2, $receiver_email).$this_config['business']);

if ($payment_status == 'PENDING')
  mail("$receiver_email","PENDING STORMPAY PAYMENT","Account is already active (Payment completion takes 7 days)!\nPayer:$payer_email\nAmount:$payment_gross\nID:$txn_id","From:Amember Stormpay Notification <$payer_email>\n");

elseif ($payment_status != 'SUCCESS' && $payment_status != 'COMPLETE')
    stormpay_error(sprintf(_PLUG_PAY_STORMPAY_ERROR3, $payment_status));

// process payment
$err = $db->finish_waiting_payment($invoice, 'stormpay',
        $txn_id, $payment_gross, $vars);
if ($err)
    stormpay_error("finish_waiting_payment error: $err");

?>