<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: ogone Payment Plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 5431 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/


class payment_ogone extends amember_payment {
    var $title = _PLUG_PAY_OGONE_TITLE;
    var $description = _PLUG_PAY_OGONE_DESC;
    var $fixed_price=0;
    var $recurring=0;
    var $built_in_trials=0;
    function get_plugin_features()
    {
        $title = $config['payment']['ogone']['title'] ? $config['payment']['ogone']['title'] : _PLUG_PAY_OGONE_TITLE;
        $description = $config['payment']['ogone']['description'] ? $config['payment']['ogone']['description'] : _PLUG_PAY_OGONE_DESC;
    }
    ///
    function do_bill($amount, $title, $products, $u, $invoice){
        global $config;
        $tosha = $this->config['hashing_method'] ? 
        'ACCEPTURL='.$config['root_url'].'/thanks.php?payment_id='.$invoice.$this->config['sha_id'].
        'AMOUNT='.($amount*100).$this->config['sha_id'].
        'CANCELURL='.$config['root_url'].'/cancel.php'.$this->config['sha_id'].
        'CN='.$u['name_f'].' '.$u['name_l'].$this->config['sha_id'].
        'CURRENCY=EUR'.$this->config['sha_id'].
        'DECLINEURL='.$config['root_url'].'/cancel.php'.$this->config['sha_id'].
        'EMAIL='.$u['email'].$this->config['sha_id'].
        'EXCEPTIONURL='.$config['root_url'].'/cancel.php'.$this->config['sha_id'].
        'LANGUAGE=en_US'.$this->config['sha_id'].
        'OPERATION=SAL'.$this->config['sha_id'].
        'ORDERID='.$invoice.$this->config['sha_id'].
        'PSPID='.$this->config['company_id'].$this->config['sha_id'].
        'TITLE='.$title.$this->config['sha_id']
        : 
        
        $invoice.($amount*100)."EUR".$this->config['company_id'].'SAL'.$this->config['sha_id'] ;
        
        $vars = array(  
            'PSPID'  => $this->config['company_id'],
            'amount'      => str_replace('.', '', sprintf('%.2f', $amount)),
            'currency'    => 'EUR',
            'Language'    => 'en_US',
            'TITLE'   => $title,
            'ACCEPTURL' => $config['root_url'] . '/thanks.php?payment_id='.$invoice,
            'declineurl' => $config['root_url'] . '/cancel.php',
            'exceptionurl' => $config['root_url'] . '/cancel.php',
            'cancelurl' => $config['root_url'] . '/cancel.php',
            
            'orderID'    => $invoice,
            'CN' => $u['name_f'] . ' ' . $u['name_l'],
            'owneraddress'   => $u['street'],
            'ownercity'      => $u['city'],
            'ownerZIP'       => $u['zip'],
            'EMAIL'       => $u['email'],
            'operation'       => "SAL",
            'SHASign'       => sha1($tosha),
        );
	if($this->config['hashing_method']) $utf='_UTF8';
        if($this->config['testing'])
		$url="https://secure.ogone.com/ncol/test/orderstandard$utf.asp";
	else
		$url="https://secure.ogone.com/ncol/prod/orderstandard$utf.asp";
        return $this->encode_and_redirect($url, $vars);
    }

    function process_postback($vars){
        global $db;

        $invoice      = $vars['PAYID'];
        $amount       = $vars['amount'];
        $payment_id   = intval($vars['orderID']);
        $status       = $vars['STATUS'];
        $post_type    = $vars['post_type'];
        
        if (!in_array(substr($status,0,1), array(5,9))){
            $this->postback_error(sprintf(_PLUG_PAY_OGONE_ERROR, $status));
            return false;
        }
        if (!$amount){
            $this->postback_error(_PLUG_PAY_OGONE_ERROR2);
            return false;
        }

        // process payment
        $err = $db->finish_waiting_payment(
            $payment_id, $this->get_plugin_name(), 
            $invoice, $amount, $vars);

        if ($err) 
            $this->postback_error("finish_waiting_payment error: $err");
    
    }
}

$pl = & instantiate_plugin('payment', 'ogone');

?>
