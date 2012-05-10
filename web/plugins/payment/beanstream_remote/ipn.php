<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: beanstream_remote Payment Plugin
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5012 $)
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
$this_config = $plugin_config['payment']['beanstream_remote'];


/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
$amount     = doubleval($vars['trnAmount']);
$payment_id = intval($vars['trnOrderNumber']);
$pnref = $vars['trnId'];

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function beanstream_remote_error($msg){
    global $order_id, $payment_id, $pnref;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_BEANSTREAM_REM_ERROR, $msg, $pnref, $payment_id, "<br />", "\n").get_dump($vars));
}

$db->log_error(_PLUG_PAY_BEANSTREAM_REM_DEBUG . get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

if ($vars['messageId'] == '1'){
    $err = $db->finish_waiting_payment($payment_id, 'beanstream_remote', 
        $pnref, $amount, $vars);
    if ($err) 
        beanstream_remote_error("finish_waiting_payment error: $err");
    if ($vars['rbAccountId']){
        $p = $db->get_payment($payment_id);
        $p['data']['beanstream_rbaccountid'] = $vars['rbAccountId'];
        $db->update_payment($payment_id, $p);
    }

    if ($vars['trnRecurring']){
        $p = $db->get_payment($payment_id);
        $p['expire_date'] = '2012-12-31';
        $db->update_payment($payment_id, $p);
    }
} else {
    //payment failed
}

?>
