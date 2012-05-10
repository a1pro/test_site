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
            'paysys_id' => 'directone_remote',
            'title'     => $config['payment']['directone_remote']['title'] ? $config['payment']['directone_remote']['title'] : _PLUG_PAY_DIRECTONEREM_TITLE,
            'description' => $config['payment']['directone_remote']['description'] ? $config['payment']['directone_remote']['description'] : sprintf(_PLUG_PAY_DIRECTONEREM_DESC,'<a href="http://www.directone_remote.com" target="_blank">','</a>'),
            'public'    => 1
        )
);

// need to configure products in directone_remote and set thanks page to ./thanks.php
class payment_directone_remote extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $product = & $db->get_product($product_id);

        $fields = "payment_reference=&payment_number=&payment_amount=&bank_reference=&vendor_name=&";

        $vars = array(
            'vendor_name'       => $this->config['account_name'],
             $product['title']  => sprintf('1,%.2f', $price),
            'payment_reference' => $payment_id,
            'return_link_text' => _PLUG_PAY_DIRECTONEREM_RETLNK,
            'return_link_url' => $config['root_url'] .'/thanks.php?payment_id='.$payment_id,
            'reply_link_url' => $config['root_url'] .'/plugins/payment/directone_remote/mpn.php?'.$fields,
        );
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        if ($this->config['testing'])
            header("Location: https://vault.safepay.com.au/cgi-bin/test_payment.pl?$vars");
        else
            header("Location: https://vault.safepay.com.au/cgi-bin/make_payment.pl?$vars");

        exit();
    }
}

?>
