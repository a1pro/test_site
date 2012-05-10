<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");


class payment_merchantanywhere extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('merchantanywhere', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('merchantanywhere', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['merchantanywhere']['title'] ? $config['payment']['merchantanywhere']['title'] : _PLUG_PAY_MERCANYW_TITLE,
            'description' => $config['payment']['merchantanywhere']['description'] ? $config['payment']['merchantanywhere']['description'] : _PLUG_PAY_MERCANYW_DESC,
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
        $ret = cc_core_get_url("https://webservices.primerchants.com/billing/TransactionCentral/processCC.asp", $vars1);
        foreach(split("&", $ret) as $line){
            list($k, $v) = split("=", $line);
            $res[$k] = $v;
        }    

            $res['RESULT']      = $res[Auth];
            $res['RESPMSG']     = $res[Notes];
            $res['AVS']         = $res[AVSCode];
            $res['PNREF']       = $res[TransID];
        return $res;
    }
    function void_transaction($pnref, &$log){
        return "";
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

        srand(time());
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }
        $vars = array(
            "MerchantID"    => $this->config['merchantID'],
            "RegKey" => $this->config['tkey'],
            "REFID" => $payment['payment_id'] . '-' . rand(100, 999),
            "Amount" =>   $amount,
            "AccountNo" => $cc_info['cc_number'],
            "CCMonth" => substr($cc_info['cc-expire'],0,2),
            "CCYear" => substr($cc_info['cc-expire'],2,2),
            "NameonAccount" =>  $cc_info['cc_name_f']." ".$cc_info['cc_name_l'],
            "AVSADDR" =>  $cc_info['cc_street'],
            "AVSZIP" =>      $cc_info['cc_zip']
        );
        
        if ($this->config['testing']){
            $vars[MerchantID]   =   "10011";
            $vars[RegKey]       =   "KK48NPYEJHMAH6DK";
        }
        if ($cc_info['cc_code'])
            $vars['CVV2'] = $cc_info['cc_code'];

        // prepare log record
        $vars_l = $vars; 
        $vars_l['AcountNo'] = $cc_info['cc'];
        if ($vars['CVV2'])
            $vars_l['CVV2'] = preg_replace('/./', '*', $vars['CVV2']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if (preg_match("/\d+/",$res[RESULT])){
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif (preg_match("/Declined/i", $res[RESULT])) {
            return array(CC_RESULT_DECLINE_PERM, $res['RESPMSG'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['RESPMSG'], "", $log);
        }
    }
}

function merchantanywhere_get_member_links($user){
    return cc_core_get_member_links('merchantanywhere', $user);
}

function merchantanywhere_rebill(){
    return cc_core_rebill('merchantanywhere');
}

cc_core_init('merchantanywhere');
?>
