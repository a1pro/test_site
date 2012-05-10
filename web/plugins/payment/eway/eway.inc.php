<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: EWAY payment plugin
*    FileName $RCSfile$
*    Release: 2.4.0PRO ($Revision: 1781 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");
require_once($config['root_dir']."/plugins/payment/eway/epayment.php");


class payment_eway extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('eway', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;
        return cc_core_get_cancel_link('eway', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['eway']['title'] ? $config['payment']['eway']['title'] : _PLUG_PAY_EWAY_TITLE,
            'description' => $config['payment']['eway']['description'] ? $config['payment']['eway']['description'] : _PLUG_PAY_EWAY_DESC,
            'type_options' => array('VISA' => 'VISA', 'MASTERCARD' => 'MASTERCARD', 'AMEX' => 'AMEX', 'DINERS' => 'DINERS'),
            'name_f' => 1
        );
    }

    /*************************************************************
      cc_bill - do real cc bill
      member - member info
      currency - in payment system value
      invoice here is to be passed to payment system exactly
      charge_type
      payment here is for reference, you shouldn't take on it
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount,
        $currency, $product_description,
        $charge_type, $invoice, $payment){
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////
        if ($charge_type == CC_CHARGE_TYPE_TEST)
            return array(CC_RESULT_SUCCESS, "", "", array('test transaction' => 'no validation'));

        $vars_l = $vars;
        $vars_l['trnCardNumber'] = $cc_info['cc'];
        /////
        $obj = new electronic_payment($this->config['customer_id'], round($amount*100, 0),
            $cc_info['cc_name_f'], $cc_info['cc_name_l'], $member['email'],
            $cc_info['cc_street'], $cc_info['cc_zip'], $product_description,
            $payment['payment_id'], $cc_info['cc_type'],
            $cc_info['cc_number'],
            substr($cc_info['cc-expire'], 0, 2),substr($cc_info['cc-expire'], 2, 2));
        $obj->xml_request = str_replace($cc_info['cc_number'], $cc_info['cc'], $obj->xml_request);
        $log[] = array('REQUEST' => $obj->xml_request);
        $log[] = array('RESPONSE' => $obj->xml_response);
        if ($obj->trxn_status() == 'True'){
            return array(CC_RESULT_SUCCESS, "", $obj->trxn_number(), $log);
        } else {
            return array(CC_RESULT_DECLINE_PERM, $obj->trxn_error(), "", $log);
        }
    }
}

function eway_get_member_links($user){
    return cc_core_get_member_links('eway', $user);
}

function eway_rebill(){
    return cc_core_rebill('eway');
}

cc_core_init('eway');
?>
