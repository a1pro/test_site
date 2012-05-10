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
*    Release: 3.1.8PRO ($Revision: 3601 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*/






class payment_cashu extends amember_payment {
    var $title       = "CashU";
    var $description = "Credit card payment";
    var $fixed_price = 0;
    var $recurring   = 0;

    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config;
        global $db;
		$orig_product_id = $product_id;
        if (is_array($product_id))
             $product_id = $product_id[0];
        $product = & get_product($product_id);
        if (count($orig_product_id)>1)
            $product->config['title'] = $config['multi_title'];


        $vars = array(
            'merchant_id' => $this->config['merchant_id'],
            'amount' => sprintf("%.2f",$price),
            'currency' => $this->config['currency'],
            'language' => 'en',
            'display_text' => $product->config['title'],
			'token' => md5(strtolower($this->config['merchant_id'].":".sprintf("%.2f",$price).":".$this->config['currency'].":").$this->config['secret']),
            'txt1'       => $product->config['title'],
            'txt2'        => $payment_id,
		'test_mode' => $this->config['testing']

        );
		$t = &new_smarty();
		$t->assign('vars', $vars);
		$t->display(dirname(__FILE__) . '/cashu.html');
		exit;
    }
    
    function log_debug($vars){
        global $db;
        $s = "CASHU DEBUG:<br />\n";
        foreach ($vars as $k=>$v)
            $s .= "[$k] => '$v'<br />\n";
        $db->log_error($s);
    }
	
	function validate_thanks(&$vars){
		global $db;
		$this->log_debug($vars);
		if(md5(strtolower($this->config['merchant_id'].":".$vars['amount'].":".$vars['currency'].":").$this->config['secret'])!=$vars['token'])
		{
			$db->log_error("CashU token validation error.");
			return "CashU token validation error.";
		}
		if(sha1(strtolower($this->config['merchant_id'].":".$vars['trn_id'].":").$this->config['secret'])!=$vars['verificationString'])
		{
			$db->log_error("CashU verificationString validation error.");
			return "CashU verificationString validation error.";
		}
		return '';
	}

    function process_thanks(&$vars){
        global $db;

        //$db->log_debug($vars);

        $payment_id = intval($vars['txt2']);

		if ($payment_id) {
            $err = $db->finish_waiting_payment($payment_id, 'cashu', $vars['trn_id'], $vars['amount'], $vars);
            if ($err)
                return "finish_waiting_payment error: ".$err;
        }
		else{
			$db->log_error("CashU DEBUG: Payment not found");
			return "Error. Payment not found";
		}
        $GLOBALS['vars']['payment_id'] = $payment_id;
    }

    function init(){
        parent::init();
    }
}

$pl = & instantiate_plugin('payment', 'cashu');
