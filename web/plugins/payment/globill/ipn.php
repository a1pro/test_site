<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: GloBill Payment Plugin
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
$this_config = $plugin_config['payment']['globill'];


/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
$action     = $vars['do']; // add | remove | sync
$amount     = doubleval($vars['price']);
$status     = intval($vars['status']); // active | expired
$payment_id = intval($vars['user1']);

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function globill_error($msg){
    global $order_id, $payment_id, $pnref;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_GLOBILL_FERROR, $msg, $pnref, $payment_id, '<br />')."\n".get_dump($vars));
}

$db->log_error("GLOBILL DEBUG: " . get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

if ($vars['wusername'] != $this_config['wusername']){
    globill_error(sprintf(_PLUG_PAY_GLOBILL_ERROR2, $vars[wusername]));
}

switch ($action){
case 'add':
    $err = $db->finish_waiting_payment($payment_id, 'globill', 
        $pnref, $amount, $vars);
    if ($err) 
        globill_error("finish_waiting_payment error: $err");
    $db->log_error("$payment_id,$pnref, $amount, $vars");
    // set expire date to infinite
    $p = $db->get_payment($payment_id);    
    $product = $db->get_product($p['product_id']);
    if ($product['is_recurring'])
        $p['expire_date'] = '2012-12-31';
    $db->update_payment($payment_id, $p);
    break;
case 'remove':
    if (!$vars['isexpired']) break;
    $p = $db->get_payment($payment_id);    
    $p['data'][] = $vars;
    $yesterday = date('Y-m-d', time()-3600*24);
    $p['expire_date'] = $yesterday;
    $db->update_payment($payment_id, $p);
    break;
}


?>
