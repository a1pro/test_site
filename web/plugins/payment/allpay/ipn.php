<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Probilling Payment Plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1785 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

include "../../../config.inc.php";

$this_config = $plugin_config['payment']['allpay'];
$t = & new_smarty();

/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();

$pnref      = $vars['t_id'];
$amount     = doubleval($vars['amount']);
$status     = $vars['status'];
$payment_id = intval($vars['control']);

function get_dump($var){
//dump of array
    $s = "";
    foreach ((array)$var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function allpay_error($msg){
    global $order_id, $payment_id, $pnref, $db;
    global $vars;
    $db->log_error(sprintf(_PLUG_PAY_ALLPAY_FERROR, $msg, $pnref, $payment_id, '<br />'));
    echo "OK";
    exit;
}

$db->log_error("AllPay DEBUG: " . get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

/// check ip
if ( !preg_match('/^217\.17\.41\.5/', $_SERVER['REMOTE_ADDR']) ){
    allpay_error(_PLUG_PAY_ALLPAY_ERROR);
}

// check status
if ($status != 'OK'){
    allpay_error(_PLUG_PAY_ALLPAY_ERROR2);
}

// check that it is our payment
if ($vars['id'] != $this_config['seller_id']){
    allpay_error(_PLUG_PAY_ALLPAY_ERROR4);
}

// check amount
//if (!$amount)
//    allpay_error(_PLUG_PAY_ALLPAY_ERROR5); // disabled

$pm = $db->get_payment($payment_id);

$amount = $pm['amount']; // will use initial amount

//check if payment is already processed
$payments = & $db->get_user_payments(intval($pm['member_id']));
foreach ($payments as $p){
    if ($p['receipt_id'] == $pnref){
       allpay_error(sprintf(_PLUG_PAY_ALLPAY_ERROR6, $pnref, $p['payment_id']));
    }
}

// process payment
$err = $db->finish_waiting_payment($payment_id, 'allpay', 
        $pnref, $amount, $vars);

if ($err){
    allpay_error("finish_waiting_payment error: $err");
}

echo "OK";
?>