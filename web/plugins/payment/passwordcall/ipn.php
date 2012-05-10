<?php

include "../../../config.inc.php";

$this_config = $plugin_config['payment']['passwordcall'];

function get_dump($var){
//dump of array
    $s = "";
    foreach ((array)$var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function passwordcall_error($msg){
    global $txn_id, $invoice;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_PSWDCALL_FERROR, $txn_id, $invoice, '<br />')."\n".get_dump($vars));
}

// read post from passwordcall system and add 'cmd'
$vars = get_input_vars();

$req = 'cmd=_notify-validate';
foreach ($vars as $k => $v) {
    if (get_magic_quotes_gpc())
        $vars[$k] = $v = stripslashes($v);
    $req .= "&" . urlencode($k) . "=" . urlencode ($v);
}

// assign posted variables to local variables
$invoice        = $vars['transaction_ref'];
$webmaster_id   = $vars['wmid'];
$txn_id         = $vars['pwcpasswort'];
$payment_gross  = 0;

$db->log_error("passwordcall DEBUG<br />\n".get_dump($vars));

if ($webmaster_id != $this_config['webmaster_id'])
    passwordcall_error(sprintf(_PLUG_PAY_PSWDCALL_ERROR, $webmaster_id));

// process payment
$err = $db->finish_waiting_payment($invoice, 'passwordcall',
        $txn_id, $payment_gross, $vars);
if ($err)
    passwordcall_error("finish_waiting_payment error: $err");

?>