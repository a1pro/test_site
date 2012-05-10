<?php 
/*
*
*
*     Author: Centipaid Corporation
*      Email: admin@centipaid.com
*        Web: http://www.centipaid.com
*    Details: Centipaid CART API Payment Plugin
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

$this_config = $plugin_config['payment']['centipaid'];
$t = & new_smarty();
/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
$invoice    = intval($vars['x_invoice_num']);
$pnref      = $vars['x_trans_id'];
$amount     = doubleval($vars['x_amount']);
$result     = intval($vars['x_response_code']);
$respmsg    = $vars['x_response_reason_text'];

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}


function centipaid_error($msg){
    global $order_id, $invoice, $pnref;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_CENTIP_FERROR,$msg,$pnref,$invoice,'<br />'),"\n".get_dump($vars));
}

function centipaid_user_error($msg){
    global $t;
    $t->assign(error, array(_PLUG_PAY_CENTIP_ERROR."<b>" 
        . $msg . "</b>"._PLUG_PAY_CENTIP_ERROR2));    
    $t->display('fatal_error.html');
    exit();
}

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

if ($result != 1){
    centipaid_user_error($respmsg);
}
if (!$amount){
    centipaid_user_error(_PLUG_PAY_CENTIP_ERROR3);
}

// get MD5
//$md5source = $this_config['secret'] . $this_config['login'] . 
//    $vars['x_trans_id'] . $vars['x_amount'];
//$md5 = md5($md5source);
//if (strtoupper($md5) != $vars['x_MD5_Hash']){
//    centipaid_error('MD5 hash incorrect');
//}

// check test mode
if (!$vars['x_trans_id'] && !$this_config['testing']){
    centipaid_error(_PLUG_PAY_CENTIP_ERROR4);
}

// process payment
$err = $db->finish_waiting_payment($invoice, 'centipaid', 
        $pnref, $amount, $vars);
if ($err) 
    centipaid_error(sprintf(_PLUG_PAY_CENTIP_ERROR5,$err));

// show thanks page
$t->display('thanks.html');
?>
