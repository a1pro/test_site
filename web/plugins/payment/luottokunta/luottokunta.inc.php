<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: luottokunta payment plugin
*    FileName $RCSfile$
*    Release: 2.4.0PRO ($Revision: 1913 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_luottokunta extends payment
{
    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars)
    {
        return cc_core_do_payment('luottokunta', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id)
    {
        global $db;
        return cc_core_get_cancel_link('luottokunta', $payment_id);
    }
    function get_plugin_features()
    {
        return array(
            'title' => 'luottokunta',
            'description' => 'Credit card payment',
            'phone' => 2,
            'code' => 1,
            'name_f' => 2,
            'type_options' => array('VISA' => 'VISA', 'MASTERCARD' => 'MASTERCARD'),
        );
    }
    function run_transaction($vars)
    {
        foreach ($vars as $kk=>$vv)
        {
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        header("Location: https://dmp.luottokunta.fi/paymentwebinterface/webpayment?".$vars1);
        exit();
    }
    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, $currency, $product_description, $charge_type, $invoice, $payment)
    {
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////
        if ($cc_info['cc_name_f'] == '')
        {
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }

        $amount*=100;
        $street = $cc_info['cc_street'] ? $cc_info['cc_street'] : $member['street'];
        $zip = $cc_info['cc_zip'] ? $cc_info['cc_zip'] : $member['zip'];
        $mac = md5($this->config['merchant_id'].$payment['payment_id'].$amount.$this->config['secret']);

        $vars = array
        (
            "Merchant_Number"                  => $this->config['merchant_id'],
            "Card_Details_Transmit"            => 1,
            "Device_Category"                  => 1,
            "Order_Number"                     => $payment['payment_id'],
            "Ecom_Payment_Card_Number"         => $cc_info['cc_number'],
            "Ecom_Payment_Card_ExpDate_Month"  => substr($cc_info['cc-expire'], 0, 2),
            "Ecom_Payment_Card_ExpDate_Year"   => '20'.substr($cc_info['cc-expire'], 2, 2),
            "Amount"                           => $amount,
            "Currency_Code"                    => 978,
//            "Order_Description"                => $member['name_f'].' '.$member['name_l'].'<br>'.$street.'<br>'.$zip.' '.$cc_info['cc_country'],
            "Success_Url"                      => $config[root_url]."/plugins/payment/luottokunta/ipn.php?pid=".$payment['payment_id']."&amount=".$amount,
            "Failure_Url"                      => $config[root_url]."/plugins/payment/luottokunta/ipn.php?pid=".$payment['payment_id']."&amount=".$amount,
            "Transaction_Type"                 => 1,
            "Authentication_Mac"               => $mac
        );
        if ($cc_info['cc_code'])
            $vars['Ecom_Payment_Card_Verification'] = $cc_info['cc_code'];

        // prepare log record
        $vars_l = $vars;
        if ($vars['Ecom_Payment_Card_Verification'])
            $vars_l['Ecom_Payment_Card_Verification'] = preg_replace('/./', '*', $vars['CVC']);
        $log[] = $vars_l;

        $res = $this->run_transaction($vars);
    }
}

function luottokunta_get_member_links($user)
{
    return cc_core_get_member_links('luottokunta', $user);
}

function luottokunta_rebill()
{
   exit();
}

cc_core_init('luottokunta');
?>