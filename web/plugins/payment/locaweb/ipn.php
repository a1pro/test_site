<?php
/*
*
*
*     Author: Alexander Smith
*      Email: alexander@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: AlertPay Payment Plugin
*    FileName $RCSfile$
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


$this_config = $plugin_config['payment']['alertpay'];
$t = & new_smarty();

/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();

$pnref      = $vars[ap_referencenumber];
$amount     = doubleval($vars['ap_amount']);
$status     = $vars['ap_status'];
$payment_id = intval($vars['apc_1']);

function get_dump($var){
//dump of array
    $s = "";
    foreach ((array)$var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function alertpay_error($msg){
    global $order_id, $payment_id, $pnref, $db;
    global $vars;
    $db->log_error(sprintf(_PLUG_PAY_ALERTPAY_FERROR, $msg, $pnref, $payment_id, '<br />')."\n".get_dump($vars));
    die($msg);
}

function alertpay_get_begin_date($member_id, $product_id){
    global $db;
    $payments = & $db->get_user_payments(intval($member_id));
    $date = date('Y-m-d');
    foreach ($payments as $p){
        if (($p['product_id'] == $product_id) &&
            ($p['expire_date'] > $date) &&
            ($p['completed'] > 0)
            ) 
            $date = $p['expire_date'];
    }
    list($y,$m,$d) = split('-', $date);
    $date = date('Y-m-d', mktime(0,0,0,$m, $d, $y));
    return $date;
}

$db->log_error("ALERTPAY DEBUG: " . get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

//if (md5($this_config[secret]) != $vars[securitycode]){
if (!(md5($this_config['secret']) == $vars['ap_securitycode'] || $this_config['secret'] == $vars['ap_securitycode'])){
    alertpay_error(_PLUG_PAY_ALERTPAY_ERROR);
}

// check status
if ($status != 'Success' && $status != 'Subscription-Payment-Success')
    alertpay_error(_PLUG_PAY_ALERTPAY_ERROR2);

if (!$this_config['testing'] && $vars['ap_test'])
    alertpay_error(_PLUG_PAY_ALERTPAY_ERROR3);

// check that it is our payment
if ($vars['ap_merchant'] != $this_config['merchant'])
    alertpay_error(_PLUG_PAY_ALERTPAY_ERROR4);

// check amount
if (!$amount){
    alertpay_error(_PLUG_PAY_ALERTPAY_ERROR5);
}

$pm = $db->get_payment($payment_id);

//check if payment is already processed
//$payments = & $db->get_user_payments(intval($pm['member_id']));
//foreach ($payments as $p){
//    if ($p['receipt_id'] == $pnref){
//       alertpay_error(sprintf(_PLUG_PAY_ALERTPAY_ERROR6, $pnref, $p[payment_id]));
//    }
//}

if ($pm){
    if ($pm['completed']){ // this is completed, we should add new one
        $product = get_product($pm['product_id']);
        $beg_date = alertpay_get_begin_date($pm['member_id'], $pm['product_id']);
        list($y,$m,$d) = split('-', $beg_date);
        $beg_date1 = date('Y-m-d', mktime(0,0,0,$m, $d, $y) + 3600 * 24);
        $payment_id = $db->add_waiting_payment(
            $pm['member_id'], 
            $pm['product_id'], 
            $pm['paysys_id'], 
            $amount, 
            $beg_date1, 
            $product->get_expire($beg_date1),
            array('ORIG_ID' => $payment_id)
        );
    } 
}

// process payment
$err = $db->finish_waiting_payment($payment_id, 'alertpay', 
        $pnref, $amount, $vars);

if ($err) 
    alertpay_error("finish_waiting_payment error: $err");

$_GET['payment_id'] = $_POST['payment_id'] = 
    $_GET['payment_id'] = $_POST['payment_id'] = $payment_id;

?>
