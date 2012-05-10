<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: authorize_mobile payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.amember.com/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

class payment_paypal_mobile extends amember_payment {
    var $title = 'PayPal';
    var $description = 'PayPal Mobile Checkout';
    var $fixed_price = 0;
    var $recurring = 0;

    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars){
	global $config, $db;

	$payment = $db->get_payment($payment_id);
	$product = $db->get_product($product_id);
	$member  = $db->get_user($member_id);
	
	$nvp = array(
		'AMT' 		=> $price,
		'CURRENCYCODE' 	=> $product['paypal_mobile_currency'] ? $product['paypal_mobile_currency'] : 'USD',
		'DESC' 		=> substr($product['title'], 0, 127),
		'NUMBER'	=> $product_id,
		'CUSTOM' 	=> $payment_id,
		'INVNUM' 	=> $payment_id,
		'RETURNURL' 	=> $config['root_url'].'/plugins/payment/paypal_mobile/thanks.php',
		'CANCELURL' 	=> $config['root_url'].'/cancel.php',
		'EMAIL' 	=> substr($member['email'], 0, 127)
	);

	$redirect_URL = "https://mobile.paypal.com/wc?t=";

	if ($this->config['testing']){
		$payment['data'][] = $nvp;
		$db->update_payment($payment['payment_id'], $payment);
		$redirect_URL = "https://www.sandbox.paypal.com/wc?t=";
	}

        $nvpstr = array();
	foreach ($nvp as $k=>$v){
		$nvpstr[] = urlencode($k) . '=' . urlencode($v);
	}
	$nvpstr = implode ('&', $nvpstr);

	$response = $this->nvp_api_call("SetMobileCheckout", $nvpstr);
        $token = $response["TOKEN"];

	if ($token){
		$payment['data']['token'] = $token;
		$db->update_payment($payment['payment_id'], $payment);

		header("Location: " . $redirect_URL . $token);
		exit;
	} else {
		if ($this->config['testing'])
			$db->log_error("PayPal Mobile Checkout ERROR: SetMobileCheckout \$response=<br />" . $this->get_dump($response));
		fatal_error("PayPal Mobile Checkout ERROR. Please contact site Administrator.");
	}
        
    }

    function process_thanks(&$vars){
        global $db;
        if ($this->config['testing'])
		$db->log_error("PayPal Mobile DEBUG: process_thanks \$vars=<br />" . $this->get_dump($vars));

	$token = $vars['token'];
	$payment_id = $db->get_payment_by_data('token', $token);
	$payment = $db->get_payment($payment_id);

	$nvp = array(
		'TOKEN'	=> $payment['data']['token']
	);

        $nvpstr = array();
	foreach ($nvp as $k=>$v){
		$nvpstr[] = urlencode($k) . '=' . urlencode($v);
	}
	$nvpstr = implode ('&', $nvpstr);

	$response = $this->nvp_api_call("DoMobileCheckoutPayment", $nvpstr);

	if ($this->config['testing'])
		$db->log_error("PayPal Mobile Checkout DEBUG: DoMobileCheckoutPayment \$response=<br />" . $this->get_dump($response));

	$payment['data'][] = $response;
	$db->update_payment($payment['payment_id'], $payment);

	if ($response['PAYMENTSTATUS'] != 'Completed')
		return "PayPal Mobile Checkout ERROR: [" . $response['PAYMENTSTATUS'] . "] " . $response['L_SHORTMESSAGE0'];

	$err = $db->finish_waiting_payment($payment_id, 'paypal_mobile', $response['RECEIPTID'], '', $vars);
	if ($err)
		return "PayPal Mobile Checkout ERROR: " . $err;

	$GLOBALS['vars']['payment_id'] = $payment_id;

    }

    function init(){
        parent::init();

        add_product_field('paypal_mobile_currency',
            'PayPal Mobile Currency',
            'select',
            'valid only for PayPal Mobile processing.<br /> You should not change it<br /> if you use
            another payment processors',
            '',
            array('options' => array(
		'USD' => 'U.S. Dollar',
		'EUR' => 'Euro',
		'GBP' => 'Pound Sterling',
		'CAD' => 'Canadian Dollar',
		'AUD' => 'Australian Dollar',
		'CHF' => 'Swiss Franc',
		'CZK' => 'Czech Koruna',
		'DKK' => 'Danish Krone',
		'HKD' => 'Hong Kong Dollar',
		'HUF' => 'Hungarian Forint',
		'JPY' => 'Japanese Yen',
		'NOK' => 'Norwegian Krone',
		'NZD' => 'New Zealand Dollar',
		'PLN' => 'Polish Zloty',
		'SEK' => 'Swedish Krona',
		'SGD' => 'Singapore Dollar'
            	))
            );
        add_payment_field('token', 'PayPal Mobile Token',
            'readonly', 'internal');

    }

	function nvp_api_call($methodName, $nvpStr){

		$version = '56.0';

		$API_UserName  = $this->config['api_user'];
		$API_Signature = $this->config['api_sig'];
		$API_Password  = $this->config['api_pass'];
		if ($this->config['testing'])
			$API_Endpoint ='https://api-3t.sandbox.paypal.com/nvp';
		else
			$API_Endpoint = 'https://api-3t.paypal.com/nvp';

		$nvpreq = "METHOD=".urlencode($methodName)."&VERSION=".urlencode($version)."&PWD=".urlencode($API_Password)."&USER=".urlencode($API_UserName)."&SIGNATURE=".urlencode($API_Signature)."&".$nvpStr;

		$response = get_url($API_Endpoint, $nvpreq);
		$nvpResArray = $this->deformatNVP($response);
		return $nvpResArray;
	}

	function deformatNVP($nvpstr){

		$intial = 0;
	 	$nvpArray = array();


		while(strlen($nvpstr)){
			//postion of Key
			$keypos= strpos($nvpstr, '=');
			//position of value
			$valuepos = strpos($nvpstr, '&') ? strpos($nvpstr, '&') : strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr, $intial, $keypos);
			$valval=substr($nvpstr, $keypos + 1, $valuepos - $keypos - 1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] = urldecode( $valval);
			$nvpstr=substr($nvpstr, $valuepos + 1, strlen($nvpstr));
	     }
		return $nvpArray;
	}


}

$pl = & instantiate_plugin('payment', 'paypal_mobile');
?>
