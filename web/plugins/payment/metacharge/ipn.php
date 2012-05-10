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

$this_config = $plugin_config['payment']['metacharge'];


if ($this_config['auth_username'] != '' && $this_config['auth_password'] != '') {

    // do HTTP Basic Authorisation
    if (!isset($_SERVER['PHP_AUTH_USER'])) { // HTTP Authentication
        header('WWW-Authenticate: Basic realm="aMember Metacharge PRN response"');
        header('HTTP/1.0 401 Unauthorized');
        print "Error - HTTP Auth Username is not entered";
        exit();
    }
    
    // checking name and password
    if( ($_SERVER['PHP_AUTH_USER'] != $this_config['auth_username'])  
     || ($_SERVER['PHP_AUTH_PW']  != $this_config['auth_password'])){
        header('WWW-Authenticate: Basic realm="aMember Metacharge PRN response"');
        header('HTTP/1.0 401 Unauthorized');
        print "Error - Incorrect HTTP Auth username or password entered";
        exit();
    }

}

$t = & new_smarty();

/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();

$pnref      = intval($vars['intTransID']);
$pnrel      = intval($vars['intRelatedID']);
$scheduleID = intval($vars['intScheduleID']);

$amount     = doubleval($vars['fltAmount']);
$status     = intval($vars['intStatus']);
$payment_id = intval($vars['strCartID']);


function metacharge_error($msg){
    global $order_id, $payment_id, $pnref, $db;
    global $vars;
    $db->log_error(sprintf(_PLUG_PAY_METACHARGE_FERROR, $msg, $pnref, $payment_id, '<br />')."\n".metacharge_get_dump($vars));
    die($msg);
}

function metacharge_get_begin_date($member_id, $product_id){
    global $db;
    $payments = & $db->get_user_payments(intval($member_id));
    $date = date('Y-m-d');
    foreach ($payments as $p){
        if (($p['product_id'] == $product_id) &&
            ($p['expire_date'] > $date) &&
            ($p['completed'] > 0)
            ) 
            $date = $p['expire_date'];
    }
    list($y,$m,$d) = split('-', $date);
    $date = date('Y-m-d', mktime(0,0,0,$m, $d, $y));
    return $date;
}

$db->log_error("METACHARGE DEBUG: " . metacharge_get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

// check status
if ($status != '1')
    metacharge_error(sprintf(_PLUG_PAY_METACHARGE_ERROR2, $vars['strMessage']));

if (!$this_config['testing'] && $vars['intTestMode'])
    metacharge_error(_PLUG_PAY_METACHARGE_ERROR3);

// check that it is our payment
if ($vars['intInstID'] != $this_config['installation_id'])
    metacharge_error(_PLUG_PAY_METACHARGE_ERROR4);

// check amount
if (!$amount){
    metacharge_error(_PLUG_PAY_METACHARGE_ERROR5);
}

if (!$payment_id && $pnrel){ // find initial transaction
    $payment_id = $db->get_payment_by_data('receipt_id', $pnrel);
}
$pm = $db->get_payment($payment_id);

//check if payment is already processed
$payments = & $db->get_user_payments(intval($pm['member_id']));
foreach ($payments as $p){
    if ($p['receipt_id'] == $pnref){
       metacharge_error(sprintf(_PLUG_PAY_METACHARGE_ERROR6, $pnref, $p['payment_id']));
    }
}

if ($pm){
    if ($pm['completed']){ // this is completed, we should add new one
        $product = get_product($pm['product_id']);
        $beg_date = metacharge_get_begin_date($pm['member_id'], $pm['product_id']);
        list($y,$m,$d) = split('-', $beg_date);
        $beg_date1 = date('Y-m-d', mktime(0,0,0,$m, $d, $y) + 3600 * 24);
	$oldpid = $payment_id;
        $payment_id = $db->add_waiting_payment(
            $pm['member_id'], 
            $pm['product_id'], 
            $pm['paysys_id'], 
            $amount, 
            $beg_date1, 
            $product->get_expire($beg_date1),
            array('ORIG_ID' => $payment_id)
        );
	$pm = $db->get_payment($payment_id);
    } 
}

if ($pm && $scheduleID){
    $pm['data']['ScheduleID'] = $scheduleID;
    $db->update_payment($payment_id, $pm);
}

// process payment
$err = $db->finish_waiting_payment($payment_id, 'metacharge', 
        $pnref, $amount, $vars);

if ($err) 
    metacharge_error("finish_waiting_payment error: $err");

$_GET['payment_id'] = $_POST['payment_id'] = 
    $_GET['payment_id'] = $_POST['payment_id'] = $payment_id;

//include '../../../thanks.php';
?>
