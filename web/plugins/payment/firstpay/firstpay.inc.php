<?php


if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_firstpay extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('firstpay', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('firstpay', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['firstpay']['title'] ? $config['payment']['firstpay']['title'] : _PLUG_PAY_FIRSTPAY_TITLE,
            'description' => $config['payment']['firstpay']['description'] ? $config['payment']['firstpay']['description'] : _PLUG_PAY_FIRSTPAY_DESC,
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
        $ret = cc_core_get_url("https://www.firstpay.net/vt/consumer_auth_txt.asp", $vars1);

        if($ret=="Invalid"){
            return array("RESULT" => "Invalid", "RESPMSG" => "Invalid info", "MESSAGE" =>$ret);
        }
        $arr = preg_split('/\,/', $ret);
        $res = array(
            'RESULT'      => $arr[1],
            'RESPMSG'     => $arr[2],
            'RESPONSECODE'         => $arr[3],
            'PNREF'       => $arr[3]
        );
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
            "merchant"    => $this->config['login'],
            "ticket" => $payment['payment_id'],
            "amount" =>   $amount,
            "card" => $cc_info['cc_number'],
            "expdate" => $cc_info['cc-expire'],
            "cardname" =>  $cc_info['cc_name_f']." ".$cc_info['cc_name_l'],
            "address" =>  $cc_info['cc_street'],
            "zip" =>      $cc_info['cc_zip'],
            "authenticator" => md5($this->config['secret'].$this->config['login'].$payment[payment_id].$amount)
        );
        
        if ($cc_info['cc_code'])
            $vars['cvd'] = $cc_info['cc_code'];

        // prepare log record
        $vars_l = $vars; 
        $vars_l['card'] = $cc_info['cc'];
        if ($vars['cvd'])
            $vars_l['cvd'] = preg_replace('/./', '*', $vars['cvd']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if (preg_match("/Approved/i", $res[RESULT])){
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif (preg_match("/Declined/i", $res[RESULT])) {
            return array(CC_RESULT_DECLINE_PERM, ($res['RESPMSG'] ? $res[RESPMSG] : $res[RESULT]), "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, ($res['RESPMSG'] ? $res[RESPMSG] : $res[RESULT]), "", $log);
        }
    }
}

function firstpay_get_member_links($user){
    return cc_core_get_member_links('firstpay', $user);
}

function firstpay_rebill(){
    return cc_core_rebill('firstpay');
}

cc_core_init('firstpay');
?>
