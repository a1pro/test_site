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


add_paysystem_to_list(
array(
            'paysys_id' => 'egold',
            'title'     => $config['payment']['egold']['title'] ? $config['payment']['egold']['title'] : _PLUG_PAY_EGOLD_TITLE,
            'description' => $config['payment']['egold']['description'] ? $config['payment']['egold']['description'] : sprintf(_PLUG_PAY_EGOLD_DESC,'<a href="http://www.e-gold.com" target=_blank>','</a>'),
            'public'    => 1
        )
);

class payment_egold extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config;
        $product = & get_product($product_id);
        
        $vars = array(
            'PAYEE_ACCOUNT'  => $this->config['merchant_id'],
            'PAYEE_NAME'     => $this->config['merchant_name'],
            'PAYMENT_AMOUNT' => $price,
            'PAYMENT_UNITS'  => $this->config['units'],
            'PAYMENT_METAL_ID' => 0,
            'PAYMENT_ID'     => $payment_id,
            'STATUS_URL'     => 
                  $config['root_url'] . '/plugins/payment/egold/ipn.php',
            'PAYMENT_URL'    => 
               sprintf("%s/thanks.php?payment_id=%d",
                $config['root_url'],
                $payment_id),
            'PAYMENT_URL_METHOD' => 'LINK',
            'NOPAYMENT_URL'      => $config['root_url'].'/signup.php',
            'BAGGAGE_FIELDS'     => '',
            'SUGGESTED_MEMO'     => 
            $product->config['title'] . ' ' . 
            $product->config['description']
        );
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://www.e-gold.com/sci_asp/payments.asp?$vars");
        exit();
    }
}

?>
