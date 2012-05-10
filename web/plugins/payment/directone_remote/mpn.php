<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: directone Payment Plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4770 $)
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
$this_config = $config['payment']['directone_remote'];
/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
$pnref      = $vars['payment_number'];
$amount     = doubleval($vars['payment_amount']);
$payment_id = intval($vars['payment_reference']);

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function directone_error($msg){
    global $order_id, $payment_id, $pnref;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_DIRECTONEREM_ERROR,$msg,$pnref,$payment_id,'<br />')."\n".get_dump($vars));
}

$db->log_error("directone DEBUG: " . get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

if (!preg_match('/^202\.62\.63\./', $_SERVER['REMOTE_ADDR'])){
    directone_error("DirectOne post from unknown address: {$_SERVER['REMOTE_ADDR']}");
}

if ($vars['vendor_name'] != $this_config['account_name'])
    directone_error("DirectOne post came with incorect vendor_name: $vars[vendor_name]");

$err = $db->finish_waiting_payment($payment_id, 'directone_remote', 
    $pnref, $amount, $vars);
if ($err) 
    directone_error("finish_waiting_payment error: $err");

?>
