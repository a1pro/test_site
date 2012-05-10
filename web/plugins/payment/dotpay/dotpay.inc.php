<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: DotPay payment plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 2604 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

class payment_dotpay extends amember_payment {
    var $title = _PLUG_PAY_ALLPAY_TITLE;
    var $description = _PLUG_PAY_ALLPAY_DESC;
    var $fixed_price=0;
    var $recurring=0;
//    var $supports_trial1=0;
    var $built_in_trials=0;
        
    function do_bill($amount, $title, $products, $u, $invoice){
        global $config, $db;
        $product = $products[0];
        
        $vars = array(
            'id'                => $this->config['seller_id'],
            'amount'            => $amount,
            'currency'          => $product['dotpay_currency'] ? $product['dotpay_currency'] : 'PLN',
            'description'       => $title,
            'lang'              => $this->config['lang'],
            'control'           => $invoice,
            
            'URL'               => $config['root_url']."/plugins/payment/dotpay/thanks.php",
            'type'              => '0', //after a payment process the buyer will see the button which redirects the session
                                        //to the internet address which is defined in URL parameter.
            
            'URLC'              => $config['root_url']."/plugins/payment/dotpay/ipn.php",
            
            'firstname'         => $u['name_f'],
            'lastname'          => $u['name_l'],
            'email'             => $u['email'],
            'street'            => $u['street'],
            'state'             => $u['state'],
            'city'              => $u['city'],
            'postcode'          => $u['zip'],
            'country'           => $u['country']
        );

        $count_recurring = 0;
        foreach ($products as $p)
            if ($p['is_recurring']) $count_recurring++;
        if ($count_recurring > 1) fatal_error(_PLUG_PAY_PAYPALR_FERROR8);     
            
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
	//$db->log_error("DotPay DEBUG: https://ssl.dotpay.eu?$vars");
        header("Location: https://ssl.dotpay.eu?$vars");
        exit();
    }
    
    function init(){
        parent::init();
        add_product_field(
            'dotpay_currency', 'DotPay Currency',
            'select', 'currency for DotPay gateway',
            '',
            array('options' => array(
                'PLN'  => 'PLN',
                'EUR'  => 'EUR',
                'USD'  => 'USD',
                'GBP'  => 'GBP',
                'JPY'  => 'JPY'
            ))
        );
    }
}

instantiate_plugin('payment', 'dotpay');
?>
