<?php 

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: secpay payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3456 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");


class payment_secpay extends payment {
function reparse($str)
{
	$f = split("[&?]",$str);
	foreach($f as $v)
	{
		$s = split("[=]",$v,2);
		$r[$s[0]] = $s[1];
	}	
	return $r;
}	
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('secpay', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('secpay', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['secpay']['title'] ? $config['payment']['secpay']['title'] : _PLUG_PAY_SECPAY_TITLE,
            'description' => $config['payment']['secpay']['description'] ? $config['payment']['secpay']['description'] : _PLUG_PAY_SECPAY_DESC,
            'code' => 2,
            'name_f' => 2,
            'currency' => array('USD' => 'USD', 'EUR' => 'EUR', 'GBP' =>'GBP'),
            'start_date' => 2, 
			'maestro_solo_switch' => 1,
            'type_options' => array('Visa' => 'Visa', 'Master Card' => 'Master Card', 'Switch / UK Maestro'=>'Switch / UK Maestro', 'Maestro'=>'Maestro', 'Solo'=>'Solo', 'Delta'=>'Delta', 'JCB'=>'JCB')

        );
    }
	    function run_transaction_old($vars, $method='validateCardFull'){
        $client = new soapclient($u = 'https://www.secpay.com/java-bin/services/SECCardService?wsdl', true);

        $err = $client->getError();
        if ($err) {
            return array('message' => sprintf(_PLUG_PAY_SECPAY_ERROR, $err));
        }
        $result = $client->call($method, $vars, "http://www.secpay.com");
        // Check for a fault
        if ($client->fault) {
            if ($err) {
                return array('message' => _PLUG_PAY_SECPAY_ERROR2 . join(' - ', array_values($result)) );
            }
        } else {
            // Check for errors
            $err = $client->getError();
            if ($err) {
                return array('message' => sprintf(_PLUG_PAY_SECPAY_ERROR3, $err) );
            } else {    
                if ($result[0] == '?')
                    $result = substr($result, 1);
                $ret = $this->reparse($result);
                return $ret;
            }
        }
    }

    function run_transaction($vars, $method='threeDSecureEnrolmentRequest'){
        $client = new soapclient($u = 'https://www.secpay.com/java-bin/services/SECCardService?wsdl', true);
        $err = $client->getError();
        if ($err) {
            return array('message' => sprintf(_PLUG_PAY_SECPAY_ERROR, $err));
        }
        $result = $client->call($method, $vars, "http://www.secpay.com");
        // Check for a fault
        if ($client->fault) {
            if ($err) {
                return array('message' => _PLUG_PAY_SECPAY_ERROR2 . join(' - ', array_values($result)) );
            }
        } else {
            // Check for errors
            $err = $client->getError();
            if ($err) {
                return array('message' => sprintf(_PLUG_PAY_SECPAY_ERROR3, $err) );
            } else {    
                if ($result[0] == '?')
                    $result = substr($result, 1);
                $ret = $this->reparse($result);
                return $ret;
            }
        }
    }

    function void_transaction($trans_id, $amount, &$log){
        $vars = array(
            'mid' => $this->config['id'],        // Test MerchantId
            'vpn_pass' => $this->config['pass'], // VPN password
            'remote_pswd' => $this->config['remote_pass'],
            'trans_id' => $trans_id, // merchants transaction id
            'new_trans_id' => "{$trans_id}_RFND",
            'amount' => $amount, // Amount
        );
        $vars_l = $vars;
        $log[] = $vars_l;
        $res = $this->run_transaction($vars, 'refundCardFull');
        $log[] = $res;
        return $res;
    }
    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config,$db;
        $log = array();
        //////////////////////// cc_bill /////////////////////////
        require_once($config['root_dir']."/plugins/payment/secpay/lib/nusoap.php");

        srand(time());
        
        if ($charge_type == CC_CHARGE_TYPE_TEST) 
            $amount = "1.00";
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }
		if ($charge_type == CC_CHARGE_TYPE_RECURRING)
		{
			$new_payment = $db->get_payment($invoice);
			$x = preg_split('/ /', $new_payment['data'][0]['RENEWAL_ORIG']);
			$old_invoice = $x[1];

			$vars = array(
				'mid' => $this->config['id'],        // Test MerchantId
				'vpn_pswd' => $this->config['pass'], // VPN password
				'trans_id' => "AMEMBER$old_invoice", // merchants transaction id
				'amount' => $amount, // Amount
				'remote_pswd' => $this->config['remote_pass'], 
				'new_trans_id' => "AMEMBER$invoice", 
				'exp_date' => substr($new_payment['end_date'],2,2).substr($new_payment['end_date'],5,2), 
				'order' => "prod=$product_description,item_amount=$amount" //prod=funny_book,item_amount=18.50x1;prod=sad_book,item_amount=16.50x2 
				);
		}
		else
		{
			$vars = array(
				'mid' => $this->config['id'],        // Test MerchantId
				'vpn_pswd' => $this->config['pass'], // VPN password
				'trans_id' => "AMEMBER$invoice", // merchants transaction id
				'ip' => $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'], // The ip of the original caller
				'name' => $cc_info['cc_name_f'] . ' ' . $cc_info['cc_name_l'], // Card Holders Name
				'card_number' => $cc_info['cc_number'], // Card number
				'amount' => $amount, // Amount
				'expiry_date' => $cc_info['cc-expire'], // Expiry Date
				'issue_number' => $cc_info['cc_issuenum'], // Issue (Switch/Solo only)
				'start_date' => $cc_info['cc-start_date'], // Start Date
				'order' => $product_description, // Order Item String
				'shipping' => "name=".$cc_info['cc_name_f']."+".$cc_info['cc_name_l'].",addr_1=".urlencode($cc_info['cc_street']).",city=".urlencode($cc_info['cc_city']).",state=".urlencode($cc_info['cc_state']).",post_code=".urlencode($cc_info['cc_zip']).",email=".$member['email']."",
				'billing' => "name=".$cc_info['cc_name_f']."+".$cc_info['cc_name_l'].",addr_1=".urlencode($cc_info['cc_street']).",city=".urlencode($cc_info['cc_city']).",state=".urlencode($cc_info['cc_state']).",post_code=".urlencode($cc_info['cc_zip']).",email=".$member['email']."",
				'options' => "dups=false", // Options String
				'device_category' => "0", // web-borwser type
				'accept_headers' => $_SERVER['HTTP_ACCEPT'],
				'user_agent' => $_SERVER['HTTP_USER_AGENT'],
				'mpi_merchant_name' => $config['site_title'], // Options String
				'mpi_merchant_url' => $config['root_url'], // Options String
				'mpi_description' => $product_description, // Options String
				'purchaseRecurringFrequency' => "1", // Options String
				'purchaseRecurringExpiry' => str_replace("-","",$payment['expire_date']),//date("Ymd"), // Options String			
				'purchaseInstallments' => "dups=false" // Options String			
			);
		}
        $vars[options] .= ",currency=".($currency ? $currency : 'USD');
        switch ($this->config['testing']){
            case 1: $vars['options'] .= ",test_status=true,test_mpi_status=true"; break;
            case 2: $vars['options'] .= ",test_status=false"; break;
        }
        // prepare log record
        $vars_l = $vars; 
		if($cc_info['cc'])
			$vars_l['card_number'] = $cc_info['cc'];
        $log[] = $vars_l;
        /////
		if ($charge_type == CC_CHARGE_TYPE_RECURRING)
			$res = $this->run_transaction($vars,"repeatCardFull");
		else
			$res = $this->run_transaction($vars);
        $log[] = $res;
    
        if ($res['valid'] == 'true' ){
			if ($res['mpi_status_code']==200)
			{
				if( $charge_type == CC_CHARGE_TYPE_START_RECURRING || $charge_type == CC_CHARGE_TYPE_REGULAR)
				{
					$payment_log = $db->get_payment($invoice);
					foreach ($log as $v)
						$payment_log['data'][] = $v;
					$payment_log['receipt_id'] = "redirect_to_acs_url";
					$db->update_payment($payment_log['payment_id'], $payment_log);
					setcookie($res['MD'], $invoice, time()+1036800, '/');
					$t = &new_smarty();
//					$t->assign('pareq', str_replace(" ","+",$res['PaReq']));
					$t->assign('pareq', $res['PaReq']);
					$t->assign('termurl', $config['root_url']."/plugins/payment/secpay/thanks.php");
					$t->assign('md', $res['MD']);
					$t->assign('acs_url', urldecode($res['acs_url']));
					$t->display(dirname(__FILE__) . '/3dsecure.html');
					exit();
				}
				else
				{
					return array(CC_RESULT_DECLINE_TEMP, $res['message'], "", $log);
				}
				//return;
			}
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res['trans_id'], $res['amount'], $log);
            return array(CC_RESULT_SUCCESS, "", $res['auth_code'], $log);
        } 
		elseif ($res['valid'] == 'false') 
		{
			if($charge_type == CC_CHARGE_TYPE_RECURRING && $cc_info['cc'])
			{
				$payment_log = $db->get_payment($invoice);
				foreach ($log as $v)
					$payment_log['data'][] = $v;
				$payment_log['receipt_id'] = "redirect_to_acs_url";
				$db->update_payment($payment_log['payment_id'], $payment_log);
				return $this->cc_bill_old($cc_info, $member, $amount,$currency, $product_description,$charge_type, $invoice, $payment);
			}
			else
            return array(CC_RESULT_DECLINE_TEMP, $res['message'], "", $log);
        } else {
            return array(CC_RESULT_DECLINE_TEMP, $res['message'], "", $log);
        }
    }
	
	function cc_bill_old($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////
        require_once($config['root_dir']."/plugins/payment/secpay/lib/nusoap.php");

        srand(time());
        
        if ($charge_type == CC_CHARGE_TYPE_TEST) 
            $amount = "1.00";
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }
        $vars = array(
            'mid' => $this->config['id'],        // Test MerchantId
            'vpn_pswd' => $this->config['pass'], // VPN password
            'trans_id' => "AMEMBER$invoice", // merchants transaction id
            'ip' => $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'], // The ip of the original caller
            'name' => $cc_info['cc_name_f'] . ' ' . $cc_info['cc_name_l'], // Card Holders Name
            'card_number' => $cc_info['cc_number'], // Card number
            'amount' => $amount, // Amount
            'expiry_date' => $cc_info['cc-expire'], // Expiry Date
            'issue_number' => $cc_info['issue_number'], // Issue (Switch/Solo only)
            'start_date' => $cc_info['cc-start_date'], // Start Date
            'order' => $product_description, // Order Item String
            'shipping' => "", // Shipping Address
            'billing' => "name=".$cc_info['cc_name_f']."+".$cc_info['cc_name_l'].",addr_1=".urlencode($cc_info['cc_street']).",city=".urlencode($cc_info['cc_city']).",state=".urlencode($cc_info['cc_state']).",post_code=".urlencode($cc_info['cc_zip']).",email=".$member['email']."",
            'options' => "dups=false", // Options String
        );
        $vars[options] .= ",currency=".($currency ? $currency : 'USD');
        switch ($this->config['testing']){
            case 1: $vars['options'] .= ",test_status=true"; break;
            case 2: $vars['options'] .= ",test_status=false"; break;
        }
        if ($cc_info['cc_code'])
            $vars['options'] .= ",cv2=$cc_info[cc_code],req_cv2=true";
        if ($cc_info['cc_type']){
            $vars['options'] .= ",card_type=$cc_info[cc_type]";
        }
        if($cc_info['cc_start_date']){
            $vars["start_date"] = $cc_info['cc-start-date'];
        }
        // prepare log record
        $vars_l = $vars; 
        $vars_l['card_number'] = $cc_info['cc'];
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;
    
        if ($res['valid'] == 'true' ){
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res['trans_id'], $res['amount'], $log);
            return array(CC_RESULT_SUCCESS, "", $res['auth_code'], $log);
        } elseif ($res['valid'] == 'false') {
            return array(CC_RESULT_DECLINE_TEMP, $res['message'], "", $log);
        } else {
            return array(CC_RESULT_DECLINE_TEMP, $res['message'], "", $log);
        }
    }

	
	function handle_postback($vars)
	{
		global $db,$config;
		$log = array();
		require_once($config['root_dir']."/plugins/payment/secpay/lib/nusoap.php");
		
	    if(!isset($vars['MD'])){
			print "Post error";
			exit();
		}
		if(!isset($_COOKIE[$vars['MD']])){
			print "Cookie error";
			$db->log_error("Cookie not set for MD=".$vars['MD']);
			exit();
		}
		
		$payment_id = $_COOKIE[$vars['MD']];
		if (!$payment_id){
			print "Payment not found";
			$db->log_error("Payment not found for MD=".$vars['MD']);
			exit();
		}
		
		$payment = $db->get_payment($payment_id);
		//write logs
		$log[] = $vars;

		$sendvars=array(
			"mid" => $this->config['id'],        // Test MerchantId
			"vpn_pswd" => $this->config['pass'], // VPN password
			"trans_id" => "AMEMBER$payment_id", // merchants transaction id
			"md" => $vars['MD'],
			"paRes" => $vars['PaRes'],
			"options" => ""
			);		
        switch ($this->config['testing']){
            case 1: $sendvars['options'] .= "test_status=true,test_mpi_status=true"; break;
            case 2: $sendvars['options'] .= "test_status=false"; break;
        }
		$res = $this->run_transaction($sendvars,"threeDSecureAuthorisationRequest");
		$log[] = $sendvars;
		//write logs and payment
		$log[] = $res;
		foreach ($log as $v)
			$payment['data'][] = $v;
		$db->update_payment($payment_id, $payment);
		
		if($res['mpi_status_code']!=237 || $res['valid'] != "true")
		{
			print $res['mpi_message'];
			$db->log_error($res['mpi_message']." for payment #".$payment_id);
			exit();
		}

		$invoice = $payment_id;		
		// process payment
		$err = $db->finish_waiting_payment($invoice, 'secpay', $vars['MD'], $payment['amount'], $vars);
		if ($err)
			$this->secpay_error("finish_waiting_payment error: $err");
		
		$t = & new_smarty();
		
		$t->assign('payment', $payment);
		if ($payment) {
			$t->assign('product', $db->get_product($payment['product_id']));
			$t->assign('member', $db->get_user($payment['member_id']));
		}
		if (!($prices = $payment['data'][0]['BASKET_PRICES'])){
			$prices = array($payment['product_id'] => $payment['amount']);
		}
		$pr = array();
		$subtotal = 0;
		foreach ($prices as $product_id => $price){
			$v  = $db->get_product($product_id);
			//        $v['price'] = $price;
			$subtotal += $v['price'];
			$pr[$product_id] = $v;
		}
		$t->assign('subtotal', $subtotal);
		$t->assign('total', array_sum($prices));
		$t->assign('products', $pr);
		$t->display("thanks.html");
	}

	
	function secpay_error($msg){
		global $txn_id, $invoice;
		global $vars;
		fatal_error("Secpay ERROR: $msg (Details: transID: $vars[MD], payment_id: $invoice)");
	}
}

function secpay_get_member_links($user){
    return cc_core_get_member_links('secpay', $user);
}

function secpay_rebill(){
    return cc_core_rebill('secpay');
}

cc_core_init('secpay');
?>
