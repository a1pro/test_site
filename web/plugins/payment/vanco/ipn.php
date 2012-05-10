<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: multicards Link Single Payment Plugin
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

$this_config = $plugin_config['payment']['vanco'];
$t = & new_smarty();
/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();

$confirmation = $vars["confirmation"] ? $vars["confirmation"] : $vars["?confirmation"];
$errors = $vars["errors"] ? $vars["errors"] : $vars["?errors"];

function get_dump($var)
{
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}


function vanco_error($msg){
    global $order_id, $invoice, $pnref;
    global $vars;
    fatal_error(sprintf('multicards ERROR: %s (Details: PNREF:%s, invoice:%d)%s', $msg, $pnref, $invoice, '<br />')."\n".get_dump($vars));
}

function vanco_user_error($msg){
    global $t,$config;
	
    $t->assign('error', array("Error processing payment:<br />\n<b> " 
        . $msg . " </b><br />\n Please return back and try again."));
	$t->assign('admin_email',$config['admin_email']);
    $t->display('fatal_error.html');
    exit();
}

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

if ($errors)
{
    vanco_user_error($errors);
	exit();
}

if (!$confirmation)
{
	vanco_error(" fatal error: no confirmation ");
	exit();
} else {
	if (!$vars['payment_id'])
	{
		vanco_error(" fatal error: no payment id ");
		exit();
	} else {
		$payment_id = intval($vars['payment_id']);
	}
	$pm = $db->get_payment($payment_id);
}


// process payment
$err = $db->finish_waiting_payment($payment_id, 'vanco', 
        $confirmation, '', $vars);
if ($err) 
    vanco_error("finish_waiting_payment error: $err");

// show thanks page
$t->assign('payment', $pm);
$t->assign('product', $db->get_product($pm['product_id']));
$t->assign('member', $db->get_user($pm['member_id']));
if (!($prices = $pm['data'][0]['BASKET_PRICES'])){
	$prices = array($pm['product_id'] => $pm['amount']);
}
$pr = array();
$subtotal = 0;
foreach ($prices as $product_id => $price){
	$v  = $db->get_product($product_id);
	$subtotal += $v['price'];
	$pr[$product_id] = $v;
}
$t->assign('subtotal', $subtotal);
$t->assign('total', array_sum($prices));
$t->assign('products', $pr);
$t->assign('subtotal', $subtotal);
$t->display('thanks.html');
?>
