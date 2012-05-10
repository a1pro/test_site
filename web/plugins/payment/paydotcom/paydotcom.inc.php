<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    FileName $RCSfile: paydotcom.inc.php,v $
*    Release: 2.4.0PRO ($Revision: 1.1.2.6.4.28 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
                                                                     *
*/

add_paysystem_to_list(
array(
            'paysys_id'   => 'paydotcom',
            'title'       => 'paydotcom',
            'description' => 'purchase from paydotcom',
            'recurring' => 1,
            'public'    => 1
        )
);

add_product_field(
            'paydotcom_id', 'paydotcom ID',
            'text', 'you must create the same product<br>
             in paydotcom and enter its number here',
             ''
);

class payment_paydotcom extends payment
{
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars)
    {

        global $config, $db;
        $product = & get_product($product_id);

        $u = & $db->get_user(intval($member_id));

        $vars = array(
           'id'           => $product->config['paydotcom_id'],
           'paymentid'    => $payment_id,
           'amount'       => $price
        );

        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $varsx = join('&', $vars1);
        header($s="Location: https://paydotcom.com/sell.php?".$varsx);
        exit();
    }
}

?>