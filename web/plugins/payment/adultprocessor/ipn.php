<?php
include "../../../config.inc.php";
$this_config = $plugin_config['payment']['adultprocessor'];
$vars = get_input_vars();

$pnref      = $vars["transactionID"];
$amount     = doubleval($vars['amount']);
$md5     = $vars['ref2'];
$payment_id = intval($vars['ref1']);
$success = $vars['success'];

function get_dump($var){
    $s = "";
    foreach ((array)$var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function adultprocessor_error($msg){
    global $order_id, $payment_id, $pnref, $db;
    global $vars;
    $db->log_error(sprintf("ADULTPROCESSOR ERROR: %s (Details: PNREF:%s, invoice:%d)%s", $msg, $pnref, $payment_id, '<br />')."\n".get_dump($vars));
    die($msg);
}

$db->log_error("ADULTPROCESSOR DEBUG: " . get_dump($vars));

if (md5($this_config['merchantid'].$this_config['merchant_password'].$payment_id) != $md5)
    adultprocessor_error("Incorrect secret code");

if ($success != '1')
    adultprocessor_error("Transaction declined ");

if (!$payment_id)
    adultprocessor_error("No payment_id");

$pm = $db->get_payment($payment_id);


// process payment
$err = $db->finish_waiting_payment($payment_id,'adultprocessor',$pnref,$amount,$vars);

if ($err) 
    adultprocessor_error("finish_waiting_payment error: $err");

$_GET['payment_id'] = $_POST['payment_id'] = 
    $_GET['payment_id'] = $_POST['payment_id'] = $payment_id;

?>
