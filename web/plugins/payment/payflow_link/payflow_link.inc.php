<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: PayFlow Link Payment Plugin
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


add_paysystem_to_list(
array(
            'paysys_id' => 'payflow_link',
            'title'     => $config['payment']['payflow_link']['title'] ? $config['payment']['payflow_link']['title'] : _PLUG_PAY_PAYFLINK_TITLE,
            'description' => $config['payment']['payflow_link']['description'] ? $config['payment']['payflow_link']['description'] : sprintf(_PLUG_PAY_PAYFLINK_DESC, '<a href="http://www.verisign.com/products/payflow/link/" target=_blank>', '</a>'),
            'public'    => 1
        )
);

class payment_payflow_link extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $product = & get_product($product_id);
        $user  = $db->get_user($member_id);

        $vars = array(  
            'LOGIN'       => $this->config['login'],
            'PARTNER'     => $this->config['partner'],
            'AMOUNT'      => sprintf('%.2f', $price),
            'TYPE'        => 'S',
            'INVOICE'     => $payment_id,
            'DESCRIPTION' => $product->config['title'],
            'NAME'        => $user[name_f]." ".$user[name_l],
            'ADDRESS'     => $user[street],
            'CITY'        => $user[city],
            'STATE'       => $user[state],
            'COUNTRY'     => $user[country],
            'ZIP'         => $user[zip],
            'EMAIL'       => $user[email],
            'PHONE'       => $user[data][phone]

        );


        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://payments.verisign.com/payflowlink?$vars");
        exit();
    }
}

?>
