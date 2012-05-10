<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: ccbill Payment Plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1739 $)
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
$this_config = $plugin_config['payment']['ccbill'];


/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
$payment_id = intval($vars['payment_id']);
$pnref = $vars['subscription_id'];

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function ccbill_error($msg){
    global $order_id, $payment_id, $pnref;
    global $vars,  $db;
    $db->log_error("ccbill ERROR: $msg (Details: PNREF:'$pnref', invoice:'$payment_id')<br />\n".get_dump($vars));
    die("ccBill plugin error, please don't run this file manually - it is not supposed to work from your browser");
}

$db->log_error("CCBILL DEBUG:<br />\n" . get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

if ($vars['clientAccnum'] != $this_config['account']){
    ccbill_error("Incorrect CCBILL account number: [$vars[clientAccnum]] instead of [$this_config[account]]");
}
if ($host = gethostbyaddr($addr=$_SERVER['REMOTE_ADDR'])){
    if (!strlen($host) || ($addr==$host)) {
        //   ccbill_error("Cannot resolve host: ($addr=$host)\n");
        // let is go, as some hosts are just unable to resolve names
    } elseif (!preg_match('/ccbill\.com$/', $host))
        ccbill_error("POST is not from ccbill.com, it is from ($addr=$host)\n");
}

if ($vars['reasonForDecline'] != '') { // it is decline
    $p = $db->get_payment($payment_id);    
    $p['data'][] = $vars;
    $db->update_payment($payment_id, $p);
} elseif ($vars['subscription_id'] != ''){
    $p = $db->get_payment($payment_id);
    $product = &get_product($p['product_id']);
    if (intval($product->config['ccbill_id']) != intval($vars['typeId'])){
        ccbill_error("Product ID doesn't match: {$product->config['ccbill_id']} and {$vars['typeId']}");
    }
    $err = $db->finish_waiting_payment($payment_id, $p['paysys_id'], 
        $pnref, '', $vars);
    if ($err) 
        ccbill_error("finish_waiting_payment error: $err");
} else {
    ccbill_error("Not-recognized POST");
}


?>
