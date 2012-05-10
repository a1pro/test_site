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


$t = & new_smarty();

/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
$action     = $vars['action'];
$pnref      = $vars['transaction_nmb'];
$amount     = doubleval($vars['transaction_amount']);
$status     = intval($vars['status_code']);
$payment_id = intval($vars['payment_id']);

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function probilling_error($msg){
    global $order_id, $payment_id, $pnref;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_PROBILLING_FERROR, $msg, $pnref, $payment_id)."\n".get_dump($vars));
}

$db->log_error("PROBILLING DEBUG: " . get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

switch ($action){
case 'ADD':
    if (($status != 'NEW') && ($status != 'PAID')){
        probilling_error(_PLUG_PAY_PROBILLING_ERROR);
    }
    $err = $db->finish_waiting_payment($payment_id, 'probilling', 
        $pnref, $amount, $vars);
    if ($err) 
        probilling_error("finish_waiting_payment error: $err");
    // set expire date to infinite
    $p = $db->get_payment($payment_id);    
    $product = $db->get_product($p['product_id']);
    if ($product['is_recurring'])
        $p['expire_date'] = '2012-12-31';
    $db->update_payment($payment_id, $p);
    break;
case 'RENEW':
    if (($status != 'NEW') && ($status != 'PAID')){
        probilling_error(_PLUG_PAY_PROBILLING_ERROR);
    }
    $p = $db->get_payment($payment_id);    
    $p['amount'] += $amount;
    $p['data'][] = $vars;
    $db->update_payment($payment_id, $p);
    break;
case 'REMOVE':
    $p = $db->get_payment($payment_id);    
    $p['data'][] = $vars;
    $yesterday = date('Y-m-d', time()-3600*24);
    $product = $db->get_product($p['product_id']);
//    if ($product['is_recurring'])
        $p['expire_date'] = $yesterday;
//    else {
//        $p['completed'] = 0;
//        $p['amount'] -= $amount;
//    }        
    $db->update_payment($payment_id, $p);
    break;
default:
    probilling_error("Unknown action: $action");
}
