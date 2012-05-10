<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");


global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");
require_once($config['root_dir']."/plugins/payment/bluepay/bp20.php");

class payment_bluepay extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('bluepay', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('bluepay', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['bluepay']['title'] ? $config['payment']['bluepay']['title'] : _PLUG_PAY_BLUEPAY_TITLE,
            'description' => $config['payment']['bluepay']['description'] ? $config['payment']['bluepay']['description'] : _PLUG_PAY_BLUEPAY_DESC,
            'phone' => 2,
            'code' => 1,
            'name_f' => 2
        );
    }

    function run_transaction($vars){
        $bp = new BluePayment($this->config[account_id], 
                              $this->config[secret], 
                              ($this->config[test] ? "TEST": LIVE));
        
        $bp->sale($vars[amount]);
        $bp->payType = 'CREDIT';
        $bp->setCustInfo(
            $vars[cc],
            $vars[cvv2],
            $vars[expire],
            $vars[name1],
            $vars[name2],
            $vars[street],
            $vars[city],
            $vars[state],
            $vars[zip],
            $vars[country]                    
        );
        $bp->process();

        $res = array(
            'STATUS'      => $bp->getStatus(),
            'AVS'         => $bp->getAvsResp(),
            'PNREF'       => $bp->getTransId(),
            'CVV_VALID'   => $bp->getCvv2Resp(), 
            'AUTH_CODE'   => $bp->getAuthCode(),
            'RESPMSG'     => $bp->getMessage() 
        );
        return $res;
    }
    function void_transaction($pnref, &$log){
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
        if ($charge_type == CC_CHARGE_TYPE_TEST) 
            $amount = "1.00";
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }
        $vars = array(
            "cc" => $cc_info['cc_number'],
            "expire" => $cc_info['cc-expire'],
            "email"    =>    $member['email'],
            "name1" =>  $cc_info['cc_name_f'],
            "name2" =>   $cc_info['cc_name_l'],
            "street" =>  $cc_info['cc_street'],
            "city" =>     $cc_info['cc_city'],
            "state" =>    $cc_info['cc_state'],
            "zip" =>      $cc_info['cc_zip'],
            "country" =>  $cc_info['cc_country'],
            "amount" => $amount, 
        );
        
        if ($cc_info['cc_code'])
            $vars['cvv2'] = $cc_info['cc_code'];

        // prepare log record
        $vars_l = $vars; 
        $vars_l['cc'] = $cc_info['cc'];
        if ($vars['cvv2'])
            $vars_l['cvv2'] = preg_replace('/./', '*', $vars['cvv2']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['STATUS'] == '1'){
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif ($res['RESULT'] == '0') {
            return array(CC_RESULT_DECLINE_PERM, $res['RESPMSG'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['RESPMSG'], "", $log);
        }
    }
}

function bluepay_get_member_links($user){
    return cc_core_get_member_links('bluepay', $user);
}

function bluepay_rebill(){
    return cc_core_rebill('bluepay');
}

cc_core_init('bluepay');
?>
