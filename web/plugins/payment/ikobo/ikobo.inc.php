<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: ikobo.COM Payment Plugin
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

class payment_ikobo extends amember_payment {
    var $title = _PLUG_PAY_IKOBO_TITLE;
    var $description = _PLUG_PAY_IKOBO_DESC;
    var $fixed_price=0;
    var $recurring=0;
    var $built_in_trials=0;

    function get_plugin_features()
    {
        $title = $config['payment']['ikobo']['title'] ? $config['payment']['ikobo']['title'] : _PLUG_PAY_IKOBO_TITLE;
        $description = $config['payment']['ikobo']['description'] ? $config['payment']['ikobo']['description'] : _PLUG_PAY_IKOBO_DESC;
    }
    ///
    function do_bill($amount, $title, $products, $u, $invoice){
        global $config;
        $vars = array(  
            'item_id' => $products[0]['ikobo_product_id'],
            'poid' => $this->config['account_id'],
            'custom' => $invoice
        );

        return $this->encode_and_redirect("https://www.ikobo.com/merchant/purchase.php", $vars);
    }

    function resend_postback($url, $vars){
        $s = array();
        foreach ($vars as $k => $v)
            $s[] = urlencode($k) . '=' . urlencode($v);
        get_url($url, join('&', $s));
    }

    function process_postback($vars){
        global $db;

        $invoice      = $vars['confirmation'];
        $amount       = sprintf('%.2f', $vars['total'] / 100);
        $payment_id   = intval($vars['custom']);
        
        if ($this->config['postback_pass'] != $vars['pwd'])
            $this->postback_error(_PLUG_PAY_IKOBO_ERROR);

        if ($this->config['resend_postback'])
            foreach (preg_split('/\s+/', $this->config['resend_postback']) as $url){
                $url = trim($url);
                if ($url == '') continue;                
                $this->resend_postback($url, $vars);
            }

        // process payment
        $err = $db->finish_waiting_payment(
            $payment_id, $this->get_plugin_name(), 
            $invoice, $amount, $vars);

        if ($err) 
            $this->postback_error("finish_waiting_payment error: $err");
    
    }
    function init(){
        parent::init();
        add_product_field('ikobo_product_id', 'iKobo Product ID *',
            'text', 'have a look to iKobo plugin for description',
            'validate_ikobo_id');
    }
}

$pl = & instantiate_plugin('payment', 'ikobo');

function validate_ikobo_id(&$p, $field){  
    if (intval($p->config[$field]) <= 0) {
        return "iKobo Product ID is a necessary field! Please read iKobo plugin readme for details";
    }
    return '';
}

?>
