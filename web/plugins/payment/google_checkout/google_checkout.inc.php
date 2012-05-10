<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: google checkout payment plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4781 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $config;

require_once($config['root_dir'].'/plugins/payment/google_checkout/gCheckout.php');
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_google_checkout extends payment {

    function get_plugin_features(){
        $this->init();
        return array(
            'title' => $config['payment']['google_checkout']['title'] ? $config['payment']['google_checkout']['title'] : _PLUG_PAY_GOOGLE_CHECKOUT_TITLE,
            'description' => $config['payment']['google_checkout']['description'] ? $config['payment']['google_checkout']['description'] : _PLUG_PAY_GOOGLE_CHECKOUT_DESC
        );
    }

    function get_cancel_link($payment_id){
        return cc_core_get_cancel_link('google_checkout', $payment_id);
    }

    function cc_bill($cc_info="", $member, $amount, $currency, $product_description, $charge_type, $invoice, $payment){
	    global $vars;

	    $res = $this->do_payment($invoice, $member['member_id'], $payment['product_id'], $amount, $payment['begin_date'], $payment['expire_date'], $vars, $is_rebilling=true);
	    
	    $receipt_id = "";
	    if ($res['new-google-order-number'])
	        $receipt_id = $res['new-google-order-number'];
	    
	    $status = CC_RESULT_INTERNAL_ERROR;
	    if (!$res[1]) // if no error message, do nothing (we have only received confirmation, payment will be handled by plugin itself)
	        $status = CC_RESULT_IGNORE; //CC_RESULT_SUCCESS;
	    if (!$res)
	        $res = array();
        return array($status, $res[1], $receipt_id, $res);
    }
   
    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars, $is_rebilling=false){
        
        global $config, $db;
        
        $checkout_xml_schema = 'http://checkout.google.com/schema/2';
        $gCheckout = new gCheckout($this->config["merchant_id"], $this->config["merchant_key"], $cart_expires = '', 
                                   $checkout_xml_schema, $this->config["currency"], $this->config["debug"], $this->config["sandbox"], $this->config["allow_create"]);
        
        // Specify an expiration date for the order and build <shopping-cart>
        $tomorrow  = mktime(0, 0, 0, date("m"), date("d") + 1, date("Y"));
        //$cart_expiration = date("Y-m-d", $tomorrow) . "T" . date("H:i:s");
        $cart_expiration = date("c", $tomorrow);
        
        if (!$is_rebilling)
            $gCheckout->setCartExpirationDate($cart_expiration);

        if (!is_array($price))
            $price = array($product_id => $price);

        $payment = $db->get_payment($payment_id);
        
        if ($is_rebilling){
            $payment['data']['google_is_rebilling'] = 1;
            $db->update_payment($payment['payment_id'], $payment);
        }
        
        if ($pr = $payment['data'][0]['BASKET_PRODUCTS'])
            $product_id = $pr;
        
        if ($prices = $payment['data'][0]['BASKET_PRICES'])
            $price = $prices;
        
        $taxes = $payment['data']['TAXES'];
        
        if (!is_array($product_id))
            $product_id = (array)$product_id;
            
        $recurring_count = 0;
        foreach ((array)$product_id as $pid){
            $product = $db->get_product($pid);
            if ($product['is_recurring']) $recurring_count++;
        }
        if ($recurring_count > 1){
            //$db->log_error("Google Checkout ERROR: Only one subscription item is allowed per cart");
            return array("Google Checkout ERROR: Only one subscription item is allowed per cart");
        }
        
        foreach ((array)$product_id as $pid){
            ///////////////////////////////////////
            // Add product
            ///////////////////////////////////////
            // Specify item data and create an item to include in the order
            $product = $db->get_product($pid);
            $item_name = trim(strip_tags($product['title'])); 
            $item_description = trim(strip_tags($product['description']));
            $quantity = "1";
	        $item_currency = $product['google_currency'];
            
            //$unit_price = $product['price'];
            $unit_price = $price[$pid];
            
            $tax_table_selector = "";
            $merchant_private_item_data = "<product-id>$pid</product-id>";

	        $digital_delivery = array();
	        if ($product['digital_content']){
		        $digital_delivery['delivery_schedule'] = $product['delivery_schedule'];
		        $digital_delivery['delivery_method'] = $product['delivery_method'];
		        $digital_delivery['delivery_description'] = $product['delivery_description'];
		        $digital_delivery['delivery_key'] = $product['delivery_key'];
		        $digital_delivery['delivery_url'] = $product['delivery_url'];
	        }

	        $subscription = array();
	        if ($product['is_recurring'] && !$is_rebilling){
		        if ($product['rebill_times']){
		            if ($product['trial1_price'] && $product['trial1_days'])
		                $subscription['rebill_times'] = $product['rebill_times'] - 1;
		            else
		                $subscription['rebill_times'] = $product['rebill_times'];
		        }

		        if ($product['trial1_days'] && $product['trial1_price'] != '')
		            $unit_price = $product['price'];

		        $days = $product['expire_days'];
		        if ($product['trial1_days'] && !$is_rebilling)
		            $days = $product['trial1_days'];
		        
		        list($period, $period_unit) = google_checkout_get_days($days);
		        $ret = 0;
		        switch ($period_unit){
			        case 'Y':
			            $ret = $period * 365;
			            break;
			        case 'M':
			            $ret = $period * 30;
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

		        $start_date = time() + 3600 * 24 * $ret;
		        $start_date = date("Y-m-d 00:00:00", $start_date);
		        $start_date = strtotime($start_date);
		
                $subscription['start_date'] = date("c", $start_date); // ISO 8601 date (added in PHP 5)

		        $subscription_period = "";
		        list($period, $period_unit) = google_checkout_get_days($product['expire_days']);
		        if ($period_unit == 'D' && $period == '1')
			        $subscription_period = "DAILY";
		        if (($period_unit == 'W' && $period == '1') || ($period_unit == 'D' && $period == '7'))
			        $subscription_period = "WEEKLY";
		        if ($period_unit == 'W' && $period == '2')
			        $subscription_period = "SEMI_MONTHLY";
		        if ($period_unit == 'M' && $period == '1')
			        $subscription_period = "MONTHLY";
		        if ($period_unit == 'M' && $period == '2')
			        $subscription_period = "EVERY_TWO_MONTHS";
		        if ($period_unit == 'M' && $period == '4')
			        $subscription_period = "QUARTERLY";
		        if ($period_unit == 'Y' && $period == '1')
			        $subscription_period = "YEARLY";
		        $subscription['period'] = $subscription_period;
	        }
            

            ///////////////////////////////////////
            // Add Taxes info
            ///////////////////////////////////////

/* DISABLED. Taxes per product isn't supported

            if ($config['use_tax']){
                $area_type = "country";
                $area_place = "ALL";
                $tax_area = $gCheckout->getTaxArea($area_type, $area_place);
                
                if ($product['use_tax']){
                    $rate = $taxes[$pid] / $product['price'];
                } else {
                    $rate = "0.00";
                }
                
                if (!$rate) $rate = "0.00";
                $gCheckout->addAlternateTaxRule($rate, $tax_area);
                
                $standalone = 'true';
                $name = $pid;
                $gCheckout->addAlternateTaxTable($standalone, $name);
                
                $tax_table_selector = $pid;
            }
*/

        $gCheckout->addItem($item_name, $item_description, $quantity, $unit_price, $tax_table_selector, $merchant_private_item_data, $digital_delivery, $subscription, $item_currency);
	    if ($product['is_recurring'] && !$is_rebilling){
		    if ($product['trial1_days'] && $product['trial1_price'] != '')
			    //add regular <item> for initial trial charge
			    $gCheckout->addItem($item_name, $item_description, $quantity, $product['trial1_price'], $tax_table_selector, $merchant_private_item_data, $digital_delivery, '', $item_currency);
		    else
			    //add regular <item> for first recurring charge
			    $gCheckout->addItem($item_name, $item_description, $quantity, $unit_price, $tax_table_selector, $merchant_private_item_data, $digital_delivery, '', $item_currency);
	        }
        }
        
        
        ///////////////////////////////////////
        // Add extra data
        ///////////////////////////////////////
        $merchant_private_data = "<payment-id>$payment_id</payment-id><member-id>$member_id</member-id>";
        $gCheckout->setMerchantPrivateData($merchant_private_data);
        
        ///////////////////////////////////////
        // Add Merchant Calculations data
        ///////////////////////////////////////
        //$merchant_calc_url = $config['root_surl']."/plugins/payment/google_checkout/ResponseHandler.php";
        //$gCheckout->setMerchantCalculations($merchant_calc_url, $accepts_coupons="", $accept_gift_certificates="");

        ///////////////////////////////////////
        // Add shipping info
        ///////////////////////////////////////
        // Create list of areas where a particular shipping option is available
        /*
        $allowed_country_area = "ALL";    // OR: "CONTINENTAL_48", "FULL_50_STATES"
        $allowed_state = array();        // Ex: array("CA", "NY", "DC", "NC")
        $allowed_zip = array();        // Ex: array("94043", "94086", "91801", "91362")
        $allowed_restrictions = $gCheckout->getAllowedAreas($allowed_country_area, $allowed_state, $allowed_zip);
        */
        /*
        $excluded_country_area = "TX";    // OR: "CONTINENTAL_48", "FULL_50_STATES"
        $excluded_state = array();        // Ex: array("CA", "NY", "DC", "NC")
        $excluded_zip = array();        // Ex: array("94043", "94086", "91801", "91362")
        $excluded_restrictions = $gCheckout->getExcludedAreas($excluded_country_area, $excluded_state, $excluded_zip);
        */

        /*
        $name = 'UPS Ground';
        $prc = '10.0';
        $gCheckout->setFlatRateShipping($name, $price, $allowed_restrictions, $excluded_restrictions);
        */
        
        /*
        $name = 'Merchant Shipping';
        $prc = '20.0';
        $gCheckout->setMerchantCalculatedShipping($name, $price, $allowed_restrictions, $excluded_restrictions);
        */
        
        // Create a <pickup> shipping option
        $name = "Pickup";
        $prc = "0.00";
        $gCheckout->setPickup($name, $prc);
        
        ///////////////////////////////////////
        // Add Taxes info
        ///////////////////////////////////////
        if ($config['use_tax']){

            ////////// http://code.google.com/intl/ru/apis/checkout/developer/Google_Checkout_XML_API_Taxes.html //////////

            if ($config['tax_type'] == '2'){ // TODO: MUST BE FIXED FOR NEW REGIONAL TAX STRUCTURE - alex@cgi-central.net
                foreach ($config['regional_taxes'] as $regional_tax){
                    
                    $area_type = "country";
                    $area_place = "ALL";
                    if ($regional_tax['state'] && $regional_tax['country']) {

                        if ($regional_tax['country'] == 'US'){
                            $area_type = "country";
                            $area_place = 'ALL';
                            //CONTINENTAL_48 - All U.S. states except Alaska and Hawaii
                            //FULL_50_STATES - All U.S. states
                            //ALL - All U.S. postal service addresses, including military addresses, U.S. insular areas, etc.
                            $tax_area = $gCheckout->getTaxArea($area_type, $area_place);
            
                            $area_type = "state";
                            $area_place = $regional_tax['state'];
                            $tax_area = $gCheckout->getTaxArea($area_type, $area_place);
                        } else {
                            $tax_area = "<tax-area><world-area/></tax-area>";
                        }

                    } elseif ($regional_tax['country'] && $regional_tax['country'] == 'US') {

                        $area_type = "country";
                        $area_place = "ALL";
                        $tax_area = $gCheckout->getTaxArea($area_type, $area_place);

                    } elseif ($regional_tax['country']) {

                        $tax_area = "<tax-area><world-area/></tax-area>";

                    }
                    
                    $rate = $regional_tax['tax_value'] / 100;
                    $shipping_taxed = 'false';
                    $gCheckout->addDefaultTaxRule($rate, $tax_area, $shipping_taxed);
                    
                }
            } else {
    
                $area_type = "country";
                $area_place = "ALL";
                $tax_area = $gCheckout->getTaxArea($area_type, $area_place);
                $rate = $config['tax_value'] / 100;
                $shipping_taxed = 'false';
                $gCheckout->addDefaultTaxRule($rate, $tax_area, $shipping_taxed);
    
            }

            $merchant_calculated = 'false';
            $gCheckout->CreateTaxTables($merchant_calculated);
            
        }
        
        ///////////////////////////////////////
        // Information about shipping, taxes, and merchant calculations used at checkout time.
        ///////////////////////////////////////
        $gCheckout->setMerchantCheckoutFlowSupport($edit_cart_url ="", $continue_shopping_url = "", $request_buyer_phone_number = false);

        ///////////////////////////////////////
        // Create Checkout Shopping Cart
        ///////////////////////////////////////

      
        // Get <checkout-shopping-cart> XML
        
        if (!$is_rebilling){
            $xml_cart   = $gCheckout->getCart();
        } else {
            //$oldp_id = $payment['data'][0]['RENEWAL_ORIG'];
            //$x = preg_split('/ /', $oldp_id);
            //$oldp_id = $x[1];
            //$oldp = $db->get_payment($oldp_id);
            //$xml_cart   = $gCheckout->get_recurrence_request($oldp['receipt_id']); //$oldp['data']['google-order-number']
            
            $initial_payment_id = $this->find_initial_payment($payment['payment_id']);
            $initial_payment = $db->get_payment($initial_payment_id);
            $xml_cart   = $gCheckout->get_recurrence_request($initial_payment['receipt_id']);
        }
        $response   = $gCheckout->SendRequest($xml_cart, 'request');
	    $res        = $gCheckout->ProcessXmlData ($response);
		
        if ($res['error-message']){
            $gCheckout->LogMessage ($res['error-message'], $debug_only_msg = true);
            return array("Google Checkout payment error. Please contact site administrator: <a href=\"mailto:".$config['admin_email']."\">".$config['admin_email']."</a>", $res['error-message']);
        }
        
        return $res;

    }
    
    function find_initial_payment($payment_id){
        global $db;
        $payment_id = intval($payment_id);
        $payment = $db->get_payment($payment_id);
        while ($prev_payment_id = $payment['data'][0]['RENEWAL_ORIG']){
            $x = preg_split('/ /', $prev_payment_id);
            $prev_payment_id = $x[1];
            $payment = $db->get_payment($prev_payment_id);
        }
        return $payment['payment_id'];
    }


    function init(){

		add_product_field('google_merchant_item_id', 'Google Checkout Merchant item ID', 'text', 'contains a value, such as a stock keeping unit (SKU), that you use to uniquely identify an item',
		'google_checkout_validate_product');
		add_product_field('google_currency', 'Google Checkout Currency', 'text', 'identifies the unit of currency associated with the Price<br />
		The value of the currency attribute must be a three-letter <a href="http://www.iso.org/iso/en/prods-services/popstds/currencycodeslist.html" target="_blank">ISO 4217 currency code</a>', '');

		    add_product_field('rebill_times', 'Recurring Times', 'text',
		        'Recurring Times. This is the number of payments which<br />will occur at the regular rate. If omitted, payment will<br />
		         continue to recur at the regular rate until the subscription<br />is cancelled.<br />
		         NOTE: this option is working for particular payment processing methods only'
		        );

		    add_product_field('digital_content', 'Google Checkout Digital content', 'checkbox',
		'see <a href="http://code.google.com/intl/ru/apis/checkout/developer/Google_Checkout_Digital_Delivery.html" target="_blank">manual</a>', '');

		    add_product_field('delivery_schedule', 'Google Checkout Delivery schedule', 'select', 'you can specify when the buyer will receive instructions for accessing the purchased digital content', '',
			array('options' => array(
				'PESSIMISTIC' => 'Pessimistic delivery',
				'OPTIMISTIC' => 'Optimistic delivery')
				),
			array('default' => 'PESSIMISTIC')
		);
		    add_product_field('delivery_method', 'Google Checkout Delivery method', 'select', 'you can specify how instructions for accessing the digital content will be communicated to the buyer', '',
			array('options' => array(
				'email' => 'Email delivery',
				'key' => 'Key/URL delivery',
				'description' => 'Description-based delivery')
				),
			array('default' => 'email')
		);
		add_product_field('delivery_description', 'Google Checkout Delivery Description', 'textarea', 'contains instructions for downloading a digital content item', '');
		add_product_field('delivery_key', 'Google Checkout Delivery Key', 'text', 'contains a key needed to download or unlock a digital content item', '');
		add_product_field('delivery_url', 'Google Checkout Delivery URL', 'text', 'specifies a URL from which the customer can download or access the purchased content', '');

    }

}

function google_checkout_get_days($orig_period){
	if (preg_match('/^\s*(\d+)\s*([y|Y|m|M|w|W|d|D]{0,1})\s*$/',
		$orig_period, $regs)){
	    $period = $regs[1];
	    $period_unit = $regs[2];
	    if (!strlen($period_unit)) $period_unit = 'd';
	    $period_unit = strtoupper($period_unit);
	} else {
	    fatal_error("Incorrect value for expire days: " . $orig_period);
	}
	return array($period, $period_unit);
}

function google_checkout_validate_product(&$p){
	$error = "";
	//check periods
	if ($p->config['is_recurring']){
		list($period, $period_unit) = google_checkout_get_days($p->config['expire_days']);

		if ($period_unit == 'D' && !in_array($period, array(1, 7)))
			$error = "Google Checkout allows only Daily and Weekly subscriptions. ";
		if ($period_unit == 'W' && !in_array($period, array(1, 2)))
			$error = "Google Checkout allows only Weekly and Semi-monthly subscriptions. ";
		if ($period_unit == 'M' && !in_array($period, array(1, 2, 4)))
			$error = "Google Checkout allows only Monthly, Every two months and Quarterly subscriptions. ";
		if ($period_unit == 'Y' && $period != '1')
			$error = "Google Checkout allows only Yearly subscriptions. ";
	}
	return $error;
}

//$pl = & instantiate_plugin('payment', 'google_checkout');

function google_checkout_get_member_links($user){
    //return cc_core_get_member_links('google_checkout', $user);
}

function google_checkout_rebill(){
    return cc_core_rebill('google_checkout');
}
                                        
cc_core_init('google_checkout');

?>
