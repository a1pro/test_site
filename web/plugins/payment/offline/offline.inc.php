<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: The installation file
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1781 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/
global $config;


add_paysystem_to_list(
array(
            'paysys_id'   => 'offline',
            'title'       => $config['payment']['offline']['title'] ? $config['payment']['offline']['title'] : _PLUG_PAY_OFFLINE_TITLE,
            'description' => $config['payment']['offline']['description'] ? $config['payment']['offline']['description'] : _PLUG_PAY_OFFLINE_DESC,
            'recurring'   => 0,
            'public'      => 1
        )
);

class payment_offline extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $payment = $db->get_payment($payment_id);
        $products = array();


        if (is_array($product_ids=$payment['data'][0]['BASKET_PRODUCTS'])) {
            foreach ($product_ids as $pid){
                $pr = $db->get_product($pid);
                $pr['price'] = $payment['data'][0]['BASKET_PRICES'][$pid];
                $products[] = $pr;
            }
        } else 
            $products = array( $db->get_product($product_id) );

        $t = & new_smarty();
        $t->template_dir = dirname(__FILE__);
        $t->assign('product', $products[0]);
        $t->assign('products', $products);
        $t->assign('payment', $payment);
        $t->assign('member', $db->get_user($member_id));

        $t->assign('price', $price);
        $t->assign('begin_date', $begin_date);
        $t->assign('expire_date', $expire_date);
        $t->assign('vars', $vars);
        $t->assign('config', $config);

        $t->display("offline.html");
        exit();
    }
}

?>
