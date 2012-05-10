<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: paycom Link Single Payment Plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1907 $)
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
$this_config = $plugin_config['payment']['paycom'];
$t = & new_smarty();
/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();

$co_code     = $vars['co_code'];
$amount      = "";
$invoice     = intval($vars['payment_id']);
$result      = $vars['ans']['0']; // Y or N

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}


function paycom_error($msg){
    global $order_id, $invoice, $pnref;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_PAYCOM_ERROR9, $msg), 1);
}

function paycom_user_error($msg){
    global $t;
    $t->assign(error, array(_PLUG_PAY_PAYCOM_ERROR 
        . $msg . _PLUG_PAY_PAYCOM_ERROR2));
    $t->display('fatal_error.html');
    exit();
}

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

$p = $db->get_payment(intval($vars['x_payment_id']));
$pr = $db->get_product($p['product_id']);

$t = new_smarty();
$t->assign('payment', $p);
$t->assign('product', $pr);
$t->assign('member',  $db->get_user($p['member_id']));

if ($result != 'Y'){   
    $t->display("cancel.html");
    exit();
}

if (($this_config['testing'] == '') && ($vars['ans'] == 'YGOODTEST|null'))
    paycom_error(_PLUG_PAY_PAYCOM_ERROR10);

if ($pr['paycom_id'] != $vars['product_id'])
    paycom_error(_PLUG_PAY_PAYCOM_ERROR6);


// check merchant id
if ($this_config['co_code'] != $co_code){
    paycom_error(_PLUG_PAY_PAYCOM_ERROR8);
}

$t->display("thanks.html");
?>
