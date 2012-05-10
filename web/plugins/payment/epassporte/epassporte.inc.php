<?php


if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 

//  @version $Id: epassporte.inc.php 1781 2006-06-22 10:05:50Z avp $

add_paysystem_to_list(
array(
            'paysys_id' => 'epassporte',
            'title'     => $config['payment']['epassporte']['title'] ? $config['payment']['epassporte']['title'] : _PLUG_PAY_EPASSPORT_TITLE,
            'description' => $config['payment']['epassporte']['description'] ? $config['payment']['epassporte']['description'] : _PLUG_PAY_EPASSPORT_DESC,
            'public'    => 1
        )
);


// need to configure products in clickbank and set thanks page to ./thanks.php
class payment_epassporte extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $db, $config, $plugin_config;

        $t = & new_smarty();
        $t->assign(array(
            'this_config' => $this->config,
            'member'  => $db->get_user($member_id),
            'payment' => $pm = $db->get_payment($payment_id),
            'product' => $db->get_product($pm['product_id'])
        ));
        $t->display(dirname(__FILE__).'/epassporte.html');
        exit();
    }
}

?>
