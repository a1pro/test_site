<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3601 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*/






class payment_icepay extends amember_payment {
    var $title       = "Icepay";
    var $description = "Credit card payment";
    var $fixed_price = 0;
    var $recurring   = 0;

    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config;
        global $db;
        $product = & get_product($product_id);
		$member = $db->get_user($member_id);

		if(!$product->config['icepay_currency'])
			$product->config['icepay_currency']="GBP";
        $vars = array(
            'merchant' => $this->config['merchkey'],
            'amount' => intval($price*100),
            'currency' => $product->config['icepay_currency'],
			'ic_country' => $member['country'],
            'orderid'        => $payment_id,
            'reference'       => "AMEMBER".$payment_id,
			'description' => $product->config['title'],
			'visa_checksum' => sha1($this->config['secret']."|".$this->config['merchkey']."|".intval($price*100)."|".$product->config['icepay_currency']."|".$payment_id."|CREDITCARD|VISA"),
			'master_checksum' => sha1($this->config['secret']."|".$this->config['merchkey']."|".intval($price*100)."|".$product->config['icepay_currency']."|".$payment_id."|CREDITCARD|MASTER"),
			'amex_checksum' => sha1($this->config['secret']."|".$this->config['merchkey']."|".intval($price*100)."|".$product->config['icepay_currency']."|".$payment_id."|CREDITCARD|AMEX"),
            'urlcompleted' => $config['root_url']."/plugins/payment/icepay/thanks.php",
        );
		$t = &new_smarty();
		$t->assign('vars', $vars);
		$t->display(dirname(__FILE__) . '/icepay.html');
    }
    
    function log_debug($vars){
        global $db;
        $s = "ICEPAY DEBUG:<br />\n";
        foreach ($vars as $k=>$v)
            $s .= "[$k] => '$v'<br />\n";
        $db->log_error($s);
    }
    function validate_thanks(&$vars){
		$vars['Currency']='GBP';
		$vars['Duration']='0';
		$vars['ConsumerIPAddres']= '';
		$sha1 = sha1(
			$this->config['secret']."|".
			$this->config['merchkey']."|".
			$vars['Status']."|".
			$vars['StatusCode']."|".
			$vars['OrderID']."|".
			$vars['PaymentID']."|".
			$vars['Reference']."|".
			$vars['TransactionID']
	/*."|".
			$vars['Amount']."|".
			$vars['Currency']."|".
			$vars['Duration']."|".
			$vars['ConsumerIPAddres']*/
			);
		if ($vars['Checksum'] != $sha1)
			return "Validation Error $sha1";
	}

    function process_thanks(&$vars){
		global $db;
        $this->log_debug($vars);
		if($vars['Status']!="OK")
		{
			return $vars['StatusCode'];
		}
		else
		{			
			$error = $db->finish_waiting_payment($vars['OrderID'],'icepay',$vars['TransactionID'],'', $vars);
			if ($error) 
			{
				if($error) $db->log_error("ICEPAY finish_waiting_payment error - $error");
				return "ICEPAY finish_waiting_payment error - $error";				
			}
		}
    }
    function init(){
        parent::init();
        add_product_field('icepay_currency',
            'Icepay Currency',
            'select',
            'valid only for Icepay processing.<br /> You should not change it<br /> if you use
            another payment processors',
            '',
            array('options' => array(
                'GBP' => 'GBP',
                'USD' => 'USD',
                'EUR' => 'EUR'
            ))
            );
		
    }
}

$pl = & instantiate_plugin('payment', 'icepay');
?>
