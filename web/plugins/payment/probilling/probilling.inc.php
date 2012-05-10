<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Probilling payment plugin
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


add_product_field(
            'is_recurring', 'Recurring Billing',
            'select', 'should user be charged automatically<br />
             when subscription expires',
            '',
            array('options' => array(
                '' => 'No',
                1  => 'Yes'
            ))
);

add_paysystem_to_list(
array(
            'paysys_id'   => 'probilling',
            'title'       => $config['payment']['probilling']['title'] ? $config['payment']['probilling']['title'] : _PLUG_PAY_PROBILLING_TITLE,
            'description' => $config['payment']['probilling']['description'] ? $config['payment']['probilling']['description'] : _PLUG_PAY_PROBILLING_DESC,
            'recurring'   => 1,
            'public'      => 1,
            'fixed_price' => 1
        )
);

add_product_field('pon', 
    'Probilling PON',
    'text',
    'probilling payment option number. You have to define'."\n<br />".
    'in probilling the same product and write their option number' . "\n<br />".
    'in this field'
    );


class payment_probilling extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $product = & get_product($product_id);
        $u  = $db->get_user($member_id);
        $vars = array(
            'pon' => $product->config['pon'],
            'success_redirect' => "$config[root_url]/thanks.php",
            'failure_redirect' => "$config[root_url]/cancel.php",
            'first_name'       => $u['name_f'],
            'last_name'        => $u['name_l'],
            'address'          => $u['street'],
            'city'             => $u['city'],
            'country_code'          => $u['country'],
            'state'            => ($u['state'] == 'XX') ? 'NA' : $u['state'],
            'zip'              => $u['zip'],
            'email'            => $u['email'],
            'username'         => $u['login'],
            'password'         => $u['pass'],
            'payment_id'       => $payment_id,
            'member_id'        => $member_id
        );
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://www.probilling.com/pos/posapi.cfm?$vars");
        exit();
    }
    function get_cancel_link($payment_id){
        return "https://www.probilling.com/consumer/cancelStep1.cfm";
    }
}

?>
