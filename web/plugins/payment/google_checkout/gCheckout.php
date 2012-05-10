<?php

    //@ini_set('include_path', '.:'.$config['root_dir'].'/includes/pear'); // aMember v.4
    @ini_set('include_path', '.:'.dirname(__FILE__).'/PEAR');
    
    require_once('XML/Serializer.php');
    require_once('XML/Unserializer.php');
    
/**
 * Google Checkout class
 * (based on Google GCheckout / Checkout Shopping cart object by Ron Howard - Expert Database Solutions, LLC 2006)
 **/
class gCheckout{
	var $_php_version;
	var $_merchant_id;
	var $_merchant_key;
	var $_arr_shopping_cart;
	var $_serializer_options;
	var $_state_serializer_options;
	var $_zip_serializer_options;
	var $_state_area_serializer_options;
	var $_remove_tags;
	var $_checkout_currency;
	var $_checkout_xml_schema;

    var $_checkout_url;
    var $_checkout_diagnose_url;
    var $_request_url;
    var $_request_diagnose_url;
    var $_errors = array();
    var $_debug = false;
	var $_allow_create = false;
    var $_mp_type = 'MISSING_PARAM';
    
    var $_merchant_calc;
    var $_tax_tables;
    var $_default_tax_rules;
    var $_alternate_tax_rules;
    var $_alternate_tax_tables;
		

    function get_days($orig_period){
    	$ret = 0;
        if (preg_match('/^\s*(\d+)\s*([y|Y|m|M|w|W|d|D]{0,1})\s*$/', $orig_period, $regs)){
            $period = $regs[1];
            $period_unit = $regs[2];
            if (!strlen($period_unit)) $period_unit = 'd';
            $period_unit = strtoupper($period_unit);

            switch ($period_unit){
                case 'Y':
                    $ret = $period * 365;
                    break;
                case 'M':
                    $ret = $period * intval(date("t")); // days in curent month
                    break;
                case 'W':
                    $ret = $period * 7;
                    break;
                case 'D':
                    $ret = $period;
                    break;
                default:
                    fatal_error(sprintf("Unknown period unit: %s", $period_unit));
            }
        } else {
            fatal_error("Incorrect value for expire days: ".$orig_period);
        }
        return $ret;
    }
    
		// GCheckout Constructor
		function gCheckout($merchant_id = '', $merchant_key = '', $cart_expires = '',
		                   $checkout_xml_schema = 'http://checkout.google.com/schema/2', $checkout_currency = 'USD', $debug = '0', $sandbox = '0', $allow_create = '0') {
			
			// Set your Google GCheckout Mercant information.
			$this->_merchant_id 	= $merchant_id;
			$this->_merchant_key = $merchant_key;
			
			
			// Set Current PHP Versionin information.
			$this->_php_version = explode("-", phpversion());
			$this->_php_version = explode(".", $this->php_version[0]);
			
			$this->_checkout_xml_schema = $checkout_xml_schema;
			$this->_checkout_currency = $checkout_currency;

			if ($sandbox){
				$this->_checkout_url = "https://sandbox.google.com/checkout/api/checkout/v2/checkout/Merchant/".$merchant_id;
				$this->_checkout_diagnose_url = "https://sandbox.google.com/checkout/api/checkout/v2/checkout/Merchant/".$merchant_id."/diagnose";
				$this->_request_url = "https://sandbox.google.com/checkout/api/checkout/v2/request/Merchant/".$merchant_id;
				$this->_request_diagnose_url = "https://sandbox.google.com/checkout/api/checkout/v2/request/Merchant/".$merchant_id."/diagnose";
			} else {
				$this->_checkout_url = "https://checkout.google.com/api/checkout/v2/checkout/Merchant/".$merchant_id;
				$this->_checkout_diagnose_url = "https://checkout.google.com/api/checkout/v2/checkout/Merchant/".$merchant_id."/diagnose";
				$this->_request_url = "https://checkout.google.com/api/checkout/v2/request/Merchant/".$merchant_id;
				$this->_request_diagnose_url = "https://checkout.google.com/api/checkout/v2/request/Merchant/".$merchant_id."/diagnose";
			}

			// Initialize Shopping Cart
			$this->_setShoppingCart();
										
			// Set Serializer Options
			$this->_setSerializerOptions();
			
			// Check Cart Expires Date
			if(!empty($cart_expires)) {
				$this->setCartExpirationDate($cart_expires);
			}
			
			if ($debug) $this->_debug = true; else $this->_debug = false;
			if ($allow_create) $this->_allow_create = true; else $this->_allow_create = false;

			
			// set remove tags
			$this->_remove_tags = array("<REMOVE>", "</REMOVE>");
		}
		
		
		//////////////////////////////////////////////
		// PUBLIC  METHODS
		//////////////////////////////////////////////
		
		/**
		 * Returns the XML GCheckout Shopping Cart
		 *
		 * @return  XML  GCheckout Checkout Shopping Cart
		 */
		function getCart() {
				/**
				 * Get New XML Serializer
				 */
				$serializer = new XML_Serializer($this->_serializer_options);
				
				$rslt = $serializer->serialize($this->_arr_shopping_cart);
				
				// Display the XML document;
				return $serializer->getSerializedData(); 
		}

		function get_recurrence_request($google_order_number="") {
				/**
				 * Get New XML Serializer
				 */
				$options = $this->_rebill_serializer_options;
				$options['rootAttributes']['google-order-number'] = $google_order_number;

				$serializer = new XML_Serializer($options);
				
				$rslt = $serializer->serialize($this->_arr_shopping_cart);
				
				// Display the XML document;
				return $serializer->getSerializedData(); 
		}
		
		
		/**
		 * Returns the XML Shopping Cart Signature
		 *
		 * @param string $xml_cart
		 * @return  string
		 */
		function getSignature($xml_cart) {
			return $this->_getHmacSha1($xml_cart, $this->_merchant_key);
		}
		
		/**
		 * Adds an Item to the GCheckout Cart
		 *
		 * @param unknown_type $item_name
		 * @param unknown_type $item_description
		 * @param unknown_type $quantity
		 * @param unknown_type $unit_price
		 * @param unknown_type $tt_selector
		 * @param unknown_type $private_item_data
		 */
		function addItem($item_name, $item_description, $quantity = 1, $unit_price = 0, $tt_selector="", $private_item_data="", $digital_delivery=array(), $subscription=array(), $item_currency="") {

			 	/**
			 	 * Check if there are already items in the cart
			 	 */
			 	if(empty($this->_arr_shopping_cart['shopping-cart']['items'])) {
			 		$this->_arr_shopping_cart['shopping-cart']['items']	= array();
			 	}
			 	
				/**
				 * Strip HTML entities
				 */
				$item_name 		= htmlentities($item_name);
				$item_description 	= htmlentities($item_description);
				if (!$item_currency)
					$item_currency = $this->_checkout_currency;
				
				
				/**
				 * Build New Item Array
				 */
				$item_price = $unit_price;
				if ($subscription)
					$item_price = '0';

				$arr_item =  array(
					'item-name' => $item_name,
					'item-description' => $item_description,
					'unit-price' => array(
						'_attributes' => array('currency' => $item_currency),
						'_content' 	  => $item_price
					 ),
					'quantity' => $quantity
						
				);
				if ($digital_delivery){
					$digital_content = "";
					$digital_content .= "<display-disposition>".$digital_delivery['delivery_schedule']."</display-disposition>";
					
					switch ($digital_delivery['delivery_method']){
						case 'email':
							$digital_content .= "<email-delivery>true</email-delivery>";
						break;
						case 'key':
							$digital_content .= "<description>".htmlspecialchars($digital_delivery['delivery_description'])."</description>";
							$digital_content .= "<key>".$digital_delivery['delivery_key']."</key>";
							$digital_content .= "<url>".$digital_delivery['delivery_url']."</url>";
						break;
						case 'description':
							$digital_content .= "<description>".htmlspecialchars($digital_delivery['delivery_description'])."</description>";
						break;
					}
					if (!$subscription) $arr_item['digital-content'] = $digital_content;
				}

				if ($subscription){
					$subscription_content = '
<payments>
';
if ($subscription['rebill_times'] > 0)
	$subscription_content .= '<subscription-payment times="'.$subscription['rebill_times'].'">';
else
	$subscription_content .= '<subscription-payment>';

$subscription_content .= '<maximum-charge currency="'.$item_currency.'">'.$unit_price.'</maximum-charge>';
/* The <maximum-charge> tag, which is a subtag of <subscription-payment>, specifies the maximum amount that the customer can be charged.
 * If you do not charge tax, this number should be the same as the unit-price of the recurrent-item (see below);
 * if you do, it should be a close estimate greater than the total cost you expect each recurrence charge to have. This number will be displayed on the buy page.
 */

$subscription_content .= '
</subscription-payment>
</payments>
<recurrent-item>
<item-name>'.$item_name.'</item-name>
<item-description>'.$item_description.'</item-description>
<quantity>'.$quantity.'</quantity>
<unit-price currency="'.$item_currency.'">'.$unit_price.'</unit-price>
';
if ($digital_delivery) $subscription_content .= '<digital-content>'.$digital_content.'</digital-content>';
$subscription_content .= '
</recurrent-item>
';
					$attributes = array('type' => 'merchant', 'period' => $subscription['period']);
					if ($subscription['start_date']) $attributes['start-date'] = $subscription['start_date'];
					$arr_item['subscription'] = array(
						'_attributes' => $attributes,
						'_content' 	  => $subscription_content
					);
				}
				
				if(!empty($private_item_data)) {
					$arr_item['merchant-private-item-data'] = $private_item_data;

				}
				
				if(!empty($tt_selector)) {
					$arr_item['tax-table-selector'] = $tt_selector;
				}
								
				/**
				 * Push the Item into the cart
				 */
				array_push($this->_arr_shopping_cart['shopping-cart']['items'], $arr_item);
						 	
		}
		
		function setCartExpirationDate($expire_date) {
			
			$this->_arr_shopping_cart['shopping-cart']['cart-expiration'] = array('good-until-date' => $expire_date);
		}

		function setMerchantPrivateData($merchant_private_data) {
			
			$this->_arr_shopping_cart['shopping-cart']['merchant-private-data'] = $merchant_private_data;
		}
		
		
		
		/**
		 * Sets a mercant flat rate shipping charge
		 *
		 * @param unknown_type $name
		 * @param unknown_type $price
		 * @param unknown_type $shipping_restrictions
		 */
		function setFlatRateShipping($name, $price, $allowed_restrictions = "", $excluded_restrictions = "") {
			/**
			 * Get shipping object
			 */
			$arr_flat_rate_shipping_obj = $this->_getShippingArray('flat-rate-shipping', $name, $price, $allowed_restrictions, $excluded_restrictions);
			
			/**
			 * Append to shipping method array
			 */
			$this->_arr_shopping_cart['checkout-flow-support']['merchant-checkout-flow-support']['shipping-methods']['flat-rate-shipping'] = $arr_flat_rate_shipping_obj;
		}
		
		

		
		/**
		 * Enter description here...
		 *
		 * @param unknown_type $name
		 * @param unknown_type $price
		 */
		function setPickup($name, $price) {
			
			/**
			 * Get shipping object
			 */
			$arr_pickup = $this->_getShippingArray('pickup', $name, $price);
			
			/**
			 * Append to shipping method array
			 */
			$this->_arr_shopping_cart['checkout-flow-support']['merchant-checkout-flow-support']['shipping-methods']['pickup'] = $arr_pickup;
		}
		
		
		/**
		 * Enter description here...
		 *
		 * @param unknown_type $name
		 * @param unknown_type $price
		 * @param unknown_type $shipping_restrictions
		 */
		function setMerchantCalculatedShipping($name, $price, $allowed_restrictions = "", $excluded_restrictions = "") {
			
			/**
			 * Get shipping object
			 */
			$arr_merchant_calculated_shipping = $this->_getShippingArray('merchant-calculated-shipping', $name, $price, $allowed_restrictions, $excluded_restrictions);
			
			/**
			 * Append to shipping method array
			 */
			$this->_arr_shopping_cart['checkout-flow-support']['merchant-checkout-flow-support']['shipping-methods']['merchant-calculated-shipping'] = $arr_merchant_calculated_shipping;
		}
	
		
		/**
		 * returns shipping-restriction object
		 *
		 * @param unknown_type $country_area
		 * @param unknown_type $arr_states
		 * @param unknown_type $arr_zips
		 * @return unknown
		 */
		function getAllowedAreas($country_area, $arr_states, $arr_zips) {
			return  $this->_getAllowedAreas($country_area, $arr_states, $arr_zips);
		}
		
		
		/**
		 * returns shipping restriction object
		 *
		 * @param unknown_type $country_area
		 * @param unknown_type $arr_states
		 * @param unknown_type $arr_zips
		 * @return unknown
		 */
		function getExcludedAreas($country_area, $arr_states, $arr_zips) {
			return $this->_getAllowedAreas($country_area, $arr_states, $arr_zips, $type = "excluded");
		}
		
		
		/**
		 * Sets the merchant flow support options
		 *
		 * @param unknown_type $edit_cart_url
		 * @param unknown_type $continue_shopping_url
		 * @param unknown_type $request_buyer_phone_number
		 */
		function setMerchantCheckoutFlowSupport($edit_cart_url ="", $continue_shopping_url = "", $request_buyer_phone_number = false) {
			
			if ($edit_cart_url)
			    $this->_arr_shopping_cart['checkout-flow-support']['merchant-checkout-flow-support']['edit-cart-url'] 		 	= $edit_cart_url;
			
			if ($continue_shopping_url)
			    $this->_arr_shopping_cart['checkout-flow-support']['merchant-checkout-flow-support']['continue-shopping-url'] 	= $continue_shopping_url;
			
			$this->_arr_shopping_cart['checkout-flow-support']['merchant-checkout-flow-support']['request-buyer-phone-number'] 	= ($request_buyer_phone_number == true ? 'true' : 'false' );
			
			if (!empty($this->_merchant_calc)){
			    $this->_arr_shopping_cart['checkout-flow-support']['merchant-checkout-flow-support']['merchant-calculations']   = $this->_merchant_calc;
			}
			
			if (!empty($this->_tax_tables)){
			    $this->_arr_shopping_cart['checkout-flow-support']['merchant-checkout-flow-support']['tax-tables'] = $this->_tax_tables;
			}
			
		}

	
        /////////////////////////////////////////////
        // Global API
        /////////////////////////////////////////////


        function get_payment_by_data($data_name = '', $data_value = ''){
            global $db;
            $payment_id = 0;
            
            $q = $db->query($s = "SELECT payment_id
                FROM {$db->config['prefix']}payments
                WHERE receipt_id = '".$db->escape($data_value)."'
                ORDER BY payment_id
                LIMIT 0,1
                ");
            $r = mysql_fetch_assoc($q);
            $payment_id = $r['payment_id'];
            
            if (!$payment_id){

                $q = $db->query($s = "SELECT payment_id, data
                    FROM {$db->config['prefix']}payments
                    WHERE data like '%".$db->escape($data_value)."%'
                    ORDER BY payment_id
                    ");
                
                while ($r = mysql_fetch_assoc($q)){
                    $r['data'] = $db->decode_data($r['data']);
                    if ($r['data'][$data_name] == $data_value){
                        $payment_id = $r['payment_id'];
                        break; // payment found. break while loop
                    }
                }
                
            }
            
            return $payment_id;
        }

        function find_last_payment($google_order_number=""){
            global $db;
            if (!$google_order_number)
                return;
                
            $payment_id = $this->get_payment_by_data('google-order-number', $google_order_number);
            $payment = $db->get_payment($payment_id);

            $q = $db->query($s = "SELECT payment_id, data
                FROM {$db->config['prefix']}payments
                WHERE member_id='".$payment['member_id']."'
                AND product_id='".$payment['product_id']."'
                AND completed = 1
                ORDER BY payment_id
                ");
            
            while ($r = mysql_fetch_assoc($q)){
                $r['data'] = $db->decode_data($r['data']);
                if($r['data'][0]['RENEWAL_ORIG'] == "RENEWAL_ORIG: ".$payment_id){
                    $payment_id = $r['payment_id'];
                }
            }

            return $payment_id;
        }
        



        /**
         * The SendRequest function verifies that you have provided values for
         * all of the parameters needed to send a Google Checkout
         * Checkout or Order Processing API request. It then logs the request, 
         * calls the GetCurlResponse function to execute the request, 
         * and logs the response.
         *
         * @param    $request     XML API request
         * @param    $post_url    URL address to which the request will be sent
         * @return   $response    synchronous response from the Google Checkout 
         *                        server
         */
        
        function SendRequest($request, $post_type = 'request', $add_headers = true) {
        
            // Check for errors
            $error_function_name = "SendRequest()";
            
            // Check for missing parameters
            $this->CheckForError($this->_mp_type, $error_function_name, "request", $request);
            $this->CheckForError($this->_mp_type, $error_function_name, "post_type", $post_type);
            
            switch ($post_type){
                case 'request':
                    $post_url = $this->_request_url;
                    break;
                case 'request_diagnose':
                    $post_url = $this->_request_diagnose_url;
                    break;
                case 'checkout':
                    $post_url = $this->_checkout_url;
                    break;
                case 'checkout_diagnose':
                    $post_url = $this->_checkout_diagnose_url;
                    break;
                default:
                    $post_url = $this->_request_url;
                    break;
            }
            
            // Log outgoing message
            //$this->LogMessage("Google Checkout Request:<br />" . $post_url . "<br />" . nl2br(htmlspecialchars($request)), $debug_only_msg = true);
            $this->LogMessage("Google Checkout Request:\n" . $post_url . "\n" . $request, $debug_only_msg = true);
        
            // Execute the API request and capture the response to the request
            $response = $this->GetCurlResponse($request, $post_url, $this->_merchant_id, $this->_merchant_key, $add_headers);
        
            // Log incoming message
            //$this->LogMessage("Google Checkout Response:<br />" . nl2br(htmlspecialchars($response)), $debug_only_msg = true);
            $this->LogMessage("Google Checkout Response:\n" . $response, $debug_only_msg = true);
        
            // Return the response to the API request
            return $response;
        }
        
        /**
         * The GetCurlResponse function sends an API request to Google Checkout 
         * and returns the response. The HTTP Basic Authentication scheme is 
         * used to authenticate the message.
         *
         * This function utilizes cURL, client URL library functions.
         * cURL is supported in PHP 4.0.2 or later versions, documented at
         * http://us2.php.net/curl
         *
         * @param    $request     XML API request
         * @param    $post_url    URL address to which the request will be sent
         * @return   $response    synchronous response from the Google Checkout 
         *                            server
         */
        
        function GetCurlResponse($request = '', $post_url = '', $merchant_id = '', $merchant_key = '', $add_headers = false) {
        
            global $config;
            
            $header = array();
            $url_array = parse_url($post_url);
            $pos = strpos($url_array['path'], "checkout");
            if ($pos === false || $add_headers) {
                $header[] = "Content-type: application/xml";
                $header[] = "Accept: application/xml";
            }

            if (extension_loaded("curl")){
                $ch=curl_init($post_url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                if ($request)  {
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
                }
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                if (!empty($header)) {
                    curl_setopt($ch, CURLOPT_USERPWD, $merchant_id . ":" . $merchant_key);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                }
    
                $response = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    trigger_error(curl_error($ch), E_USER_ERROR);
                } else {
                    curl_close($ch);
                }
                
            } else {
                $curl = $config['curl'];
                if (!strlen($curl)) {
                    $err = "cURL path is not set - cc transaction cannot be completed";
                    $this->LogMessage($err, $debug_only_msg = true);
                    return $err;
                }
                $params = "";
                if (!empty($header)) {
                    $params .= " --basic -u " . escapeshellarg($merchant_id . ":" . $merchant_key);
                    foreach ($header as $line){
                        $params .= " --header " . escapeshellarg($line);
                    }
                }
                
                if (substr(php_uname(), 0, 7) == "Windows") {
                    if ($request)
                    $response = `$curl $params -d "$request" "$post_url"`;
                    else
                    $response = `$curl $params "$post_url"`;
                } else {
                    $post_url  = escapeshellarg($post_url);
                    $request = escapeshellarg($request);
                    if ($request)
                    $response = `$curl $params -d $request $post_url`;
                    else
                    $response = `$curl $params $post_url`;
                }
                
            }
        
            // Return the response to the API request
            return $response;
        }


        /**
         * The DisplayDiagnoseResponse function is a debugging function that 
         * sends a Google Checkout API request and then evaluates the Google Checkout 
         * response to determine whether the request used valid XML. If the request 
         * did not use valid XML, the function displays an error message and a link
         * where you can edit the XML and then try to validate it again.
         *
         * This function calls the SendRequest function to execute the API request.
         *
         * @param    $request     XML API request
         * @param    $post_url    URL address to POST the form
         * @param    $xml         Unencoded version of XML used in API request
         * @param    $action      This variable indicates whether the function
         *                              should print a form on the page containing
         *                              information about the API request if the XML 
         *                              is invalid.
         * @return    $response   Boolean (true=XML is valid;false=XML is invalid)
         */
        
        function DisplayDiagnoseResponse($request, $post_type = 'request', $xml, $action) {
        
            // Execute the API request and capture the Google Checkout server's response
            $diagnose_response = $this->SendRequest($request, $post_type);
        
            /*
             * If the function finds that the request contained valid XML, the
             * $validated variable will be set to true
             */
            $validated = false;
        
        	$unserializer = new XML_Unserializer();
        	$rslt = $unserializer->unserialize($diagnose_response);
        	$root_element = $unserializer->getUnserializedData();
        	$root_tag = $unserializer->getRootName();

            /*
             * This if-else block determines whether the API response indicates
             * that the response contained invalid XML or if there was some other
             * problem associated with the request, such as an invalid signature.
             */
            if ($root_tag == "diagnosis") {
                $error_message = $root_element['warnings'];
                if (!(empty($error_message))) {
                    $string = $error_message['string'];
                    $result = $string;
                } else {
                    $validated = true;
                }
            } else if ($root_tag == "error") {
                $error_message = $root_element['error-message'];
                $result = $error_message;
                $warning_messages = $root_element['string'];
            } else if ($root_tag == "request-received") {
                $validated = true;
            }
        
            /*
             * If the request is invalid, print the reason that the request is
             * invalid if the $GLOBALS["error_report_type"] variable indicates 
             * that errors should be displayed in the user's browser. Also display
             * a link to a tool where the user can edit the XML request unless the
             * validation request was submitted from that tool.
             */
            $msg = '';
            if ($validated == false)
            {
                $msg .= "This XML is NOT Validated!";
                foreach($result as $message) {
                    $msg .= htmlentities($message);
                }
                if (($root_tag == "error") && (sizeof($warning_messages)) > 0) {
                    foreach ($warning_messages as $message) {
                        $msg .= htmlentities($message);
                    }
                }
                $this->LogMessage($msg, $debug_only_msg = true);
            }
        
            /*
             * Return a Boolean value indicating whether the request
             * contained valid XML.
             */
            return $validated;
        }

        /**
         * The logMessage function logs a message
         * 
         * @param  $message       message to be written
         */
        function LogMessage($message, $debug_only_msg = false) {
        
            global $db;
            // Don't log debug messages if Test mode disabled.
            if ((!$this->_debug && !$debug_only_msg) || $this->_debug)
                $db->log_error($message);
            
        }

        /**
         * The CheckForError function determines whether a parameter has a null
         * value and prints the appropriate error message if the parameter does
         * have a null value.
         *
         * @param    $error_type       The type of error being flagged.
         *                                   e.g. GLOBALS["MISSING_PARAM"]
         * @param    $function_name    The function where the error occurred
         * @param    $param_name       The name of the parameter being checked
         * @param    $param_value      The parameter value submitted to the function
         */
        
        function CheckForError($error_type, $function_name, $param_name, $param_value="") {
        
            // Log an error if the parameter value is null
            if ($param_value == "") {
                $this->LogMessage($this->error_msg($error_type, $function_name, $param_name, $param_value), $debug_only_msg = false);
            }
        }

        /*
         * The error_msg function returns the error message that should be
         * logged for the $error_type.
         *
         * @param    $error_type       The type of error being flagged.
         *                                 e.g. GLOBALS["MISSING_PARAM"],
         *                                 "INVALID_INPUT_ARRAY", "MISSING_CURRENCY"
         *                                 "MISSING_TRACKING"
         * @param    $function_name    The function where the error occurred
         * @param    $param_name       The name of the parameter being checked
         * @param    $param_value      The parameter value submitted to the function
         * @return   $errstr           error message 
         */
        function error_msg($error_type, $function_name, $param_name="", $param_value="") {
        
            /*
             * This code block selects the error message that corresponds to
             * the value of the $error_type variable.
             *
             * +++ CHANGE ME +++
             * You can change any of the error messages logged for these errors.
             */
        
            switch ($error_type) {
        
                /*
                 * MISSING_PARAM error
                 * A function call omits a required parameter.
                 */
                case "MISSING_PARAM":
                    $errstr = "Error calling function \"" . $function_name . "\": Missing Parameter: \"$" . $param_name . "\" must be provided.";
                    break;
        
                /*
                 * INVALID_INPUT_ARRAY error
                 * AddAreas() function called with invalid value for
                 * $state_areas or $zip_areas parameter
                 */
                case "INVALID_INPUT_ARRAY":
                    $errstr = "Error calling function \"" . $function_name . "\": Invalid Input: \"" . $param_name . "\" should be an array.";
                    break;
        
                /*
                 * MISSING_CURRENCY error
                 * The $GLOBALS["currency"] value is empty.
                 */
                case "MISSING_CURRENCY":
                    $errstr = "Error calling function \"" . $function_name . "\": Missing Parameter: \"\$GLOBALS[\"currency\"]\"" . "must be set when the \"\$amount\" is set.";
                    break;
        
                /*
                 * INVALID_ALLOW_OR_EXCLUDE_VALUE error
                 * AddAreas() function called with invalid value for 
                 * $allow_or_exclude parameter.
                 */
                case "INVALID_ALLOW_OR_EXCLUDE_VALUE";
                    $errstr = "Error calling function \"" . $function_name . "\": Areas must either be allowed or excluded.";
                    break;
        
                /*
                 * MISSING_TRACKING error
                 * The ChangeShippingInfo() function in 
                 * OrderProcessingAPIFunctions.php is being called without 
                 * specifying a tracking number even though a shipping 
                 * carrier is specified.
                 */
                case "MISSING_TRACKING":
                    $errstr = "Error calling function \"" . $function_name . "\": Missing Parameter: \"\$tracking_number\" must be set " . "if the \"\$carrier\" is set.";
                    break;
        
                default:
                    break;
            }
        
            return $errstr;
        }

        /////////////////////////////////////////////
        // Response Handler API
        /////////////////////////////////////////////

        /**
         * The ProcessXmlData function creates a DOM object representation of the
         * XML document received from Google Checkout. It then evaluates the root 
         * tag of the document to determine which function should handle the document.
         *
         * This function routes the XML responses that Google Checkout sends in 
         * response to API requests. These replies are sent to one of the other 
         * functions in this library.
         *
         * This function also routes Merchant Calculations API requests and
         * Notification API requests. Those requests are processed by functions
         * in the MerchantCalculationsAPIFunctions.php and 
         * NotificationAPIFunctions.php libraries, respectively.
         *
         * @param    $xml_data    The XML document sent by the Google Checkout server.
         */
        
        function ProcessXmlData($xml_response) {
            
        	$unserializer = new XML_Unserializer();
        	$rslt = $unserializer->unserialize($xml_response);
        	$dom_data_root = $unserializer->getUnserializedData();
        	$message_recognizer = $unserializer->getRootName();

            $sn = '';
            if (preg_match('/serial-number="([\d\-]+)">/i', $xml_response, $matches))
                $sn = $matches[1];
            	
        	//$this->LogMessage ("Google Checkout: ProcessXmlData: " . "<br />" . nl2br(htmlspecialchars($xml_response)), $debug_only_msg = true);
        	$this->LogMessage ("Google Checkout: ProcessXmlData: " . "\n" . $xml_response, $debug_only_msg = true);
        
            /*
             * Select the appropriate function to handle the XML document
             * by evaluating the root tag of the document. Functions to
             * handle the following types of responses are contained in
             * this document:
             *     <request-received>
             *     <error>
             *     <diagnosis>
             *     <checkout-redirect>
             *
             * This function routes the following types of responses
             * to the MerchantCalculationsAPIFunctions.php file:
             *     <merchant-calculation-callback>
             *
             * This function routes the following types of responses
             * to the NotificationAPIFunctions.php file:
             *     <new-order-notification>
             *     <order-state-change-notification>
             *     <charge-amount-notification>
             *     <chargeback-amount-notification>
             *     <refund-amount-notification>
             *     <risk-information-notification>
             * 
             */
        
            switch ($message_recognizer) {
            
                // <request-received> received
                case "request-received":
                    $this->ProcessRequestReceivedResponse($dom_data_root, $sn);
                    break;
        
                // <error> received
                case "error":
                    $this->ProcessErrorResponse($dom_data_root, $sn);
                    break;
        
                // <diagnosis> received
                case "diagnosis":
                    $this->ProcessDiagnosisResponse($dom_data_root, $sn);
                    break;
        
                // <checkout-redirect> received
                case "checkout-redirect":
                    $this->ProcessCheckoutRedirect($dom_data_root, $sn);
                    break;
                /*
                 * +++ CHANGE ME +++
                 * The following case is only for partners who are implementing 
                 * the Merchant Calculations API. If you are not implementing 
                 * the Merchant Calculations API, you may ignore this case.
                 */
        
                // <merchant-calculation-callback> received
                case "merchant-calculation-callback":
                    $this->ProcessMerchantCalculationCallback($dom_data_root, $sn);
                    break;
        
                /*
                 * +++ CHANGE ME +++
                 * The following cases are only for partners who are
                 * implementing the Notification API. If you are not
                 * implementing the Notification API, you may ignore
                 * the remaining cases in this function.
                 */
        
                // <new-order-notification> received
                case "new-order-notification":
                    $this->ProcessNewOrderNotification($dom_data_root, $sn);
                    break;
        
                // <order-state-change-notification> received
                case "order-state-change-notification":
                    $this->ProcessOrderStateChangeNotification($dom_data_root, $sn);
                    break;
        
                // <charge-amount-notification> received
                case "charge-amount-notification":
                    $this->ProcessChargeAmountNotification($dom_data_root, $sn);
                    break;
        
                // <chargeback-amount-notification> received
                case "chargeback-amount-notification":
                    $this->ProcessChargebackAmountNotification($dom_data_root, $sn);
                    break;
            
                // <refund-amount-notification> received
                case "refund-amount-notification":
                    $this->ProcessRefundAmountNotification($dom_data_root, $sn);
                    break;
        
                // <risk-information-notification> received
                case "risk-information-notification":
                    $this->ProcessRiskInformationNotification($dom_data_root, $sn);
                    break;

                // <subscription-request-received> received
                case "subscription-request-received":
                    $this->ProcessRebillingNotification($dom_data_root, $sn);
                    break;
                
                // <cancelled-subscription-notification> received
                case "cancelled-subscription-notification":
                    $this->ProcessCancellationNotification($dom_data_root, $sn);
                    break;
        
                /*
                 * None of the above: The message is not recognized. 
                 * You should not remove this case.
                 */
                default:
                    $this->SendNotificationAcknowledgment($sn);
                    return array('error-message' => "Google Checkout ProcessXmlData: The message is not recognized.");
                    break;
            }
            return $dom_data_root;
        }
        
        /******** Functions for processing synchronous response messages *********/
        
        /**
         * The ProcessRequestReceivedResponse function receives a synchronous
         * Google Checkout response to an API request originating from your site. This
         * function indicates that your API request contained properly formed
         * XML but does not indicate whether your request was processed successfully.
         *
         * @param    $xml_response    synchronous response XML message
         */
        function ProcessRequestReceivedResponse($dom_data_root, $sn) {
            /*
             * +++ CHANGE ME +++
             * You may need to modify this function if you wish to log
             * information or perform other actions when you receive
             * a Google Checkout <request-received> response. The <request-received> 
             * response indicates that you sent a properly formed XML request to 
             * Google Checkout. However, this response does not indicate whether your 
             * request was processed successfully.
             */
        
            $this->LogMessage ("Google Checkout: Request Received Response", $debug_only_msg = true);
            
            $this->SendNotificationAcknowledgment($sn);
        
        }
        
        /**
         * The ProcessErrorResponse function receives a synchronous Google Checkout 
         * response to an API request originating from your site. This function 
         * indicates that your API request was not processed. A request might not be
         * processed if it does not contain properly formed XML or if it does not 
         * contain a valid merchant ID and merchant key.
         *
         * @param    $xml_response    synchronous response XML message
         */
        function ProcessErrorResponse($dom_data_root, $sn) {
            /*
             * +++ CHANGE ME +++
             * You may need to modify this function if you wish to log
             * information or perform other actions when you receive
             * a Google Checkout <error> response. The <error> response indicates 
             * that you sent an invalid XML request to Google Checkout and 
             * contains information explaining why the request was invalid.
             */

            if ($dom_data_root['error-message'])
                $this->LogMessage ("Google Checkout Error: " . $dom_data_root['error-message'], $debug_only_msg = false);
            
            //$this->SendNotificationAcknowledgment($sn); //disabled to avoid XML errors on signup page
            
        }
        
        /**
         * The ProcessDiagnosisResponse function receives a synchronous Google
         * Checkout response to an API request sent to the Google Checkout
         * XML validator. You can submit a request to the validator by appending
         * the text "/diagnose" to the POST target URL. The response to a
         * diagnostic request contains a list of any warnings returned by
         * the Google Checkout validator.
         *
         * @param    $xml_response    synchronous response XML message
         */
        function ProcessDiagnosisResponse($dom_data_root, $sn) {
            /*
             * +++ CHANGE ME +++
             * You may need to modify this function if you wish to log
             * warnings or perform other actions when you receive
             * a Google Checkout <diagnosis> response. The <diagnosis> response 
             * contains warnings that the Google Checkout XML validator generated 
             * when evaluating your XML request.
             */

            $this->LogMessage ("Google Checkout: Diagnosis Response", $debug_only_msg = true);
            
            $this->SendNotificationAcknowledgment($sn);
        }
        
        /**
         * The ProcessCheckoutRedirect function receives a synchronous Google
         * Checkout response to a Checkout API request. The <checkout-redirect>
         * response identifies the URL to which you should redirect your customer
         * so that the customer can complete an order using Google Checkout.
         *
         * @param    $xml_response    synchronous response XML message
         */
        function ProcessCheckoutRedirect($dom_data_root, $sn) {
        
            //$this->SendNotificationAcknowledgment($sn);
        
            // Identify the URL to which the customer should be redirected
        	$redirect_url = $dom_data_root['redirect-url'];
        	$redirect_url = str_replace("shoppingcartshoppingcart", "shoppingcart&shoppingcart", $redirect_url);
            html_redirect($redirect_url, '', 'Please wait', 'Please wait');
            exit();
        
        }

        /////////////////////////////////////////////
        // Checkout API
        /////////////////////////////////////////////

        
        /**
         * The CreateUsCountryArea function is a wrapper function that calls the
         * CreateUsPlaceArea function. The CreateUsPlaceArea function, in turn,
         * creates and returns a <us-country-area> XML block.
         *
         * @param   $area_place       The U.S. region that should be included
         *                                in the XML block. Valid values are
         *                                CONTINENTAL_48, FULL_50_STATES and ALL.
         * @return  <us-country-area> XML
         */
        function CreateUsCountryArea($area_place) {
            return $this->CreateUsPlaceArea("country", $area_place);
        }
        
        /**
         * The CreateUsStateArea function is a wrapper function that calls the
         * CreateUsPlaceArea function. The CreateUsPlaceArea function, in turn,
         * creates and returns a <us-state-area> XML block.
         *
         * @param   $area_place     The U.S. state that should be included
         *                              in the XML block. The value should be a
         *                              two-letter U.S. state abbreviation.
         * @return  <us-state-area> XML
         */
        function CreateUsStateArea($area_place) {
            return $this->CreateUsPlaceArea("state", $area_place);
        }
        
        /**
         * The CreateUsZipArea function is a wrapper function that calls the
         * CreateUsPlaceArea function. The CreateUsPlaceArea function, in turn,
         * creates and returns a <us-zip-area> XML block.
         *
         * @param   $area_place   The zip code that should be included
         *                            in the XML block. The value should be a
         *                            five-digit zip code or a zip code pattern.
         * @return  <us-zip-area> XML
         */
        function CreateUsZipArea($area_place) {
            return $this->CreateUsPlaceArea("zip", $area_place);
        }
        
        /**
         * The CreateUsPlaceArea function creates <us-country-area>, 
         * <us-state-area> and <us-zip-area> XML blocks.
         *
         * @param   $area_type       The type of XML object to be created. Valid
         *                               values are "country", "state" and "zip".
         * @param   $area_place      This value corresponds to the accepted
         *                               $area_place parameter values for the
         *                               CreateUsCountryArea, CreateUsStateArea and
         *                               CreateUsZipArea functions.
         * @return  $dom_area_obj    <us-country-area>, <us-state-area> 
         *                               or <us-zip-area> XMLDOM 
         */
        function CreateUsPlaceArea($area_type, $area_place) {
        
            $error_function_name = "CreateUsPlaceArea(" . $area_type . ":" . $area_place . ")";
        
            // The area_type must be specified for the function call to execute.
            $this->CheckForError($this->_mp_type, $error_function_name, $area_type, $area_place);
        
            $area = array("us-" . $area_type . "-area" => array());
        
            /*
             * Create the elements that contain the $area_place data
             */
            if ($area_type == "state") {
                $area["us-" . $area_type . "-area"]['state'] = $area_place;
        
            } elseif ($area_type == "zip") {
                $area["us-" . $area_type . "-area"]['zip-pattern'] = $area_place;
        
            } elseif ($area_type == "country") {
                $area["us-" . $area_type . "-area"] = array('_attributes' => array('country-area' => $area_place));
            }
        
            return $area;
        }
        
        /**
         * The getTaxArea function creates a <tax-area> XML DOM, which identifies
         * a geographic region where a tax rate applies.
         *
         * @param   $tax_area_type       Valid values are "country", 
         *                                   "state" and "zip"
         * @param   $tax_area_place      See the valid values for the
         *                                   $area_place parameter of the
         *                                   CreateUsPlaceArea function 
         * @return  $dom_tax_area_obj    <tax-area> XML containing the 
         *                                   child elements that correspond to
         *                                   the specified $area_type
         */
        function getTaxArea($area_type, $area_place) {
        
            $error_function_name = "getTaxArea(" . $area_type . ")";
        
            // You must provide an $area_type value or the function will not execute.
            $this->CheckForError($this->_mp_type, $error_function_name, $area_type, $area_type);
        
            // Create the <tax-area> element
            $tax_area = array('tax-area' => array());
        
            /*
             * Call the CreateUsPlaceArea function to create the child
             * elements of the <tax-area> element
             */
            $area = $this->CreateUsPlaceArea($area_type, $area_place);
            $tax_area['tax-area']["us-" . $area_type . "-area"] = $area["us-" . $area_type . "-area"];
        
            return $tax_area;
        }
        
        
        /**
         * The addDefaultTaxRule function creates and returns a
         * <default-tax-rule> XML DOM.
         *
         * @param   $rate              The tax rate to assess for a
         *                                 given tax rule.
         * @param   $dom_tax_area      An XML DOM that identifies the
         *                                 area where a tax rate should be applied.
         * @param   $shipping_taxed    A Boolean value that indicates
         *                                 whether shipping costs are taxed
         *                                 in the specified tax area.
         * @return  $dom_default_tax_rule_obj    
         *                             <default-tax-rule> XML
         */
        function addDefaultTaxRule($rate, $tax_area, $shipping_taxed="") {
        
            $error_function_name = "CreateDefaultTaxRule()";
        
            /*
             * You must specify a $rate and provide a $dom_tax_area object
             * for each tax rule
             */
            $this->CheckForError($this->_mp_type, $error_function_name, "rate", $rate);
            $this->CheckForError($this->_mp_type, $error_function_name, "tax_area", $tax_area);
        
            $default_tax_rule = array();
        
            // Add a <shipping-taxed> element if a $shipping_taxed value is provided
            if ($shipping_taxed != "") {
                $default_tax_rule['shipping-taxed'] = $shipping_taxed;
            }
        
            // Add the tax rate for the tax rule
            $default_tax_rule['rate'] = $rate;
        
            // Add the tax area to the tax rule
            $default_tax_rule['tax-area'] = $tax_area['tax-area'];
       
        	if (empty($this->_default_tax_rules))
        	    $this->_default_tax_rules = array();
        	
        	$this->_default_tax_rules[] = $default_tax_rule;
        	
        	return $default_tax_rule;

        }
        
        
        /**
         * The addAlternateTaxRule function creates and returns an
         * <alternate-tax-rule> XML DOM.
         *
         * @param   $rate                    tax rate
         * @param   $dom_tax_area            <tax-area> XML DOM
         * @return  $dom_alt_tax_rule_obj    <alternate-tax-rule> XML DOM
         */
        function addAlternateTaxRule($rate, $tax_area) {
        
            // Check for errors
            $error_function_name = "CreateAlternateTaxRule()";
        
            /*
             * You must specify a $rate and provide a $dom_tax_area object
             * for each tax rule
             */
            $this->CheckForError($this->_mp_type, $error_function_name, "rate", $rate);
            $this->CheckForError($this->_mp_type, $error_function_name, "tax_area", $tax_area);
        
            $alt_tax_rule = array();
        
            // Add the tax rate for the tax rule
            $alt_tax_rule['rate'] = $rate;
        
            // Add the tax area to the tax rule
            $alt_tax_rule['tax-area'] = $tax_area['tax-area'];
        
        	if (empty($this->_alternate_tax_rules))
        	    $this->_alternate_tax_rules = array();
        	
        	$this->_alternate_tax_rules[] = $alt_tax_rule;
        	
        	return $alt_tax_rule;
        }
        
        
        /**
         * The CreateAlternateTaxTable function creates and returns an
         * <alternate-tax-table> XML DOM. The XML will contain any 
         * <alternate-tax-rule> elements that have not already been included
         * in an <alternate-tax-table>.
         *
         * @param   $standalone    A Boolean value that indicates how taxes
         *                             should be calculated if there is no
         *                             matching <alternate-tax-rule> for the 
         *                             customer's area.
         * @param   $name          A name that is used to identify the tax table
         * @return  $dom_alt_tax_tables_obj
         *                         <alternate-tax-table> XML DOM
         */
        function addAlternateTaxTable($standalone, $name) {
        
            /*
             * There must be at least one alternate tax rule to include
             * in the <alternate-tax-table>. This tax table will include
             * any <alternate-tax-rule> elements that were created since
             * after the last call to the CreateAlternateTaxTable function.
             */
            if (empty($this->_alternate_tax_rules)) {
                $this->LogMessage("Google Checkout: You must have at least one alternate tax rule.", $debug_only_msg = false);
            }
            
            // Check for errors
            $error_function_name = "CreateAlternateTaxTable()";
            
            /*
             * You must specify values for the $standalone and $name parameters
             */
            $this->CheckForError($this->_mp_type, $error_function_name, "standalone", $standalone);
            $this->CheckForError($this->_mp_type, $error_function_name, "name", $name);
        
            /*
             * Create an <alternate-tax-tables> element, if one has not yet
             * been created, to contain all <alternate-tax-table> elements
             */
            if (empty($this->_alternate_tax_tables)) {
                $this->_alternate_tax_tables = array();
            }
            
            // Create the <alternate-tax-table> element
            $alternate_tax_table = array('_attributes' => array(
                                                                'name' => $name,
                                                                'standalone' => $standalone
                                                                ));
            // Create the <alternate-tax-rules> object
            $alternate_tax_table['alternate-tax-rules'] = $this->_alternate_tax_rules;

        	$serializer_options =  array(
        	                    "addDecl"=> false,
        						"indent"=>"     ",
        						"encoding" =>"UTF-8",
        						"rootName" => 'alternate-tax-table',
        						"scalarAsAttributes" => false,
                        		"attributesArray"    => '_attributes',
                        		"contentName"        => '_content',
                        		"defaultTagName"	 => 'alternate-tax-rule',
                        		"replaceEntities"    => XML_SERIALIZER_ENTITIES_NONE
        						);
        
        	$serializer = new XML_Serializer($serializer_options);
        	$rslt = $serializer->serialize($alternate_tax_table);
        	$alternate_tax_table_xml = $serializer->getSerializedData();
        	
        	$this->_alternate_tax_tables[] = $alternate_tax_table_xml;
            
            // Clean alternate_tax_rules array
            $this->_alternate_tax_rules = array();
            
            return $alternate_tax_table;
        
        }
        
        
        /**
         * The CreateTaxTables element constructs the <tax-tables> XML.
         *
         * @param   $merchant_calculated    A Boolean value that indicates
         *                                      whether tax for the order is
         *                                      calculated using a special process.
         * @return  $dom_tax_tables_obj     <tax-tables> XML DOM
         */
        function CreateTaxTables($merchant_calculated) {
        
            // Check for errors
            $error_function_name = "CreateTaxTables()";
           
            /*
             * You must have already created the <default-tax-table> XML DOM
             * before calling this function. You must also provide a value
             * for the $merchant_calculated parameter.
             */
            if (empty($this->_default_tax_rules)) {
                $this->LogMessage("Google Checkout: You must have have at least one default tax rule.", $debug_only_msg = false);
            }
            $this->CheckForError($this->_mp_type, $error_function_name, "merchant_calculated", $merchant_calculated);
        
            if (empty($this->_tax_tables))
                $this->_tax_tables = array();

            // Set the $merchant-calculated attribute on the <tax-tables> element
            if ($merchant_calculated != "") {
                    $this->_tax_tables = array('_attributes' => array('merchant-calculated' => $merchant_calculated));
            }
            
            $tax_rules = array();
            foreach ((array)$this->_default_tax_rules as $tax_rule){
                $tax_rules[] = $tax_rule;
            }
            $default_tax_table = array();
            $default_tax_table['tax-rules'] = $tax_rules;

        	$serializer_options =  array(
        	                    "addDecl"=> false,
        						"indent"=>"     ",
        						"encoding" =>"UTF-8",
        						"rootName" => 'REMOVE',
        						"scalarAsAttributes" => false,
                        		"attributesArray"    => '_attributes',
                        		"contentName"        => '_content',
                        		"defaultTagName"	 => 'default-tax-rule',
                        		"replaceEntities"    => XML_SERIALIZER_ENTITIES_NONE
        						);
        
        	$serializer = new XML_Serializer($serializer_options);
        	$rslt = $serializer->serialize($default_tax_table);
        	$default_tax_table = $this->_removeTag($serializer->getSerializedData());
        	$this->_tax_tables['default-tax-table'] = $default_tax_table;

            $alternate_tax_tables = array();
            foreach ((array)$this->_alternate_tax_tables as $alternate_tax_table){
                $alternate_tax_tables[] = $alternate_tax_table;
            }

        	$serializer_options =  array(
        	                    "addDecl"=> false,
        						"indent"=>"     ",
        						"encoding" =>"UTF-8",
        						"rootName" => 'REMOVE',
        						"scalarAsAttributes" => false,
                        		"attributesArray"    => '_attributes',
                        		"contentName"        => '_content',
                        		"defaultTagName"	 => 'REMOVE',
                        		"replaceEntities"    => XML_SERIALIZER_ENTITIES_NONE
        						);
        
        	$serializer = new XML_Serializer($serializer_options);
        	$rslt = $serializer->serialize($alternate_tax_tables);
        	$alternate_tax_tables = $this->_removeTag($serializer->getSerializedData());
/////        	$this->_tax_tables['alternate-tax-tables'] = $alternate_tax_tables;
        	
        	return $this->_tax_tables;

        }
        
        
        /**
         * The setMerchantCalculations function creates and returns a 
         * <merchant-calculations> XML DOM.
         *
         * @param   $merchant_calc_url           Callback URL for merchant 
         *                                           calculations
         * @param   $accept_merchant_coupons     Boolean value that indicates
         *                                           whether Google Checkout should 
         *                                           display an option for customers 
         *                                           to enter coupon codes for an 
         *                                           order
         * @param   $accept_gift_certificates    Boolean value that indicates
         *                                           whether Google Checkout should 
         *                                           display an option for customers 
         *                                           to enter gift certificate codes 
         * @return  $dom_merchant_calc_obj       <merchant-calculations> XML DOM
         */
        function setMerchantCalculations($merchant_calc_url, $accepts_coupons="", $accept_gift_certificates="") {
        
            // Verify that there is a value for the $merchant_calc_url parameter
            $error_function_name = "CreateMerchantCalculations()";
            $this->CheckForError($this->_mp_type, $error_function_name, "merchant_calc_url", $merchant_calc_url);
        
            // Create the <merchant-calculations> element
            if (empty($this->_merchant_calc)) {
                $this->_merchant_calc = array();
            }
        
            // Create the <merchant-calculations-url> element
            $this->_merchant_calc['merchant-calculations-url'] = $merchant_calc_url;
        
            // Create the <accepts-merchant-coupons> element
            if ($accepts_coupons != "") {
                $this->_merchant_calc['accept-merchant-coupons'] = $accepts_coupons;
            }
        
            // Create the <accepts-gift-certificates> element
            if ($accept_gift_certificates != "") {
                $this->_merchant_calc['accept-gift-certificates'] = $accept_gift_certificates;
            }
        
           return $this->_merchant_calc;
        }
        
        /////////////////////////////////////////////
        // Notification API
        /////////////////////////////////////////////

        function ProcessCancellationNotification($dom_data_root, $sn) {
            
            global $db;
            /*
            <?xml version="1.0" encoding="UTF-8"?>
            <cancelled-subscription-notification xmlns="http://checkout.google.com/schema/2" serial-number="***************-*****-*">
            <item-ids />
            <reason>Customer request to cancel</reason>
            <timestamp>YYYY-MM-DDTHH:MM:SS.***Z</timestamp>
            <google-order-number>***************</google-order-number>
            </cancelled-subscription-notification> 
            */
            $this->LogMessage ("Google Checkout: Cancellation Notification #" . $dom_data_root['google-order-number'], $debug_only_msg = false);
            
            $payment_id = $this->find_last_payment($dom_data_root['google-order-number']);
            $payment = $db->get_payment($payment_id);
            $payment['data']['CANCELLED'] = 1;
            $payment['data']['CANCELLED_AT'] = strftime($config['time_format'], time());
            $db->update_payment($payment['payment_id'], $payment);

            $this->SendNotificationAcknowledgment($sn);
        }

        function ProcessRebillingNotification($dom_data_root, $sn) {
            
            global $db;
            /*
            <?xml version="1.0" encoding="UTF-8"?>
            <subscription-request-received xmlns="http://checkout.google.com/schema/2" new-google-order-number="***************" serial-number="********-****-****-****-************" /> 
            */
            $this->LogMessage ("Google Checkout: Re-billing Notification received [" . $dom_data_root['new-google-order-number'] . "]", $debug_only_msg = false);

            $this->SendNotificationAcknowledgment($sn);
        }


        /**
         * The ProcessNewOrderNotification function is a shell function for 
         * handling a <new-order-notification>. You will need to modify this 
         * function to transfer the information contained in a 
         * <new-order-notification> to your internal systems that process that data.
         *
         * @param    $xml_response    asynchronous notification XML DOM
         */
        function ProcessNewOrderNotification($dom_data_root, $sn) {
            /*
             * +++ CHANGE ME +++
             * New order notifications inform you of new orders that have
             * been submitted through Google Checkout. A <new-order-notification>
             * message contains a list of the items in an order, the tax
             * assessed on the order, the shipping method selected for the
             * order and the shipping address for the order.
             *
             * If you are implementing the Notification API, you need to
             * modify this function to relay the information in the
             * <new-order-notification> to your internal systems that
             * process this order data.
             */
            
            global $db;
            
            $this->LogMessage ("Google Checkout: New Order Notification #" . $dom_data_root['google-order-number'], $debug_only_msg = false);

//$this->LogMessage ("DEBUG: [".serialize($dom_data_root)."]");

            $cart = $dom_data_root['shopping-cart'];
            $payment_id = $cart['merchant-private-data']['payment-id'];
            $memeber_id  = $cart['merchant-private-data']['memeber-id'];

            $payments = array();
            if ($payment_id) $payments[] = $payment_id;

	    $buyer = $dom_data_root['buyer-billing-address'];
	    $email = trim($buyer['email']);
	    $users = $db->users_find_by_string($email, 'email', $exact=1);

	    if (!$memeber_id && !$payment_id && $this->_allow_create){
		// create new member/subscription

		if (!$users && check_email($email)){
		    // No member exists. Create new account.

			$name_f = trim($buyer['structured-name']['first-name']);
			$name_l = trim($buyer['structured-name']['last-name']);
                	if (!$name_f && !$name_l)
                    	    list ($name_f, $name_l) = @explode(" ", $buyer['contact-name']);

			$address = $buyer['address1'];
			$city = $buyer['city'];
			$zip = $buyer['postal-code'];
			$state = $buyer['region'];
			$country = $buyer['country-code'];
			$phone = $buyer['phone'];

		        $v = array(
		            'email' => $email,
		            'name_f' => $name_f,
		            'name_l' => $name_l,
		            'address' => $address,
		            'city' => $city,
		            'zip' => $zip,
		            'state' => $state,
		            'country' => $country
		        );
		        $v['login'] = generate_login($v);
		        $v['pass'] = generate_password($v);
		    	$member_id = $db->add_pending_user($v);

		        // and add payment(s)
			foreach ((array)$dom_data_root['shopping-cart']['items'] as $item){
				$products = $db->get_products_list();
				foreach ($products as $pr){
				    if ($pr['google_merchant_item_id'] != '' && $pr['google_merchant_item_id'] == $item['merchant-item-id']){
				    	$product_id = $pr['product_id'];

    			        	$product = $db->get_product($product_id);
	    		        	$price = $product['price'];
	    		        	$begin_date = date("Y-m-d");
	    		                $duration = $this->get_days($product['expire_days']) * 3600 * 24;
	    		                $expire_date = date('Y-m-d', time() + $duration);
	    		        	$payment_id = $db->add_waiting_payment($member_id, $product_id, $paysys_id='google_checkout', $price, $begin_date, $expire_date, $vars, $additional_values=false);
	    		        	
	    		        	if ($payment_id) $payments[] = $payment_id;
				    }
				}
			}
		    }

	    }

            
            //$member  = $db->get_user($memeber_id);
            foreach ($payments as $payment_id){
                $q = $db->query($s = "UPDATE {$db->config['prefix']}payments
                    SET receipt_id = '".$db->escape($dom_data_root['google-order-number'])."'
                    WHERE payment_id='$payment_id'
                    ");
            
                $payment = $db->get_payment($payment_id);
                $payment['receipt_id']                      = $dom_data_root['google-order-number'];
                $payment['data']['google-order-number']     = $dom_data_root['google-order-number'];
                $payment['data']['fulfillment-order-state'] = $dom_data_root['fulfillment-order-state'];
                $payment['data']['financial-order-state']   = $dom_data_root['financial-order-state'];
            
                $err = $db->update_payment($payment_id, $payment);
                if ($err)
                    $this->LogMessage($err, $debug_only_msg = false);
            }

            $this->SendNotificationAcknowledgment($sn);
        }
        
        /**
         * TheProcessOrderStateChangeNotification function is a shell function 
         * for handling a <order-state-change-notification>. You will need to 
         * modify this function to transfer the information contained in a 
         * <order-state-change-notification> to your internal systems that 
         * process that data.
         *
         * @param    $xml_response    asynchronous notification XML DOM
         */
        function ProcessOrderStateChangeNotification($dom_data_root, $sn) {
            /*
             * +++ CHANGE ME +++
             * Order state change notifications signal an update to an order's
             * financial status or its fulfillment status. An 
             * <order-state-change-notification> identifies the new financial 
             * and fulfillment statuses for an order. It also identifies the 
             * previous statuses for the order. Google Checkout will send an 
             * <order-state-change-notification> to confirm status changes that 
             * you trigger by using the Order Processing API requests. For 
             * example, if you send Google Checkout a <cancel-order> request, 
             * Google Checkout will respond through the Notification API to inform 
             * you that the order's status has been changed to "canceled".
             *
             * If you are implementing the Notification API, you need to
             * modify this function to relay the information in the
             * <order-state-change-notification> to your internal systems that
             * process financial or fulfillment status information.
             */
             
            global $db;
            
            $this->LogMessage ("Google Checkout: Order State Change Notification #" . $dom_data_root['google-order-number'].
                "<br />new-fulfillment-order-state: ".$dom_data_root['new-fulfillment-order-state'].
                "<br />new-financial-order-state: ".$dom_data_root['new-financial-order-state'], $debug_only_msg = false);
        
            $payment_id = $this->get_payment_by_data('google-order-number', $dom_data_root['google-order-number']);
            
            if ($payment_id > 0){
                $payment = $db->get_payment($payment_id);
                $payment['data']['fulfillment-order-state'] = $dom_data_root['new-fulfillment-order-state'];
                $payment['data']['financial-order-state']   = $dom_data_root['new-financial-order-state'];
                $err = $db->update_payment($payment_id, $payment);
                if ($err)
                    $this->LogMessage($err, $debug_only_msg = false);
            } else {
                $this->LogMessage("Google Checkout ProcessOrderStateChangeNotification:\n get_payment_by_data('google-order-number', ".$dom_data_root['google-order-number'].")", $debug_only_msg = true);
            }
             
            $this->SendNotificationAcknowledgment($sn);
        }
        
        /**
         * The ProcessChargeAmountNotification function is a shell function for 
         * handling a <charge-amount-notification>. You will need to modify this 
         * function to relay the information in the <charge-amount-notification>
         * to your internal systems that process that data.
         *
         * @param    $xml_response    asynchronous notification XML DOM
         */
        function ProcessChargeAmountNotification($dom_data_root, $sn) {
            /*
             * +++ CHANGE ME +++
             * Charge amount notifications inform you that a customer has been
             * charged for either the full amount or a partial amount of an
             * order. A <charge-amount-notification> contains the order number
             * that Google assigned to the order, the value of the most recent
             * charge to the customer and the total amount that has been
             * charged to the customer for the order. Google Checkout will send a
             * <charge-amount-notification> after charging the customer.
             *
             * If you are implementing the Notification API, you need to
             * modify this function to relay the information in the
             * <charge-amount-notification> to your internal systems that
             * process this order data.
             */
        
            global $db;
            
            $this->LogMessage ("Google Checkout: Charge Amount Notification #" . $dom_data_root['google-order-number'], $debug_only_msg = false);
        
            $payment_id = $this->get_payment_by_data('google-order-number', $dom_data_root['google-order-number']);
            if ($payment_id > 0){
                $payment = $db->get_payment($payment_id);
                $payment['data']['charge-amount-notification'] = $dom_data_root;
                $err = $db->update_payment($payment_id, $payment);
                if ($err)
                    $this->LogMessage($err, $debug_only_msg = false);
                
                $err = $db->finish_waiting_payment($payment['payment_id'], $payment['paysys_id'], $payment['receipt_id'], $payment['amount'], $vars='', $payer_id='');
                if ($err) {
                    $this->LogMessage($err . ": payment_id = $payment[payment_id]", $debug_only_msg = false);
                } else {
                    if ($payment['data']['google_is_rebilling'])
                        $db->query("UPDATE {$db->config['prefix']}rebill_log
                            SET status = 0
                            WHERE rebill_payment_id = '".intval($payment['payment_id'])."'
                            AND status = 3");
                }
                
                //mail_rebill_success_member($member, $payment_id, $product);
            }
        
            //$xml = $this->CreateAddTrackingData($dom_data_root['google-order-number'], $carrier, $tracking_number);
            $xml = $this->CreateDeliverOrder($dom_data_root['google-order-number'], $carrier="", $tracking_number="");
            
            $response = $this->SendRequest($xml, 'request');
            $res = $this->ProcessXmlData ($response);
            
            $this->SendNotificationAcknowledgment($sn);
        }
        
        /**
         * The ProcessChargebackAmountNotification function is a shell function 
         * for handling a <chargeback-amount-notification>. You will need to 
         * modify this function to transfer the information contained in a 
         * <chargeback-amount-notification> to your internal systems that 
         * process that data.
         *
         * @param    $xml_response    asynchronous notification XML DOM
         */
        function ProcessChargebackAmountNotification($dom_data_root, $sn) {
            /*
             * +++ CHANGE ME +++
             * Chargeback amount notifications inform you that a customer 
             * has initiated a chargeback against an order and that Google Checkout 
             * has approved the chargeback. A <chargeback-amount-notification> 
             * contains the order number that Google assigned to the order, 
             * the value of the most recent chargeback against the order
             * and the total amount that has been charged back against the 
             * order. Google Checkout will send a <chargeback-amount-notification> 
             * after approving the chargeback.
             *
             * If you are implementing the Notification API, you need to
             * modify this function to relay the information in the
             * <chargeback-amount-notification> to your internal systems that
             * process this order data.
             */
            global $db;
            
            $this->LogMessage ("Google Checkout: Chargeback Amount Notification #" . $dom_data_root['google-order-number'], $debug_only_msg = false);
        
            $payment_id = $this->get_payment_by_data('google-order-number', $dom_data_root['google-order-number']);
            if ($payment_id > 0){
                $payment = $db->get_payment($payment_id);
                $payment['data']['google-chargeback-amount'] = $dom_data_root['chargeback-amount-notification'];
                $payment['completed'] = 0;
                $err = $db->update_payment($payment_id, $payment);
                if ($err)
                    $this->LogMessage($err, $debug_only_msg = false);
            }
        
            $this->SendNotificationAcknowledgment($sn);
        }
        
        /**
         * The ProcessRefundAmountNotification function is a shell function for 
         * handling a <refund-amount-notification>. You will need to modify this 
         * function to transfer the information contained in a 
         * <refund-amount-notification> to your internal systems that handle that data.
         *
         * @param    $xml_response    asynchronous notification XML DOM
         */
        function ProcessRefundAmountNotification($dom_data_root, $sn) {
            /*
             * +++ CHANGE ME +++
             * Refund amount notifications inform you that a customer has been
             * refunded either the full amount or a partial amount of an order
             * total. A <refund-amount-notification> contains the order number
             * that Google assigned to the order, the value of the most recent
             * refund to the customer and the total amount that has been
             * refunded to the customer for the order. Google Checkout will send a
             * <refund-amount-notification> after refunding the customer.
             *
             * If you are implementing the Notification API, you need to
             * modify this function to relay the information in the
             * <refund-amount-notification> to your internal systems that
             * process this order data.
             */
            global $db;
            
            $this->LogMessage ("Google Checkout: Refund Amount Notification #" . $dom_data_root['google-order-number'], $debug_only_msg = false);
        
            $payment_id = $this->get_payment_by_data('google-order-number', $dom_data_root['google-order-number']);
            if ($payment_id > 0){
                $payment = $db->get_payment($payment_id);
                $payment['data']['google-refund-amount'] = $dom_data_root['refund-amount-notification'];
                $payment['completed'] = 0;
                $err = $db->update_payment($payment_id, $payment);
                if ($err)
                    $this->LogMessage($err, $debug_only_msg = false);
            }
        
            $this->SendNotificationAcknowledgment($sn);
        }
        
        /**
         * TheProcessRiskInformationNotification function is a shell function for 
         * handling a <risk-information-notification>. You will need to modify this 
         * function to transfer the information contained in a 
         * <risk-information-notification> to your internal systems that process 
         * that data.
         * @param    $xml_response    asynchronous notification XML DOM
         */
        function ProcessRiskInformationNotification($dom_data_root, $sn) {
            /*
             * +++ CHANGE ME +++
             * Risk information notifications provide financial information about
             * a transaction to help you ensure that an order is not fraudulent.
             * A <risk-information-notification> includes the customer's billing
             * address, a partial credit card number and other values to help you
             * verify that an order is not fraudulent. Google Checkout will send you a
             * <risk-information-notification> message after completing its
             * risk analysis on a new order.
             *
             * If you are implementing the Notification API, you need to
             * modify this function to relay the information in the
             * <risk-information-notification> to your internal systems that
             * process this order data.
             */
            
            global $db;
            
            $this->LogMessage ("Google Checkout: Risk Information Notification #" . $dom_data_root['google-order-number'], $debug_only_msg = false);
        
            $payment_id = $this->get_payment_by_data('google-order-number', $dom_data_root['google-order-number']);
            $ri = $dom_data_root['risk-information'];
        
            if ($payment_id > 0){
                $payment = $db->get_payment($payment_id);
                $payment['data']['google-risk-information'] = $ri;
                $err = $db->update_payment($payment_id, $payment);
                if ($err)
                    $this->LogMessage($err, $debug_only_msg = false);
        
                /*
                <risk-information>
                Boolean.
                If true, order is covered by Google's Chargeback Resolution Policy and is eligible for Google's Payment Guarantee.
                If false, order is covered by Google's Chargeback Resolution Policy.
                (See Google Checkout Program Policies and Guidelines, section 7).  
                http://checkout.google.com/seller/policies.html      
                */
                if ( (is_bool($ri['eligible-for-protection']) && $ri['eligible-for-protection']) ||
                     ($ri['eligible-for-protection'] == 'true') || $this->_debug){
                        
                    $xml = $this->CreateChargeOrder($dom_data_root['google-order-number'], $payment['amount']);
                    $response = $this->SendRequest($xml, 'request');
                    $res = $this->ProcessXmlData ($response);
                    
                } else {
        
                    $db->delete_payment($payment_id);
                    $xml = $this->CreateCancelOrder($dom_data_root['google-order-number'], $reason="order is covered by Google's Chargeback Resolution Policy. (See http://checkout.google.com/seller/policies.html - Google Checkout Program Policies and Guidelines, section 7).", $comment="");
                    $response = $this->SendRequest($xml, 'request');
                    $res = $this->ProcessXmlData ($response);
            
                }
            }
            
            
            $this->SendNotificationAcknowledgment($sn);
        }
        
        /**
         * The SendNotificationAcknowledgment function responds to a Google Checkout 
         * notification with a <notification-acknowledgment> message. If you do 
         * not send a <notification-acknowledgment> in response to a Google Checkout 
         * notification, Google Checkout will resend the notification multiple times.
         */
        function SendNotificationAcknowledgment($sn = '') {

            $vars = get_input_vars();
            if (!$sn && $vars['serial-number'])
                $sn = $vars['serial-number'];
            if ($sn)
                $sn = " serial-number=\"".$sn."\"";            

            $acknowledgment = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<notification-acknowledgment xmlns=\"http://checkout.google.com/schema/2\"".$sn." />";

	        header ("Content-Type: application/xml; charset=UTF-8");
	        echo $acknowledgment;
	        $this->LogMessage ("Google Checkout: Ack sent: $acknowledgment", $debug_only_msg = true);
            
            // don't work for unknown reason:
            // Error parsing XML; message from parser is: Unexpected element (notification-acknowledgment) encountered: notification-acknowledgment
            //
            //$response = $this->SendRequest($acknowledgment, 'request');
            
        }


        /////////////////////////////////////////////
        // Order Processing API
        /////////////////////////////////////////////


        /**
         * The CreateArchiveOrder function is a wrapper function that calls the
         * ChangeOrderState function. The ChangeOrderState function, in turn,
         * creates an <archive-order> command for the specified order, which is
         * identified by its Google Checkout order number ($google_order_number). The 
         * <archive-order> command moves an order from the Inbox in the Google Checkout
         * Merchant Center to the Archive folder.
         *
         * @param   $google_order_number    A number, assigned by Google Checkout, 
         *                                      that uniquely identifies an order.
         * @return  <archive-order> XML
         */
        function CreateArchiveOrder($google_order_number) {
            return $this->ChangeOrderState($google_order_number, "archive");
        }
        
        /**
         * The CreateCancelOrder function is a wrapper function that calls the
         * ChangeOrderState function. The ChangeOrderState function, in turn,
         * creates a <cancel-order> command for the specified order, which is
         * identified by its Google Checkout order number ($google_order_number). The 
         * <cancel-order> command instructs Google Checkout to cancel an order.
         *
         * @param   $google_order_number    A number, assigned by Google Checkout, 
         *                                      that uniquely identifies an order.
         * @param   $reason                 The reason an order is being canceled
         * @param   $comment                A comment pertaining to a canceled order
         * @return  <cancel-order> XML
         */
        function CreateCancelOrder($google_order_number, $reason, $comment="") {
            return $this->ChangeOrderState($google_order_number, "cancel", $reason, 0, $comment);
        }
        
        /**
         * The CreateChargeOrder function is a wrapper function that calls the
         * ChangeOrderState function. The ChangeOrderState function, in turn,
         * creates a <charge-order> command for the specified order, which is
         * identified by its Google Checkout order number ($google_order_number). The
         * <charge-order> command prompts Google Checkout to charge the customer for an 
         * order and to change the order's financial order state to "CHARGING".
         *
         * @param   $google_order_number    A number, assigned by Google Checkout, 
         *                                      that uniquely identifies an order.
         * @param   $amount                 The amount that Google Checkout should 
         *                                      charge the customer
         * @return  <charge-order> XML
         */
        function CreateChargeOrder($google_order_number, $amount="") {
            return $this->ChangeOrderState($google_order_number, "charge", 0, $amount);
        }
        
        /**
         * The CreateProcessOrder function is a wrapper function that calls the
         * ChangeOrderState function. The ChangeOrderState function, in turn,
         * creates a <process-order> command for the specified order, which is
         * identified by its Google Checkout order number ($google_order_number). The
         * <process-order> command changes the order's fulfillment order state 
         * to "PROCESSING".
         *
         * @param   $google_order_number    A number, assigned by Google Checkout, 
         *                                      that uniquely identifies an order.
         * @return  <process-order> XML
         */
        function CreateProcessOrder($google_order_number) {
            return $this->ChangeOrderState($google_order_number, "process");
        }
        
        /**
         * The CreateRefundOrder function is a wrapper function that calls the
         * ChangeOrderState function. The ChangeOrderState function, in turn,
         * creates a <refund-order> command for the specified order, which is
         * identified by its Google Checkout order number ($google_order_number). The 
         * <refund-order> command instructs Google Checkout to issue a refund for an 
         * order.
         *
         * @param   $google_order_number    A number, assigned by Google Checkout, 
         *                                      that uniquely identifies an order.
         * @param   $reason                 The reason an order is being refunded
         * @param   $amount                 The amount that Google Checkout should 
         *                                      refund to the customer.
         * @param   $comment                A comment pertaining to a refunded order
         * @return  <refund-order> XML
         */
        function CreateRefundOrder($google_order_number, $reason, $amount="", $comment="") {
            return $this->ChangeOrderState($google_order_number, "refund", $reason, $amount, $comment);
        }
        
        /**
         * The CreateUnarchiveOrder function is a wrapper function that calls the
         * ChangeOrderState function. The ChangeOrderState function, in turn,
         * creates an <unarchive-order> command for the specified order, which is
         * identified by its Google Checkout order number ($google_order_number). The 
         * <unarchive-order> command moves an order from the Archive folder in the
         * Google Checkout Merchant Center to the Inbox.
         *
         * @param   $google_order_number    A number, assigned by Google Checkout, 
         *                                      that uniquely identifies an order.
         * @return  <unarchive-order> XML
         */
        function CreateUnarchiveOrder($google_order_number) {
            return $this->ChangeOrderState($google_order_number, "unarchive");
        }
        
        /**
         * The CreateDeliverOrder function is a wrapper function that calls the
         * ChangeShippingInfo function. The ChangeShippingInfo function, in turn,
         * creates an <deliver-order> command for the specified order, which is
         * identified by its Google Checkout order number ($google_order_number). The 
         * <deliver-order> command changes the order's fulfillment order state 
         * to "DELIVERED". It can also be used to add shipment tracking information
         * for an order.
         *
         * @param   $google_order_number    A number, assigned by Google Checkout, 
         *                                      that uniquely identifies an order.
         * @param   $carrier                The carrier handling an order shipment
         * @param   $tracking_number        The tracking number assigned to an
         *                                      order shipment by the shipping carrier
         * @return  <deliver-order> XML
         */
        function CreateDeliverOrder($google_order_number, $carrier="", $tracking_number="") {
            return $this->ChangeShippingInfo($google_order_number, "deliver-order", $carrier, $tracking_number);
        }
        
        /**
         * The CreateAddTrackingData function is a wrapper function that calls the
         * ChangeShippingInfo function. The ChangeShippingInfo function, in turn,
         * creates an <add-tracking-data> command for the specified order, which is
         * identified by its Google Checkout order number ($google_order_number). The 
         * <add-tracking-data> command adds shipment tracking information to an order.
         *
         * @param   $google_order_number    A number, assigned by Google Checkout, 
         *                                      that uniquely identifies an order.
         * @param   $carrier                The carrier handling an order shipment
         * @param   $tracking_number        The tracking number assigned to an
         *                                      order shipment by the shipping carrier
         * @return  <add-tracking-data> XML
         */
        function CreateAddTrackingData($google_order_number, $carrier, $tracking_number) {
            return $this->ChangeShippingInfo($google_order_number, "add-tracking-data", $carrier, $tracking_number);
        
        }
        
        /**
         * The ChangeOrderState function creates XML documents used to send 
         * Order Processing API commands to Google Checkout. This function creates 
         * the XML for the following commands:
         *         <archive-order>
         *         <cancel-order>
         *         <charge-order>
         *         <process-order>
         *         <refund-order>
         *         <unarchive-order>
         * 
         * @param   $google_order_number    A number, assigned by Google Checkout, 
         *                                      that uniquely identifies an order.
         * @param   $function_name          The type of command that should be
         *                                      created. Valid values for this
         *                                      parameter are "archive", "cancel",
         *                                      "charge", "process", "refund" and
         *                                      "unarchive".
         * @param   $reason                 The reason an order is being refunded
         * @param   $amount                 The amount that Google Checkout should 
         *                                      charge or refund to the customer.
         * @param   $comment                A comment pertaining to a refunded order
         * @return  XML corresponding to the specified $function_name
         */
        function ChangeOrderState($google_order_number, $function_name, $reason="", $amount="", $comment="") {
            
            $this->_mp_type = 'MISSING_PARAM';
            /*
             * Verify that the necessary parameter values have been provided.
             * The $google_order_number and $function_name parameters are
             * required for all commands. The $reason parameter is required 
             * for <cancel-order> and <refund-order> commands. In addition,
             * if an $amount is provided for either the <charge-order> or
             * <refund-order> commands, then the $GLOBALS["currency"] variable
             * must also have a value.
             */
            $error_function_name = "ChangeOrderState(" . $function_name . ")";
            $this->CheckForError($this->_mp_type, $error_function_name, "google_order_number", $google_order_number);
            if ($function_name == "cancel" || $function_name == "refund") {
                $this->CheckForError($this->_mp_type, $error_function_name, "reason", $reason);
            }
        
            if ($function_name == "charge" || $function_name == "refund") {
                $error_type = "MISSING_CURRENCY";
                if ($amount != "" && $this->_checkout_currency == "") {
                    $this->LogMessage(error_msg($error_type, $error_function_name), $debug_only_msg = false);
                }
            }
        
        	$serializer_options =  array(
        	                    "addDecl"=> true,
        						"indent"=>"     ",
        						"encoding" =>"UTF-8",
        						"rootName" => $function_name."-order",
        						"rootAttributes" 	 => array(
        						    "xmlns" => $this->_checkout_xml_schema,
        						    "google-order-number" => $google_order_number
        						    ),
        						"scalarAsAttributes" => false,
                        		"attributesArray"    => '_attributes',
                        		"contentName"        => '_content',
                        		"defaultTagName"	 => 'item',
                        		"replaceEntities"    => XML_SERIALIZER_ENTITIES_NONE
        						);
        
        
        	$change_order_state	= array();
        	if (($function_name == "charge" || $function_name == "refund") && $amount != ""){
            	$change_order_state['amount'] = array(
                                                		'_attributes' => array('currency' => $this->_checkout_currency),
                                                		'_content' 	  => $amount
                                                	    );
            }
            if ($function_name == "cancel" || $function_name == "refund") {
            	$change_order_state['reason'] = $reason;
            }
            if ($comment != ""){
                $change_order_state['comment'] = $comment;
            }
            
        	$serializer = new XML_Serializer($serializer_options);
        	$rslt = $serializer->serialize($change_order_state);
        	return $serializer->getSerializedData(); 
        
        }
        
        /**
         * The ChangeShippingInfo function creates XML documents used to send 
         * Order Processing API commands to Google Checkout. This function creates 
         * the XML for the following commands:
         *         <deliver-order>
         *         <add-tracking-data>
         * 
         * @param   $google_order_number    A number, assigned by Google Checkout, 
         *                                      that uniquely identifies an order.
         * @param   $function_name          The type of command that should be
         *                                      created. Valid values for this
         *                                      parameter are "deliver" and
         *                                      "add-tracking-data".
         * @param   $carrier                The carrier handling an order shipment
         * @param   $tracking_number        The tracking number assigned to an
         *                                      order shipment by the shipping carrier
         * @return  XML corresponding to the specified $function_name
         */
        function ChangeShippingInfo($google_order_number, $function_name, $carrier="", $tracking_number="") {
        
            $this->_mp_type = 'MISSING_PARAM';
        
            /*
             * Verify that the necessary parameter values have been provided.
             * The $google_order_number and $function_name parameters are
             * required for all commands. For the <deliver-order> command, the
             * $carrier and $tracking_number parameters are optional; however,
             * if the $carrier is provided, then a $tracking_number must also
             * be provided. For the <add-tracking-data> command, the $carrier
             * and $tracking_number parameters are both required.
             */
            $error_function_name = "ChangeShippingInfo(" . $function_name . ")";
            $this->CheckForError($this->_mp_type, $error_function_name, "google_order_number", $google_order_number);
        
            // Tracking information is optional for deliver-order, 
            // but required for add-tracking-data
            if ($function_name == "deliver-order") {
                // Check for missing tracking number when carrier is set
                $error_type = "MISSING_TRACKING";
                if ($carrier != "" && $tracking_number == "") {
                    $this->LogError(error_msg($error_type, $error_function_name), $debug_only_msg = false);
                }
            } elseif ($function_name == "add-tracking-data") {
                $this->CheckForError($this->_mp_type, $error_function_name, "carrier", $carrier);
                $this->CheckForError($this->_mp_type, $error_function_name, "tracking_number", $tracking_number);
            }
        
        	$serializer_options =  array(
        	                    "addDecl"=> true,
        						"indent"=>"     ",
        						"encoding" =>"UTF-8",
        						"rootName" => $function_name,
        						"rootAttributes" 	 => array(
        						    "xmlns" => $this->_checkout_xml_schema,
        						    "google-order-number" => $google_order_number
        						    ),
        						"scalarAsAttributes" => false,
                        		"attributesArray"    => '_attributes',
                        		"contentName"        => '_content',
                        		"defaultTagName"	 => 'item',
                        		"replaceEntities"    => XML_SERIALIZER_ENTITIES_NONE
        						);
        
        
        	$data	= array();
        
            if ($carrier != "") {
                $data['tracking-data']['carrier'] = $carrier;
                $data['tracking-data']['tracking-number'] = $tracking_number;
            }
            
        	$serializer = new XML_Serializer($serializer_options);
        	$rslt = $serializer->serialize($data);
        	return $serializer->getSerializedData(); 
        
        }
        
        /**
         * The CreateAddMerchantOrderNumber function creates the XML for the
         * <add-merchant-order-number> Order Processing API command. This command
         * is used to associate the Google order number with the ID that the
         * merchant assigns to the same order.
         *  
         * @param   $google_order_number      A number, assigned by Google Checkout, 
         *                                      that uniquely identifies an order.
         * @param   $merchant_order_number    A string, assigned by the merchant,
         *                                      that uniquely identifies the order.
         * @return  <add-merchant-order-number> XML
         */
        function CreateAddMerchantOrderNumber($google_order_number, $merchant_order_number) {
                
        	$serializer_options =  array(
        	                    "addDecl"=> true,
        						"indent"=>"     ",
        						"encoding" =>"UTF-8",
        						"rootName" => "add-merchant-order-number",
        						"rootAttributes" 	 => array(
        						    "xmlns" => $this->_checkout_xml_schema,
        						    "google-order-number" => $google_order_number
        						    ),
        						"scalarAsAttributes" => false,
                        		"attributesArray"    => '_attributes',
                        		"contentName"        => '_content',
                        		"defaultTagName"	 => 'item',
                        		"replaceEntities"    => XML_SERIALIZER_ENTITIES_NONE
        						);
        
        	$data	= array();
        	$data['merchant-order-number'] = $merchant_order_number;
        
        	$serializer = new XML_Serializer($serializer_options);
        	$rslt = $serializer->serialize($data);
        	return $serializer->getSerializedData(); 
            
        }
        
        /**
         * The CreateSendBuyerMessage function creates the XML for the 
         * <send-buyer-message> Order Processing API command. This command 
         * is used to send a message to a customer.
         *
         * @param   $google_order_number    A number, assigned by Google Checkout, 
         *                                    that uniquely identifies an order.
         * @param   $message                The text of the message that you
         *                                    want to send to the customer
         * @param   $send_email             A Boolean value that indicates whether
         *                                    Google Checkout should email the customer
         *                                    when the <deliver-order> command is
         *                                    processed for the order.
         * @return  <send-buyer-message> XML
         */
        function CreateSendBuyerMessage($google_order_number, $message, $send_email="") {
                
            $this->_mp_type = 'MISSING_PARAM';
            
            // The $google_order_number and $message parameters must have values
            $error_function_name = "CreateSendBuyerMessage()";
            $this->CheckForError($this->_mp_type, $error_function_name, "google_order_number", $google_order_number);
            $this->CheckForError($this->_mp_type, $error_function_name, "message", $message);
        
        	$serializer_options =  array(
        	                    "addDecl"=> true,
        						"indent"=>"     ",
        						"encoding" =>"UTF-8",
        						"rootName" => "send-buyer-message",
        						"rootAttributes" 	 => array(
        						    "xmlns" => $this->_checkout_xml_schema,
        						    "google-order-number" => $google_order_number
        						    ),
        						"scalarAsAttributes" => false,
                        		"attributesArray"    => '_attributes',
                        		"contentName"        => '_content',
                        		"defaultTagName"	 => 'item',
                        		"replaceEntities"    => XML_SERIALIZER_ENTITIES_NONE
        						);
        
        	$data	= array();
        	$data['message'] = $message;
        	if ($send_email != "") {
        	    $data['send-email'] = $send_email;
        	}
        
        	$serializer = new XML_Serializer($serializer_options);
        	$rslt = $serializer->serialize($data);
        	return $serializer->getSerializedData(); 
        
        }

		//////////////////////////////////////////////
		// Merchant Calculations API
		//////////////////////////////////////////////
        /**
         * The ProcessMerchantCalculationCallback function handles a 
         * <merchant-calculation-callback> request and returns a 
         * <merchant-calculation-results> XML response. This function calls 
         * the CreateMerchantCalculationResults function, which constructs 
         * the <merchant-calculation-results> response. This function then 
         * prints the <merchant-calculation-results> response to return the 
         * <merchant-calculation-results> information to Google Checkout and logs the 
         * response as well.
         * 
         * @param  $dom_mc_callback_obj      <merchant-calculation-callback> XML
         */
        function ProcessMerchantCalculationCallback($dom_mc_callback_obj, $sn) {
        
            /*
             * Process <merchant-calculation-callback> and create 
             * <merchant-calculation-results>
             */
            //$xml_mc_results = CreateMerchantCalculationResults($dom_mc_callback_obj);
        
            // Respond with <merchant-calculation-results> XML
            //echo $xml_mc_results;
        
            // Log <merchant-calculation-results>
            $this->LogMessage("Google Checkout Merchant Calculation Callback", $debug_only_msg = true);
            
            $this->SendNotificationAcknowledgment($sn);
        }


		//////////////////////////////////////////////
		// PRIVATE METHODS
		//////////////////////////////////////////////
		/**
		 * Enter description here...
		 *
		 * @param unknown_type $country_area
		 * @param unknown_type $arr_states
		 * @param unknown_type $arr_zips
		 * @param unknown_type $type
		 */
		function _getAllowedAreas($country_area, $arr_states, $arr_zips, $type="allowed"){
			
			$arr_areas = array( 
									"$type-areas" => array()
								);
								
			
			if(!empty($country_area)) {
				$arr_areas["$type-areas"]['us-country-area'] = array('_attributes' => array('country-area' => $country_area));
			}
			
			
			/**
			 * if we have states to allow / exclude
			 */
			if(!empty($arr_states)) {
				foreach ($arr_states as $state) {
					/**
					 * Bit of a hack since the XML_Serializer does not allow 
					 * more than one 'default' repeatable tags.
					 * 
					 * Google Has decided this crazy markup
					 */
					$state_data .= "<us-state-area>
										<state>
											".$state."
										</state>
									</us-state-area>";
				}
				$arr_areas["$type-areas"] = $state_data;
			}
			
			/**
			 * if we have zips to allow / exclude
			 */
			if(!empty($arr_zips)) {
				$zip_serializer = new XML_Serializer($this->_zip_serializer_options);
				$zip_serializer->serialize($arr_zips);
				$arr_areas["$type-areas"]['us-zip-area'] = $this->_removeTag($zip_serializer->getSerializedData());
			}
			
			return $arr_areas;
		}
		
		/**
		 * Enter description here...
		 *
		 * @param unknown_type $shipping_type
		 * @param unknown_type $name
		 * @param unknown_type $price
		 * @param unknown_type $shipping_restrictions
		 * @return unknown
		 */
		function _getShippingArray($shipping_type, $name, $price, $allowed_restrictions = "", $excluded_restrictions = "") {
			/**
			 * Check if there exists a shiping-methods
			 */
			if(empty($this->_arr_shopping_cart['checkout-flow-support']['merchant-checkout-flow-support']['shipping-methods'])) {
				$this->_arr_shopping_cart['checkout-flow-support']['merchant-checkout-flow-support']['shipping-methods'] = array();
			}


			/**
			 * Build Flat Rate Shipping Method Element
			 */
			$arr_shipping_obj = array(
											'price' => array(
														'_attributes' => array('currency' => $this->_checkout_currency),
														'_content'    => $price),
														
											 '_attributes' => array('name' => $name)
										
									);
			
			/**
			 * Add shipping restrictions (allowed / excluded)
			 */
			if(!empty($allowed_restrictions)) {
				$arr_shipping_obj['shipping-restrictions']['allowed-areas'] = $allowed_restrictions['allowed-areas'];
			}
			
			
			if(!empty($excluded_restrictions)) {
				$arr_shipping_obj['shipping-restrictions']['excluded-areas'] = $excluded_restrictions['excluded-areas'];
			}
			
			return $arr_shipping_obj;
		}
		
		/**
		 * Private: Sets the XML_Serializer Options for the GCheckout XML format
		 *
		 */
		function _setSerializerOptions() {
			$this->_serializer_options =  array("addDecl"=> true,
												"indent"=>"     ",
												"encoding" =>"UTF-8",
												"rootName" => 'checkout-shopping-cart',
												"rootAttributes" 	 => array("xmlns"=> $this->_checkout_xml_schema),
												"scalarAsAttributes" => false,
				                        		"attributesArray"    => '_attributes',
				                        		"contentName"        => '_content',
				                        		"defaultTagName"	 => 'item',
				                        		"replaceEntities"    => XML_SERIALIZER_ENTITIES_NONE
												);

			$this->_rebill_serializer_options =  array("addDecl"=> true,
												"indent"=>"     ",
												"encoding" =>"UTF-8",
												"rootName" => 'create-order-recurrence-request',
												"rootAttributes" 	 => array("xmlns"=> $this->_checkout_xml_schema, "google-order-number"=>""),
												"scalarAsAttributes" => false,
				                        		"attributesArray"    => '_attributes',
				                        		"contentName"        => '_content',
				                        		"defaultTagName"	 => 'item',
				                        		"replaceEntities"    => XML_SERIALIZER_ENTITIES_NONE
												);

			$this->_state_serializer_options =  array("addDecl"=> false,
												"indent"=>" ",
												"rootName" => "REMOVE",
												"scalarAsAttributes" => false,
				                        		"attributesArray"    => '_attributes',
				                        		"contentName"        => '_content',
				                        		"defaultTagName"	 => 'state'
												);
			$this->_zip_serializer_options =  array("addDecl"=> false,
												"indent"=>" ",
												"rootName" =>"REMOVE",
												"scalarAsAttributes" => false,
				                        		"attributesArray"    => '_attributes',
				                        		"contentName"        => '_content',
				                        		"defaultTagName"	 => 'zip-pattern'
												);		
			$this->_state_area_serializer_options =  array("addDecl"=> false,
												"indent"=>" ",
												"rootName" =>"REMOVE",
												"scalarAsAttributes" => false,
				                        		"attributesArray"    => '_attributes',
				                        		"contentName"        => '_content',
				                        		"defaultTagName"	 => 'us-state-area'
												);											

		}
		
		/**
		 * Private: Initializes the base shopping cart array
		 *
		 */
		function _setShoppingCart() {
			$this->_arr_shopping_cart	= array(
									'shopping-cart' => array(),
									'checkout-flow-support' => array(
															'merchant-checkout-flow-support' => array()
															)
							);
		}
		
		
		/**
		 * Hash function that computes HMAC-SHA1 value.
		 * This function is used to produce the signature 
		 * that is reproduced and compared on the other end 
		 * for data integrity.
		 *
		 * @param	$data		message data
		 * @param	$merchant_key	secret Merchant Key
		 * @return	$hmac		value of the calculated HMAC-SHA1
		 */
		function _getHmacSha1($data, $merchant_key) {
			
		    $blocksize = 64;
		    $hashfunc = 'sha1';
		
		    if (strlen($merchant_key) > $blocksize) {
		        $merchant_key = pack('H*', $hashfunc($merchant_key));
		    }
		
		    $merchant_key = str_pad($merchant_key, $blocksize, chr(0x00));
		    $ipad = str_repeat(chr(0x36), $blocksize);
		    $opad = str_repeat(chr(0x5c), $blocksize);
		    $hmac = pack(
		                    'H*', $hashfunc(
		                            ($merchant_key^$opad).pack(
		                                    'H*', $hashfunc(
		                                            ($merchant_key^$ipad).$data
		                                    )
		                            )
		                    )
		                );
		    return $hmac;
		}
		
		
		/**
		 * Enter description here...
		 *
		 * @param unknown_type $input
		 * @return unknown
		 */
		function _removeTag($input) {
			return str_replace($this->_remove_tags,"", $input);
		}

	} // END CLASS DEFINITION
	
?>
