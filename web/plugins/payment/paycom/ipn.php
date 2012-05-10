<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: paycom Link Single Payment Plugin
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5406 $)
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

$this_config = $plugin_config['payment']['paycom'];
$t = & new_smarty();
/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();

$co_code     = $vars['co_code'];
$amount      = "";
$invoice     = intval($vars['x_payment_id']);
$result      = $vars['ans']['0']; // Y or N

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}
$db->log_error("Paycom IPN".get_dump($vars));


function paycom_error($msg){
    global $invoice;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_PAYCOM_FERROR, $msg, $invoice, '<br\>')."\n".get_dump($vars));
}

function paycom_user_error($msg){
    global $t;
    $t->assign(error, array(sprintf(_PLUG_PAY_PAYCOM_ERROR, '<b>')
        . $msg . sprintf(_PLUG_PAY_PAYCOM_ERROR2, '</b>')));    
    $t->display('fatal_error.html');
    exit();
}

function validate_src( $src, $allowed){
  if ($src == "")
    return false;
  $allowed = "/" . str_replace(".", "\.", $allowed) . "/";
  if (preg_match($allowed, $src) != 0)
    return true;
  return false;
}

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

if (($this_config['IPN_pass'] != '') && ($this_config['IPN_pass'] != $_GET['cred'])){
    paycom_error(_PLUG_PAY_PAYCOM_ERROR3.  $_GET['cred']);
 }
if ($result != 'Y'){
  paycom_error(sprintf(_PLUG_PAY_PAYCOM_ERROR4, $invoice, '<br />')."\n".get_dump($vars));
}

if (($this_config['testing'] == '') && ($vars['ans'] == 'YGOODTEST|null'))
    paycom_error(_PLUG_PAY_PAYCOM_ERROR5);
//if (!$amount){
//    paycom_error("returned amount empty or = 0");
//}

$p = $db->get_payment(intval($invoice));
$pr = $db->get_product($p['product_id']);
if ($pr['paycom_id'] != $vars['product_id'])
    paycom_error(sprintf(_PLUG_PAY_PAYCOM_ERROR6, $pr[paycom_id], $vars[product_id]));

// check IP
if ($this_config['ip'] && !(validate_src($_SERVER['REMOTE_ADDR'],$this_config['ip']))){
    paycom_error(sprintf(_PLUG_PAY_PAYCOM_ERROR7, $_SERVER['REMOTE_ADDR']).$this_config['ip']);
}

// check merchant id
if ($this_config['co_code'] != $co_code){
    paycom_error(_PLUG_PAY_PAYCOM_ERROR8);
}

// process payment
$err = $db->finish_waiting_payment($invoice, 'paycom', 
        $vars['transaction_id'], $amount, $vars);
if ($err) 
    paycom_error("finish_waiting_payment error: $err");



print "OK";
?>
