<?php 

//      @version $Id: epass-return.php 1907 2006-08-17 11:08:14Z avp $


include "../../../config.inc.php";


$this_config = $plugin_config['payment']['epassporte'];
$t = & new_smarty();
/////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();

$invoice     = intval($vars['']);
$result      = $vars['ans']['0']; // Y or N

function get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}


function epassporte_error($msg){
    global $order_id, $invoice, $pnref;
    global $vars;
    fatal_error(_PLUG_PAY_EPASSPORT_ERROR.$msg, 1);
}

function epassporte_user_error($msg){
    global $t;
    $t->assign(error, array(_PLUG_PAY_EPASSPORT_ERROR2."<b>" 
        . $msg . "</b>"._PLUG_PAY_EPASSPORT_ERROR3));    
    $t->display('fatal_error.html');
    exit();
}

//////////////////////////////////////////////////////////////////////////////
//
//                           M   A   I   N
//
//////////////////////////////////////////////////////////////////////////////

$p = $db->get_payment(intval($vars['user1']));
$pr = $db->get_product($p['product_id']);

$t = new_smarty();
$t->assign('payment', $p);
$t->assign('product', $pr);
$t->assign('member',  $db->get_user($p['member_id']));

if ($result != 'Y'){   
    $t->display("cancel.html");
    exit();
}


$t->display("thanks.html");
?>
