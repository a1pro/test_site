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


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_epayeu extends payment {
	function reparse($str)
	{
		$f = split("[&?]",$str);
		foreach($f as $v)
		{
			$s = split("[=]",$v,2);
			$r[$s[0]] = $s[1];
		}
		return $r;
	}	

	function get_plugin_features(){
		return array(
			'title' => $config['payment']['epayeu']['title'] ? $config['payment']['epayeu']['title'] : "Epay.eu",
			'description' => $config['payment']['epayeu']['description'] ? $config['payment']['epayeu']['description'] : "Credit card payment",
			);
	}
	
	function run_transaction($vars, $method='authorize'){
		$client = new soapclient_epayeu('https://ssl.ditonlinebetalingssystem.dk/remote/subscription.asmx?WSDL', 'wsdl', '', '', '', '');
		$err = $client->getError();
		if ($err) {
			return array('message' => sprintf('Constructor error: %s', $err));
		}
		$result = $client->call($method, array('parameters' => $vars));
		// Check for a fault
		if ($client->fault) {
			if ($err) {
				return array('message' => 'SOAP call fault: ' . join(' - ', array_values($result)) );
			}
		} else {
			// Check for errors
			$err = $client->getError();
			if ($err) {
				return array('message' => sprintf('SOAP error: %s', $err) );
			} else {				
				return $result;
			}
		}
	}
	

	function get_first_payment($payment)
	{
		global $db;		
		$original_pid = $payment['payment_id'];		
		if ($payment_id = $payment['data'][0]['RENEWAL_ORIG'])
		{
			do {
				$pid = $payment['payment_id'];
				$x = preg_split('/ /', $payment['data'][0]['RENEWAL_ORIG']);
				$payment_id = $x[@count($x)-1];
				$payment = $db->get_payment($payment_id);
			} while ($payment_id <> $payment['data'][0]['RENEWAL_ORIG']);
		} else {
			$pid = $payment['payment_id'];
		}
		$p = $db->get_payment($pid);
		return $p['data']['epayeu_subscription_id'];
	}


	//recurring payment
	function cc_bill($cc_info, $member, $amount,
		$currency, $product_description,
		$charge_type, $invoice, $payment)
	{
		global $config,$db;
		$log = array();
		require_once($config['root_dir']."/plugins/payment/epayeu/soap.php");
					
		$new_payment = $db->get_payment($invoice);
		$x = preg_split('/ /', $new_payment['data'][0]['RENEWAL_ORIG']);
		$old_payment = $db->get_payment($x[1]);
		$subscriptionid = $old_payment['data']['epayeu_subscription_id'] ? 
			$old_payment['data']['epayeu_subscription_id'] : 
			$this->get_first_payment($new_payment);

		$vars = array(
			"merchantnumber"    => $this->config['merchantnumber'],
			"subscriptionid"  => $subscriptionid,
			"orderid" => $invoice,
			"amount" => intval($amount*100),
			"currency" => $this->config['currency'],
			"instantcapture" => 1
			);
		$log[] = $vars;
		$res = $this->run_transaction($vars);
		$log[] = $res;
		
		if ($res['authorizeResult'] == 'true'){
			if ($charge_type == CC_CHARGE_TYPE_TEST)
				$this->void_transaction($res['transactionid'], $log);
			return array(CC_RESULT_SUCCESS, "", $res['transactionid'], $log);
		} else {
			$err_vars = array(
				"merchantnumber"    => $this->config['merchantnumber'],
				"epayresponsecode" => $res['epayresponse']);
			$err_res = $this->run_transaction($err_vars,'getEpayError');
			return array(CC_RESULT_INTERNAL_ERROR, $err_res['epayResponseString'], "", $log);
		}
	}

	//first payment with redirect
	function do_payment($payment_id, $member_id, $product_id,
		$price, $begin_date, $expire_date, &$vars){
	        global $config,$db;
	        $product = & get_product($product_id);
	        
	        $vars = array(
	        	'subscription' => $product->config['is_recurring'] ? '1' : '0',
	        	'merchantnumber' => $this->config['merchantnumber'],
	        	'amount' =>intval($price*100),
	        	'currency' => $this->config['currency'],
	        	'orderid'        => $payment_id,
	        	'md5key' => md5($this->config['currency'].intval($price*100).$payment_id.$this->config['secret']),
	        	'windowstate' => $this->config['windowstate'],
	        	);
		$t = &new_smarty();
		$t->assign('vars', $vars);
		$t->display(dirname(__FILE__) . '/epayeu.html');
		exit;
	}
	function log_debug($vars){
		global $db;
		$s = "EPAYEU DEBUG:<br />\n";
		foreach ($vars as $k=>$v)
			$s .= "[$k] => '$v'<br />\n";
		$db->log_error($s);
	}
	
	function validate_thanks(&$vars){
		global $db;
		$this->log_debug($vars);
		if($vars['eKey']!=md5($vars['amount'].$vars['orderid'].$vars['tid'].$this->config['secret']))
			return "Unable to validate that you have paid, please contact the webmaster";
	}
	
	function process_thanks(&$vars){
		global $db;
		$err = $db->finish_waiting_payment($vars['orderid'],'epayeu',$vars['tid'],'', $vars);
		if ($err)
			return "finish_waiting_payment error: $err";
		$p = $db->get_payment($vars['orderid']);
		$p['data']['epayeu_subscription_id'] = $vars['subscriptionid'];
		$db->update_payment($p['payment_id'],$p);
		$vars['member_id']=$p['member_id'];
		$vars['payment_id']=$p['payment_id'];
		$vars['product_id']=$p['product_id'];
	}
	
	function process_postback(&$vars){
		global $db;
		$this->log_debug($vars);
		$err = $db->finish_waiting_payment($vars['orderid'],'epayeu',$vars['tid'],'', $vars);
		if ($err)
			return "finish_waiting_payment error: $err";
		$p = $db->get_payment($vars['orderid']);
		$p['data']['epayeu_subscription_id'] = $vars['subscriptionid'];
		$db->update_payment($p['payment_id'],$p);
		$vars['member_id']=$p['member_id'];
		$vars['payment_id']=$p['payment_id'];
		$vars['product_id']=$p['product_id'];
	}
	function init(){
		parent::init();
		add_payment_field('epayeu_subscription_id', 'Epay.eu subscription ID', 'hidden', '');
	}
}
function epayeu_get_member_links($user){
	return;
}

function epayeu_rebill(){
	return cc_core_rebill('epayeu');
}
cc_core_init('epayeu');
