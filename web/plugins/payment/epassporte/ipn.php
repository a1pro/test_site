<?php 

//      @version $Id: ipn.php 1785 2006-06-22 10:46:40Z avp $

include "../../../config.inc.php";


debug_http_vars("Epassporte IPN");
$this_config = $plugin_config['payment']['epassporte'];
$t = & new_smarty();
/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();


function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}


function epassporte_error($msg){
    global $order_id, $invoice;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_EPASSPORT_ERROR4, $msg, $invoice, '<br />')."\n".get_dump($vars));
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

// check IP
if ($this_config['ip'] && !(validate_src($_SERVER['REMOTE_ADDR'],$this_config['ip']))){
  epassporte_error(sprintf(_PLUG_PAY_EPASSPORT_ERROR7, $_SERVER['REMOTE_ADDR']).$this_config['ip']);
    
}

if (($this_config['IPN_pass'] != '') && ($this_config['IPN_pass'] != $_GET['cred'])){
    epassporte_error(sprintf(_PLUG_PAY_EPASSPORT_ERROR8, $_GET['cred']));
 }

$result      = $vars['ans']['0']; // Y or N
$invoice     = intval($vars['user1']);
$amount      = $vars['total_amount'];
settype($amount,      'double');
if ($result != 'Y'){
  debug(sprintf(_PLUG_PAY_EPASSPORT_ERROR5, $invoice, '<br />')."\n".get_dump($vars));
}

//if (!$amount){
//    epassporte_error("returned amount empty or = 0");
//}

$p = $db->get_payment(intval($invoice));


// check merchant id
if ($this_config['account'] != $vars['acct_num']){
    epassporte_error(_PLUG_PAY_EPASSPORT_ERROR6);
}
// Create the transaction log
$tlog = $vars['debit_trans_idx'] . " // " . $vars['credit_trans_idx'];

// process payment
$err = $db->finish_waiting_payment($invoice, 'epassporte', 
                                   $tlog, $amount, $vars);

//
// Get ready to do the reply to accept or reject the 
// transaction on the ePassport side
//
$url = "https://www.epassporte.com";
$page = "/secure/eppurchaseverify.cgi";

//
// We need to initialise the following POST variables: credit_trans_idx,debit_trans_idx,total_amount
// Plus msg which describe will be sent to the customer and action (verify or reject)
//

$postfields = "credit_trans_idx=" .  urlencode($vars['credit_trans_idx']);
$postfields .= "&debit_trans_idx=" . urlencode($vars['debit_trans_idx']);
$postfields .= "&total_amount=" . $vars['total_amount'];

if ($err) {
  $postfields .= "&msg=" . urlencode(_PLUG_PAY_EPASSPORT_ERROR9. $err);
  $postfields .= "&action=" .  urlencode("reject");
 }else{
  $postfields .= "&msg=" . urlencode(_PLUG_PAY_EPASSPORT_ERROR10);
  $postfields .= "&action=" . urlencode("verify");
 }

$ch = curl_init();

// Follow any Location headers
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

curl_setopt($ch, CURLOPT_URL, $url . $page);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Alert cURL to the fact that we're doing a POST, and pass the associative array for POSTing.
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
//
// Turn off SSL check
//
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

$output = curl_exec($ch);
curl_close($ch);
if ($err) {
  epassporte_error("finish_waiting_payment error: $err");
 }
if ($output != "status=YMYOK")
  epassporte_error(_PLUG_PAY_EPASSPORT_ERROR11.$output);
else
  debug(sprintf(_PLUG_PAY_EPASSPORT_ERROR12, $invoice));
print "OK";
?>
