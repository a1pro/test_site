<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: merchantplanb payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2078 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

function merchantplanb_get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

class payment_merchantplanb extends amember_payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('merchantplanb', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('merchantplanb', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['authotize_aim']['title'] ? $config['payment']['merchantplanb']['title'] : _PLUG_PAY_MERCHANTPLANB_TITLE,
            'description' => $config['payment']['merchantplanb']['description'] ? $config['payment']['merchantplanb']['description'] : _PLUG_PAY_MERCHANTPLANB_DESC,
            'phone' => 1,
            'code' => 1,
            'name_f' => 2,
            'type_options' => array('VISA'       => 'Visa', 
                                    'MASTERCARD' => 'MasterCard')
        );
    }
    
    function run_transaction($vars){
        global $db;
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        
        $ret = cc_core_get_url("https://www.merchantplanb.com:444/proc/zproc_planb.asp", $vars1);
        
        $arr = preg_split('/\|/', $ret);
        
        $res = array(
            "RESULT"        => $arr[0],
            "RESULT_TEXT"   => $arr[1],
            "PNREF"         => $arr[2],
            "RESPMSG"       => $arr[3]
        );
        $db->log_error("MerchantPlanb RESPONSE:<br />\n" . merchantplanb_get_dump($res));

        return $res;
    }
    function void_transaction($pnref, &$log, $vars){
        global $db;
        
        $vars['Transtype']      = "Refund";
        $vars['Transactionid']  = $pnref;

        $vars_l = $vars;
        $vars_l['Cardnumber'] = $cc_info['cc'];
        if ($vars['CVV'])
            $vars_l['CVV'] = preg_replace('/./', '*', $vars['CVV']);
        $log[] = $vars_l;
        $db->log_error ("MerchantPlanb REQUEST:<br />\n" . merchantplanb_get_dump($vars_l));
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
        global $config, $db;
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        srand(time());
        if ($charge_type == CC_CHARGE_TYPE_TEST){
            $amount = "1.00";
        }
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }

        if(!$product_description){
    	    global $db;
    	    $product = $db->get_product($payment['product_id']);
    	    $product_description = $product['title'];
	    }
        $vars = array(
        // Required Fields
            "MerchantID"        => $this->config['merchant_id'],
            "MerchantPASS"      => $this->config['merchant_pass'],
            "Transtype"         => 'Sale', // Sale or Refund
            "Transactionid"     => '', // Only Required on Refunds
            "Cardnumber"        => $cc_info['cc_number'],
            "CardType"          => $cc_info['cc_type'], // Visa, Amex, Discover or MasterCard
            "CVV"               => $cc_info['cc_code'], // CVV Security Code
            "ExpiryMonth"       => substr($cc_info['cc-expire'], 0, 2),
            "ExpiryYear"        => substr($cc_info['cc-expire'], 2, 2),
            "Amount"            => sprintf('%.2f', $amount), //Dollar amount to be charged in 0.00 format
            
        // Optional Fields
            "Fname"             => $cc_info['cc_name_f'],
            "Lname"             => $cc_info['cc_name_l'],
            "Address1"          => $cc_info['cc_street'],
            "City"              => $cc_info['cc_city'],
            "State"             => $cc_info['cc_state'],
            "Zip"               => $cc_info['cc_zip'],
            "Country"           => $cc_info['cc_country'],
            "Phone"             => $cc_info['cc_phone'],
            "Email"             => $member['email'],
            "IP"                => $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'],
            "InvoiceNumber"     => $payment['payment_id'],
            "ProductDescription"=> $product_description,
            "CurrencyCode"      => $this->config['currency_code'] ? $this->config['currency_code'] : 'USD'
        );


        // prepare log record
        $vars_l = $vars; 
        $vars_l['Cardnumber'] = $cc_info['cc'];
        if ($vars['CVV'])
            $vars_l['CVV'] = preg_replace('/./', '*', $vars['CVV']);
        $log[] = $vars_l;
        /////
        $db->log_error ("MerchantPlanb REQUEST:<br />\n" . merchantplanb_get_dump($vars_l));
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['RESULT'] == '1'){ //  && $res['RESULT_TEXT'] == "approved"
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res['PNREF'], $log, $vars);
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif ($res['RESULT'] == '2') {
            return array(CC_RESULT_DECLINE_PERM, $res['RESPMSG'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['RESPMSG'], "", $log);
        }
    }
}

function merchantplanb_get_member_links($user){
    return cc_core_get_member_links('merchantplanb', $user);
}

function merchantplanb_rebill(){
    return cc_core_rebill('merchantplanb');
}
                                        
cc_core_init('merchantplanb');
?>
