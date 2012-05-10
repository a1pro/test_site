<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: micropayment_cc payment plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 2078 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

if(!defined('MCP__CREDITCARDSERVICE_INTERFACE')) 
	define('MCP__CREDITCARDSERVICE_INTERFACE', 'IMcpCreditcardService_v1_1');
if(!defined('MCP__CREDITCARDSERVICE_NVP_URL')) 
	define('MCP__CREDITCARDSERVICE_NVP_URL', 'https://webservices.micropayment.de/public/creditcard/v1.1/nvp/');
require_once($config['root_dir']."/plugins/payment/micropayment_cc/lib/init.php");
require_once($config['root_dir']."/plugins/payment/micropayment_cc/services/" . MCP__CREDITCARDSERVICE_INTERFACE . '.php');
require_once( MCP__SERVICELIB_DISPATCHER . 'TNvpServiceDispatcher.php');

class payment_micropayment_cc extends payment {
	var $log = array();
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('micropayment_cc', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('micropayment_cc', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['micropayment_cc']['title'] ? $config['payment']['micropayment_cc']['title'] : 'Micropayment_cc',
            'description' => $config['payment']['micropayment_cc']['description'] ? $config['payment']['micropayment_cc']['description'] : 'Credit card payment',
            'phone' => 2,
            'company' => 1,
            'code' => 1,
            'name_f' => 2,
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
		$product = & $db->get_product($payment['product_id']);
        if(!$product_description){
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
			if ( !$member['data']['micropayment_cc_customer_vault_id'])
                return array(CC_RESULT_DECLINE_PERM, 'micropayment_cc_customer_vault_id is not defined', "", $log);
		}

        if (0==$amount) return array(CC_RESULT_SUCCESS, "", 'micropayment_cc', array());
		$dispatcher = new TNvpServiceDispatcher(MCP__CREDITCARDSERVICE_INTERFACE, MCP__CREDITCARDSERVICE_NVP_URL);
		try {
			$sessionId = null;
			$result = $dispatcher -> sessionCreate(
				$this->config['key'], 
				$this->config['testing'], 
				$member['data']['micropayment_cc_customer_vault_id'],
				$sessionId, 
				$product['micropayment_cc_project'],
				null,
				null,
				null,
				intval($amount*100),
				$currency,
				$product_description,
				$product_description,
				$member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'],
				null,
				false);	
			$sessionId = $result['sessionId'];
			$log[]=$result;

			$result = $dispatcher -> transactionPurchase(
				$this->config['key'], 
				$this->config['testing'], 
				$sessionId, 
				$cc_info['cc_code']);
			$log[]=$result;
		}
		catch(Exception $e)
		{
			return array(CC_RESULT_INTERNAL_ERROR, $e->getCode()." ".$e -> getMessage(), "", $log);
		}
			
		if ($result['sessionStatus'] == 'SUCCESS' && $result['transactionStatus'] == 'SUCCESS'){
            return array(CC_RESULT_SUCCESS, "", $result['transactionId'], $log);
//        } elseif ($res['transactionStatus'] != 'SUCCESS') {
//            return array(CC_RESULT_DECLINE_PERM, $res['responsetext'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, "Transaction status:".$result['transactionStatus'], "", $log);
        }

    }
    
    function save_cc_info($cc_info, & $member,$description='') {
        global $db;
        ////validate user profile, update if incorrect, create if no exists
        $member['data']['cc'] = '**** **** **** '.substr($cc_info['cc_number'], -4);
		$member['data']['cc_code'] = $cc_info['cc_code'];
        if (isset($cc_info['cc_expire_Month'])) {
            $member['data']['cc-expire'] = sprintf('%02d%02d', $cc_info['cc_expire_Month'], substr($cc_info['cc_expire_Year'], 2, 2));
        } else {
            $member['data']['cc-expire'] = $cc_info['cc-expire'];
        }
		$log=array();
		$dispatcher = new TNvpServiceDispatcher(MCP__CREDITCARDSERVICE_INTERFACE, MCP__CREDITCARDSERVICE_NVP_URL);
//echo "DEBUG2";
		if ($member['data']['micropayment_cc_customer_vault_id']) {
			$customerId = $member['data']['micropayment_cc_customer_vault_id'];
		}
		else
		{
			try {
			$result = $dispatcher -> customerCreate(
				$this->config['key'], 
				$this->config['testing'], 
				md5($member['email'].time()),
				null,
				$cc_info['cc_name_f'],
				$cc_info['cc_name_l'],
				$member['email'],
				'en-GB');
//echo "DEBUG3";var_dump($result);
				if($result) $customerId = $result;
			}
			catch(Exception $e)
			{
				return array(CC_RESULT_INTERNAL_ERROR, $e->getCode()." ".$e -> getMessage(), "", $log);
			}
		}
		
		try {
			$result = $dispatcher -> addressSet(
				$this->config['key'],
				$this->config['testing'],
				$customerId,
				$cc_info['cc_street'],
				$cc_info['cc_zip'],
				$cc_info['cc_city'],
				$cc_info['cc_country']);
			$log[]=$result;
		}
		catch(Exception $e)
		{
			return array(CC_RESULT_INTERNAL_ERROR, $e->getCode()." ".$e -> getMessage(), "", $log);
		}
		
		try {
			$result = $dispatcher -> creditcardDataSet(
				$this->config['key'],
				$this->config['testing'],
				$customerId,
				$cc_info['cc_number'],
				"20".substr($cc_info['cc-expire'], 2, 2),
				substr($cc_info['cc-expire'], 0, 2));
			$log[]=$result;
		}
		catch(Exception $e)
		{
			return array(CC_RESULT_INTERNAL_ERROR, $e->getCode()." ".$e -> getMessage(), "", $log);
		}
		
		$member['data']['micropayment_cc_customer_vault_id']=$customerId;
		$db->update_user($member['member_id'], $member);
		global $log_p;
		$this->log=$log;
	}

    function payment_micropayment_cc($config) {
        parent::payment($config);
		add_product_field('micropayment_cc_project', 'Micropayment_cc Project Code','text', '');
        add_member_field('micropayment_cc_customer_vault_id', 'Micropayment_cc Customer Vault Id','', '');
    }

}

function micropayment_cc_get_member_links($user){
    return cc_core_get_member_links('micropayment_cc', $user);
}

function micropayment_cc_rebill(){
    return cc_core_rebill('micropayment_cc');
}
                                        
cc_core_init('micropayment_cc');