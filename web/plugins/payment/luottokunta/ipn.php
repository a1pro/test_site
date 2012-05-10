<?php

include "../../../config.inc.php";

$this_config = $plugin_config['payment']['luottokunta'];

//----------------------------------------------------
// read post from Luottokunta system and add 'cmd'
$vars = $_POST ? $_POST : $_GET;
//$req = 'cmd=_notify-validate';
foreach ($vars as $k => $v) 
   {
      if (get_magic_quotes_gpc())
         $vars[$k] = $v = stripslashes($v);
      $req .= "&" . urlencode($k) . "=" . urlencode ($v);
   }

// assign posted variables to local variables
$LKPRC = $vars['LKPRC'];
$LKSRC = $vars['LKSRC'];
$LKMAC = $vars['LKMAC'];

$pid   = $vars['pid'];
$amount= intval($vars['amount']);
$amount/=100;


//----------------------------------------------------

function get_dump($vars)
{
//dump of array
    $s = "";
    foreach ((array)$vars as $k=>$v)
        $s .= "$k => $v<br>\n";
    return $s;
}

function luotto_error($msg)
{
    global $txn_id, $pid;
    global $vars;
    fatal_error("Luottokunta ERROR: $msg (Details: txn_id:'$txn_id', invoice:'$pid')<br>\n".get_dump($vars));
}

$db->log_error("Luottokunta DEBUG<br>\n".get_dump($vars));

if (isset($LKPRC) || isset($LKSRC))
{
   switch ($LKPRC)
   {
      case 3:
         $msg='A required parameter was not found.';
         break;
      case 8:
         $msg='A duplicate object exists. As indicated by the SRC, a payment with this payment number already exists.';
         break;
      case 24:
         $msg='The parameter has a null value.';
         break;
      case 34:
         $msg='The operation failed for financial reasons (the card Issuer declined an authorisation)';
         break;
      case '-4':
         $msg='A required parameter was too short. The parameter is indicated by the SRC.';
         break;
   }
   luotto_error($msg);
}
//Secret_Key_Amount_Order_Number_Merchant_Number
$mac = md5($this_config['secret'].$vars['amount'].$pid.$this_config['merchant_id']);
$mac = strtoupper($mac);
if ($mac != $LKMAC)
   luotto_error('CHECKSUM is incorrect!');
// process payment
$err = $db->finish_waiting_payment($pid, 'luottokunta',
        '', $amount, $vars);
if ($err)
    luotto_error("finish_waiting_payment error: $err");

include '../../../thanks.php';

?>