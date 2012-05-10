<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: braintree payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2078 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_braintree extends payment {
	var $log = array();
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('braintree', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('braintree', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['braintree']['title'] ? $config['payment']['braintree']['title'] : 'Braintree',
            'description' => $config['payment']['braintree']['description'] ? $config['payment']['braintree']['description'] : 'Credit card payment',
            'phone' => 2,
            'company' => 1,
            'code' => 1,
            'name_f' => 2
        );
    }

    function run_transaction($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url("https://secure.braintreepaymentgateway.com/api/transact.php", $vars1);
        parse_str($ret,$res);
		return $res;
    }
    
    
    function cc_bill($cc_info, $member, $amount,
        $currency, $product_description,
        $charge_type, $invoice, $payment){
        global $config,$db;
        $log = array();
        //////////////////////// cc_bill /////////////////////////
        if(!$product_description){
			$product = $db->get_product($payment[product_id]);
			$product_description = $product[title];
		}

        if (CC_CHARGE_TYPE_RECURRING != $charge_type) {
            $result=$this->save_cc_info($cc_info, $member,$product_description);
            if (is_array($result))
                return $result;
			$log=$this->log;
			$member=$db->get_user($member['member_id']);
        }
		else
		{
			if ( !$member['data']['braintree_customer_vault_id'])
                return array(CC_RESULT_DECLINE_PERM, 'braintree_customer_vault_id is not defined', "", $log);
		}

        if (0==$amount) return array(CC_RESULT_SUCCESS, "", 'braintree', array());
		
		$vars = array(
			'type' => 'sale',
			'customer_vault_id' => $member['data']['braintree_customer_vault_id'],
			'amount' => sprintf("%.2f",$amount),
			'orderid' => $invoice,
			'key_id' => $this->config['key_id'],
			'hash' => md5("$invoice|".sprintf("%.2f",$amount)."|".$member['data']['braintree_customer_vault_id']."|".gmdate("YmdHis")."|".$this->config['key']),
			'time' => gmdate("YmdHis")
			);
		$log[] = $vars;
		$res=$this->run_transaction($vars);
 		$log[] = $res;
		//print_r($log);
		if ($res['response'] == '1'){
            return array(CC_RESULT_SUCCESS, "", $res['orderid'], $log);
        } elseif ($res['response'] == '2') {
            return array(CC_RESULT_DECLINE_PERM, $res['responsetext'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['responsetext'], "", $log);
        }

    }
    
    function save_cc_info($cc_info, & $member,$description='') {
        global $db;
        ////validate user profile, update if incorrect, create if no exists
        $member['data']['cc'] = '**** **** **** '.substr($cc_info['cc_number'], -4);
        if (isset($cc_info['cc_expire_Month'])) {
            $member['data']['cc-expire'] = sprintf('%02d%02d', $cc_info['cc_expire_Month'], substr($cc_info['cc_expire_Year'], 2, 2));
        } else {
            $member['data']['cc-expire'] = $cc_info['cc-expire'];
        }
		$log=array();
		if ($member['data']['braintree_customer_vault_id']) {
			$vars=array(
				'ipaddress' => $_SERVER['REMOTE_ADDR'],
				'firstname' => $cc_info['cc_name_f'],
				'lastname' => $cc_info['cc_name_l'],
				'address1' => $cc_info['cc_street'],
				'city' => $cc_info['cc_city'],
				'state' => $cc_info['cc_state'],
				'zip' => $cc_info['cc_zip'],
				'phone' => $cc_info['cc_phone'],
				'email' => $member['email'],
				'orderdescription' => $description,
				'website' => $_SERVER['HTTP_HOST'],
				
				'customer_vault' => 'update_customer',
				'customer_vault_id' => $member['data']['braintree_customer_vault_id'],
				'orderid' => '',
				'amount' => '',
				'ccnumber' => $cc_info['cc_number'],
				'ccexp' => $member['data']['cc-expire'],
				'payment' => 'creditcard',
				'key_id' => $this->config['key_id'],
				'hash' => md5("||".$member['data']['braintree_customer_vault_id']."|".gmdate("YmdHis")."|".$this->config['key']),
				'time' => gmdate("YmdHis")
				);
		}
		else
		{
			$vars=array(
				'ipaddress' => $_SERVER['REMOTE_ADDR'],
				'firstname' => $cc_info['cc_name_f'],
				'lastname' => $cc_info['cc_name_l'],
				'address1' => $cc_info['cc_street'],
				'city' => $cc_info['cc_city'],
				'state' => $cc_info['cc_state'],
				'zip' => $cc_info['cc_zip'],
				'phone' => $cc_info['cc_phone'],
				'email' => $member['email'],
				'orderdescription' => $description,
				'website' => $_SERVER['HTTP_HOST'],
				
				'customer_vault' => 'add_customer',
				'orderid' => '',
				'amount' => '',
				'ccnumber' => $cc_info['cc_number'],
				'ccexp' => $member['data']['cc-expire'],
				'payment' => 'creditcard',
				'key_id' => $this->config['key_id'],
				'hash' => md5("||".gmdate("YmdHis")."|".$this->config['key']),
				'time' => gmdate("YmdHis")
				);
		}
		$vars_l=$vars;
		$vars_l['ccnumber']='****';
		$log[]=$vars_l;
		$res=$this->run_transaction($vars);
		$log[]=$res;
        if ($res['response'] == '1'){
			$member['data']['braintree_customer_vault_id']=$res['customer_vault_id'];
            $db->update_user($member['member_id'], $member);
			global $log_p;
			$this->log=$log;
        } elseif ($res['response'] == '2') {
            return array(CC_RESULT_DECLINE_PERM, $res['responsetext'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['responsetext'], "", $log);
        }        
    }

    function payment_braintree($config) {
        parent::payment($config);        
        add_member_field('braintree_customer_vault_id', 'Braintree Customer Vault Id','', '');
    }

}

function braintree_get_member_links($user){
    return cc_core_get_member_links('braintree', $user);
}

function braintree_rebill(){
    return cc_core_rebill('braintree');
}
                                        
cc_core_init('braintree');