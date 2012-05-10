<?php 
// IT IS FROM PROBILLING, try to change it for egspay
// you will see helpful records in aMember Error Log
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: EgsPay Payment Plugin
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
$action     = $vars['command'];
//$pnref      = $vars['transaction_nmb'];
$amount     = doubleval($vars['amount']);
$payment_id = intval($vars['var1']);

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function egspay_error($msg){
    global $order_id, $payment_id, $pnref;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_EGSPAY_ERROR,$msg,$payment_id,'<br />')."\n".get_dump($vars));
}

$db->log_error("egspay DEBUG: " . get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

// add ip protection

switch ($action){
case 'add_member':
    $err = $db->finish_waiting_payment($payment_id, 'egspay', 
        $pnref, $amount, $vars);
    if ($err) 
        egspay_error("finish_waiting_payment error: $err");
    // set expire date to infinite
    $p = $db->get_payment($payment_id);    
    $product = $db->get_product($p['product_id']);
    if ($product['is_recurring'])
        $p['expire_date'] = '2012-12-31';
    $db->update_payment($payment_id, $p);
    break;
case 'update_member':
    $p = $db->get_payment($payment_id);    
    $p['amount'] += $amount;
    $p['data'][] = $vars;
    $db->update_payment($payment_id, $p);
    break;
case 'remove_member':
    $p = $db->get_payment($payment_id);    
    $p['data'][] = $vars;
    $yesterday = date('Y-m-d', time()-3600*24);
    $product = $db->get_product($p['product_id']);
    if ($product['is_recurring'])
        $p['expire_date'] = $yesterday;
    else {
        $p['completed'] = 0;
//        $p['amount'] -= $amount;
    }        
    $db->update_payment($payment_id, $p);
    break;
}



