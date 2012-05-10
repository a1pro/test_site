<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_vmerchant extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('vmerchant', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('vmerchant', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['vmerchant']['title'],
            'description' => $config['payment']['vmerchant']['description'],
            'phone' => 2,
            'company' => 1,
            'code' => 1,
            'name_f' => 2,
            'no_recurring' => 1
        );
    }
    function run_transaction($vars){
              
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        
        
        $ret = cc_core_get_url("https://www.myvirtualmerchant.com/VirtualMerchant/process.do", $vars1, true);
                
        $res = array();
        $pattern='/([_A-Za-z0-9\-]+)=(.*)(\n|$)/'; 
        preg_match_all($pattern, $ret, $match, PREG_SET_ORDER);
        foreach ($match as $m) {
            $res[$m[1]]=trim($m[2]);
        }
        return $res;
    }
    
    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////
        if ($charge_type == CC_CHARGE_TYPE_TEST) {
           return array(CC_RESULT_SUCCESS, "", "free trial", $log);
        }
       
        if(!$product_description){
	    global $db;
	    $product = $db->get_product($payment[product_id]);
	    $product_description = $product[title];
	}
        $vars = array(
            "ssl_merchant_id"      => $this->config['merchant_id'],
            "ssl_pin"              => $this->config['pin'],
            "ssl_invoice_number"   => $payment['payment_id'],
            "ssl_amount"           => $amount,
            "ssl_transaction_type" => 'ccsale',
            "ssl_card_number"      => $cc_info['cc_number'],
            "ssl_exp_date"         => $cc_info['cc-expire'],
            "ssl_show_form"        => "FALSE",
            "ssl_avs_address"      => $cc_info['cc_street'],
            "ssl_avs_zip"          => $cc_info['cc_zip'],
            "ssl_result_format"    => "ASCII",
            "ssl_salestax"         => "0",

            'ssl_description' => $product_description,
            'ssl_company' => $cc_info['cc_company'],
            'ssl_first_name' => $member['name_f'],
            'ssl_last_name' => $member['name_l'],
            'ssl_city' => $cc_info['cc_city'],
            'ssl_state' => $cc_info['cc_state'],
            'ssl_country' => $cc_info['cc_country'],
            'ssl_phone' => $cc_info['cc_phone'],
            'ssl_email' => $member['email']
        );
        if (isset($payment['data']['TAXES'])) {
            foreach ($payment['data']['TAXES'] as $p=>$a) {
                $vars['ssl_salestax']=$vars['ssl_salestax']+$a;
            }
        }
         
        if ($this->config['user_id'])
            $vars['ssl_user_id'] = $this->config['user_id'];
        if ($this->config['testing'])
            $vars['ssl_test_mode'] = 'TRUE';
        
        if ($cc_info['cc_code']){
            $vars['ssl_cvv2cvc2']               = $cc_info['cc_code'];
            $vars['ssl_cvv2cvc2_indicator']     = "1";

        }else{
            $vars['ssl_cvv2cvc2_indicator']     = "0";     
        }
        // prepare log record
        $vars_l = $vars; 
        $vars_l['ssl_card_number'] = $cc_info['cc'];
        if ($vars['ssl_cvv2cvc2'])
            $vars_l['ssl_cvv2cvc2'] = preg_replace('/./', '*', $vars['ssl_cvv2cvc2']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;
        
        if ($res['ssl_result'] == '0'){
            return array(CC_RESULT_SUCCESS, "", $res['ssl_txn_id'], $log);
        } elseif ($res['ssl_result'] == '1') {
            return array(CC_RESULT_DECLINE_PERM, $res['ssl_result_message'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['errorCode'].' : '.$res['errorMessage'], "", $log);
        }
    }
}

function vmerchant_get_member_links($user){
    return cc_core_get_member_links('vmerchant', $user);
}

function vmerchant_rebill(){
    return cc_core_rebill('vmerchant');
}
                                        
cc_core_init('vmerchant');
?>
