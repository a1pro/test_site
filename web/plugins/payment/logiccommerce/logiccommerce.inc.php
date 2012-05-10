<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: logiccommerce payment plugin
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


class payment_logiccommerce extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('logiccommerce', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('logiccommerce', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['logiccommerce']['title'] ? $config['payment']['logiccommerce']['title'] : _PLUG_PAY_LOGICCOM_TITLE,
            'description' => $config['payment']['logiccommerce']['description'] ? $config['payment']['logiccommerce']['description'] : _PLUG_PAY_LOGICCOM_DESC,
            'phone' => 2,
            'code' => 1,
            'name' => 2
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
        srand(time());
        $vars = array(
            'MerchantId'  => $this->config['merchant_id'] ,
            'CustomerId'  => $this->config['customer_id'] ,
            'ZoneId'  => $this->config['zone_id'] ,
            'Username'  => $this->config['username'] ,


            'InvoiceNumber'   => $payment['payment_id'] . '-' . rand(1000,9999),
            'CustomerNumber'  => $member['member_id'],
            'Amount'          => $amount,
            'TransactionType' => 'C',
            'CardName' => $cc_info['cc_name'],
            'CardNumber' => $cc_info['cc_number'],
            'ExpiryMM' => substr($cc_info['cc-expire'], 0, 2),
            'ExpiryYY' => substr($cc_info['cc-expire'], 2, 2),

            'Email' => $member['email'],
            'Phone' => $cc_info['cc_phone'],
            'Address' => $cc_info['cc_street'],
            'City' => $cc_info['cc_city'],
            'State' => $cc_info['cc_state'],
            'Zip' => $cc_info['cc_zip'],
            'Country' => $cc_info['cc_country'],
            'AVSON' => 'Y',
            'EterminalID' => ''
        );
        if ($cc_info['cc_code']) 
            $vars['CVV'] = $cc_info['cc_code'];
        // prepare log record
        $vars_l = $vars; 
        $vars_l['CardNumber'] = $cc_info['cc'];
        if ($vars['CVV'])
            $vars_l['CVV'] = preg_replace('/./', '*', $vars['trnCardCvd']);
        $log[] = $vars_l;
        /////
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url("https://secure.logiccommerce.com/services/Payment.asmx/Process", $vars1);

        $log[] = array('' => str_replace('<', '&lt;', $ret));
        $res = array();
        foreach (array('EterminalId', 'AuthCode', 'Response', 
                'ResponseCode', 'Status', 'System', 'InvoiceNumber') as $k)
            if (preg_match("/<$k>(.+?)<\/$k>/", $ret, $regs))
                $res[$k] = $regs[1];
        if ($res['Status'] == 0){
            return array(CC_RESULT_SUCCESS, "", $res['EterminalId'], $log);
        } else {
            if ($res['System'] == '1') 
                return array(CC_RESULT_INTERNAL_ERROR, $res['Response'], "", $log);
            else 
                return array(CC_RESULT_DECLINE_PERM, $res['Response'], "", $log);
        }
    }
}

function logiccommerce_get_member_links($user){
    return cc_core_get_member_links('logiccommerce', $user);
}

function logiccommerce_rebill(){
    return cc_core_rebill('logiccommerce');
}

cc_core_init('logiccommerce');
?>
