<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alexander Smith
*      Email: alexander@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Saferpay payment plugin
*    FileName $RCSfile$
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

class payment_saferpay extends amember_payment {
    var $title       = "Saferpay";
    var $description = "Credit card payments";
    var $fixed_price = 0;
    var $recurring   = 1;
    var $built_in_trials = 1;
    function do_recurring_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars)
    {
    	global $db;
	// the hosting gateway URL to execute authorization requests
	$execute = "https://www.saferpay.com/hosting/Execute.asp";
	// set the payment attributes and create hosting URL
        $product = & get_product($product_id);
        if (count($orig_product_id)>1)
            $product->config['title'] = $config['multi_title'];
	$member=$db->get_user($member_id);
	$params =
	"spPassword=".($this->config['testing'] ? "XAjc3Kna" : $this->config['password']). // special hosting password
	"&ACCOUNTID=".($this->config['testing'] ? "99867-94913159" : $this->config['merchant']). // saferpay account id
	"&AMOUNT=".intval($price*100). // 12.95 */
	"&CURRENCY=EUR". // 3-letter currency code
	"&PAN=".$member['data']['saferpay_user_pan']. // credit card number
	"&EXP=".$member['data']['saferpay_user_expyear'].$member['data']['saferpay_user_expmonth']. // expiry date YYMM
	//"&CVC=474". // CVC2/CVV
	"&DESCRIPTION=". // sales description
	urlencode($product->config['title']).
	"&RESPONSEFORMAT=URL"; // get response data as URL encoded parameters
	
	// execute the online authorization and retrieve result from hosting server
	$response = get_url($execute,$params);//join("", file($url));
	echo $params;
	echo $response;
	if (substr($response, 0, 2) == "OK") // check if result is OK...
	{
		parse_str(substr($response, 3));
		// $RESULT = result of transaction
		// $ID = saferpay transaction identifier, store in DBMS
		// check saferpay result code of authorization (0 = success)
		if ($RESULT == 0) {
			// the hosting gateway URL to control payments
			$capture = "https://www.saferpay.com/hosting/PayComplete.asp";
			$params = "ACCOUNTID=".$this->config['merchant']."&spPassword=".$this->config['password']."&ID=$ID";
			$response = get_url($capture,$params);//join("", file($url)); // complete payment by hosting server
			if (substr($response, 0, 2) == "OK") {
				//finish waiting payment
				return;
			} else {
				$db->log_error("Error: retry capture later ($response)");
				return "Error: retry capture later ($response)";
			}
		} else {
			$db->log_error("Authorization failed (result code $RESULT)");
			return "Authorization failed (result code $RESULT)";
		}
	} else { // ...or if an error happened
		$db->log_error("Application Error: $response");
		return "Application Error: $response";
	}
    }
    
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
	$user=$db->get_user($member_id);
	
	if($user['data']['saferpay_user_pan'] && $product->config['is_recurring'])
			return $this->do_recurring_payment($payment_id, $member_id, $product_id,$price, $begin_date, $expire_date, $vars);

        $orig_product_id = $product_id;
        if (is_array($product_id))
             $product_id = $product_id[0];
        $product = & get_product($product_id);
        if (count($orig_product_id)>1)
            $product->config['title'] = $config['multi_title'];
        $u  = $db->get_user($member_id);
        if ($this->config['testing'])
		$accountid = "99867-94913159";
	else
		$accountid = $this->config['merchant'];
        $vars = array(
            'ACCOUNTID'   => $accountid,
            'AMOUNT' => intval($price*100), 
            'CURRENCY'     => "EUR",
			'DESCRIPTION' => $product->config['title'],
            //'NOTIFYURL'      => $config['root_url']."/plugins/payment/saferpay/ipn.php",
            'SUCCESSLINK'      => $config['root_url']."/plugins/payment/saferpay/thanks.php",
            'FAILLINK'      => $config['root_url']."/cancel.php",
            'BACKLINK'      => $config['root_url']."/cancel.php",
            'CCCVC' => "yes", 
            'CCNAME'     => "yes",
			'ORDERID' => $payment_id,
			'CARDREFID' => 'new',
        );

        $vars1 = array();
		$vars_log=$vars;
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
		$payment_url = get_url("https://www.saferpay.com/hosting/CreatePayInit.asp",$vars);
		
		if( strtolower( substr( $payment_url, 0, 36 ) ) != "https://www.saferpay.com/vt/pay.asp?" ) 
		{
			fatal_error("PHP-CURL is not working correctly for outgoing SSL-calls on your server");
		}

        header("Location: $payment_url");
        exit();
    }
    function log_debug($vars){
        global $db;
        $s = "Saferpay DEBUG:<br />\n";
        foreach ($vars as $k=>$v)
            $s .= "[$k] => '$v'<br />\n";
        $db->log_error($s);
    }
    function validate_thanks(&$vars){
        global $db;
	if ($this->config['testing'])
		$accountid = "99867-94913159";
	else
		$accountid = $this->config['merchant'];
        $this->log_debug($vars);
		$vt_data = $vars["DATA"];
		$vt_signature = $vars["SIGNATURE"];
		if( substr($vt_data, 0, 15) == "<IDP MSGTYPE=\\\"" ) 
			$vt_data = stripslashes($vt_data);
		$saferpay_payconfirm_gateway = "https://www.saferpay.com/hosting/VerifyPayConfirm.asp";
		$payconfirm_url = $saferpay_payconfirm_gateway . "?DATA=" . urlencode($vt_data) . "&SIGNATURE=" . urlencode($vt_signature);
		//
		$cs = curl_init($payconfirm_url);
		curl_setopt($cs, CURLOPT_PORT, 443);		// set option for outgoing SSL requests via CURL
		curl_setopt($cs, CURLOPT_SSL_VERIFYPEER, false); // ignore SSL-certificate-check - session still SSL-safe
		curl_setopt($cs, CURLOPT_HEADER, 0);	// no header in output
		curl_setopt ($cs, CURLOPT_RETURNTRANSFER, true); // receive returned characters
		$verification = curl_exec($cs);
		curl_close($cs);
		
		//
		if( strtoupper( substr( $verification, 0, 3 ) ) != "OK:" ) 
		{
			$db->log_error("Saferpay confirmation failed. $verification");
			return "Confirmation failed. $verification";
		}
		// delete starting and trailing markes <IDP .... />
		$data = ereg_replace("^<IDP( )*", "", $vt_data);
		$data = ereg_replace("( )*/( )>$", "", $data);
		$data = trim($data);
		$vt_xml = array();
		while(strlen($data) > 0)
		{
			$pos = strpos($data, "=\"");
			$name = substr($data, 0, $pos); // get attribute name
			$data = substr($data, $pos + 2); // skip ="
			$pos = strpos($data, "\"");
			$value = substr($data, 0, $pos); // get attribute value 
			$data = substr($data, $pos + 1); // skip "
			$data = trim($data);
			$vt_xml[$name] = $value;
		}
		if( !$vt_xml["ORDERID"]) {
			die("ORDER ID wrong, possible manipulation");
		}
		$payment = $db->get_payment($vt_xml["ORDERID"]);
		// **************************************************
 		// * Compare values
		// **************************************************
		if( $vt_xml["ACCOUNTID"] != $accountid ) {
			$db->log_error("ACCOUNTID wrong, possible manipulation");
			return "ACCOUNTID wrong, possible manipulation";
		}
		elseif( $vt_xml["AMOUNT"] != intval($payment['amount']*100) ) {
			$db->log_error("AMOUNT wrong, possible manipulation");
			return "AMOUNT wrong, possible manipulation";
		}
		//print_r($vt_xml);
		$eci = $vt_xml["ECI"];	
		// * Payment Capturing
		$vpc = array();
		parse_str( substr( $verification, 3), $vpc );
		//print_r($vpc);
		$saferpay_paycomplete_gateway = "https://www.saferpay.com/hosting/PayComplete.asp";
		$vt_id = $vpc["ID"];
		$vt_token = $vpc["TOKEN"];
		$paycomplete_url = $saferpay_paycomplete_gateway . "?ACCOUNTID=" . $accountid; 
		$paycomplete_url .= "&ID=" . urlencode($vt_id) . "&TOKEN=" . urlencode($vt_token);
		if ($this->config['testing']) {
			$paycomplete_url .= "&spPassword=XAjc3Kna";
		}
		$cs = curl_init($paycomplete_url);
		curl_setopt($cs, CURLOPT_PORT, 443);			// set option for outgoing SSL requests via CURL
		curl_setopt($cs, CURLOPT_SSL_VERIFYPEER, false);	// ignore SSL-certificate-check - session still SSL-safe
		curl_setopt($cs, CURLOPT_HEADER, 0);			// no header in output
		curl_setopt ($cs, CURLOPT_RETURNTRANSFER, true);	// receive returned characters
		$answer = curl_exec($cs);
		curl_close($cs); 
		if( strtoupper( $answer ) != "OK" ) {
			$db->log_error("Confirmation OK - Capture failed, $answer");
			return "Confirmation - Capture failed";
		}
		$vars+=$vt_xml;
		$err = $db->finish_waiting_payment($vt_xml["ORDERID"], 'saferpay',$vt_xml['ID'], $payment['amount'], $vars);
		//print_r($vars);
		if ($err)
		{
			$db->log_error("Finish_waiting_payment error: $err");
			return "Finish_waiting_payment error: $err";
		}
		$member=$db->get_user($payment['member_id']);
		$member['data']['saferpay_user_pan'] = $vt_xml['CARDREFID'];
		$member['data']['saferpay_user_expmonth'] = $vt_xml['EXPIRYMONTH'];
		$member['data']['saferpay_user_expyear'] = $vt_xml['EXPIRYYEAR'];
		$db->update_user($member['member_id'],$member);
	}
	
    function init(){
        parent::init();
		add_member_field('saferpay_user_pan', '', 'hidden');
		add_member_field('saferpay_user_expmonth', '', 'hidden');
		add_member_field('saferpay_user_expyear', '', 'hidden');
    }
	
}
$pl = & instantiate_plugin('payment', 'saferpay');

?>
