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
*    Release: 3.2.3PRO ($Revision: 3601 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*/

global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_quickpay extends payment {
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('quickpay', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['quickpay']['title'] ? $config['payment']['quickpay']['title'] : "Quickpay",
            'description' => $config['payment']['quickpay']['description'] ? $config['payment']['quickpay']['description'] : "Credit Card Payments",
            'code' => 1,
            'name_f' => 2
        );
    }
	function from_res($name,$str)
	{
		$pos=@strpos($str,"<$name>");
		if(!($pos===false))
		{
			$pos+=(strlen($name)+2);
			$res=@substr($str,$pos,@strpos($str,"</$name>",$pos)-$pos);
			return trim($res);
		}
		else
		return "";
	}

    function run_transaction($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url("https://secure.quickpay.dk/api", $vars1);
		$res = array(
			"msgtype",
			"ordernumber",
			"amount",
			"time",
			"state",
			"chstat",
			"qpstat",
			"qpstatmsg",
			"merchantemail",
			"merchant",
			"transaction",
			"cardtype",
			"md5check"			
        );
		foreach($res as $one)
			$result[$one]=$this->from_res($one,$ret);
        return $result;
    }
    
    function get_orig_payment_ref($payment){
        if (preg_match('/RENEWAL_ORIG:\s+(\d+)$/', $payment['data'][0]['RENEWAL_ORIG'], $regs)){
            $i = $regs[1];
            if ($i > 0) {
                global $db;
                $p = $db->get_payment($i);
                if ($p['payment_id']) {
                    return $p['receipt_id'];
                } else {
                }
            }
        } 
        return '';
    }


    // recurring payment
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        srand(time());
        if ($charge_type == CC_CHARGE_TYPE_TEST){
            $amount = "1.00";
		}
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }

        if(!$product_description){
			global $db;
			$product = $db->get_product($payment[product_id]);
			$product_description = $product[title];
		}
		$currency = $this->config['currency'];
		$ordernumber = $payment['payment_id'] . '-' . rand(100, 999);
		$orig_id = $this->get_orig_payment_ref($payment);
        $vars = array(
            "protocol"  => "3",
            "msgtype"    => "recurring",
            "merchant" => $this->config['merchant_id'],
            "ordernumber" => $ordernumber,
            "amount" => $amount*100,
            "currency" => $currency ? $currency : 'USD',
            "autocapture" =>   "1",
            "transaction" =>   $orig_id,
            "md5check" => md5("3recurring".$this->config['merchant_id'].$ordernumber.
				($amount*100).($currency ? $currency : 'USD')."1".$orig_id.$this->config['secret']) 
        );
        
        if ($this->config['testing'])
            $vars['testmode'] = '1';

        // prepare log record
        $vars_l = $vars; 
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['qpstat'] == '000'){
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res['transaction'], $log);
            return array(CC_RESULT_SUCCESS, "", $res['transaction'], $log);
        } elseif (intval($res['qpstat']) < 9) {
            return array(CC_RESULT_DECLINE_PERM, $res['qpstatmsg'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['qpstatmsg'], "", $log);
        }
    }
    
    //first payment
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config;
        global $db;
        $product = & get_product($product_id);
        
        $message='authorize';
        if($product->config['is_recurring']) $message='subscribe';
        
		$md5str = "3".
		$message.
		$this->config['merchant_id'].
		"en".
		"000".$payment_id.
		intval($price*100).
		$this->config['currency'].
		$config['root_url']."/thanks.php".
		$config['root_url']."/plugins/payment/quickpay/cancel.php".
		$config['root_url']."/plugins/payment/quickpay/ipn.php".
		"1".
		$product->config['title'].
		$this->config['testing'].
		$this->config['secret'];
		
        $vars = array(
            'message' => $message,
            'merchant' => $this->config['merchant_id'],
            'amount' =>intval($price*100),
            'currency' => $this->config['currency'],
            'ordernumber'        => $payment_id,
			'testmode' => $this->config['testing'],
			'description' => $product->config['title'],
            'md5check' => md5($md5str),
        );
		$t = &new_smarty();
		$t->assign('vars', $vars);
		$t->assign('config', $config);
		$t->display(dirname(__FILE__) . '/quickpay.html');
    }
    
    function log_debug($vars){
        global $db;
        $s = "QUICKPAY DEBUG:<br />\n";
        foreach ($vars as $k=>$v)
            $s .= "[$k] => '$v'<br />\n";
        $db->log_error($s);
    }
    function get_dump($var){
	//dump of array
		$s = "";
		foreach ((array)$var as $k=>$v)
			$s .= "$k => $v<br />\n";
		return $s;
	}

	
    function validate_ipn($vars){
		$md5str= $vars['msgtype'].
		$vars['ordernumber'].
		$vars['amount'].
		$vars['currency'].
		$vars['time'].
		$vars['state'].
		$vars['qpstat'].
		$vars['qpstatmsg'].
		$vars['chstat'].
		$vars['chstatmsg'].
		$vars['merchant'].
		$vars['merchantemail'].
		$vars['transaction'].
		$vars['cardtype'].
		$vars['cardnumber'].
		$this->config['secret'];
        $md5_hash =  md5($md5str);
        if ($md5_hash==$vars['md5check'])
            return 1;
    	else
    	    return 0;
    }
	function quickpay_error($msg){
		global $order_id, $payment_id, $pnref, $db;
		global $vars;
		$db->log_error("QUICKPAY ERROR:".$msg."\n".$this->get_dump($vars));
		die($msg);
	}
    
    function process_postback($vars){
        global $db, $config;
		$this->log_debug($vars);
		if ($vars['qpstat'] != '000')
			$this->quickpay_error("payment has not finished");
        if (!$this->validate_ipn($vars))
            $this->quickpay_error("IPN validation failed.");
		$err = $db->finish_waiting_payment(intval($vars['ordernumber']),'quickpay',$vars['transaction'],$vars['amount']/100, $vars);
		if ($err)
			$db->quickpay_error("finish_waiting_payment error: $err");
    }
}
function quickpay_get_member_links($user){
}

function quickpay_rebill(){
    return cc_core_rebill('quickpay');
}
                                        
cc_core_init('quickpay');
?>
