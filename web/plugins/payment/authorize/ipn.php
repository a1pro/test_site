<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: authorize Link Single Payment Plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4672 $)
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

$this_config = $plugin_config['payment']['authorize'];
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


function authorize_error($msg){
    global $order_id, $invoice, $pnref;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_AUTHORIZE_ERROR, $msg, $pnref, $invoice, "<br />", "\n").get_dump($vars));
}

function authorize_user_error($msg){
    global $t;
    $t->assign(error, array(_PLUG_PAY_AUTHORIZE_ERR_ARR." <b>" 
        . $msg . "</b> "._PLUG_PAY_AUTHORIZE_ERR_ARR2));    
    $t->display('fatal_error.html');
    exit();
}

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////
$db->log_error("AUTHORIZE.NET SIM DEBUG: " . get_dump($vars));

if ($result != 1){
    authorize_user_error($respmsg);
}
if (!$amount){
    authorize_user_error(_PLUG_PAY_AUTHORIZE_USER_ERR);
}

// get MD5
$md5source = $this_config['secret'] . $this_config['login'] . 
    $vars['x_trans_id'] . $vars['x_amount'];
$md5 = md5($md5source);
if (strtoupper($md5) != $vars['x_MD5_Hash']){
    authorize_error(_PLUG_PAY_AUTHORIZE_ERROR2);
}

// check test mode
if (!$vars['x_trans_id'] && !$this_config['testing']){
    authorize_error(_PLUG_PAY_AUTHORIZE_ERROR3);
}

// process payment
$err = $db->finish_waiting_payment($invoice, 'authorize', 
        $pnref, $amount, $vars);
if ($err) 
    authorize_error(sprintf(_PLUG_PAY_AUTHORIZE_ERROR4, $err));

if ($invoice){
    $t->assign('payment', $pm = $db->get_payment($invoice));
    if ($pm) {
        $t->assign('product', $db->get_product($pm['product_id']));
        $t->assign('member', $db->get_user($pm['member_id']));
    }
    if (!($prices = $pm['data'][0]['BASKET_PRICES'])){
        $prices = array($pm['product_id'] => $pm['amount']);
    }
    $pr = array();
    $subtotal = 0;
    foreach ($prices as $product_id => $price){
        $v  = $db->get_product($product_id);
//        $v['price'] = $price;
        $subtotal += $v['price'];
        $pr[$product_id] = $v;
    }
    $t->assign('subtotal', $subtotal);
    $t->assign('total', array_sum($prices));
    $t->assign('products', $pr);
}

// show thanks page
$t->display('thanks.html');
?>
