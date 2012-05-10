<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Centipaid Corporation
*      Email: admin@centipaid.com
*        Web: http://www.centipaid.com
*    Details: Centipaid CART API Payment Plugin
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
            'paysys_id' => 'centipaid',
            'title'     => $config['payment']['centipaid']['title'] ? $config['payment']['centipaid']['title'] : _PLUG_PAY_CENTIP_TITLE,
            'description' => $config['payment']['centipaid']['description'] ? $config['payment']['centipaid']['description'] : sprintf(_PLUG_PAY_CENTIP_DESC,'<a target=centipaid href="http://www.centipaid.com/faq/question.php?qstId=1">','</a>'),
            'public'    => 1
        )
);

class payment_centipaid extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config;
        $product = & get_product($product_id);

        $vars = array(  
            'x_Version'   => '1.4',
            'x_Login'     => $this->config['login'],
            'x_Test_Request' => $this->config['testing'] ? 'TRUE' : 'FALSE',
            'x_Show_Form' => 'PAYMENT_FORM',
            'x_Amount'    => $price = sprintf('%.2f', $price),
            'x_ADC_Relay_Response' => 'True',
            'x_Hide_Centipaid' => '1',
            'x_Receipt_Link_URL' => 
                $config['root_url'] . '/thanks.php',
            'x_ADC_URL'   => $config['root_url'] .  
                '/plugins/payment/centipaid/ipn.php',
            'x_invoice_num'   => $payment_id,
            'x_Cust_ID'       => $member_id,
            'x_Description'   => $product->config['title'],

            'x_fp_sequence' => $payment_id,
            'x_fp_timestamp' => $tstamp = time()
        );
        $data = $this->config['login']."^".$payment_id."^".$tstamp."^".$price."^";
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$kk=$vv";
        }
        $vars2 = join('&', $vars1);
        header("Location: https://pay.centipaid.com/cart.php?$vars2");
        exit();
    }
}


?>
