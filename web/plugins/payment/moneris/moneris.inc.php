<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Moneris payment plugin
*    FileName $RCSfile$
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_moneris extends amember_payment {
    function do_payment($payment_id, $member_id, $product_id,$price, $begin_date, $expire_date, &$vars)
	{
        return cc_core_do_payment('moneris', $payment_id, $member_id, $product_id,$price, $begin_date, $expire_date, $vars);
    }
	
	function get_cancel_link($payment_id)
	{
        return cc_core_get_cancel_link('moneris', $payment_id);
    }
	
    function get_plugin_features()
	{
        return array(
            'title' => $config['payment']['moneris']['title'] ? $config['payment']['moneris']['title'] : 'Moneris eSelect',
            'description' => $config['payment']['moneris']['description'] ? $config['payment']['moneris']['description'] : 'Credit card payment',
			'code' => 2,
			'housenumber' => 1,
			'use_moneris_template' => 1,
			'name_f' => 1
        );
    }
	
    function run_transaction($txnArray, $cvdTemplate=array(), $avsTemplate=array(), $custInfo = array())
	{
		global $db, $config;

		if (is_file($config['root_dir'].'/plugins/payment/moneris/lib/mpgclasses.php'))
			require_once($config['root_dir'].'/plugins/payment/moneris/lib/mpgclasses.php');
		else
			return;
		
		$store_id = $this->config['store_id'];
		$api_token = $this->config['api_token'];

		$mpgTxn = new mpgTransaction($txnArray);
		if ($cvdTemplate)
		{
			$mpgCvdInfo = new mpgCvdInfo($cvdTemplate);
			$mpgTxn->setCvdInfo($mpgCvdInfo);
		}
		if ($avsTemplate)
		{
			$mpgAvsInfo = new mpgAvsInfo($avsTemplate);
			$mpgTxn->setAvsInfo($mpgAvsInfo);
		}
		if($custInfo){
		    $mpgCustInfo = new mpgCustInfo($custInfo);
		    $mpgTxn->setCustInfo($mpgCustInfo);
		}
		
		$mpgRequest = new mpgRequest($mpgTxn);
		$mpgHttpPost = new mpgHttpsPost($store_id, $api_token, $mpgRequest, $this->config['testing']);
		$mpgResponse = $mpgHttpPost->getMpgResponse();
		
		return $mpgResponse;
    }
	
    function void_transaction($transaction_result, &$log, $order_id)
	{
		global $config, $db;
		$crypttype = '7';
		
        $vars = array(
            "type"       => 'purchasecorrection',
			"txn_number" => $transaction_result["TxnNumber"],
            "order_id"   => $order_id,
			"crypt_type" => $crypttype
		);
			
        $vars_l = $vars;
        $log[] = $vars_l;
        $res = $this->run_transaction($vars);
		if (is_object($res))
			$res_log = $this->moneris_get_transaction_result_array($res);
        $log[] = $res_log;
        return $res;
    }
	
	function cc_bill($cc_info, $member, $amount, $currency, $product_description, $charge_type, $invoice, $payment)
	{
        global $config, $db;
        $log = array();

        srand(time());
        if ($charge_type == CC_CHARGE_TYPE_TEST) 
            $amount = "1.00";
        if ($cc_info['cc_name_f'] == '')
		{
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }

        if(!$product_description)
		{
		    $product = $db->get_product($payment['product_id']);
		    $product_description = $product['title'];
		}
		
		$crypttype = '7';
		
        $vars = array(
            "type"       => 'purchase',
            "order_id"   => 'aMember-'.$payment['payment_id'],
            "cust_id"    => $member['member_id'],
			"amount"     => $amount,
			"pan"        => $cc_info['cc_number'],
			"expdate"    => substr($cc_info['cc-expire'],2,2).substr($cc_info['cc-expire'],0,2),
			"crypt_type" => $crypttype);

		$cvdTemplate = array(
			"cvd_indicator" => '1',
			"cvd_value" 	=> $cc_info['cc_code']);
		
		$avsTemplate = array(
			"avs_street_number" => $cc_info['cc_housenumber'],
			"avs_street_name" => $cc_info['cc_street'],
			"avs_zipcode" => $cc_info['cc_zip']);
			
		$custInfo = array(
		    'email'	=> $member['email'], 
		    'billing'   => array(
		        'first_name' 	=> $cc_info[cc_name_f],
		        'last_name'	=>	$cc_info[cc_name_l], 
		        'address'	=>	$cc_info['cc_street'],
		        'city'		=>	$cc_info['cc_city'],
		        'province' 	=>	$cc_info['cc_state'],
		        'postal_code'	=>	$cc_info['cc_zip'],
		        'country'       =>	$cc_info['cc_country']
		    )            	
		);
		
        $vars_l = array_merge($vars, $avsTemplate, $custInfo); 
        $vars_l['pan'] = $cc_info['cc'];
		
        $log[] = $vars_l;
        $res = $this->run_transaction($vars, $cvdTemplate, $avsTemplate,$custInfo);
		if (is_object($res))
			$res_log = $this->moneris_get_transaction_result_array($res);
		$log[] = $res_log;
		if ($res_log["CVDResponse"])
		{
			if ($res_log["CVDResponse"] == 'M' || $res_log["CVDResponse"] == '1M' || $res_log["CVDResponse"] == 'U' || $res_log["CVDResponse"] == '1U')
				$CVDResponse = 1;
			else
				$CVDResponse = 0;
		} else {
			$CVDResponse = 1;
		}
		
		if ($res_log["AVSResponse"])
		{
			if ($res_log["AVSResponse"] == 'A' ||
					$res_log["AVSResponse"] == 'B' ||
					$res_log["AVSResponse"] == 'D' ||
					$res_log["AVSResponse"] == 'M' ||
					$res_log["AVSResponse"] == 'P' ||
					$res_log["AVSResponse"] == 'Y' ||
					$res_log["AVSResponse"] == 'Z' || 
					$res_log["AVSResponse"] == 'X' || 
					$res_log["AVSResponse"] == 'S')
				$AVSResponse = 1;
			else
				$AVSResponse = 0;
		} else {
			$AVSResponse = 1;
		}
		
        if ($res_log && $CVDResponse && $AVSResponse && ($res_log["ResponseCode"] === 0 || ($res_log["ResponseCode"] > 0 && $res_log["ResponseCode"] <= 49))) {
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res_log, $log, $vars['order_id']);
            return array(CC_RESULT_SUCCESS, "", $res_log['TxnNumber'], $log);
        } elseif ($res_log && ($res_log["ResponseCode"] >= 50 && $res_log["ResponseCode"] <= 999)) {
			$error_message = ($message = $this->moneris_get_response_message($res_log["ResponseCode"])) ? $message : $res_log['Message'];
            return array(CC_RESULT_DECLINE_PERM, $error_message, "", $log);
        } else {
			if (!is_file($config['root_dir'].'/plugins/payment/moneris/lib/mpgclasses.php'))
				$error_message = 'mpgClasses lib not found';
			elseif ($res_log['Message'])
				$error_message = $res_log['Message'];
			else
				$error_message = 'Unknown or NULL error';
            return array(CC_RESULT_INTERNAL_ERROR, $error_message, "", $log);
        }
    }

	function moneris_get_transaction_result_array($mpgResponse)
	{
		$log = array();
		$log["CardType"] = $mpgResponse->getCardType();
		$log["TransAmount"] = $mpgResponse->getTransAmount();
		$log["TxnNumber"] = $mpgResponse->getTxnNumber();
		$log["ReceiptId"] = $mpgResponse->getReceiptId();
		$log["TransType"] = $mpgResponse->getTransType();
		$log["ReferenceNum"] = $mpgResponse->getReferenceNum();
		$log["ResponseCode"] = $mpgResponse->getResponseCode();
		$log["ISO"] = $mpgResponse->getISO();
		$log["Message"] = $mpgResponse->getMessage();
		$log["AuthCode"] = $mpgResponse->getAuthCode();
		$log["Complete"] = $mpgResponse->getComplete();
		$log["TransDate"] = $mpgResponse->getTransDate();
		$log["TransTime"] = $mpgResponse->getTransTime();
		$log["Ticket"] = $mpgResponse->getTicket();
		$log["TimedOut"] = $mpgResponse->getTimedOut();
		$log["AVSResponse"] = $mpgResponse->getAvsResultCode();
		$log["CVDResponse"] = $mpgResponse->getCvdResultCode();
		return $log;
	}
	
	function moneris_get_response_message($ResponseCode)
	{
		$ResponseArray = array(
			'0' => 'Approved, account balances included',
			'1' => 'Approved, account balances not included',
			'2' => 'Approved, country club',
			'3' => 'Approved, maybe more ID',
			'4' => 'Approved, pending ID (sign paper draft)',
			'5' => 'Approved, blind',
			'6' => 'Approved, VIP',
			'7' => 'Approved, administrative transaction',
			'8' => 'Approved, national NEG file hit OK',
			'9' => 'Approved, commercial',
			'23' => 'Amex - credit approval',
			'24' => 'Amex 77 - credit approval',
			'25' => 'Amex - credit approval ',
			'26' => 'Amex - credit approval ',
			'27' => 'Credit card approval',
			'28' => 'VIP Credit Approved',
			'29' => 'Credit Response Acknowledgement',
			'50' => 'Decline',
			'51' => 'Expired Card',
			'52' => 'PIN retries exceeded',
			'53' => 'No sharing',
			'54' => 'No security module',
			'55' => 'Invalid transaction',
			'56' => 'No Support',
			'57' => 'Lost or stolen card',
			'58' => 'Invalid status',
			'59' => 'Restricted Card',
			'60' => 'No Chequing account',
			'60' => 'No Savings account',
			'61' => 'No PBF',
			'62' => 'PBF update error',
			'63' => 'Invalid authorization type',
			'64' => 'Bad Track 2',
			'65' => 'Adjustment not allowed',
			'66' => 'Invalid credit card advance increment',
			'67' => 'Invalid transaction date',
			'68' => 'PTLF error',
			'69' => 'Bad message error',
			'70' => 'No IDF',
			'71' => 'Invalid route authorization',
			'72' => 'Card on National NEG file ',
			'73' => 'Invalid route service (destination)',
			'74' => 'Unable to authorize',
			'75' => 'Invalid PAN length',
			'76' => 'Low funds',
			'77' => 'Pre-auth full',
			'78' => 'Duplicate transaction',
			'79' => 'Maximum online refund reached',
			'80' => 'Maximum offline refund reached',
			'81' => 'Maximum credit per refund reached',
			'82' => 'Number of times used exceeded',
			'83' => 'Maximum refund credit reached',
			'84' => 'Duplicate transaction - authorization number has already been corrected by host. ',
			'85' => 'Inquiry not allowed',
			'86' => 'Over floor limit ',
			'87' => 'Maximum number of refund credit by retailer',
			'88' => 'Place call ',
			'89' => 'CAF status inactive or closed',
			'90' => 'Referral file full',
			'91' => 'NEG file problem',
			'92' => 'Advance less than minimum',
			'93' => 'Delinquent',
			'94' => 'Over table limit',
			'95' => 'Amount over maximum',
			'96' => 'PIN required',
			'97' => 'Mod 10 check failure',
			'98' => 'Force Post',
			'99' => 'Bad PBF',
			'100' => 'Unable to process transaction',
			'101' => 'Place call',
			'102' => '',
			'103' => 'NEG file problem',
			'104' => 'CAF problem',
			'105' => 'Card not supported',
			'106' => 'Amount over maximum',
			'107' => 'Over daily limit',
			'108' => 'CAF Problem',
			'109' => 'Advance less than minimum',
			'110' => 'Number of times used exceeded',
			'111' => 'Delinquent',
			'112' => 'Over table limit',
			'113' => 'Timeout',
			'115' => 'PTLF error',
			'121' => 'Administration file problem',
			'122' => 'Unable to validate PIN: security module down',
			'150' => 'Merchant not on file',
			'200' => 'Invalid account',
			'201' => 'Incorrect PIN',
			'202' => 'Advance less than minimum',
			'203' => 'Administrative card needed',
			'204' => 'Amount over maximum ',
			'205' => 'Invalid Advance amount',
			'206' => 'CAF not found',
			'207' => 'Invalid transaction date',
			'208' => 'Invalid expiration date',
			'209' => 'Invalid transaction code',
			'210' => 'PIN key sync error',
			'212' => 'Destination not available',
			'251' => 'Error on cash amount',
			'252' => 'Debit not supported',
			'426' => 'AMEX - Denial 12',
			'427' => 'AMEX - Invalid merchant',
			'429' => 'AMEX - Account error',
			'430' => 'AMEX - Expired card',
			'431' => 'AMEX - Call Amex',
			'434' => 'AMEX - Call 03',
			'435' => 'AMEX - System down',
			'436' => 'AMEX - Call 05',
			'437' => 'AMEX - Declined',
			'438' => 'AMEX - Declined',
			'439' => 'AMEX - Service error',
			'440' => 'AMEX - Call Amex',
			'441' => 'AMEX - Amount error',
			'475' => 'CREDIT CARD - Invalid expiration date',
			'476' => 'CREDIT CARD - Invalid transaction, rejected',
			'477' => 'CREDIT CARD - Refer Call',
			'478' => 'CREDIT CARD - Decline, Pick up card, Call',
			'479' => 'CREDIT CARD - Decline, Pick up card',
			'480' => 'CREDIT CARD - Decline, Pick up card',
			'481' => 'CREDIT CARD - Decline',
			'482' => 'CREDIT CARD - Expired Card',
			'483' => 'CREDIT CARD - Refer',
			'484' => 'CREDIT CARD - Expired card - refer',
			'485' => 'CREDIT CARD - Not authorized',
			'486' => 'CREDIT CARD - CVV Cryptographic error',
			'487' => 'CREDIT CARD - Invalid CVV',
			'489' => 'CREDIT CARD - Invalid CVV',
			'490' => 'CREDIT CARD - Invalid CVV',
			'800' => 'Bad format',
			'801' => 'Bad data',
			'802' => 'Invalid Clerk ID',
			'809' => 'Bad close ',
			'810' => 'System timeout',
			'811' => 'System error',
			'821' => 'Bad response length',
			'877' => 'Invalid PIN block',
			'878' => 'PIN length error',
			'880' => 'Final packet of a multi-packet transaction',
			'881' => 'Intermediate packet of a multi-packet transaction',
			'889' => 'MAC key sync error',
			'898' => 'Bad MAC value',
			'899' => 'Bad sequence number - resend transaction',
			'900' => 'Capture - PIN Tries Exceeded',
			'901' => 'Capture - Expired Card',
			'902' => 'Capture - NEG Capture',
			'903' => 'Capture - CAF Status 3',
			'904' => 'Capture - Advance < Minimum',
			'905' => 'Capture - Num Times Used',
			'906' => 'Capture - Delinquent',
			'907' => 'Capture - Over Limit Table',
			'908' => 'Capture - Amount Over Maximum',
			'909' => 'Capture - Capture',
			'960' => 'Initialization failure - merchant number mismatch',
			'961' => 'Initialization failure -pinpad  mismatch',
			'963' => 'No match on Poll code',
			'964' => 'No match on Concentrator ID',
			'965' => 'Invalid software version number',
			'966' => 'Duplicate terminal name'
			);
		if ($ResponseArray[$ResponseCode])
			return $ResponseArray[$ResponseCode];
		else
			return '';
	}
}

function moneris_get_member_links($user)
{
    return cc_core_get_member_links('moneris', $user);
}

function moneris_rebill()
{
    return cc_core_rebill('moneris');
}
                                        
//if($_SERVER['REMOTE_ADDR']=='82.116.39.130')
cc_core_init('moneris');

?>