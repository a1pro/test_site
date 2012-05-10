<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");
require_once($config['root_dir']."/plugins/payment/psigate/class.psigate_xml.php");

function psigate_get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

class payment_psigate extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('psigate', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('psigate', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['psigate']['title'] ? $config['payment']['psigate']['title'] : _PLUG_PAY_PSIGATE_TITLE,
            'description' => $config['payment']['psigate']['description'] ? $config['payment']['psigate']['description'] : _PLUG_PAY_PSIGATE_DESC,
            'code' => 2,
            'name_f' => 2,
            'phone' => 1,
            'company' => 1,
        );
    }

    function run_transaction($vars){
        global $db;

        $psi = new PsiGatePayment;
    
    	// Not all fields below are required.  Consult manual for specifics.
    
        if ($vars['testmode']){
            $psi->setGatewayURL('https://dev.psigate.com:7989/Messenger/XMLMessenger');
        } else {
            $psi->setGatewayURL('https://secure.psigate.com:7934/Messenger/XMLMessenger');
        }
    	$psi->setStoreID($vars['storeid']);
    	$psi->setPassPhrase($vars['passphrase']);

    	$psi->setPaymentType('CC');
    	$psi->setCardAction($vars['card-action']); // 0 – Sale, 1 – PreAuth, 2 – PostAuth, 3 – Credit, 4 – Forced PostAuth, 9 – Void
    	//$psi->setOrderID($vars['order-id']); // For PostAuth, Credit, and Void transactions, OrderID is required and it must its value must be the same as the OrderID of the associated transaction request.
    	$psi->setSubTotal($vars['card-amount']); // Amount
    	
    	$psi->setCardNumber($vars['card-number']); // Card Number
    	$psi->setCardExpMonth(substr($vars['card-exp'], 0, 2)); // Month in 2-digit format
    	$psi->setCardExpYear(substr($vars['card-exp'], 2, 2)); // Year in 2-digit format

    	if ($vars['card-cvv']){
        	$psi->setCardIDCode('1'); // Pass CVV code
        	/*
            Passes the status for Visa CVV2, MasterCard CVC2, and Amex CID.  
            If unknown leave blank.
            0 = Bypassed
            1 = Value present
            2 = Value illegible
            9 = Card has no CVV2 value
        	*/
        	$psi->setCardIDNumber($vars['card-cvv']); // Passes Visa CVV2, MasterCard CVC2, and Amex CID numbers 
        }


        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $proxy_ip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $proxy_ip = $_SERVER["REMOTE_ADDR"];
            }
            $client_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            $forwarded_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $client_ip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $client_ip = $_SERVER["REMOTE_ADDR"];
            }
            $proxy_ip = '0.0.0.0';
            $forwarded_ip = '';
        }
    	$psi->setCustomerIP($client_ip); // Customer IP address, for fraud
    	$psi->setUserID($vars['user-id']); // Unique customer identifier set by merchant.
    	
/**/
    	$psi->setItemID($vars['item1']);
    	$psi->setItemDescription($vars['description1']);
    	$psi->setItemQty($vars['quantity1']);
    	$psi->setItemPrice($vars['price1']);
/**/

    	
    	$psi->setShiptotal($vars['shipping']); // shipping
    	$psi->setTaxTotal1($vars['tax']); // Tax value 1, ex Sales Tax
/*
    	$psi->setTaxTotal2('0.00'); // Tax value 2, ex VAT
    	$psi->setTaxTotal3('0.00'); // Tax value 3, ex GST
    	$psi->setTaxTotal4('0.00'); // Tax value 4, ex PST
    	$psi->setTaxTotal5('0.00'); // Tax value 5
*/
    	
    	$psi->setBname($vars['card-name']); // Billing Name
    	$psi->setBcompany($vars['card-company']); // Company Name
    	$psi->setBaddress1($vars['card-address']); // Billing Address 1
    	$psi->setBaddress2(''); // Billing Address 2
    	$psi->setBcity($vars['card-city']); // Billing City
    	$psi->setBprovince( $vars['card-state'] ); // Billing state or province
    	$psi->setBpostalCode($vars['card-zip']); // Billing Zip
    	$psi->setBcountry($vars['card-country']); // Country Code - 2 alpha characters

    	$psi->setPhone($vars['user-phone']); // Customer Phone
    	$psi->setEmail($vars['user-email']); // Customer Email
    	$psi->setComments('');  // comments, whatever you'd like
    	
/*
    	$psi->setSname(''); // Shipping Name
    	$psi->setScompany(''); // Shipping Company
    	$psi->setSaddress1(''); // Shipping Address 1
    	$psi->setSaddress2(''); // Shipping Address 2
    	$psi->setScity(''); // Shipping City
    	$psi->setSprovince(''); // Shipping state or province
    	$psi->setSpostalCode(''); // Shipping Zip
    	$psi->setScountry(''); // Shipping country
*/
    	
    
        //  $psi->setTestResult('A'); // Test result if you'd like to pass one.  See xml guide for more details
        /*
        TestResult may be set to simulate a response from the bank.
        A simulated transaction result shall be returned once the transaction request passes the fulfillment and fraud rule checks.  
        
        A – Simulates an approved response.
        D – Simulates a declined response.
        R – Randomly approves or declines orders.
        F – Simulates a fraud response.        
        */
    
    	// Send transaction data to the gateway
        $psi_xml_error = (!($psi->doPayment() == PSIGATE_TRANSACTION_OK));

        $return['RESULT']   = $psi->getTrxnApproved();
        $return['RESPMSG']  = $psi->getTrxnErrMsg();
        $return['PNREF']    = $psi->getTrxnTransRefNumber();
        $return['ORDERID']  = $psi->getTrxnOrderID();
        $return['AVS']      = $psi->getTrxnAVSResult();
        $return['CVV_VALID'] = $psi->getTrxnCardIDResult();

        $return['TAX_TOTAL'] = $psi->getTrxnTaxTotal();
        $return['SUB_TOTAL'] = $psi->getTrxnSubTotal();
        $return['FULL_TOTAL'] = $psi->getTrxnFullTotal();

       
        $err = '';
        if ($psi->getTrxnErrMsg())
            $err = "<br />PSiGate ERROR: " . $psi->getTrxnErrMsg();
        $db->log_error("PSiGate RESPONSE: ".$psi->getTrxnReturnCode() . $err);

        return $return;
    }
    function void_transaction($pnref, &$log){
        return "";
    }
    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $db, $config, $plugin_config;
        
        $this_config   = $plugin_config['payment']['psigate'];
        $product = $db->get_product($payment['product_id']);
        
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        if ($charge_type == CC_CHARGE_TYPE_TEST){
            $card_action = '1';  // PreAuth - PSiGate guarantees that the card issuer shall reserve the authorized amount for five days.
            $card_amount = '1.00';
            $tax_amount = '0.00';
        } else {
            $card_action = '0'; // 0 – Sale, 1 – PreAuth, 2 – PostAuth, 3 – Credit, 4 – Forced PostAuth, 9 – Void
            $tax_amount = $payment['tax_amount'];
            $card_amount = $amount;
            if ($tax_amount > 0)
                $card_amount = $card_amount - $tax_amount;
        }

        srand(time());
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }

        if ($this_config['testmode']){
        	$storeid = 'teststore';
        	$passphrase = 'psigate1234';
        } else {
        	$storeid = $this_config['storeid'];
        	$passphrase = $this_config['passphrase'];
        }
        

        $vars = array(
            "card-action"       => $card_action,
            "storeid"           => $storeid,
            "passphrase"        => $passphrase,
            "testmode"          => $this_config['testmode'],
            "card-amount"       => $card_amount,
            "card-name"         => $cc_info['cc_name_f']." ".$cc_info['cc_name_l'],
            "card-address"      => $cc_info['cc_street'],
            "card-city"         => $cc_info['cc_city'],
            "card-state"        => $cc_info['cc_state'],
            "card-zip"          => preg_replace("/[^0-9a-zA-Z]/i", "", $cc_info['cc_zip']),
            "card-country"      => $cc_info['cc_country'],
            "card-number"       => $cc_info['cc_number'],
            "card-exp"          => $cc_info['cc-expire'],
            "card-cvv"          => $cc_info['cc_code'],
            "card-company"      => $cc_info['cc_company'],

            "user-id"           => $member['login'],
            "user-email"        => $member['email'],
            "user-phone"        => $cc_info['cc_phone'],
            'shipping'          => '0.00',
            'tax'               => $tax_amount,
            'order-id'          => $payment['receipt_id'],
            'item1'             => $product['product_id'],
            'price1'            => $card_amount,
            'quantity1'         => 1,
            'description1'      => $product['title']
        );

        // prepare log record
        $vars_l = $vars; 
        $vars_l['card-number'] = $cc_info['cc'];
        if ($vars['card-cvv'])
            $vars_l['card-cvv'] = preg_replace('/./', '*', $vars['card-cvv']);
        if ($vars['passphrase'])
            $vars_l['passphrase'] = preg_replace('/./', '*', $vars['passphrase']);
        $log[] = $vars_l;
        /////
        $db->log_error("PSiGate DEBUG:<br />".psigate_get_dump($vars_l));
        
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if (preg_match("/Approved/i", $res['RESULT'])){
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif (preg_match("/Declined/i", $res['RESULT'])) {
            return array(CC_RESULT_DECLINE_PERM, ($res['RESPMSG'] ? $res['RESPMSG'] : $res['RESULT']), "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, ($res['RESPMSG'] ? $res['RESPMSG'] : $res['RESULT']), "", $log);
        }
    }
}

function psigate_get_member_links($user){
    return cc_core_get_member_links('psigate', $user);
}

function psigate_rebill(){
    return cc_core_rebill('psigate');
}

cc_core_init('psigate');
?>
