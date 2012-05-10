<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: PayFlow Link Single Payment Plugin
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5071 $)
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


$this_config = $plugin_config['payment']['ematters'];
$t = & new_smarty();

/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
$result = $vars['rcode'];
$invoice = $vars['UID'];

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function ematters_error($msg){
    global $order_id, $invoice, $pnref;
    global $vars;
    header("HTTP/1.1 404 Not found");
    header("Status: 404 Not Found");
    fatal_error("EMATTERS ERROR: $msg (Details: invoice:'$invoice')<br />\n".get_dump($vars));
}


//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

$p = $db->get_payment($invoice);
$rcode = $result - ($p['amount'] * 100) - ($this_config['merchant_id']*$vars['UID']);
$codes = array(
00 =>_PLUG_PAY_EMATTERS_CODE0,
01 =>_PLUG_PAY_EMATTERS_CODE1,
04 =>_PLUG_PAY_EMATTERS_CODE2,
08 =>_PLUG_PAY_EMATTERS_CODE3,
12 =>_PLUG_PAY_EMATTERS_CODE4,
31 =>_PLUG_PAY_EMATTERS_CODE5,
39 =>_PLUG_PAY_EMATTERS_CODE6,
51 =>_PLUG_PAY_EMATTERS_CODE7,
61 =>_PLUG_PAY_EMATTERS_CODE8,
91 =>_PLUG_PAY_EMATTERS_CODE9,
708 =>_PLUG_PAY_EMATTERS_CODE10,
709 =>_PLUG_PAY_EMATTERS_CODE11,
810 =>_PLUG_PAY_EMATTERS_CODE12,
812 =>_PLUG_PAY_EMATTERS_CODE13,
813 =>_PLUG_PAY_EMATTERS_CODE14,
816 =>_PLUG_PAY_EMATTERS_CODE15,
980 =>_PLUG_PAY_EMATTERS_CODE16,
990 =>_PLUG_PAY_EMATTERS_CODE17
);

if ((intval($rcode) != 1) && (intval($rcode) != 8)){
    $res = $codes[$rcode]; if ($res) $res = " $res";
    fatal_error(sprintf(_PLUG_PAY_EMATTERS_ERROR,$res));
}
// process payment
$err = $db->finish_waiting_payment($invoice, 'ematters', '', '', $vars);


if ($err)  ematters_error("finish_waiting_payment error: $err"); 
else{
        $t = & new_smarty();
        $pm = $db->get_payment($payment_id);
        $t->assign('payment', $pm);
        $t->assign('product', $db->get_product($pm['product_id']));
        $t->assign('member', $db->get_user($pm['member_id']));                
        $t->display("thanks.html");
}
// else  	the payment has succeeded give user confirmation and link to logon
?>