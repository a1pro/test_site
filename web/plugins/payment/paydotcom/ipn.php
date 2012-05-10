<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: The installation file
*    FileName $RCSfile: ipn.php,v $
*    Release: 2.4.0PRO ($Revision: 1.1.2.1 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember is free for both commercial and non-commercial use providing that the
* copyright headers remain intact and the links remain on the html pages.
* Re-distribution of this script without prior consent is strictly prohibited.
*
*/

include "../../../config.inc.php";

$this_config = $plugin_config['payment']['paydotcom'];

function get_dump($var)
{
//dump of array
    $s = "";
    foreach ((array)$var as $k=>$v)
        $s .= "$k => $v<br>\n";
    return $s;
}

function paydotcom_error($msg)
{
    global $txn_id, $invoice;
    global $vars;
    fatal_error("paydotcom ERROR: $msg (Details: txn_id:'$txn_id', invoice:'$payment_id')<br>\n".get_dump($vars));
}


// assign posted variables to local variables
// note: additional IPN variables also available -- see IPN documentation
$vars = get_input_vars();

$amount            = $vars['amount'];
$payment_gross     = doubleval($vars['mc_gross']);
$payment_id        = $_GET['paymentid'];
$payment_currency  = $vars['mc_currency'];
$txn_id            = $vars['txn_id'];
$status            = $vars['payment_status'];
$secret            = $vars['pdc_secret'];

$db->log_error("paydotcom DEBUG<br>\n".get_dump($vars));

if ($secret != $this_config['secret'])
    paydotcom_error("False secret code: '$secret'");


switch ($status)
{
   case 'Completed':
   {
      $err = $db->finish_waiting_payment($payment_id, 'paydotcom',
          $pnref, $amount, $vars);
      if ($err)
          paydotcom_error("finish_waiting_payment error: $err");
      break;
   }
   case 'Refunded':
   {
      $p=$db->get_payment($invoice);
      $p['completed']=0;
      $db->update_payment($invoice, $p);
      break;
   }
   default:
      paydotcom_error("Payment is not processed, status: '$status'");
}

$t = &new_smarty();
$payment = $db->get_payment($product_id);
$product = $db->get_product($payment['product_id']);
$member  = $db->get_user($payment['member_id']);
$t->assign('payment', $payment);
$t->assign('product', $product);
$t->assign('user', $member);
$t->display("thanks.html");

?>