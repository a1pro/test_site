<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: meritus payment plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3498 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_meritus extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('meritus', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('meritus', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['meritus']['title'] ? $config['payment']['meritus']['title'] : 'Meritus',
            'description' => $config['payment']['meritus']['description'] ? $config['payment']['meritus']['description'] : 'Credit card payment',
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
        $ret = cc_core_get_url("https://webservice.paymentxp.com/wh/WebHost.aspx", $vars1);
        parse_str($ret,$res);
        return $res;
    }
    function void_transaction($pnref, &$log){
        $vars = array(
            "MerchantID"    =>   $this->config['id'],
            "MerchantKey" =>   $this->config['key'],
            "TransactionType"     =>   "CreditCardVoid",
            "TransactionID" =>   $pnref,
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
            "MerchantID"    =>   $this->config['id'],
            "MerchantKey" =>   $this->config['key'],
            "TransactionType"     =>   "CreditCardCharge",
            "CardNumber"     =>   $cc_info['cc_number'],
            "TransactionAmount"     =>   $amount,
            "ReferenceNumber"     =>   $payment['payment_id'] . '-' . rand(100, 999),
            "EmailAddress"    =>    $member['email'],
            "BillingNameFirst" =>  $cc_info['cc_name_f'],
            "BillingNameLast" =>   $cc_info['cc_name_l'],
            "BillingAddress" =>  $cc_info['cc_street'],
            "BillingCity" =>     $cc_info['cc_city'],
            "BillingState" =>    $cc_info['cc_state'],
            "BillingZipCode" =>      $cc_info['cc_zip'],
            "ClientIPAddress" => $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'],
        );
        
        // prepare log record
        $vars_l = $vars; 
        if ($vars['CardNumber'])
            $vars_l['CardNumber'] = preg_replace('/./', '*', $vars['CardNumber']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['StatusID'] == '0'){
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res['TransactionID'], $log);
            return array(CC_RESULT_SUCCESS, "", $res['TransactionID'], $log);
        } elseif (intval($res['StatusID'])>0) {
            return array(CC_RESULT_DECLINE_PERM, $res['ResponseMessage'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['ResponseMessage'], "", $log);
        }
    }
}

function meritus_get_member_links($user){
    return cc_core_get_member_links('meritus', $user);
}

function meritus_rebill(){
    return cc_core_rebill('meritus');
}
                                        
cc_core_init('meritus');
?>
