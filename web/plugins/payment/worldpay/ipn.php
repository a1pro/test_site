<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Probilling Payment Plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4477 $)
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


$this_config = $plugin_config['payment']['worldpay'];
$t = & new_smarty();

/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();

$pnref      = $vars['transId'];
$amount     = doubleval($vars['amount']);
$status     = $vars['transStatus'];
$payment_id = intval($vars['cartId']);

function get_dump($var){
//dump of array
    $s = "";
    foreach ((array)$var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function worldpay_error($msg){
    global $order_id, $payment_id, $pnref, $db;
    global $vars;
    $db->log_error(sprintf(_PLUG_PAY_WORLDPAY_FERROR, $msg, $pnref, $payment_id, '<br />')."\n".get_dump($vars));
    die($msg);
}

function worldpay_get_begin_date($member_id, $product_id){
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

$db->log_error("WORLDPAY DEBUG: " . get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

/// check ip
//if (!preg_match('/^195\.35\.90\.\d+/', $_SERVER['REMOTE_ADDR'])){

//new
$host = gethostbyaddr($_SERVER['REMOTE_ADDR']);

if (!(preg_match('/^195\.35\.90\.\d+/', $_SERVER['REMOTE_ADDR'])||
preg_match('/^155\.136\.68\.\d+/', $_SERVER['REMOTE_ADDR'])||
preg_match('/^193\.41\.220\.\d+/', $_SERVER['REMOTE_ADDR'])||
preg_match('/^195\.166\.19\.\d+/', $_SERVER['REMOTE_ADDR'])||
preg_match('/^193\.41\.221\.\d+/', $_SERVER['REMOTE_ADDR'])||
preg_match('/^155\.136\.16\.\d+/', $_SERVER['REMOTE_ADDR'])||
preg_match('/(.*)\.worldpay\.com$/i', $host) || preg_match('/(.*)\.outbound\.wp3\.rbsworldpay\.com$/i', $host)
)){

    worldpay_error(_PLUG_PAY_WORLDPAY_ERROR);
}

// check status
if ($status != 'Y')
    worldpay_error(_PLUG_PAY_WORLDPAY_ERROR2);

if (!$this_config['testing'] && $vars['testMode'])
    worldpay_error(_PLUG_PAY_WORLDPAY_ERROR3);

// check that it is our payment
if ($vars['instId'] != $this_config['installation_id'])
    worldpay_error(_PLUG_PAY_WORLDPAY_ERROR4);

// check amount
if (!$amount){
    worldpay_error(_PLUG_PAY_WORLDPAY_ERROR5);
}

$pm = $db->get_payment($payment_id);

//check if payment is already processed
$payments = & $db->get_user_payments(intval($pm['member_id']));
foreach ($payments as $p){
    if ($p['receipt_id'] == $pnref){
       worldpay_error(sprintf(_PLUG_PAY_WORLDPAY_ERROR6, $pnref, $p[payment_id]));
    }
}

if ($pm){
    if ($pm['completed']){ // this is completed, we should add new one
        $product = get_product($pm['product_id']);
        $beg_date = worldpay_get_begin_date($pm['member_id'], $pm['product_id']);
        $tax = $pm['data']['regular_tax_amount'] ? 
               $pm['data']['regular_tax_amount'] :
               $pm['data']['TAX_AMOUNT'];
        if(!$tax) $tax = $pm['tax_amount'];
        $payment_id = $db->add_waiting_payment(
            $pm['member_id'],
            $pm['product_id'],
            $pm['paysys_id'],
            $amount,
            $beg_date,
            $product->get_expire($beg_date),
            array(),
            array('ORIG_ID' => $payment_id, 
                  'TAX_AMOUNT' => $tax,
            )
        );
    }
}

// process payment
$err = $db->finish_waiting_payment($payment_id, 'worldpay',
        $pnref, $amount, $vars);

if ($err)
    worldpay_error("finish_waiting_payment error: $err");

$_GET['payment_id'] = $_POST['payment_id'] =
    $_GET['payment_id'] = $_POST['payment_id'] = $payment_id;

include '../../../thanks.php';
?>