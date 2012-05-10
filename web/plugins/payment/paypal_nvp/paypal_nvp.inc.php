<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: authorize_aim payment plugin
*    FileName $RCSfile$
*    Release: 3.0.9PRO ($Revision: 2078 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");
require_once 'CallerService.php';

class payment_paypal_nvp extends payment {
    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('paypal_nvp', $payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, $vars);
    }

    function get_plugin_features(){
        return array(
            'title' => $config['payment']['paypal_nvp']['title'] ? $config['payment']['paypal_nvp']['title'] : _PLUG_PAY_PAYPALNVP_TITLE2,
            'description' => $config['payment']['paypal_nvp']['description'] ? $config['payment']['paypal_nvp']['description'] : _PLUG_PAY_PAYPALNVP_DESC2,
            'name_f' => 2,
            'type_options' => array('Visa'       => 'Visa',
                                    'MasterCard' => 'MasterCard',
                                    'Discover'   => 'Discover',
                                    'Amex'       => 'American Express'),
            'currency' => array(
                'USD' => 'USD',
                'GBP' => 'GBP',
                'EUR' => 'EUR',
                'CAD' => 'CAD',
                'AUD' => 'AUD',
                'JPY' => 'JPY'
            ),
            'no_recurring' => 1,
            'phone' => 1,
            'code'  => 1,
        );
    }
    function do_nvp_call($nvpstr){

    global $db;
    $APIUsername = $this->config['api_user'];
    $APISignature = $this->config['api_sig'];
    $APIPassword = $this->config['api_pass'];
    if ($this->config['testing'])
    	$API_Endpoint ='https://api-3t.sandbox.paypal.com/nvp';
    else
    	$API_Endpoint = 'https://api-aa-3t.paypal.com/nvp';

    $resArray=hash_call("doDirectPayment",$nvpstr, $APIUsername, $APIPassword, $APISignature, $API_Endpoint);
    return $resArray;
    }
    function cc_bill($cc_info, $member, $amount, $currency, $product_description, $charge_type, $invoice, $payment){

        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////


        /**
        * Get required parameters from the web form for the request
        */
        $paymentType ='Sale';
        $firstName =$cc_info['cc_name_f'];
        $lastName =$cc_info['cc_name_l'];
        $creditCardType =$cc_info['cc_type'];
        $creditCardNumber = $cc_info['cc_number'];
        $expDateMonth =substr($cc_info['cc-expire'], 0, 2);

        // Month must be padded with leading zero
        $padDateMonth = str_pad($expDateMonth, 2, '0', STR_PAD_LEFT);

        $expDateYear ='20'.substr($cc_info['cc-expire'], 2,2);
        $cvv2Number = $cc_info['cc_code'];
        $address1 = $cc_info['cc_street'];
        //$address2 = urlencode($_POST['address2']);
        $city = $cc_info['cc_city'];
        $state =$cc_info['cc_state'];
        $zip = $cc_info['cc_zip'];
        $amount1 = $amount;
        //$currencyCode=urlencode($_POST['currency']);
        $currencyCode="USD";
        //$paymentType=urlencode($_POST['paymentType']);

        /* Construct the request string that will be sent to PayPal.
        The variable $nvpstr contains all the variables and is a
        name value pair string with & as a delimiter */
        $nvpstr="&PAYMENTACTION=$paymentType&AMT=$amount1&CREDITCARDTYPE=$creditCardType&ACCT=$creditCardNumber&EXPDATE=".$padDateMonth.$expDateYear."&CVV2=$cvv2Number&FIRSTNAME=$firstName&LASTNAME=$lastName&STREET=$address1&CITY=$city&STATE=$state"."&ZIP=$zip&COUNTRYCODE=US&CURRENCYCODE=$currencyCode";
		parse_str($nvpstr,$vars);
		$vars['ACCT'] = "**** **** **** ****";
		$vars['CVV2'] = "****";
		$log[]=$vars;

        $response=$this->do_nvp_call($nvpstr);
		$log[]=$response;

        /* Display the API response back to the browser.
        If the response from PayPal was a success, display the response parameters'
        If the response was an error, display the errors received using APIError.php.
        */
        $ack = strtoupper($response["ACK"]);

        if ($ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING'){
            return array(CC_RESULT_SUCCESS, "", $response['TRANSACTIONID'], $log);
        } else {
            return array(CC_RESULT_DECLINE_PERM, $response["L_ERRORCODE0"]." ".$response[L_LONGMESSAGE0], "", $log);
        }

    }

}

function paypal_nvp_get_member_links($user){
    return cc_core_get_member_links('paypal_nvp', $user);
}

function paypal_nvp_rebill(){
    return cc_core_rebill('paypal_nvp');
}

cc_core_init('paypal_nvp');

?>