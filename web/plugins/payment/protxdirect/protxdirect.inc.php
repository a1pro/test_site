<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");


class payment_protxdirect extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('protxdirect', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('protxdirect', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['protxdirect']['title'] ? $config['payment']['protxdirect']['title'] : _PLUG_PAY_PROTXDIR_TITLE,
            'description' => $config['payment']['protxdirect']['description'] ? $config['payment']['protxdirect']['description'] : _PLUG_PAY_PROTXDIR_DESC,
            'currency' => array('USD' => 'USD', 'EUR' => 'EUR', 'GBP' =>'GBP'),
            'code' => 2,
            'name_f' => 2,
            'type_options' => array(
                'VISA' => 'VISA',
                'MC' => 'Master Card',
                'UKE' => 'Visa Electron')
        );
    }

    function run_transaction($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        if($this->config[testing]){
            $ret = cc_core_get_url("https://test.sagepay.com/gateway/service/vspdirect-register.vsp", $vars1);
        }else{
            $ret = cc_core_get_url("https://live.sagepay.com/gateway/service/vspdirect-register.vsp", $vars1);
        }
        foreach(split("\r\n", $ret) as $line){
            list($k, $v) = split("=", $line);
            $res[$k] = $v;
        }    

        $res[RESULT] = $res[Status];
        $res[RESPMSG] = $res[StatusDetail];
        $res[PNREF] = $res[VPSTxId];
        return $res;
    }
    function void_transaction($req, $res, &$log){
        $vars = array(
            "VPSProtocol"    => "2.22",
            "TxType"    => "VOID",
            "Vendor"    => $this->config['login'],
            "VendorTxCode" => $req["VendorTxCode"],
            "VPSTxId" => $res["VPSTxId"],
            "SecurityKey" => $res["SecurityKey"],
            "TxAuthNo" => $res["TxAuthNo"],
        );
        $log[] = $vars;
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        if($this->config[testing]){
            $ret = cc_core_get_url("https://test.sagepay.com/gateway/service/void.vsp", $vars1);
        }else{
            $ret = cc_core_get_url("https://live.sagepay.com/gateway/service/void.vsp", $vars1);
        }
        $res = array();
        foreach(split("\r\n", $ret) as $line){
            list($k, $v) = split("=", $line);
            $res[$k] = $v;
        }    
        $log[] = $res;
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
        if ($charge_type == CC_CHARGE_TYPE_TEST) 
            $amount = "1.00";
        $vars = array(
            "VPSProtocol"    => "2.22",
            "TxType"    => "PAYMENT",
            "Vendor"    => $this->config['login'],
            "VendorTxCode" => $payment['payment_id'] . '-' . rand(100, 999),
            "Amount" =>   $amount,
            "Currency" => $currency ? $currency : 'USD',
            "Description" => 'aMember Generated: ' . $product_description,
            "CardHolder" =>  $cc_info['cc_name_f']." ".$cc_info['cc_name_l'],
            "CardNumber" => $cc_info['cc_number'],
            "ExpiryDate" => $cc_info['cc-expire'],
            "CardType"   => ($cc_info['cc_type']),
            "BillingAddress" =>  $cc_info['cc_street'],
            "BillingPostCode" =>  $cc_info['cc_zip']
        );
        
        if ($cc_info['cc_code'])
            $vars['CV2'] = $cc_info['cc_code'];

        // prepare log record
        $vars_l = $vars; 
        $vars_l['CardNumber'] = $cc_info['cc'];
        if ($vars['CV2'])
            $vars_l['CV2'] = preg_replace('/./', '*', $vars['CV2']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['RESULT'] == 'OK'){
            if ($charge_type == CC_CHARGE_TYPE_TEST) {
                $this->void_transaction($vars, $res, $log);
            }
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif ($res['RESULT'] == 'NOTAUTHED') {
            return array(CC_RESULT_DECLINE_PERM, $res['RESPMSG'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['RESPMSG'], "", $log);
        }

    }
    function run_refund($vars){
    }
}

function protxdirect_get_member_links($user){
    return cc_core_get_member_links('protxdirect', $user);
}

function protxdirect_rebill(){
    return cc_core_rebill('protxdirect');
}

cc_core_init('protxdirect');
?>
