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
            'paysys_id' => 'egspay',
            'title'     => $config['payment']['egspay']['title'] ? $config['payment']['egspay']['title'] : _PLUG_PAY_EGSPAY_TITLE,
            'description' => $config['payment']['egspay']['description'] ? $config['payment']['egspay']['description'] : sprintf(_PLUG_PAY_EGSPAY_DESC,'<a href="http://www.egspay.com" target=_blank>','</a>'),
            'public'    => 1,
            'fixed_price' => 1
        )
);

add_product_field(
            'egspay_id', 'EgsPay Product ID',
            'text', 'you must create the same product<br />
             in egspay and enter its number here'
);



// need to configure products in clickbank and set thanks page to ./thanks.php
class payment_egspay extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $product = & get_product($product_id);
        $member  = $db->get_user($member_id);

        $c_product_id = $product->config['egspay_id'];

        if (!$c_product_id)
            fatal_error("EgsPay Product ID empty for Product# $product_id");

        $vars = array(
            'siteid'    => $this->config['site_id'],
            'productid' => $c_product_id,
            'fname'     => $member['name_f'],
            'lname'     => $member['name_l'],
            'email'     => $member['email'],
            'address'   => $member['street'],
            'city'      => $member['city'],
            'state'     => $member['state'],
            'zip'       => $member['zip'],
            'country'   => $member['country'],
            'email'     => $member['email'],
            'username'  => $member['login'],
            'login'     => $member['login'],
            'password'  => $member['pass'],
            'var1'      => $payment_id,
            'var2'      => $member_id,
            'var3'      => $product_id
        );
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://secure.egspay.com/apply/apply.acp?$vars");
        exit();
    }
}

?>
