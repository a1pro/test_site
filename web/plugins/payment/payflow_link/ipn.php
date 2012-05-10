<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: PayFlow Link Single Payment Plugin
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


$this_config = $plugin_config['payment']['payflow_link'];
$t = & new_smarty();

/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
$invoice    = intval($vars['INVOICE']);
$pnref      = $vars['PNREF'];
$amount     = doubleval($vars['AMOUNT']);
$result     = intval($vars['RESULT']);
$respmsg    = $vars['RESPMSG'];

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function payflow_error($msg){
    global $order_id, $invoice, $pnref;
    global $vars;
    header("HTTP/1.1 404 Not found");
    header("Status: 404 Not Found");
    fatal_error(sprintf(_PLUG_PAY_PAYFLINK_FERROR, $msg, $pnref, $invoice, '<br />')."\n".get_dump($vars));
}


//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

if ($result != 0){
    payflow_error($respmsg);
}
if ($respmsg == 'AVSDECLINED'){
    payflow_error($respmsg);
}
if ($respmsg == 'CSCDECLINED'){
    payflow_error($respmsg);
}
if (!$amount){
    payflow_error(_PLUG_PAY_PAYFLINK_ERROR);
}

// process payment
$err = $db->finish_waiting_payment($invoice, 'payflow_link', 
        $pnref, $amount, $vars);

if ($err) 
    payflow_error("finish_waiting_payment error: $err");



?>
