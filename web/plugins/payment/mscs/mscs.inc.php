<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: mscs payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3289 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_mscs extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('mscs', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('mscs', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['mscs']['title'] ? $config['payment']['mscs']['title'] : "MSCS",
            'description' => $config['payment']['mscs']['description'] ? $config['payment']['mscs']['description'] : "Credit card payment",
            'phone' => 2,
            'company' => 2,
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
        $ret = cc_core_get_url("https://authorize.mscsecure.com/gateway/authorize.asp", $vars1);
        $ret = str_replace ("\"", "", $ret);
        $arr = preg_split('/\|/', $ret);
        $res = array(
            'IsError'      => $arr[0],
            'ErrorText'  => $arr[1],
            'ResponseCode' => $arr[2],
            'ResponseText'     => $arr[3],
            'AuthCode'         => $arr[4],
            'AVSCode'       => $arr[5],
            'TransactionID'   => $arr[6]
        );
        return $res;
    }
    function void_transaction($pnref, &$log){
        $vars = array(
            "x_login"    =>   $this->config['login'],
            "x_password"    =>   $this->config['password'],
            "x_mid"    =>   $this->config['mid'],
            "x_did"    =>   $this->config['did'],
            "x_delim_data" => "True",
            "x_response_Delimeter" => "|",
            "x_type" =>   "VOID",
            "x_AquireMethod" =>   "MOTO",
            "x_PaymentMethod" =>   "RefundCC",
            "x_debugmode" =>   "FALSE",
			"x_trans_id" => $pnref,
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
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        srand(time());
		$x_Type = "AUTH_CAPTURE";

        if ($charge_type == CC_CHARGE_TYPE_TEST){
            $amount = "1.00";
			$x_Type = "AUTH_ONLY";
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
            "x_login"    =>   $this->config['login'],
            "x_password"    =>   $this->config['password'],
            "x_mid"    =>   $this->config['mid'],
            "x_did"    =>   $this->config['did'],
            "x_delim_data" => "True",
            "x_response_Delimeter" => "|",
            "x_amount" =>   $amount,
            "x_cardnum"     =>   $cc_info['cc_number'],
            "x_exp_date" =>   $cc_info['cc-expire'],
            "x_type" =>   $x_Type,
            "x_AquireMethod" =>   "MOTO",
            "x_PaymentMethod" =>   "ChargeCC",
            "x_debugmode" =>   "FALSE",
            "x_cust_ip" =>   $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'],
			"x_firstname" => $cc_info['cc_name_f'],
			"x_lastname" => $cc_info['cc_name_l'],
			"x_company" => $cc_info['cc_company'],
			"x_address" => $cc_info['cc_street'],
			"x_city" => $cc_info['cc_city'],
			"x_state" => $cc_info['cc_state'],
			"x_zip" => $cc_info['cc_zip'],
			"x_country" => $cc_info['cc_country'],
			"x_phone" => $cc_info['cc_phone'],
			"x_custid" => $member['member_id'],
			"x_cust_email" => $member['email'],
			"x_email_customer" => "TRUE",
			"x_invoive_num" => "AMEMBER$invoice",
			"x_desc" => "$product_description purchase from amember",
			"x_ship_firstname" => $cc_info['cc_name_f'],
			"x_ship_lastname" => $cc_info['cc_name_l'],
			"x_ship_company" => $cc_info['cc_company'],
			"x_ship_address" => $cc_info['cc_street'],
			"x_ship_city" => $cc_info['cc_city'],
			"x_ship_state" => $cc_info['cc_state'],
			"x_ship_zip" => $cc_info['cc_zip'],
			"x_ship_country" => $cc_info['cc_country'],
			);
        
        if ($this->config['testing'])
            $vars['x_Test_Request'] = 'TRUE';

        // prepare log record
        $vars_l = $vars; 
        $vars_l['x_cardnum'] = $cc_info['cc'];
        if ($vars['x_cardnum'])
            $vars_l['x_cardnum'] = preg_replace('/./', '*', $vars['x_cardnum']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['IsError'] == 'True' && $res['ResponseCode'] == "1"){
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res['TransactionID'], $log);
            return array(CC_RESULT_SUCCESS, "", $res['TransactionID'], $log);
        } elseif ($res['ResponseCode'] != "1") {
            return array(CC_RESULT_DECLINE_PERM, $res['ErrorText'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['ResponseText'], "", $log);
        }
    }
}

function mscs_get_member_links($user){
    return cc_core_get_member_links('mscs', $user);
}

function mscs_rebill(){
    return cc_core_rebill('mscs');
}
                                        
cc_core_init('mscs');
?>
