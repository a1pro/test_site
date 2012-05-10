<!--success--><?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: multicards Link Single Payment Plugin
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


$this_config = $plugin_config['payment']['multicards'];
$t = & new_smarty();
/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();

$mer_id     = $vars['mer_id'];
$amount     = doubleval($vars['item1_price'] * $vars['item1_qty']);
$invoice = intval($vars['user1']);
$result     = intval($vars['user21']); //must be success

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}


function multicards_error($msg){
    global $order_id, $invoice, $pnref;
    global $vars;
    fatal_error(sprintf(_PLUG_PAY_MULTICARD_ERROR, $msg, $pnref, $invoice, '<br />')."\n".get_dump($vars));
}

function multicards_user_error($msg){
    global $t;
    $t->assign(error, array(_PLUG_PAY_MULTICARD_ERROR2."<b>" 
        . $msg . "</b> "._PLUG_PAY_MULTICARD_ERROR3));    
    $t->display('fatal_error.html');
    exit();
}

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

if ($result != 'success'){
    multicards_user_error(_PLUG_PAY_MULTICARD_ERROR4);
}
if (!$amount){
    multicards_error(_PLUG_PAY_MULTICARD_ERROR5);
}

// check IP
if ($this_config['ip'] && ($this_config['ip'] != $REMOTE_ADDR)){
    multicards_error(_PLUG_PAY_MULTICARD_ERROR6);
}

// check merchant id
if ($this_config['mer_id'] != $mer_id){
    multicards_error(_PLUG_PAY_MULTICARD_ERROR7);
}

// process payment
$err = $db->finish_waiting_payment($invoice, 'multicards', 
        $pnref, $amount, $vars);
if ($err) 
    multicards_error("finish_waiting_payment error: $err");

// show thanks page
$t->display('thanks.html');
?>
