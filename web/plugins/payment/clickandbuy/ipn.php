<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Probilling Payment Plugin
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

function clickandbuy_is_nan( $var ) {
    return !ereg ("^[-]?[0-9]+([\.][0-9]+)?$", $var);
}


$this_config = $plugin_config['payment']['clickandbuy'];
$t = & new_smarty();

/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();

$cb_linknr      = $_SERVER["HTTP_X_CONTENTID"];		//Click&Buy Link Number
$amount			= $_SERVER["HTTP_X_PRICE"];			//Click&Buy in Millicents !
$cb_uid			= $_SERVER["HTTP_X_USERID"];		//Click&Buy Customer Reference Number
$pnref	        = $_SERVER["HTTP_X_TRANSACTION"];	//Click&Buy Transaction ID is a unique id
$cb_currency	= $_SERVER["HTTP_X_CURRENCY"];		//Click&Buy CURRENCY=EUR

$payment_id     = intval($vars['payment_id']);
$amount         = doubleval($amount / 1000 / 100);

// when price >= 100 (1.00 _) divide by 1000
// when price < 100 (0.01 _)  divide by 100000
$myprice  = doubleval($vars['price'] / 100); // 1000 = 10,00_ sent over the GET parameter

$ext_bdr_id		= $vars["externalBDRID"];	  //Your unique external transaction ID , this parameter is mandatory


function clickandbuy_error($msg){
    global $order_id, $payment_id, $pnref, $db;
    global $vars;
    $db->log_error("CLICKANDBUY ERROR: " . $msg);
    //$db->log_error(sprintf(_PLUG_PAY_CLICKANDBUY_FERROR, $msg, $pnref, $payment_id, '<br />')."\n".clickandbuy_get_dump($vars));
    //die($msg);
}

$db->log_error("CLICKANDBUY DEBUG: " . clickandbuy_get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

$result = true;
$reason = '';

//Check Click&Buy UserID
if(empty($cb_uid) || clickandbuy_is_nan($cb_uid)){
	$result = false;
	$reason.= "cb_uid&";
}

// check ip
if (!preg_match('/^217\.22\.128\.\d+/', $_SERVER['REMOTE_ADDR'])){
	$result = false;
	$reason.= "cb_ip&";
}

// Check Click&Buy Transaction ID if cb_transaction_id=0 then is it a test purchase or the user is member in the servicearea
if (!$pnref){
	$result = false;
    $reason.= "cb_transaction_id&";
}

//Check Click&Buy Price
if(empty($amount)){
	$result = false;
	$reason.= "cb_price1&".$amount."#";
}

//Check My Price, please check Click&Buy price with your price!!!
if(($amount) != $myprice){
	$result = false;
	$reason.= "cb_price2=".$amount."#";
}

$pm = $db->get_payment($payment_id);

// Check external BDR ID
if ($result && !$pm['data']['ext_bdr_id']){	$pm['data']['ext_bdr_id'] = $ext_bdr_id;
	$pm['data']['trans_id'] = $pnref;
	$db->update_payment($pm['payment_id'], $pm);
}

//redirect success or error
if($result){
	header("Location: " . $config['root_surl'] . "/plugins/payment/clickandbuy/thanks.php?result=success");
} else {
    clickandbuy_error("reason=" . $reason);
	header("Location: " . $config['root_surl'] . "/plugins/payment/clickandbuy/thanks.php?result=error&reason=" . $reason);
}
?>