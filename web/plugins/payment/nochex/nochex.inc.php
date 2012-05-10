<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
///$config['payment']['nochex']['testing']=1;
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: NoChex plugin
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
            'paysys_id'   => 'nochex',
            'title'       => $config['payment']['nochex']['title'] ? $config['payment']['nochex']['title'] : _PLUG_PAY_NOCHEX_TITLE,
            'description' => $config['payment']['nochex']['description'] ? $config['payment']['nochex']['description'] : _PLUG_PAY_NOCHEX_DESC,
            'recurring'   => 0,
            'public'      => 1
        )
);

class payment_nochex extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        global $db;
        global $config;
        if (is_array($vars['product_id'])){
            $p = $db->get_payment($payment_id);
            $price_count = 0;
            foreach ($p['data'][0]['BASKET_PRICES'] as $pp)
                if ($pp) $price_count++;
            if ($price_count>1) {
                return "Only 1 paid product can be selected";
            }
            $product_id = $vars['product_id'][0];
        } 
            
        $product = & get_product($product_id);

        $vars = array(
            'email' => $this->config['business'],
            'amount' => $price,
            'ordernumber' => $payment_id,
            'description' => $product->config['title'],
            'returnurl'   => 
               sprintf("%s/thanks.php?member_id=%d&product_id=%d&payment_id=%d",
                $config['root_url'],
                $member_id, 
                $product_id,
                $payment_id)
        );
        
        //encode and send
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        if ($this->config['testing'])
            html_redirect("https://demo.nochex.com/nochex.dll/checkout?$vars");
        else
            html_redirect("https://www.nochex.com/nochex.dll/checkout?$vars");
        exit();
    }
}

?>
