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
* aMember is free for both commercial and non-commercial use providing that the
* copyright headers remain intact and the links remain on the html pages.
* Re-distribution of this script without prior consent is strictly prohibited.
*
*/


add_paysystem_to_list(
array(
            'paysys_id' => 'securetrading',
            'title'     => $config['payment']['securetraiding']['title'] ? $config['payment']['securetraiding']['title'] : _PLUG_PAY_SECURTRD_TITLE,
            'description' => $config['payment']['securetraiding']['description'] ? $config['payment']['securetraiding']['description'] : _PLUG_PAY_SECURTRD_DESC,
            'public'    => 1
        )
);

add_product_field('securetrading_currency', 
    'securetrading Currency',
    'select',
    'valid only for securetrading processing.<br /> You should not change it<br /> if you use 
    another payment processors',
    '',
    array('options' => array(
        ''    => 'USD',
        'gbp' => 'GBP',
        'eur' => 'EUR',
        'cad' => 'CAD',
        'jpy' => 'JPY'
    ))
    );

class payment_securetrading extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $products = $product_id;
        $orig_product_id = $product_id;
        if (is_array($product_id))
             $product_id = $product_id[0];

        $product = & get_product($product_id);
        if (count($orig_product_id)>1)
            $product->config['title'] = $config['multi_title'];

        $member = $db->get_user($member_id);

        $vars = array(
            'merchant'    => $this->config['merchant'],
            'merchantemail'  => $config['admin_email'],
            'orderref'    => $payment_id,
            'orderinfo' => $product->config['title'],
            'amount'      => sprintf('%d', $price),
            'name' => $member['name_f'] . ' ' . $member['name_l'],
            'address'   => $member['street'],
            'postcode' => $member['zip'],
            'town'      => $member['city'],
            'county'     => $member['state'],
            'country'   => $member['country'],
            'email'   => $member['email'],
            'phone'     => $member['data']['phone'],
            'requiredfields' => 'orderref,name,email',
            'customeremail' => 1,
            'callbackurl'   => 1
        );

        // add currency code
        if (strlen($product->config['securetrading_currency'])){
            $vars['currency'] = $product->config['securetrading_currency'];
        } else {
            $vars['currency'] = 'USD';
        }

        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://securetrading.net/authorize/form.cgi?$vars");
        exit();
    }
}

?>
