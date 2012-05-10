<?php                                                        
/*
*  User's thanks page. Displayed after sucessull payment.
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Thanks page
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1907 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/
$rd = dirname(__FILE__);
include($rd.'/config.inc.php');


###############################################################################
##
##                             M  A  I  N 
##
###############################################################################
$t = & new_smarty();
$error = '';
$vars = & get_input_vars();

if (!strlen($paysys_id))
    $paysys_id = $vars['paysys_id'];

if ($paysys_id){ //should be passed from url or plugins/payment/../thanks.php
    //process plugin work if payment system pass info to thanks page
    if (($error = plugin_validate_thanks($paysys_id, $vars))
         || ($error = plugin_process_thanks($paysys_id, $vars))
       ){
        $t->assign('error', $error);
        $t->display('thanks_error.html');
        exit();
    }
}

if (!$vars['member_id'])  $vars['member_id']  = $_GET['member_id'];
if (!$vars['product_id']) $vars['product_id'] = $_GET['product_id'];
if (!$vars['payment_id']) $vars['payment_id'] = $_GET['payment_id'];

if ($vars['member_id']){
    $t->assign('member', $db->get_user($vars['member_id']));
}
if ($vars['product_id']){
    $t->assign('product', $db->get_product($vars['product_id']));
}

if (!$vars['payment_id'])
    $vars['payment_id'] = $_SESSION['_amember_payment_id'];
    
if ($vars['payment_id']){
    $pm = $db->get_payment($vars['payment_id']);
    /// iterate until we receive paypal postback
    $count = 0;
    while (($pm['paysys_id'] == 'paypal_r') && ($pm['receipt_id'] == '')){
        sleep(1);
        $pm = $db->get_payment($vars['payment_id']);
        if (++$count > 15) break;
    }

    $t->assign('payment', $pm);
    if ($pm) {
        $t->assign('product', $db->get_product($pm['product_id']));
        $t->assign('member', $db->get_user($pm['member_id']));
    }
    if (!($prices = $pm['data'][0]['BASKET_PRICES'])){
        $prices = array($pm['product_id'] => $pm['amount']);
    }
    $pr = array();
    $subtotal = 0;
    foreach ($prices as $product_id => $price){
        $v  = $db->get_product($product_id);
//        $v['price'] = $price;
        $subtotal += $v['price'];
        $pr[$product_id] = $v;
    }
    $t->assign('subtotal', $subtotal);
    $t->assign('total', array_sum($prices));
    $t->assign('products', $pr);
}
$t->display("thanks.html");

?>
