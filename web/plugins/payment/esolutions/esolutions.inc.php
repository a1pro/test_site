<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: esolutions payment plugin
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

class payment_esolutions extends payment {
	var $log = array();
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('esolutions', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('esolutions', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['esolutions']['title'] ? $config['payment']['esolutions']['title'] : _PLUG_PAY_ESOLUTIONS_TITLE,
            'description' => $config['payment']['esolutions']['description'] ? $config['payment']['esolutions']['description'] : _PLUG_PAY_ESOLUTIONS_DESC,
            'phone' => 0,
            'company' => 0,
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
        $ret = cc_core_get_url("https://test.merchante-solutions.com/mes-api/tridentApi", $vars1);
        parse_str($ret,$arr);
        return $arr;
    }
    function void_transaction($pnref, &$log){
        $vars = array(
			"profile_id"=>$this->config['pid'],
			"profile_key"=>$this->config['pkey'],
			"transaction_type"=>"V",
			"transaction_id" =>   $pnref,
        );
        $vars_l = $vars;
        $log[] = $vars_l;
        $res = $this->run_transaction($vars);
        $log[] = $res;
        return $res;
    }
    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config,$db;
        $log = array();
        //////////////////////// cc_bill /////////////////////////
        srand(time());
        if ($charge_type == CC_CHARGE_TYPE_TEST)
	{
		$amount = "1.00";
	}
	
	if (CC_CHARGE_TYPE_RECURRING != $charge_type) 
	{
		$result=$this->save_cc_info($cc_info, $member,$product_description);
		if (is_array($result))
			return $result;
		$log=$this->log;
		$member=$db->get_user($member['member_id']);
        }
	else
	{
		if ( !$member['data']['esolutions_card_id'])
			return array(CC_RESULT_DECLINE_PERM, 'esolutions_card_id is not defined', "", $log);
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
	
        $vars = array(
			"profile_id"=>$this->config['pid'],
			"profile_key"=>$this->config['pkey'],
			"transaction_type"=>"D",
			"card_id" => $member['data']['esolutions_card_id'],
			//"card_number"=>$cc_info['cc_number'],
			//"card_exp_date"=>$cc_info['cc-expire'],
			"transaction_amount"=>$amount,
			//"cardholder_street_address"=>$cc_info['cc_street'],
			//"cardholder_zip"=>$cc_info['cc_zip'],
			"invoice_number"=>$payment['payment_id'] . '-' . rand(100, 999),//????
			//"cvv2"=>$cc_info['cc_code'],
        );
        // prepare log record
        $vars_l = $vars; 
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res["error_code"] === '000'){
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res["transaction_id"], $log);
            return array(CC_RESULT_SUCCESS, "", $res["transaction_id"], $log);
        } elseif ($res["error_code"] === '999') {
            return array(CC_RESULT_INTERNAL_ERROR, $res["auth_response_text"], "", $log);
        } else {
            return array(CC_RESULT_DECLINE_PERM, $res["auth_response_text"], "", $log);
        }
    }
    function save_cc_info($cc_info, &$member,$description='') {
        global $db;
        ////validate user profile, update if incorrect, create if no exists
        $member['data']['cc'] = '**** **** **** '.substr($cc_info['cc_number'], -4);
        if (isset($cc_info['cc_expire_Month'])) {
            $member['data']['cc-expire'] = sprintf('%02d%02d', $cc_info['cc_expire_Month'], substr($cc_info['cc_expire_Year'], 2, 2));
        } else {
            $member['data']['cc-expire'] = $cc_info['cc-expire'];
        }
	$log=array();
	$card_id=$member['data']['esolutions_card_id']?$member['data']['esolutions_card_id']:"";
//	$card_id=$member['data']['esolutions_card_id']?$member['data']['esolutions_card_id']:md5($cc_info['cc_number'].$member['login']);
	$vars=array(
		"profile_id"=>$this->config['pid'],
		"profile_key"=>$this->config['pkey'],
		"transaction_type"=>"T",
		"card_number"=>$cc_info['cc_number'],
		"card_exp_date"=>$cc_info['cc-expire'],
		//"transaction_amount"=>$amount,
		"cardholder_street_address"=>$cc_info['cc_street'],
		"cardholder_zip"=>$cc_info['cc_zip'],
		//"invoice_number"=>$payment['payment_id'] . '-' . rand(100, 999),//????
		"cvv2"=>$cc_info['cc_code'],
		);
	if($member['data']['esolutions_card_id'])
		$vars["card_id"] = $member['data']['esolutions_card_id'];
	$vars_l=$vars;
	$vars_l['card_number']='****';
	$log[]=$vars_l;
	$res=$this->run_transaction($vars);
	$log[]=$res;
        if ($res["error_code"] === '000')
	{
		if(!$member['data']['esolutions_card_id'])
			$member['data']['esolutions_card_id']=$res["transaction_id"];
		$member['esolutions_card_id']=$res["transaction_id"];
		$db->update_user($member['member_id'], $member);
		$this->log=$log;
		return;
        } elseif ($res["error_code"] === '999') 
	{
		return array(CC_RESULT_INTERNAL_ERROR, $res['auth_response_text'], "", $log);
        } else {
		return array(CC_RESULT_DECLINE_PERM, $res["auth_response_text"], "", $log);
        }
    }
    function payment_esolutions($config) {
        parent::payment($config);        
        add_member_field('esolutions_card_id', 'Esolutions Customer Card Id','', '');
    }
    
}

function esolutions_get_member_links($user){
    return cc_core_get_member_links('esolutions', $user);
}

function esolutions_rebill(){
    return cc_core_rebill('esolutions');
}
                                        
cc_core_init('esolutions');
?>
