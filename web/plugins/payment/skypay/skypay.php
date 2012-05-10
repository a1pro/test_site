<?php
if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

class Skypay {

	private	$_html = '';

	private $_postErrors = array();

	private $_registrationUrl = "https://secure.skypay.co.uk/api/transactionRegister.cgi";

	private $skypayTransactionTable = '';

	private $transactionRecordId;
	
	private $config;

	public function  __construct($config) {
		global $db;
		$this->config = $config;
		$this->skypayTransactionTable = $db->config[prefix] . 'skypay_payment_transaction';
	}
	
	public function hookPayment($payment){
		global $t, $config;

		// Build post array
		$postData = $this->buildRegistrationPost($payment);
		// register the transaction intent
		$response = $this->requestPost($postData);

		// convert the response text into an array
		$registrationResponse = $this->readRegistrationResponse($response);

		
		if($registrationResponse['Result'] == "OK"){
			$this->updateTransactionIDKey($registrationResponse['TransactionID'], $registrationResponse['TransactionKey']);
			$t->assign(array(
				'result'		=> 1,
				'testmode'		=> $this->config['testMode'],
				'paymentUrl'	=> $registrationResponse['PaymentURL'],
				'transactionID'	=> $registrationResponse['TransactionID'],
				'encBlock'		=> $registrationResponse['EncBlock']
			));
		}else{
			$email_subject = 'Failed transaction registration ' . @$registrationResponse['ErrNum'];
			$email_error = "There was an error registering the Skypay payment transaction" . chr(10);
			$email_error .= chr(10) . "Data sent:" . chr(10) .print_r($postData,1). chr(10) . "Response: ".print_r($response,1). chr(10). chr(10);

			$t->assign(array(
				'result' => 0,
				'errNum' => $registrationResponse['ErrNum'],
				'errStr' => $registrationResponse['ErrStr']
			));
			// send an email to the store owner
			$this->informVendor($email_subject, $email_error);
		}

		if($this->config['testMode']){
			$t->assign(array('successForm'=>$this->getPostTestSuccessResponseForm($registrationResponse['TransactionKey'])));
		}

		$t->assign('config', $config);
		$t->assign('display_receipt', true);
		$t->assign('display_address', true);
		$t->assign('payment', $payment);
//print_r($registrationResponse); exit;
		$t->display(dirname(__FILE__)."/templates/skypay_pay.html");
	}

	//-------------------------------- SKYPAY TRANSACTION
	/**
     * Build post array, urlencode it and put into string, record request into the db
     *
    **/
    function buildRegistrationPost($payment){
		global $db, $config;
		$product_id = $payment['product_id'];
        $orig_product_id = $product_id;
        if (is_array($product_id))
             $product_id = $product_id[0];

        $product = & get_product($product_id);
		
        if (count($orig_product_id)>1)
            $product->config['title'] = $config['multi_title'];

        $member = $db->get_user($payment['member_id']);

        // add currency code
        if (strlen($product->config['skypay_currency'])){
            $currencyCode = $product->config['skypay_currency'];
        } else {
            $currencyCode = 'GBP';
        }
		/*---------------------------------------------------------*/
		$address = $payment['data']['billing'];
		
        $postVariables = array();
        $postVariables['Username'] = $this->config['username'];
        $postVariables['Password'] = $this->config['password'];
        $postVariables['TestMode'] = $this->config['testMode'];
		// total with shipping and discounts
		$total = number_format($payment['amount'], 2, '.', '');
        $amount = (int)($total*100);
        if(!$this->config['dispatch']) {
            if(!$dispatchAmount = (int)($this->config['dispatchAmount']*100)){
                $dispatchAmount = $amount; // set whole amount if unset or set to zero in config
            }
            $postVariables['Dispatch'] = 0;
            $postVariables['DispatchAmount'] = $dispatchAmount; // Nominal amount to test card validity
        }else{
            $postVariables['Dispatch'] = 1;
            $postVariables['DispatchAmount'] = $amount; // TODO Check if it is needed when Dispatch set to 1
        }
        $postVariables['Amount'] = $amount;
        $postVariables['CardName'] = $address['name_f'] . ' ' . $address['name_l'];
        $fullAddress = '';
        if($address['street']) $fullAddress .= $address['street'];
        if($address['street2']) $fullAddress .= "," . $address['street2'];
        if($address['city']) $fullAddress .= "," . $address['city'];
        if($address['country']) $fullAddress .= "," . $db->query_one("SELECT title FROM {$db->config[prefix]}countries WHERE country='{$address['country']}'");
        $postVariables['CardAddress'] = $fullAddress;
        $postVariables['CardPostcode'] = $address['zip'];
        $postVariables['Email'] = $member['email'];
        $postVariables['CurrencyCode'] = $this->currencyLookup($currencyCode);
        $postVariables['OrderID'] = intval($payment['payment_id']);
        $postVariables['CustomerID'] = intval($payment['member_id']);
        $postVariables['AVSCV2Check'] = $this->config['avscv2'];
        $postVariables['3DSecureCheck'] = $this->config['threeDSecure'];

		$postVariables['CallbackURL']	= $config['root_surl'] . "/plugins/payment/skypay/validation.php";
		$postVariables['SuccessURL']	= $config['root_surl'] . "/thanks.php?payment_id={$payment['payment_id']}";
        $postVariables['AbortURL']		= $config['root_surl'] . "/plugins/payment/skypay/cancel.php?payment_id={$payment['payment_id']}";

        if($this->config['formTemplate']) $postVariables['FormTemplate'] = $this->config['formTemplate'];

		// add line items details
		// there is just one product here
		$lidItemDesc = htmlentities(utf8_decode($product->config['title'] . ' ' . $product->config['description']));
		$postVariables['LIDItem1Description'] = substr($lidItemDesc, 0, 15); // can't be longer than 15 chars
		$postVariables['LIDItem1Quantity'] = 1;
		$postVariables['LIDItem1GrossValue'] = $amount;

        $postVariables['Description'] = $product->config['title'] . ' - ' . $total . ' ' . $currencyCode;

        $postVariables['CallbackArgs'] = 'purchaser_ip=' . $_SERVER['REMOTE_ADDR'];
		
		$this->createNewTransactionRecord($postVariables['OrderID'], $total);

        $this->skypayDebug("Txn Register: ".print_r($postVariables,1));

        $postData = '';
        $prefix = '';
        foreach($postVariables as $key => $value){
            $postData .= $prefix . $key . '='. urlencode($value);
            $prefix = '&';
        }
		$this->skypayDebug("Txn Register: ".$postData);

		return $postData;
    }

	

	/**
     * Post data to specified url - used for the transaction's registration
     *
     * @param string $data -  urlencoded list of pairs key=value
     *
    **/
    function requestPost($data){
        set_time_limit(60);
        $output = array();
        $curlSession = curl_init();
        curl_setopt ($curlSession, CURLOPT_URL, $this->_registrationUrl);
        curl_setopt ($curlSession, CURLOPT_HEADER, 0);
        curl_setopt ($curlSession, CURLOPT_POST, 1);
        curl_setopt ($curlSession, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curlSession, CURLOPT_TIMEOUT,60);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);
        $response = curl_exec($curlSession);
        if(curl_error($curlSession)){
            return "FAIL";
        }
        curl_close ($curlSession);
        return $response;
    }


	/**
     * converts registration response to the array
     *
     * @param string $response - string returned by requestPost
     *
     * @return array key=>value from each line of the response
    **/
    function readRegistrationResponse($response){
        $response = split(chr(10), $response);
        $this->skypayDebug("Txn Register Response: " . print_r($response, true));
        $registrationResponse = array();
        foreach($response as $line){
            list($field, $value) = split("=", $line, 2);
            $registrationResponse[$field] = $value;
        }
        return $registrationResponse;
    }


	/**
     * Saving text into the tmp/skypay_debug_log.php file if SKYPAY_DEBUG set to 1 in configuration panel
     * @param string $debugText - text to be saved
     *
    **/
    function skypayDebug($debugText){
		global $db;
        if ($this->config['debug']){
			$db->log_error('SKYPAY DEBUG : ' . $debugText);
            $fp = fopen(dirname(__FILE__). '/skypay_debug_log.php', 'a');
            fwrite($fp, '-----------' . date('Y-m-d H:i:s') . '-----------------------'.chr(10));
            fwrite($fp, $debugText . chr(10));
            fwrite($fp, "-----------------------------------------------------".chr(10));
            fclose($fp);
        }
    }

	/**
     * Get currency code in ISO-4217
     *
     * @param string $currency - alphabetic code
     *
     * $return str - ISO-4217 numeric code
    **/
    function currencyLookup($currency) {
        $currencies  = array (
                            "AED" => "784" ,
                            "AFN" => "971" ,
                            "ALL" => "008" ,
                            "AMD" => "051" ,
                            "ANG" => "532" ,
                            "AOA" => "973" ,
                            "ARS" => "032" ,
                            "AUD" => "036" ,
                            "AWG" => "533" ,
                            "AZM" => "031" ,
                            "BAM" => "977" ,
                            "BBD" => "052" ,
                            "BDT" => "050" ,
                            "BGL" => "100" ,
                            "BGN" => "975" ,
                            "BHD" => "048" ,
                            "BIF" => "108" ,
                            "BMD" => "060" ,
                            "BND" => "096" ,
                            "BOB" => "068" ,
                            "BOV" => "984" ,
                            "BRL" => "986" ,
                            "BSD" => "044" ,
                            "BTN" => "064" ,
                            "BWP" => "072" ,
                            "BYR" => "974" ,
                            "BZD" => "084" ,
                            "CAD" => "124" ,
                            "CDF" => "976" ,
                            "CHF" => "756" ,
                            "CLF" => "990" ,
                            "CLP" => "152" ,
                            "CNY" => "156" ,
                            "COP" => "170" ,
                            "CRC" => "188" ,
                            "CSD" => "891" ,
                            "CUP" => "192" ,
                            "CVE" => "132" ,
                            "CYP" => "196" ,
                            "CZK" => "203" ,
                            "DJF" => "262" ,
                            "DKK" => "208" ,
                            "DOP" => "214" ,
                            "DZD" => "012" ,
                            "ECV" => "983" ,
                            "EEK" => "233" ,
                            "EGP" => "818" ,
                            "ERN" => "232" ,
                            "ETB" => "230" ,
                            "EUR" => "978" ,
                            "FJD" => "242" ,
                            "FKP" => "238" ,
                            "GBP" => "826" ,
                            "GEL" => "981" ,
                            "GHC" => "288" ,
                            "GIP" => "292" ,
                            "GMD" => "270" ,
                            "GNF" => "324" ,
                            "GTQ" => "320" ,
                            "GWP" => "624" ,
                            "GYD" => "328" ,
                            "HKD" => "344" ,
                            "HNL" => "340" ,
                            "HRK" => "191" ,
                            "HTG" => "332" ,
                            "HUF" => "348" ,
                            "IDR" => "360" ,
                            "ILS" => "376" ,
                            "INR" => "356" ,
                            "IQD" => "368" ,
                            "IRR" => "364" ,
                            "ISK" => "352" ,
                            "JMD" => "388" ,
                            "JOD" => "400" ,
                            "JPY" => "392" ,
                            "KES" => "404" ,
                            "KGS" => "417" ,
                            "KHR" => "116" ,
                            "KMF" => "174" ,
                            "KPW" => "408" ,
                            "KRW" => "410" ,
                            "KWD" => "414" ,
                            "KYD" => "136" ,
                            "KZT" => "398" ,
                            "LAK" => "418" ,
                            "LBP" => "422" ,
                            "LKR" => "144" ,
                            "LRD" => "430" ,
                            "LSL" => "426" ,
                            "LTL" => "440" ,
                            "LVL" => "428" ,
                            "LYD" => "434" ,
                            "MAD" => "504" ,
                            "MDL" => "498" ,
                            "MGA" => "969" ,
                            "MGF" => "450" ,
                            "MKD" => "807" ,
                            "MMK" => "104" ,
                            "MNT" => "496" ,
                            "MOP" => "446" ,
                            "MRO" => "478" ,
                            "MTL" => "470" ,
                            "MUR" => "480" ,
                            "MVR" => "462" ,
                            "MWK" => "454" ,
                            "MXN" => "484" ,
                            "MXV" => "979" ,
                            "MYR" => "458" ,
                            "MZM" => "508" ,
                            "NAD" => "516" ,
                            "NGN" => "566" ,
                            "NIO" => "558" ,
                            "NOK" => "578" ,
                            "NPR" => "524" ,
                            "NZD" => "554" ,
                            "OMR" => "512" ,
                            "PAB" => "590" ,
                            "PEN" => "604" ,
                            "PGK" => "598" ,
                            "PHP" => "608" ,
                            "PKR" => "586" ,
                            "PLN" => "985" ,
                            "PYG" => "600" ,
                            "QAR" => "634" ,
                            "ROL" => "642" ,
                            "RUB" => "643" ,
                            "RUR" => "810" ,
                            "RWF" => "646" ,
                            "SAR" => "682" ,
                            "SBD" => "090" ,
                            "SCR" => "690" ,
                            "SDD" => "736" ,
                            "SEK" => "752" ,
                            "SGD" => "702" ,
                            "SHP" => "654" ,
                            "SIT" => "705" ,
                            "SKK" => "703" ,
                            "SLL" => "694" ,
                            "SOS" => "706" ,
                            "SRD" => "968" ,
                            "STD" => "678" ,
                            "SVC" => "222" ,
                            "SYP" => "760" ,
                            "SZL" => "748" ,
                            "THB" => "764" ,
                            "TJS" => "972" ,
                            "TMM" => "795" ,
                            "TND" => "788" ,
                            "TOP" => "776" ,
                            "TRL" => "792" ,
                            "TTD" => "780" ,
                            "TWD" => "901" ,
                            "TZS" => "834" ,
                            "UAH" => "980" ,
                            "UGX" => "800" ,
                            "USD" => "840" ,
                            "USN" => "997" ,
                            "USS" => "998" ,
                            "UYU" => "858" ,
                            "UZS" => "860" ,
                            "VEB" => "862" ,
                            "VND" => "704" ,
                            "VUV" => "548" ,
                            "WST" => "882" ,
                            "XAF" => "950" ,
                            "XCD" => "951" ,
                            "XOF" => "952" ,
                            "XPF" => "953" ,
                            "YER" => "886" ,
                            "ZAR" => "710" ,
                            "ZMK" => "894" ,
                            "ZWD" => "716"
                            );
        return $currencies[$currency];
    }


	/**
     * dealing with transaction response wrapped
     *
     * @param array $postData
    **/
    function recordResponse($postData){
		/**
		 * get the cartId, amount (needed for recording an order)
		 * save callback response
		 * confirm/create an order
		 */
		$transactionDetails = $this->getTransactionDetails($postData['TransactionRegKey']);

		if($transactionDetails['payment_id']){
			if(!$this->saveCallbackResponse($postData)){
				// saving callback failed, lets warn shop owner
				$this->informVendor('Transaction Save Failure', 'Failed to save a successfull transaction for following details'.chr(10). print_r($postData, true));
			}else{
				global $db;
				/* Complete the order. */
				$payment_id = intval($transactionDetails['payment_id']);
				$amount = floatval($transactionDetails['amount']);

				// process payment
				$err = $db->finish_waiting_payment($payment_id, 'skypay', $postData['CrossRef'], $amount, $postData);

				if ($err){
					fatal_error(sprintf(_PLUG_PAY_SKYPAY_FERROR1, "finish_waiting_payment error: $err", $postData['CrossRef'], $payment_id, '<br />')."\n".print_r($postData, true));
				}else{
					echo '<a href="' . $config['root_surl'] . "/thanks.php?payment_id=$payment_id" . '">Go to Thank You page</a>';
					$this->informVendor('Successfull Payment', 'Successfull transaction with Skypay has been recorded for payment_id '. $payment_id);
				}
			}
		}else{
			// looks like some variable injection, lets warn shop owner
			$this->informVendor('Suspicious Transaction', 'Following data has been posted to skypay validation script but they do not match any existing transaction'.chr(10). print_r($postData, true));
		}
	}


	/**
	 * Fetch the transaction record if exists
	 *
	 * @param string $transactionKey - this is TransactionRegKey from the POST array
	 *
	 * @return array - row for the transaction
	**/
	function getTransactionDetails($transactionKey=''){
		$query = "select payment_id, amount from $this->skypayTransactionTable WHERE transaction_key = '$transactionKey'";
		global $db;
		return $db->query_first($query);
	}


	function cleanPost($postData){
		$post = $postData;
        $postData = array();
        foreach($post as $postKey=>$postValue){
            if (eregi("^[_0-9a-zA-Z-]{1,30}$",$postKey)  && strcasecmp ($postKey, 'cmd')!=0)  {
                // ^ Antidote to potential variable injection and poisoning
                $postData[$postKey] = trim(stripslashes($postValue));
            }
        }
        unset($post);
        return $postData;
	}


	/**
	 * saving transaction result into $skypayTransactionTable
	 *
	 * @param array $post - cleaned and valid set of data
	 *
	 * @return boolean - true when saved
	 *
	**/
	function saveCallbackResponse($post){
		$query = "UPDATE $this->skypayTransactionTable SET
				authcode = '".$post['Authcode']."',
				crossref = '".$post['CrossRef']."',
				avscvv_response = '".@$post['CVVResponseText']."',
				stored_card_id = '".@$post['CardID']."',
				purchaser_ip = '".$post['purchaser_ip']."',
		        tds_transaction_id = '".@$post['TDSTransactionID']."',
				tds_enrolled = '".@$post['TDSEnrolled']."',
				tds_authenticated = '".@$post['TDSAuthenticated']."',
				tds_transaction_id = '".@$post['TDSTransactionID']."',
				tds_eci = '".@$post['TDSECI']."',
				tds_cavv = '".@$post['TDSCAVV']."',
				tds_error_code = '".@$post['TDSErrorCode']."',
				tds_error_description = '".@$post['TDSErrorDescription']."',
				tds_ireq_code = '".@$post['TDSiReqCode']."',
				tds_ireq_code_detail = '".@$post['TDSiReqDetail']."',
				test_mode = '".$post['TestMode']."'
				WHERE transaction_key = '".$post['TransactionRegKey']."'"; // integration document says that it is TransactionKey but there is Reg within it !!!
		global $db;
		if($db->query($query)){
			$result = true;
			$this->skypayDebug("Updated transactions table: $query");
		}else{
			$result = false;
			$this->skypayDebug("Failed to update transactions table: $query");
		}
		return $result;
	}


	/**
	 * Send email to the vendor
	 *
	 * @param string $subject
	 * @param string $body
	 *
	**/
	function informVendor($subject, $body){
		global $config;
		$to = $this->config['vendorEmail'];
		$from = $config['admin_email'];
		if($to && mail($to, $subject, $body, "From:$from", "-f$from")){
			$this->skypayDebug('Mail sent:'.chr(10). "to: $to".chr(10). "subject: $subject".chr(10). "body: $body");
		}else{
			$this->skypayDebug('Mail failed:'.chr(10). "to: $to".chr(10). "subject: $subject".chr(10). "body: $body");
		}
	}

	/**
	 * Create table for skypay transactions recording
	 *
	**/
	function createTransactionTable(){
		global $db;
		$db->query( "CREATE TABLE IF NOT EXISTS `$this->skypayTransactionTable` (
								`id` int(10) NOT NULL auto_increment,
								`transaction_id` bigint(15) default NULL,
								`transaction_key` varchar(50) default NULL,
								`payment_id` int(10) default NULL,
								`amount` decimal(10,2) default '0.00',
								`purchaser_ip` int(10) default NULL,
								`authcode` varchar(20) default NULL,
								`crossref` varchar(50) default NULL,
								`avscvv_response` varchar(50) default NULL,
								`stored_card_id` int(10) default NULL,
								`tds_enrolled` char(1) default NULL,
								`tds_authenticated` char(1) default NULL,
								`tds_transaction_id` varchar(30) default NULL,
								`tds_eci` tinyint(3) default NULL,
								`tds_cavv` varchar(32) default NULL,
								`tds_error_code` varchar(30) default NULL,
								`tds_error_description` varchar(255) default NULL,
								`tds_ireq_code` varchar(30) default NULL,
								`tds_ireq_code_detail` varchar(255) default NULL,
								`test_mode` int(1) default NULL,
								`timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
								PRIMARY KEY  (`id`)
								) TYPE=MyISAM COMMENT='Holds the transaction details for skypay payments';");
	}


	/**
	 * updating transaction record after registering transaction intent
	 *
	 * @param string $transactionId - skypay transaction id
	 * @param string $transactionKey - skypay transaction key
	 *
	**/
	function updateTransactionIDKey($transactionId, $transactionKey){
		global $db;
		$db->query("UPDATE $this->skypayTransactionTable SET transaction_id = $transactionId ,transaction_key = '$transactionKey' WHERE id = $this->transactionRecordId");
	}


	/**
     * Insert new record into transaction table, store it for later use
     *
     * @param int $paymentId -  used to relate transaction to the order
     * @param float $total -  total amount to be paid
    **/
    function createNewTransactionRecord($paymentId, $total){
        global $db;
		$db->query("INSERT INTO $this->skypayTransactionTable (id, payment_id, amount) values (null, $paymentId, '$total')");
    	$this->transactionRecordId = mysql_insert_id($db->conn);
		$this->skypayDebug("Inserted new record: {$this->transactionRecordId} for cart id: $paymentId and amount: $total");
    }


	/**
	 * TESTING FUNCTIONALITY
	 */

	/**
	 *
     * Print form for the fake posting data to callbackUrl
     *
    **/
    function getPostTestSuccessResponseForm($transactionKey){
		global $config;
        $callbackUrl = $config['root_surl'] . "/plugins/payment/skypay/validation.php";
		$paymentTestPostResponse = array(
                                        'Authcode' => '',
                                        'CrossRef' => 123,
                                        'CVVResponseText' => '',
                                        'CardID' => 456,
                                        'Result' => 'SUCCESS',
                                        'TDSTransactionID' => 789,
                                        'TDSEnrolled' => '',
                                        'TDSAuthenticated' => '',
                                        'TDSECI' => 101,
                                        'TDSCAVV' => '',
                                        'TDSErrorCode' => '',
                                        'TDSErrorDescription' => '',
                                        'TDSiReqCode' => '',
                                        'TDSiReqDetail' => '',
                                        'TestMode' => 1
										);
		$formHtml = '<form action="' . $callbackUrl . '" method="post">';

		foreach($paymentTestPostResponse as $key=>$value){
            if($value==''){
                $value = $key;
            }
            $formHtml .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }
        $formHtml .= '<input type="hidden" name="TransactionRegKey" value="' . $transactionKey . '">';
        $formHtml .= '<input type="hidden" name="transactionRecordId" value="' . $this->transactionRecordId . '">';
        $formHtml .= '<input type="hidden" name="purchaser_ip" value="' . $_SERVER['REMOTE_ADDR'] . '">';
        $formHtml .= '<input type="hidden" name="fakeResponse" value="true">';
        $formHtml .= '<br /><input type="submit" value ="FAKE SUCCESS RESPONSE" />
                    </form>';
        return $formHtml;
    }

}

?>
