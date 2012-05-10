<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: efsnet payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1781 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $config;


require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_efsnet extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('efsnet', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('efsnet', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['efsnet']['title'] ? $config['payment']['efsnet']['title'] : _PLUG_PAY_EFSNET_TITLE,
            'description' =>$config['payment']['efsnet']['description'] ? $config['payment']['efsnet']['description'] :  _PLUG_PAY_EFSNET_DESC,
            'code' => 1,
            'name_f' => 2
        );
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
        if ($charge_type == CC_CHARGE_TYPE_TEST) 
            return array(CC_RESULT_SUCCESS, "", "", array('test transaction' => 'no validation'));

        $vars = array(
             "Method"  => "CreditCardCharge",
             "StoreID" => $this->config['store_id'],
             "StoreKey" => $this->config['store_key'],
             "ApplicationID" => 'AMemberPro v1.9.3',
             "ReferenceNumber" => '19300' . $payment['payment_id'] ,
             "TransactionAmount" => $amount,
             "AccountNumber" => $cc_info['cc_number'],
             "ExpirationMonth" => substr($cc_info['cc-expire'], 0, 2),
             "ExpirationYear" => substr($cc_info['cc-expire'], 2, 2),
             "BillingName" => $cc_info['cc_name_f'] . " " . $cc_info['cc_name_l'],
             "BillingAddress" => $cc_info['cc_street'],
             "BillingCity" => $cc_info['cc_city'],
             "BillingState" => $cc_info['cc_state'],
             "BillingPostalCode" => $cc_info['cc_zip'],
             "BillingCountry" => $cc_info['cc_country'],
             "BillingEmail" => $member['email']
             
     //        ,"ClientIPAddress" => $member['remote_addr']
        );
        if ($cc_info['cc_code']) 
            $vars['CardVerificationValue'] = $cc_info['cc_code'];
        // prepare log record
        $vars_l = $vars; 
        $vars_l['AccountNumber'] = $cc_info['cc'];
        if ($vars['CardVerificationValue'])
            $vars_l['CardVerificationValue'] = preg_replace('/./', '*', $vars['CardVerificationValue']);
        $log[] = $vars_l;
        /////
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $url_proc = 
            $this->config['testing'] ? 
            "https://testefsnet.concordebiz.com/efsnet.dll" :
            "https://efsnet.concordebiz.com/efsnet.dll";
        $ret = cc_core_get_url($url_proc."?".$vars1);
        parse_str($ret, $res);
        $log[] = $res;

        if (!strlen($res['ResponseCode'])){
            return array(CC_RESULT_INTERNAL_ERROR, "Communication error - please repeat payment attempt later", "", $log);
        } elseif ($res['ResponseCode'] == 0){
            return array(CC_RESULT_SUCCESS, "", $res['ApprovalNumber'], $log);
        } else {
            return array(CC_RESULT_DECLINE_PERM, $res['ResultMessage'], "", $log);
        }
    }
}

function efsnet_get_member_links($user){
    return cc_core_get_member_links('efsnet', $user);
}

function efsnet_rebill(){
    return cc_core_rebill('efsnet');
}

cc_core_init('efsnet');
?>
