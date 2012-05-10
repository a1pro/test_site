<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: glo-bill payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1781 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


$plugins['payment'][] = 'globill_phone';
$plugins['payment'][] = 'globill_check';
add_paysystem_to_list(
array(
            'paysys_id' => _PLUG_PAY_GLOBILL_ID,
            'title'     => _PLUG_PAY_GLOBILL_TITLE,
            'description' => sprintf(_PLUG_PAY_GLOBILL_DESC, '<a href="http://www.globill.com" target=_blank>', '</a>'),
            'public'    => 1,
            'recurring' => 1
        )
);

add_paysystem_to_list(
array(
            'paysys_id' => _PLUG_PAY_GLOBILL_PH_ID,
            'title'     => _PLUG_PAY_GLOBILL_PH_TITLE,
            'description' => sprintf(_PLUG_PAY_GLOBILL_PH_DESC, '<a href="http://www.globill.com" target=_blank>', '</a>'),
            'public'    => 1,
            'recurring' => 1
        )
);

add_paysystem_to_list(
array(
            'paysys_id' => _PLUG_PAY_GLOBILL_CH_ID,
            'title'     => _PLUG_PAY_GLOBILL_CH_TITLE,
            'description' => sprintf(_PLUG_PAY_GLOBILL_CH_DESC, '<a href="http://www.globill.com" target=_blank>', '</a>'),
            'public'    => 1,
            'recurring' => 1
        )
);

add_product_field(
            'globill_cc_id', 'GloBill CC ID',
            'text', 'you must create the same product<br />
             in GloBill for CC billing. Enter pricegroup here'
);

add_product_field(
            'globill_phone_id', 'GloBill Phone ID',
            'text', 'you must create the same product<br />
             in GloBill for Phone billing. Enter pricegroup here'
);

add_product_field(
            'globill_check_id', 'GloBill Check ID',
            'text', 'you must create the same product<br />
             in GloBill for Online Check payment. Enter pricegroup here'
);

// need to configure products in clickbank and set thanks page to ./thanks.php
class payment_globill extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db, $plugin_config;
        $product = & get_product($product_id);
        $payment = $db->get_payment($payment_id);
        $member = $db->get_user($member_id);
        $this_config = $plugin_config['payment']['globill'];
        $vars = array(
            'id' => $this_config['site_id'],
            'username' => $member['login'],
            'password' => $member['pass'],
            'email' => $member['email'],
            'firstname' => $member['name_f'],
            'lastname' => $member['name_l'],
            'address' => $member['street'],
            'city' => $member['city'],
            'state' => $member['state'],
            'zip' => $member['zip'],
            'country' => $member['country'],
            'user1'  => $payment_id,
            'user2'  => $member_id,
            'user3'  => $product_id
        );

        if ($payment['paysys_id'] == 'globill'){
            $pg = $product->config['globill_cc_id'];
            if (!$pg) return _PLUG_PAY_GLOBILL_CANNOTORDER;
        } elseif ($payment['paysys_id'] == 'globill_phone'){
            $vars['phonesign'] = 1;
            $pg = $product->config['globill_phone_id'];
            if (!$pg) return _PLUG_PAY_GLOBILL_CANNOTORDER2;
        } elseif ($payment['paysys_id'] == 'globill_check'){
            $vars['check'] = 1;
            $pg = $product->config['globill_check_id'];
            if (!$pg) return _PLUG_PAY_GLOBILL_CANNOTORDER3;
        } else {
            return sprintf(_PLUG_PAY_GLOBILL_ERROR, $payment[paysys_id]);
        }

        $vars['pricegroup'] = $pg;
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        if ($vars['phonesign'])
        header("Location: http://www.signup.globill-systems.com/cgi-bin/user/signup.cgi?$vars");
        else
        header("Location: https://www.globill-signup.com/cgi-bin/user/newsignup.cgi?$vars");
        $payment['paysys_id'] = 'globill';
        $db->update_payment($payment_id, $payment);
        exit();
    }
}

class payment_globill_phone extends payment_globill { 

}

class payment_globill_check extends payment_globill { 

}

?>
