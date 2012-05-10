<?php 

include "../../../config.inc.php";


$t = & new_smarty();

/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
$vercode = $vars['vercode'];
$res = split(":", $vercode);

$action     = $vars['trn'];
$amount     = $vars['amount'];
$payment_id = intval($vars['custom1']);
$pnref 	    = $vars['trn_id'];
$secret = $res[2];


function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function verotel_error($msg){
    global $order_id, $payment_id, $pnref;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_VEROTEL_FERROR, $msg, $payment_id, '<br />')."\n".get_dump($vars));
}

$db->log_error("VEROTEL DEBUG: " . get_dump($vars));

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

// Check secret code
global $config;
$this_config = $config['payment']['verotel'];

/*
if($this_config[secret] != $secret){
    verotel_error("Incorrect secret code!");
}
*/

// add ip protection

if ( !preg_match('/^195\.20\.32\.202/', $_SERVER['REMOTE_ADDR'])){                                                                                                     
    verotel_error("Incorrect IP!");                                                                                                                                   
}                                                                                                                                                                     
                                                                                                                                                                      
if ($this_config['testing']){                                                                                                                                         
    print "APPROVED";                                                                                                                                                 
    exit;                                                                                                                                                             
}   

function verotel_get_last_payment($invoice)
{
        global $db;
        $orig_p = $db->get_payment($invoice);
        $p_id   = $orig_p['payment_id'];
        foreach ($db->get_user_payments($orig_p['member_id'], 1) as $p){
            if (($p['product_id'] == $orig_p['product_id'])
                && ($p['data'][0]['RENEWAL_ORIG'] == "RENEWAL ORIG: $invoice")
                && ($p['expire_date'] > $orig_p['expire_date'])) {
                $p_id = $p['payment_id'];
                }
        }
		if($p_id!=$invoice)
			return verotel_get_last_payment($p_id);
		else
		{
        	return $db->get_payment($p_id);
		}
}

switch ($action){
case 'add':
    $err = $db->finish_waiting_payment($payment_id, 'verotel', 
        $pnref, $amount, $vars);
    if ($err) 
        verotel_error("finish_waiting_payment error: $err");
    // set expire date to infinite
    $p = $db->get_payment($payment_id);    
    $product = $db->get_product($p['product_id']);
    if ($product['is_recurring'])
        $p['expire_date'] = '2012-12-31';
    $db->update_payment($payment_id, $p);
    break;
case 'rebill':
	$last_payment = verotel_get_last_payment($payment_id);
    $p = $db->get_payment($payment_id);
	// set expire date to infinite
    $newp = array();
    $newp['member_id']   = $p['member_id'];
    $newp['product_id']  = $p['product_id'];
    $newp['paysys_id']   = 'verotel';
    $newp['receipt_id']  = $vars['trn_id'];
    $newp['begin_date']  = date('Y-m-d');
    $newp['expire_date'] = RECURRING_SQL_DATE;
    $newp['amount']      = $vars['amount'];    
    $newp['completed']   = 1;
    $newp['data']['RENEWAL_ORIG'] = "RENEWAL ORIG: $last_payment[payment_id]";
    $new_payment_id = $db->add_waiting_payment(
				  $newp['member_id'],
				  $newp['product_id'],
				  $newp['paysys_id'],
				  $newp['amount'],
				  $newp['begin_date'],
				  $newp['expire_date'],
				  $newp['data']
				  );
	$error = $db->finish_waiting_payment($new_payment_id, 'verotel', $vars['trn_id'], '', $vars);
	if ($error)
        verotel_error("finish_waiting_payment error: $err");
    $last_payment['expire_date'] = date('Y-m-d');
    $db->update_payment($last_payment['payment_id'], $last_payment);
    break;
case 'modify':
    $p = $db->get_payment($payment_id);    
    $p['amount'] += $amount;
    $p['data'][] = $vars;
    $db->update_payment($payment_id, $p);
    break;
case 'delete':
    $p = $db->get_payment($payment_id);    
    $p['data'][] = $vars;
    $yesterday = date('Y-m-d', time()-3600*24);
    $product = $db->get_product($p['product_id']);
    if ($product['is_recurring'])
        $p['expire_date'] = $yesterday;
    else {
        $p['completed'] = 0;
//        $p['amount'] -= $amount;
    }        
    $db->update_payment($payment_id, $p);
    break;
    default: print "ERROR"; exit;
}

print "APPROVED";
