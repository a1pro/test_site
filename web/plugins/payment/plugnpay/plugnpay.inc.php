<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

function plugnpay_get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

class payment_plugnpay extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('plugnpay', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('plugnpay', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['plugnpay']['title'] ? $config['payment']['plugnpay']['title'] : _PLUG_PAY_PLUGNPAY_TITLE,
            'description' => $config['payment']['plugnpay']['description'] ? $config['payment']['plugnpay']['description'] : _PLUG_PAY_PLUGNPAY_DESC,
            'code' => 2,
            'name_f' => 2,
            'province_outside_of_us' => 1,
            'maestro_solo_switch' => 1
        );
    }

    function run_transaction($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        
        $ret = cc_core_get_url("https://pay1.plugnpay.com/payment/pnpremote.cgi", $vars1);
        
        parse_str($ret, $res);
        global $db;
        $db->log_error("PlugNPay RESPONSE:<br />".plugnpay_get_dump($res));

        switch ($res['FinalStatus']){
            case 'success' : $return['RESULT'] = 'Approved'; break;
            case 'fraud':
            case 'badcard' : $return['RESULT'] = 'Declined'; break;
            case 'problem' : $return['RESULT'] = 'Problem'; break;
        } 
        $return['RESPMSG']   = $res['MErrMsg'] ? $res['MErrMsg'] : $res['auth-msg']; // There is no 'auth-msg' returned
        $return['RESPMSG']   = join("<br />\n", preg_split('/\|/', $return['RESPMSG']));
        $return['AVS']       = $res['avs-code'];
        $return['PNREF']     = $res['auth-code'];
        $return['CVV_VALID'] = $res['cvvresp'];
        $return['ORDERID']   = $res['orderID']; // Unique numeric order id used to identify transaction for any future activity including voids and returns.

        return $return;
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
        global $db, $config, $plugin_config;
        
        $this_config   = $plugin_config['payment']['plugnpay'];
        $product = $db->get_product($payment['product_id']);
        
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        if ($charge_type == CC_CHARGE_TYPE_TEST){
            //return array(CC_RESULT_SUCCESS, "", "", array('test transaction' => 'no validation'));
            return array(CC_RESULT_SUCCESS, "", "start trial", $log);
        }


        srand(time());
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }

        $vars = array(
            "publisher-name"    => $this_config['login'],
            "publisher-email"   => $config['admin_email'],
            "card-amount"       => $amount,
            "card-name"         => $cc_info['cc_name_f']." ".$cc_info['cc_name_l'],
            "card-address1"     => $cc_info['cc_street'],
            "card-city"         => $cc_info['cc_city'],
            "card-state"        => str_replace("XX", "", $cc_info['cc_state']),
            "card-prov"         => $cc_info['cc_province'], // Billing International Province.  For international provinces outside of US & Canada, set the "card-state" field's value to "ZZ" & include the province name here.
            "card-zip"          => preg_replace("/[^0-9a-zA-Z]/i", "", $cc_info['cc_zip']),
            "card-country"      => $cc_info['cc_country'],
            "card-number"       => $cc_info['cc_number'],
            "card-exp"          => $cc_info['cc-expire'],
            "card-cvv"          => $cc_info['cc_code'],
            "currency"		    => $this_config['currency'],

            "cardissuenum"      => $cc_info['cc_issuenum'], // Card Issue #, which is required for Maestro/Solo/Switch credit cards only.
            "cardstartdate"     => $cc_info['cc_startdate'], // Card Start Date, which is required for Maestro/Solo/Switch credit cards only.

    
            "orderID"           => '', // $payment_id . '-' . intval($_SESSION['x_try']++), // Must be a unique number.  [Alpha characters not permitted.] If not submitted one will be generated using a date/time string. This number will be required to perform any future activity for an order, including but not limited to, voids and returns.
            "app-level"         => $this_config['app_level'],
            "acct_code"         => $member['login'], // The value for "acct_code" can be up to 20 alphanumeric characters. Include this field with your orders/transactions so its value can be stored to our databases with your orders.  You can later use this field to filter/select only those orders which match the value passed when using many of the administrative functions.
    
            'shipping'          => 0,
            'tax'               => 0,
            'order-id'          => $payment['payment_id'],
            'item1'             => (($product['price_group'] < 0) ? ( - $product['price_group'] ) : $product['price_group']),
            'price1'            => $payment['amount'],
            'quantity1'         => 1,
            'description1'      => $product['title']
        );

        // prepare log record
        $vars_l = $vars; 
        $vars_l['card-number'] = $cc_info['cc'];
        if ($vars['card-cvv'])
            $vars_l['card-cvv'] = preg_replace('/./', '*', $vars['card-cvv']);
        $log[] = $vars_l;
        /////
        $db->log_error("PlugNPay DEBUG:<br />".plugnpay_get_dump($vars_l));
        
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if (preg_match("/Approved/i", $res['RESULT'])){
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif (preg_match("/Declined/i", $res['RESULT'])) {
            return array(CC_RESULT_DECLINE_PERM, ($res['RESPMSG'] ? $res['RESPMSG'] : $res['RESULT']), "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, ($res['RESPMSG'] ? $res['RESPMSG'] : $res['RESULT']), "", $log);
        }
    }
}

function plugnpay_get_member_links($user){
    return cc_core_get_member_links('plugnpay', $user);
}

function plugnpay_rebill(){
    return cc_core_rebill('plugnpay');
}

cc_core_init('plugnpay');
?>
