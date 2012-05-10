<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Instant payment notification for nochex
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5071 $)
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


$this_config = $plugin_config['payment']['nochex'];

function get_dump($var){
//dump of array
    $s = "";
    foreach ((array)$var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function nochex_error($msg){
    global $txn_id, $payment_id;
    global $vars;
#    header("Status: 500 Internal Server Error");
    fatal_error(sprintf(_PLUG_PAY_NOCHEX_FERROR, $msg, $txn_id, $payment_id, '<br />')."\n".get_dump($vars));
}

//////////////////////////////////////////////////////////////////////////////
//                                                                          //
//            P A Y M E N T    P R O C E S S I N G                          //
//                                                                          //
//////////////////////////////////////////////////////////////////////////////

// read post from nochex system and add 'cmd'
$vars = get_input_vars();
$req = '';
foreach ($vars as $k => $v) {
    if (get_magic_quotes_gpc()) 
        $vars[$k] = $v = stripslashes($v);
    $req .= urlencode($k) . "=" . urlencode ($v) . "&";
}
$db->log_error("REQUEST: $req");

// assign posted variables to local variables
// note: additional IPN variables also available -- see IPN documentation
$receiver_email = $vars['to_email'];
$payment_id     = $vars['order_id'];
$payment_gross  = $vars['amount'];
$txn_id         = $vars['transaction_id'];

if (!$receiver_email)
    nochex_error(_PLUG_PAY_NOCHEX_ERROR);


$db->log_error("nochex DEBUG<br />\n".get_dump($vars));

/////////////////////////////////////////////
$res = get_url($u="https://www.nochex.com/nochex.dll/apc/apc", $req);

$db->log_error("NOCHEX RESPONSE: $res");

if (preg_match('/AUTHORI(Z|S)ED/', $res)) {
    // check that receiver_email is an email address 
    // in your nochex account
    if ($receiver_email != $this_config['business']) 
        nochex_error(
        sprintf(_PLUG_PAY_NOCHEX_ERROR2, $receiver_email).
        $this_config['business']);
    //all ok
    $err = $db->finish_waiting_payment($payment_id,
         'nochex', $txn_id, $payment_gross, $vars);
    if ($err) 
        nochex_error("finish_waiting payment error: $err");
} else if (preg_match('/DECLINED/', $res) != false) {
    // log for manual investigation
    nochex_error(sprintf(_PLUG_PAY_NOCHEX_ERROR3, $res));
} else {
    nochex_error(sprintf(_PLUG_PAY_NOCHEX_ERROR4, $res));
}

?>
