<?php
include "../../../config.inc.php";

$this_config = $plugin_config['payment']['paymate'];
$t = & new_smarty();

/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();

$txn_id = $vars['transactionID'];
$amount = doubleval($vars['paymentAmount']);
$payment_id = intval($vars['ref']);
$payment = $db->get_payment($payment_id);
$res = $vars['responseCode'];

function paymate_error($msg){
    global $txn_id, $payment_id;
    global $vars;
    fatal_error("Paymate ERROR: $msg (Details: transID: $txn_id, payment_id: $payment_id)");
}

if($res!="PA")
	paymate_error("Transaction declined, responsecode: $res");

$err = $db->finish_waiting_payment($payment_id, 'ideal', $txn_id, $amount, $vars);
if ($err)
	paymate_error("finish_waiting_payment error: $err");

$t = & new_smarty();

if ($payment_id){
	$pm = $db->get_payment($payment_id);
	$t->assign('payment', $pm);
	if ($pm) {
		$t->assign('product', $db->get_product($pm['product_id']));
		$t->assign('member', $db->get_user($pm['member_id']));
	}
	if (!($prices = $pm['data'][0]['BASKET_PRICES'])){
		$prices = array($pm['product_id'] => $pm['amount']);
	}
	$pr = array();
	$subtotal = 0;
	foreach ($prices as $product_id => $price){
		$v  = $db->get_product($product_id);
//        $v['price'] = $price;
		$subtotal += $v['price'];
		$pr[$product_id] = $v;
	}
	$t->assign('subtotal', $subtotal);
	$t->assign('total', array_sum($prices));
	$t->assign('products', $pr);
}
$t->display("thanks.html");

?>
