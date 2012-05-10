<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_adultprocessor extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
	if($this->config['method'])
        return cc_core_do_payment('adultprocessor', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
	else
		return $this->adultprocessor_regular_hosted('adultprocessor', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
	function get_plugin_features(){
        return array(
            'title' => $config['payment']['adultprocessor']['title'] ? $config['payment']['adultprocessor']['title'] : "AdultProcessor",
            'description' => $config['payment']['adultprocessor']['description'] ? $config['payment']['adultprocessor']['description'] : "Pay by credit card/debit card - Visa/Mastercard",
            'phone' => 1,
            'code' => 1,
            'name_f' => 2
        );
    }
	
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){	
        if ( CC_CHARGE_TYPE_RECURRING==$charge_type )
		{
              return $this->adultprocessor_recurring($cc_info, $member, $amount,
        $currency, $product_description,
        $charge_type, $invoice, $payment);
        }
		else
		{
              return $this->adultprocessor_regular_post($cc_info, $member, $amount,
        $currency, $product_description,
        $charge_type, $invoice, $payment);
        }
	}
	function xml_parse_($xml) {
		$pattern1='/<([a-zA-Z]+)>([-_a-zA-Z0-9\.,@\s\|]*)<\/\1>/U';
		$pattern= '/<([a-zA-Z]+)>([-_a-zA-Z0-9\.,@\s<>\/\|]*)<\/\1>/U';
	
		if ( preg_match($pattern1, $xml) ) {
			preg_match_all($pattern, $xml, $match);
			$ar = $this->xml_clone_calculate_($match[1]);
			foreach ($match[2] as $k=>$v) {
				if ($ar[$match[1][$k]]['amount']>1) {
					$ar[$match[1][$k]]['counter']++;
					$result[$match[1][$k]][$ar[$match[1][$k]]['counter']] = $this->xml_parse_($v);
				} else {
					$result[$match[1][$k]] = $this->xml_parse_($v);
				}
			}
		} else {
			$result=$xml;
		}
		return $result;
	}
	function xml_clone_calculate_($ar) {
		$result=array();
		foreach ($ar as $k=>$v) {
			if (!isset($result[$v])) {
				$result[$v]['amount']=0;
				$result[$v]['counter']=-1;
			}
			$result[$v]['amount']++;
		}
		return $result;
	}
	function adultprocessor_recurring($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){		
		$log = array();		
		$pid = $payment['data'][0]['RENEWAL_ORIG'];
		$x = preg_split('/ /', $payment_id);
		$old_payment_id = $x[1];
		$old_payment = $db->get_payment($old_payment_id);
        $vars = array(		
		'merchantID' => $this->config['merchantid'],
		'password' => $this->config['merchant_password'],
		'report' => "1",
		
		'start_date' => date('Y-m-d', strtotime($old_payment['expire_date'])),
		'end_date' => date('Y-m-d', strtotime($old_payment['expire_date']) + 24*3600)
			);
		list($tid,$sid)=@split("/|/",$old_payment['receipt_id']);
        $log[] = $vars;
        $res = $this->run_report($vars);
        $log[] = $res;
		$trs=$this->xml_parse_($res);
		$key = array_search($sid, $trs['subscriptionID']);
		if(!($key===false))
			return array(CC_RESULT_SUCCESS, "", $trs['transactionID'][$key]."|".$sid, $log);
		else
			return array(CC_RESULT_DECLINE_PERM, "Subscription id $sid not found", "", $log);
		
	}
	
	function adultprocessor_regular_post($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config, $db;
        $log = array();
		$product=$db->get_product($payment['product_id']);
        $vars = array(
		'first_name' => $cc_info['cc_name_f'],
		'last_name' => $cc_info['cc_name_l'],
		'address1' => $cc_info['cc_street'],
		'address2' => $member['street'],
		'city' => $cc_info['cc_city'],
		'state' => $cc_info['cc_state'],
		'zip' => $cc_info['cc_zip'],
		'country' => $cc_info['cc_country'],
		'phone' => $cc_info['cc_phone'],
		'email' => $member['email'],
		'card_number' => $cc_info['cc_number'],
		'card_exp_month' => $cc_info['cc_expire_Month'],
		'card_exp_year' => $cc_info['cc_expire_Year'],
		'card_cvv' => $cc_info['cc_code'],
		'username' => $member['login'],
		'password' => $member['pass'],
		'ip' => $_SERVER['REMOTE_ADDR'],
		
		'merchantID' => $this->config['merchantid'],
		'merchant_password' => $this->config['merchant_password'],
		'remote_serverID' => $this->config['serverid'],
		
		'SiteID' => $product['adultprocessor_siteid'],
		'ScheduleID' => $product['adultprocessor_scheduleid'],
		'productID' => $product['adultprocessor_siteid']."-".$product['adultprocessor_scheduleid'],

			);
        // prepare log record
        $vars_l = $vars; 
        $vars_l['card_number'] = preg_replace('/./', '*', $vars['card_number']);
		$vars_l['card_cvv'] = preg_replace('/./', '*', $vars['card_cvv']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['success'] == '1'){
            return array(CC_RESULT_SUCCESS, "", $res['transactionID']."|".$res['subscriptionID'], $log);
        } elseif ($res['error'] == '1') {
            return array(CC_RESULT_DECLINE_PERM, $res['message'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['message'], "", $log);
        }
    }
	function adultprocessor_regular_hosted($plugin, $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars){
        global $config, $db;
        $log = array();
		$payment=$db->get_payment($payment_id);
		$product=$db->get_product($payment['product_id']);
		$member = $db->get_user($member_id);
        $vars = array(
		'first_name' => $member['name_f'],
		'last_name' => $member['name_l'],
		'username' => $member['login'],
		'password' => $member['pass'],
		'email' => $member['email'],
		'country' => $member['country'],
		'zip' => $member['zip'],
		
		'siteID' => $product['adultprocessor_siteid'],
		'scheduleID' => $product['adultprocessor_scheduleid'],
		'page_setID' => "1",
		'primary_productID' => $product['adultprocessor_siteid']."-".$product['adultprocessor_scheduleid'],
		'ref1' => $payment_id,
		'ref2' => md5($this->config['merchantid'].$this->config['merchant_password'].$payment_id)
			);
		
		$vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
		html_redirect($this->config["url"]."cgi/signup.cgi?$vars",
        0, _PLUG_PAY_CC_CORE_REDIR, 
        _PLUG_PAY_CC_CORE_REDIRDESC);
        exit();

    }
     function run_transaction($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url($this->config["url"]."cgi/service/remote_sale.cgi", $vars1);
        parse_str($ret,$res);
        return $res;
    }
    function run_report($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url($this->config["url"]."cgi/service/report.cgi", $vars1);
        return $ret;
    }
	function payment_adultprocessor($config) {

        parent::payment($config);
        add_product_field('adultprocessor_siteid', 'Adultprocessor Site ID','text', '','');
		add_product_field('adultprocessor_scheduleid', 'Adultprocessor Schedule ID','text', '','');
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('secpay', $payment_id);
    }

}
function adultprocessor_get_member_links($user){
    //return cc_core_get_member_links('adultprocessor', $user);
}

function adultprocessor_rebill(){
    return cc_core_rebill('adultprocessor');
}
cc_core_init('adultprocessor');

?>
