<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: paymentsgateway payment plugin
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

class payment_paymentsgateway extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('paymentsgateway', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('paymentsgateway', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['paymentsgateway']['title'] ? $config['payment']['paymentsgateway']['title'] : _PLUG_PAY_PYMGTWAY_TITLE,
            'description' => $config['payment']['paymentsgateway']['description'] ? $config['payment']['paymentsgateway']['description'] : _PLUG_PAY_PYMGTWAY_DESC,
            'type_options' => array(
                'VISA' => 'VISA',
                'MAST' => 'Master Card',
                'AMER' => 'American Express',
                'DISC' => 'Discover Card',
                'DINE' => "Diner's Club",
                'JCB'  => 'JCB'),
            'phone' => 0,
            'code' => 1,
            'name_f' => 2,
            'company' => 0
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
        if ($charge_type == CC_CHARGE_TYPE_TEST)
            return array(CC_RESULT_SUCCESS, "", "start trial", $log);
        //////////////////////// cc_bill /////////////////////////
        if ($this->config['live'])
            $url_proc = "https://www.paymentsgateway.net/cgi-bin/postauth.pl";
        else
            $url_proc = "https://www.paymentsgateway.net/cgi-bin/posttest.pl";
        $avs_method='';
        for ($i=0;$i<5;$i++)
            $avs_method .= intval($this->config["avs_method$i"]);

        $vars = array(
            "pg_merchant_id" => $this->config['merchant_id'],
            "pg_password" => $this->config['password'],
            "pg_transaction_type" => 10,
            "pg_total_amount" => $amount,

            "Ecom_BillTo_Postal_Name_First" => $member['name_f'],
            "Ecom_BillTo_Postal_Name_Last" => $member['name_l'],
            "Ecom_BillTo_Online_Email" => $member['email'],
            "Ecom_BillTo_Postal_Street_Line1" => $cc_info['cc_street'] ? $cc_info['cc_street'] : $member['street'],
            "Ecom_BillTo_Postal_City" => $cc_info['cc_city'],
            "Ecom_BillTo_Postal_Stateprov" => $cc_info['cc_state'],
            "Ecom_BillTo_Postal_PostalCode" => $cc_info['cc_zip'] ? $cc_info['cc_zip'] : $member['zip'],
            "Ecom_BillTo_Postal_CountryCode" => $cc_info['cc_country'],

            "Ecom_Payment_Card_Name" => ($cc_info['cc_name_f']) ? ($cc_info['cc_name_f'] . " " . $cc_info['cc_name_l']) : ($member['name_f'] . ' ' . $member['name_l']),
            "Ecom_Payment_Card_Type" => ($cc_info['cc_type']),
            "Ecom_Payment_Card_Number" => $cc_info['cc_number'],
            "Ecom_Payment_Card_ExpDate_Month" => substr($cc_info['cc-expire'], 0, 2),
            "Ecom_Payment_Card_ExpDate_Year" => '20'.substr($cc_info['cc-expire'], 2, 2),

            "pg_avs_method" => $avs_method,
        );
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        if ($cc_info['cc_code'] != '')
            $vars['ecom_payment_card_verification'] = $cc_info['cc_code'];
        
        $vars_cc = $vars; 
        $vars_cc['pg_merchant_id'] = preg_replace('/./', '*', $vars_cc['pg_merchant_id']);
        $vars_cc['pg_password'] = preg_replace('/./', '*', $vars_cc['pg_password']);
        $vars_cc['ecom_payment_card_verification'] = preg_replace('/./', '*', $vars_cc['ecom_payment_card_verification']);
        $vars_cc['Ecom_Payment_Card_Number'] = $cc_info['cc'];

        $log[] = $vars_cc;

        $buffer = cc_core_get_url($url_proc, join('&', $vars1).'&endofdata&');
        parse_str($b=preg_replace("/[\n\r]+/", "&", $buffer), $res);

        $log[] = $res;

//        print_r($log);
        if ($res['pg_response_type'] != 'A') {
            return array(CC_RESULT_DECLINE_PERM, $res['pg_response_description'], $res['pg_trace_number'], $log);
        } else { 
            return array(CC_RESULT_SUCCESS, "", $res['pg_trace_number'], $log);
        }
    }
}

function paymentsgateway_get_member_links($user){
    return cc_core_get_member_links('paymentsgateway', $user);
}

function paymentsgateway_rebill(){
    return cc_core_rebill('paymentsgateway');
}

cc_core_init('paymentsgateway');
?>
