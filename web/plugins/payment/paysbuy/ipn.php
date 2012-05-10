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

$this_config = $plugin_config['payment']['paysbuy'];

$t = & new_smarty();

/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
$db->log_error("PaySbuy IPN: " . paysbuy_get_dump($vars));

$result = substr($vars['result'], 0, 2);

if ($result == '00'){
    $payment_id = intval(substr($vars['result'], 2));
    $pnref = $vars['apCode'];
    $amount = $vars['amt'];

    $ip = $_SERVER['REMOTE_ADDR'];
    $host = gethostbyaddr($ip);
    if (!(preg_match('/(.*)\.paysbuy\.com$/i', $host) || $ip == '61.90.197.234')){
        $db->log_error("PaySbuy ERROR: Wrong IP $ip ($host)");
        exit;
    }

    $err = $db->finish_waiting_payment($payment_id, 'paysbuy', $pnref, '', $vars);
    if ($err){
        $db->log_error("PaySbuy ERROR: " . $err);
    }
}

?>
