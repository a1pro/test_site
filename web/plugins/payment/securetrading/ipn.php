<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: The installation file
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1785 $)
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


$this_config = $plugin_config['payment']['securetrading'];

function get_dump($var){
//dump of array
    $s = "";
    foreach ((array)$var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}



function securetrading_error($msg){
    global $txn_id, $invoice;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_SECURTRD_FERROR, $msg, $txn_id, $invoice, '<br />')."\n".get_dump($vars));
}


// assign posted variables to local variables
// note: additional IPN variables also available -- see IPN documentation
$vars = get_input_vars();
$invoice        = $vars['orderref'];
$payment_gross  = doubleval($vars['amount']);
$payment_currency  = $vars['currency'];
$txn_id         = $vars['streference'];

$db->log_error("securetrading DEBUG<br />\n".get_dump($vars));

// process payment
$err = $db->finish_waiting_payment($invoice, 'securetrading', 
        $txn_id, $payment_gross, $vars);
if ($err) 
    securetrading_error("finish_waiting_payment error: $err");

$t = &new_smarty();
$payment = $db->get_payment($invoice);
$product = $db->get_product($payment['product_id']);
$member  = $db->get_user($payment['member_id']);
$t->assign('payment', $payment);
$t->assign('product', $product);
$t->assign('user', $member);
$t->display("thanks.html");

?>
