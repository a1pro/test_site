<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: merchantone payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3498 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_merchantone extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('merchantone', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('merchantone', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['merchantone']['title'] ? $config['payment']['merchantone']['title'] : "Merchant One",
            'description' => $config['payment']['merchantone']['description'] ? $config['payment']['merchantone']['description'] : "Credit Card Payment",
            'phone' => 2,
            'company' => 1,
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
        $ret = cc_core_get_url("https://secure.merchantonegateway.com/api/transact.php", $vars1);
        parse_str($ret,$res);
        return $res;
    }
    function void_transaction($pnref, &$log){
        $vars = array(
            "username" => $this->config['login'],
            "password" => $this->config['pass'],
            "type" => "void",
            "transactionid" =>   $pnref,
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
		$type = "sale";

        if ($charge_type == CC_CHARGE_TYPE_TEST){
            $amount = "1.00";
			$type = "auth";
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
            "username"    => $this->config['login'],
			"password"    => $this->config['pass'],
			"type"     => $type,
			"ccnumber" => $cc_info['cc_number'],
			"ccexp" => $cc_info['cc-expire'],
			"amount" => sprintf("%02f",$amount),
			// Order Information
			"ipaddress" => $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'],
			"orderid" => $payment['payment_id'],
			"orderdescription" => $product_description,
			//"tax" => '',
			//"shipping" => '',
			"ponumber" => 'AM'.$payment['payment_id'],
			// Billing Information
			"firstname" => $cc_info['cc_name_f'],
			"lastname" => $cc_info['cc_name_l'],
			"company" => $cc_info['cc_company'],
			"address1" => $cc_info['cc_street'],
			"city" => $cc_info['cc_city'],
			"state" => $cc_info['cc_state'],
			"zip" => $cc_info['cc_zip'],
			"country" => $cc_info['cc_country'],
			"phone" => $cc_info['cc_phone'],
			"email" => $member['email'],
			//"website" => '',
			// Shipping Information
			"shipping_firstname" => $cc_info['cc_name_f'],
			"shipping_lastname" => $cc_info['cc_name_l'],
			"shipping_company" => $cc_info['cc_company'],
			"shipping_address1" => $cc_info['cc_street'],
			"shipping_city" => $cc_info['cc_city'],
			"shipping_state" => $cc_info['cc_state'],
			"shipping_zip" => $cc_info['cc_zip'],
			"shipping_country" => $cc_info['cc_country'],
			"shipping_email" => $member['email']
        );
        

        // prepare log record
        $vars_l = $vars; 
        if ($cc_info['cc_code'])
            $vars['cvv'] = $cc_info['cc_code'];
        $vars_l['ccnumber'] = $cc_info['cc'];
        if ($vars['ccnumber'])
            $vars_l['ccnumber'] = preg_replace('/./', '*', $vars['ccnumber']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['response'] == '1'){
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res['transactionid'], $log);
            return array(CC_RESULT_SUCCESS, "", $res['transactionid'], $log);
        } elseif ($res['response'] == '2') {
            return array(CC_RESULT_DECLINE_PERM, $res['responsetext'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['responsetext'], "", $log);
        }
    }
}

function merchantone_get_member_links($user){
    return cc_core_get_member_links('merchantone', $user);
}

function merchantone_rebill(){
    return cc_core_rebill('merchantone');
}
                                        
cc_core_init('merchantone');
?>
