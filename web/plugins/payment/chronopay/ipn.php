<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Chronopay Payment Plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1967 $)
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

global $config;

$t = & new_smarty();

/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();

$this_config = $plugin_config['payment']['chronopay'];
if ($this_config['ip'] && ($this_config['ip'] != $_SERVER['REMOTE_ADDR'])) {
    Chronopay_error("Incorrect IP Address ($this_config[ip] != $_SERVER[REMOTE_ADDR])");
} 
$status     = $vars['transaction_type'];
$amount     = $vars['total'];
$payment_id = intval($vars['cs1']);
$pnref      = $vars['transaction_id'];

function get_dump($var)
{
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function Chronopay_error($msg)
{
    global $order_id, $payment_id, $pnref;
    global $vars;
    fatal_error("Chronopay ERROR: $msg (Details: PNREF:'$pnref', invoice:'$payment_id')<br />\n".get_dump($vars));
}

$db->log_error("Chronopay DEBUG: " . get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////
switch ($status)
{
case 'onetime':
case 'initial':
    $err = $db->finish_waiting_payment($payment_id, 'chronopay',
        $pnref, $amount, $vars);
    if ($err)
        Chronopay_error("finish_waiting_payment error: $err");
    // set expire date to infinite
    $p = $db->get_payment($payment_id);
    $product = $db->get_product($p['product_id']);
    if ($product['is_recurring'])
        $p['expire_date'] = '2012-12-31';
    $db->update_payment($payment_id, $p);
    break;
case 'rebill':
    $p = $db->get_payment($payment_id);
    $p['amount'] += $amount;
    $p['data'][] = $vars;
    $db->update_payment($payment_id, $p);
    break;
case 'expire':
    $p = $db->get_payment($payment_id);
    $p['data'][] = $vars;
    $yesterday = date('Y-m-d', time()-3600*24);
    $product = $db->get_product($p['product_id']);
    $p['expire_date'] = $yesterday;
    $db->update_payment($payment_id, $p);
    break;
case 'refund':
case 'chargeback':
    $p=$db->get_payment($payment_id);
    $p['completed']=0;
    $db->update_payment($payment_id, $p);
    break;
default:
    Chronopay_error("Unknown action: $status");
}
