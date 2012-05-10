<?php 

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");


class payment_paymenow extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('paymenow', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('paymenow', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['paymenow']['title'] ? $config['payment']['paymenow']['title'] : _PLUG_PAY_PAYMENOW_TITLE,
            'description' => $config['payment']['paymenow']['description'] ? $config['payment']['paymenow']['description'] : _PLUG_PAY_PAYMENOW_DESC,
            'phone' => 0,
            'company' => 0,
            'code' => 0,
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
        $ret = cc_core_get_url("http://trans.atsbank.com/cgi-bin/trans.cgi", $vars1);
        $arr = preg_split('/\|/', $ret);
        $res = array(
            'RESULT'      => $arr[0],
            'RESULT_SUB'  => $arr[1],
            'REASON_CODE' => $arr[2],
            'RESPMSG'     => $arr[3],
            'AVS'         => $arr[5],
            'PNREF'       => $arr[6],
            'CVV_VALID'   => $arr[48]
        );
        return $res;
    }
    function void_transaction($pnref, &$log){
        return;
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
        global $db;
        $product = $db->get_product($payment[product_id]);
        $product_description = $product_description ? $product_description : $product[title];
        srand(time());
        if ($charge_type == CC_CHARGE_TYPE_TEST) 
            $amount = "1.00";
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }
        $vars = array(
            "action"    => "ns_quicksale_cc", 
            "ecxid"    => $this->config['accid'],
            "amount" =>   $amount,
            "cc_name" =>  $cc_info['cc_name_f']." ".$cc_info['cc_name_l'],
            "cc_num" => $cc_info['cc_number'],
            "expmon" => substr($cc_info['cc-expire'], 0,2),
            "expyear" => substr($cc_info['cc-expire'], 2) + 2000
        );
        if ($cc_info['cc_code'])
            $vars['x_Card_Code'] = $cc_info['cc_code'];

        // prepare log record
        $vars_l = $vars; 
        $vars_l['x_Card_Num'] = $cc_info['cc'];
        if ($vars['x_Card_Code'])
            $vars_l['x_Card_Code'] = preg_replace('/./', '*', $vars['x_Card_Code']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;
        $res = substr($res, 22);
        print $res;
        $res1 = split("=", $res);
        switch($res1[0]){
            case "Accepted": $res[RESULT] = 1;
                             $res[PNREF] = trim($res1[1]);
                            break;                                    
            case "Declined": $res[RESULT] = 2;
                             $res[RESPMSG] = $res1[1];
                            break;
            case "Error": $res[RESULT] = 3;
                             $res[RESPMSG] = $res1[1];
                            break;
            default: $res[RESULT] = 3;
                             $res[RESPMSG] = "Internal error";
                            break;

        }
        if ($res['RESULT'] == '1'){
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif ($res['RESULT'] == '2') {
            return array(CC_RESULT_DECLINE_PERM, $res['RESPMSG'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['RESPMSG'], "", $log);
        }
    }
}

function paymenow_get_member_links($user){
    return cc_core_get_member_links('paymenow', $user);
}

function paymenow_rebill(){
    return cc_core_rebill('paymenow');
}

cc_core_init('paymenow');
?>
