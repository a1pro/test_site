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
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/
add_paysystem_to_list(
array(
            'paysys_id' => 'paycom',

            'title'     => $config['payment']['paycom']['title'] ? $config['payment']['paycom']['title'] : _PLUG_PAY_PAYCOM_TITLE,
            'description' => $config['payment']['paycom']['description'] ? $config['payment']['paycom']['description'] : _PLUG_PAY_PAYCOM_DESC,
            'public'    => 1,
            'fixed_price' => 1
        )
);
add_product_field(
            'paycom_id', 'PayCom Product/Subscription ID',
            'text', 'you must create the same product<br />
             in paycom and enter its ID here'
);


// need to configure products in clickbank and set thanks page to ./thanks.php
class payment_paycom extends payment {
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
        $t->display(dirname(__FILE__).'/paycom.html');
        exit();
    }
}

?>
