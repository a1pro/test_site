<?php #ini_set('display_errors', 1); error_reporting(E_ALL);
if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");



//  TODO :
//  tests with lifetime subscriptions and recurring times.

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: PayPal Payment Plugin
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5415 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/


function recurring_first($a,$b){
    if ($a[recurring] == $b[recurring]) {
        return 0;
    }
    return ($a[recurring] < $b[recurring]) ? 1 : -1;
}
class payment_paypal_pro extends amember_payment {
    var $title = _PLUG_PAY_PAYPALPRO_TITLE;
    var $description = _PLUG_PAY_PAYPALPRO_DESC;
    var $fixed_price=0;
    var $recurring=1;
    var $paypal_domain = null;
    var $locale = '';
    
    function get_cancel_link($payment_id){
        global $config,$db;
        $p = $db->get_payment($payment_id);
        // Do not show cancel link if subscription was rebilled already
        foreach($db->get_user_payments($p[member_id], 1) as $op){
            if($op[data][0][RENEWAL_ORIG] == "RENEWAL_ORIG: ".$payment_id) return;
        }
        return $config[root_url]."/plugins/payment/paypal_pro/cancel.php?payment_id=".$payment_id;
    }

function get_payment_params($payment_id){
        // returns.
        global $db;
        $payment = $db->get_payment($payment_id);
        if ($payment['data'][0]['BASKET_PRODUCTS'])
            $product_ids = (array)$payment['data'][0]['BASKET_PRODUCTS'];
        else
            $product_ids = array($payment['product_id']);
        $products = array();
        foreach ($product_ids as $product_id)
            $products[] = $db->get_product($product_id);
        $member  = $db->get_user($payment[member_id]);
        $title = (count($products) == 1) ? $products[0]['title'] : $config['multi_title'];
        $invoice = $payment_id;
        return array($payment['amount'], $title, $products, $member, $invoice);
    }


    function get_common_currency($products){
        $c = '';
        foreach ($products as $p){
            if ($p['paypal_currency'] == '')
                $p['paypal_currency'] = 'USD';
            if (($c != '') && ($c != $p['paypal_currency']))
                fatal_error(_PLUG_PAY_PAYPALPRO_FERROR, 0);
            $c = $p['paypal_currency'];
        }
        return $c;
    }
    
    
    function do_bill($amount, $title, $products, $u, $invoice){
        global $config;
        $token = $this->paypalSetExpressCheckout($invoice,$products);
        if (!$token) fatal_error(_PLUG_PAY_PAYPALPRO_FERROR3);
        $_SESSION['_amember_payment_id'] = $invoice;

        html_redirect("https://{$this->paypal_domain}/cgi-bin/webscr?cmd=_express-checkout&token=$token", 0,
            _PLUG_PAY_PAYPALPRO_REDIRECT, 
            _PLUG_PAY_PAYPALPRO_REDIRECT2);
    }

    function log_vars($payment_id, $vars){
        global $db;
        if($vars['PWD']) $vars['PWD']        =   preg_replace('/./', '*', $vars['PWD']);
        if($vars['ACCT']) $vars['ACCT']        =   preg_replace('/./', '*', $vars['ACCT']);
        if($vars['CVV2']) $vars['CVV2']        =   preg_replace('/./', '*', $vars['CVV2']);
        if($vars['SIGNATURE']) $vars['SIGNATURE']  =   preg_replace('/./', '*', $vars['SIGNATURE']);
        $this->addToPaymentLog($payment_id, $vars);
    }

    function get_errors($resp){
	if($resp['ACK'] && ($resp['ACK'] == 'Success' || $resp['ACK'] == 'SuccessWithWarning')) return false;
        $ret = '';
        for($i=0; $i<10; $i++){
            if($resp['L_SHORTMESSAGE'.$i]){
                $ret .= $resp['L_SHORTMESSAGE'.$i]." (".$resp['L_ERRORCODE'.$i].") ".$resp['L_LONGMESSAGE'.$i]." ";
            }
        }
        return $ret;

    }
    function get_rand($length){
        $all_g = "ABCDEFGHIJKLMNOPQRSTWXZ";
        $pass = "";
        srand((double)microtime()*1000000);
        for($i=0;$i<$length;$i++) {
            srand((double)microtime()*1000000);
            $pass .= $all_g[ rand(0, strlen($all_g) - 1) ];
        }
        return $pass;
    }

    function paypalAPIRequest($payment_id, $vars, $disregard_error=0){
        $vars = array_merge(array(  'USER'      =>  $this->config['api_user'],
                                    'PWD'       =>  $this->config['api_pass'],
                                    'VERSION'   =>  '56.0',
                                    'SIGNATURE' =>  $this->config['api_sig'],
                                    'SUBJECT'   =>  $this->config['business']
                                    ), $vars);

        $this->log_vars($payment_id, $vars);
        $req = array();
        foreach($vars as $k=>$v){
            $req[] = $k."=".urlencode($v);
        }
        $req = join('&', $req);
        if($this->config['testing'])
            $url  = "https://api-3t.sandbox.paypal.com/nvp";
        else
            $url = "https://api-3t.paypal.com/nvp";

        $retstr = get_url($url, $req);
        if(!$retstr) fatal_error(_PLUG_PAY_PAYPALPRO_FERROR4 . "empty result from curl");
        parse_str($retstr,$ret);
        $this->log_vars($payment_id, $ret);
        
        if(($ret['ACK']!='Success')&&($ret['ACK']!='SuccessWithWarning')){
            if(!$disregard_error) fatal_error(_PLUG_PAY_PAYPALPRO_FERROR4. $this->get_errors($ret));
        }

        return $ret;
        
    }


    function getStartDelay($product){
        list($count, $unit) = parse_period($product['trial1_days'] ? $product['trial1_days'] : $product['expire_days']);
        $dat = $product['start_date'] ?  $product['start_date'] : date('Y-m-d');
        list($y,$m,$d) = split("-", $dat);
        switch($unit){
            case "d" : $tm = mktime(0,0,0,$m, $d+$count, $y); break;
            case "m" : $tm = mktime(0,0,0,$m+$count, $d, $y); break;
            case "y" : $tm = mktime(0,0,0,$m,$d,$y+$count); break;

        }
//        print date('Y-m-d', $tm); exit;
        $date = date('Y-m-d\TH:i:s.00\Z', $tm);
//        print $date; exit;
        return $date;
        
    }

    function getFreq($product){
        list($count, $unit) = parse_period($product['expire_days']);
        return $count; 
    }
    function getPeriod($product){
        list($count, $unit) = parse_period($product['expire_days']);
        switch($unit){
            case "d" : return "Day";break;
            case "m" : return "Month";break;
            case "y" : return "Year";break;
        }

    }
    function getRecurringCount($product){
        return ($product['rebill_times']);
    }
    function getBillingTerms($payment_id, $products){
        global $config, $db;
        $terms = array();
        $cnt = 0;
        $payment = $db->get_payment($payment_id);
        $u = $db->get_user($payment[member_id]);
        $coupon_code = $payment['data'][0]['COUPON_CODE'];
        $coupon = array();
        if ($config['use_coupons'] && $coupon_code != ''){
           $coupon = $db->coupon_get($coupon_code);
        }   
        $pc = & new PriceCalculator();
        $pc->setTax(get_member_tax($u['member_id']));

        foreach($products as $p){
            $po = get_product($p['product_id']);

            $pc->emptyProducts();
            $terms[$cnt]['product'] =    $p;
            $pc->addProduct($p['product_id']);
            if ( $coupon['coupon_id'] > 0 )
               $pc->setCouponDiscount($coupon['discount'], split(',',trim($coupon['product_id'])));
            if($p[is_recurring]){
// Calculate Trial first;
                $pc->setPriceFields(array('trial1_price', 'price'));
                $t = $pc->calculate();
                $terms[$cnt]['initial'] = $t->total-$t->tax;
                $terms[$cnt]['initial_tax'] = $t->tax;
// Calculate Recurring;
                $pc->emptyProducts();
                $pc->addProduct($p['product_id']);
                $pc->setPriceFields(array('price'));
                if (!$coupon['is_recurring']) $pc->setCouponDiscount(null);
                $t=$pc->calculate();
                $terms[$cnt]['recurring'] = $t->total-$t->tax;
                $terms[$cnt]['recurring_tax'] = $t->tax;
            }else{
                $pc->setPriceFields(array('price'));
                $t=$pc->calculate();
                $terms[$cnt]['initial'] = $t->total - $t->tax;
                $terms[$cnt]['initial_tax'] = $t->tax;
                $terms[$cnt]['recurring'] = 0;
            }
            $terms[$cnt]['terms_description'] = $po->getSubscriptionTerms();
// We got first payment alrelady so need to create profile with delay.
            $terms[$cnt]['start_delay'] =   $this->getStartDelay($p);
            $terms[$cnt]['freq']              =   $this->getFreq($p);
            $terms[$cnt]['period']            =   $this->getPeriod($p);
            switch($this->getRecurringCount($p)){
                case    0  :   $terms[$cnt]['recurring_count'] =0; break;
//                case    0   :   $terms[$cnt]['recurring']   =0; break;
                default     :   $terms[$cnt]['recurring_count'] = $this->getRecurringCount($p);
            }
            $cnt++;

        }


        return $terms;
    }
    function paypalSetExpressCheckout($payment_id,$products){
        global $config,$db;
        $payment = $db->get_payment($payment_id);

        $terms = $this->getBillingTerms($payment_id, $products);
        $total = 0; $total_tax = 0;
        $vars=array(
            'METHOD'        =>  'SetExpressCheckout',
            'RETURNURL'     =>  $config['root_url']."/plugins/payment/paypal_pro/return.php",
            'CANCELURL'     =>  $config['root_url']."/cancel.php?from=paypal_pro&payment_id=$payment_id",
            'CALLBACKURL'   =>  $config['root_url']."/plugins/payment/paypal_pro/ipn.php",
            'INVNUM'        =>  $payment_id,
            'NOSHIPPING'    =>  1,
            'LOCALECODE'    =>  $this->locale,
            'CURRENCYCODE'  =>  $this->get_common_currency($products)

        );
        usort($terms, "recurring_first");
        foreach($terms as $k=>$v){
            $total += $v['initial'];
            $total_tax += $v['initial_tax'];
            $p = $v['product'];
            $vars['L_NAME'.$k]  = $p['title'];
            $vars['L_DESC'.$k]  = $v['terms_description'];
            $vars['L_AMT'.$k]   = $v['initial'];
            $vars['L_TAXAMT'.$k] = $v['initial_tax'];
            if($v['recurring']){
                    $vars['L_BILLINGTYPE'.$k] = 'RecurringPayments';
                    $vars['L_BILLINGAGREEMENTDESCRIPTION'.$k]   =   $v['terms_description'];
            }

        }
        $vars['AMT'] = $total+$total_tax;
        $vars['ITEMAMT'] = $total;
        $vars['TAXAMT'] = $total_tax;
        
        $resp = $this->paypalAPIRequest($payment_id, $vars);
        return $resp['TOKEN'];
    }


    function getExpressCheckoutDetails($token){
        $vars = array(
            'METHOD'    =>  'GetExpressCheckoutDetails',
            'TOKEN'     =>  $token
        );
        $resp = $this->paypalAPIRequest($_SESSION['_amember_payment_id'],$vars);
        return $resp;
    }

    function doExpressCheckout($payment_id,$details){
            global $db;
        $payment = $db->get_payment($payment_id);
         list($amount, $title, $products, $u, $invoicex) =
                $this->get_payment_params($payment_id);

        $terms = $this->getBillingTerms($payment_id, $products);
        $total = 0; $total_tax = 0;
        $vars=array(
            'METHOD'        =>  'DoExpressCheckoutPayment',
            'TOKEN'         =>  $details['TOKEN'],
            'PAYMENTACTION' =>  'Sale',
            'INVNUM'        =>  $payment_id,
            'PAYERID'       =>  $details['PAYERID'],
            'NOSHIPPING'    =>  1,
            'LOCALECODE'    =>  $this->locale,
            'CURRENCYCODE'  =>  $this->get_common_currency($products)

        );
        usort($terms, "recurring_first");

        foreach($terms as $k=>$v){
            $total += $v['initial'];
            $total_tax += $v['initial_tax'];
            $p = $v['product'];
            $vars['L_NAME'.$k]  = $p['title'];
            $vars['L_DESC'.$k]  = $v['terms_description'];
            $vars['L_AMT'.$k]   = $v['initial'];
            $vars['L_TAXAMT'.$k]   = $v['initial_tax'];
            if($v['recurring']){
                    $vars['L_BILLINGTYPE'.$k] = 'RecurringPayments';
                    $vars['L_BILLINGAGREEMENTDESCRIPTION'.$k]   =   $v['terms_description'];
            }

        }
        $vars['AMT'] = $total+$total_tax;
        $vars['TAXAMT'] = $total_tax;
        $vars['ITEMAMT'] = $total;

        $resp = $this->paypalAPIRequest($payment_id, $vars);
        if($resp['PAYMENTSTATUS'] != 'Completed'){
            fatal_error(_PLUG_PAY_PAYPALPRO_FERROR6);
        }else{
            return array($resp['TRANSACTIONID'], $resp['AMT']);
        }
    }

    function findPaymentId($payment_id, $product_id){
        global $db;
        $payment = $db->get_payment($payment_id);

        foreach((array) $db->get_user_payments($payment['member_id'],1) as $p){
            if(($p['product_id'] == $product_id) && (($p['payment_id'] == $payment_id) || ($p['data'][0]['ORIG_ID'] == $payment_id)))
                return $p['payment_id'];
        }
    }
    function doVoid($payment_id, $authorization_id){
            $vars = array(
                        'METHOD'            =>  'DoVoid',
                        'AUTHORIZATIONID'   =>  $authorization_id,
                        'NOTE'              =>  "Void Card Validation Payment"
                    );
                    
            $this->paypalAPIRequest($payment_id, $vars);
    }
    function createRecurringBillingProfiles($payment_id, $token="", $cc_info="",$ec_details=""){
        global $db;
        $payment = $db->get_payment($payment_id);
         list($amount, $title, $products, $u, $invoicex) =
                $this->get_payment_params($payment_id);
        $terms = $this->getBillingTerms($payment_id, $products);

        usort($terms, "recurring_first");

        foreach($terms as $t){
            if(!$t['recurring']) continue;

             $vars = array( 'METHOD'            =>  'CreateRecurringPaymentsProfile',
                            'SUBSCRIBERNAME'    =>  $u['name_f']." ".$u['name_l'],
                            'PROFILESTARTDATE'  =>  $this->getStartDelay($t['product']),
                            'PROFILEREFERENCE'  =>  $payment_id."-".$t['product']['product_id'],
                            'DESC'              =>  $t['terms_description'],
                            'AUTOBILLAMT'       =>  'AddToNextBilling',
                            'BILLINGPERIOD'     =>  $t['period'],
                            'BILLINGFREQUENCY'  =>  $t['freq'],
                            'TOTALBILLINGCYCLES'=>  $t['recurring_count'],
                            'AMT'               =>  $t['recurring'],
                            'TAXAMT'            =>  $t['recurring_tax'],
                            'CURRENCYCODE'      =>  $this->get_common_currency($products),
                            'EMAIL'             =>  $u['email']
                        );
              if($token) $vars['TOKEN']    =   $token;
              if($cc_info){
                $vars['CREDITCARDTYPE']    =  $cc_info['cc_type'];
                $vars['ACCT']              =  $cc_info['cc_number'];
                $vars['EXPDATE']           =  substr($cc_info['cc-expire'],0,2).'20'.substr($cc_info['cc-expire'], 2,2);
                $vars['CVV2']              =  $cc_info['cc_code'];
                $vars['FIRSTNAME']         =  $cc_info['cc_name_f'];
                $vars['LASTNAME']          =  $cc_info['cc_name_l'];
                $vars['STREET']            =  $cc_info['cc_street'];
                $vars['CITY']              =  $cc_info['cc_city'];
                $vars['STATE']             =  $cc_info['cc_state'];
                $vars['ZIP']               =  $cc_info['cc_zip'];
                $vars['COUNTRYCODE']       =  $cc_info['cc_country'];
                $vars['PHONENUM']          =  $cc_info['cc_phone'];
              }
              if($ec_details){
                    $vars['PAYERID']        =   $ec_details['PAYERID'];
                    $vars['PAYERSTATUS']    =   $ec_details['PAYERSTATUS'];
                    $vars['COUNTRYCODE']    =   $ec_details['COUNTRYCODE'];
              }
            $resp = $this->paypalAPIRequest($payment_id, $vars, 1);

        }


    }

function process_return($vars){
        global $db, $config;

        $token = $vars['token'];
        if ($token == '')         
            $this->postback_error(_PLUG_PAY_PAYPALPRO_ERROR);

        $details = $this->getExpressCheckoutDetails($token);

        $invoice  = $_SESSION[_amember_payment_id];
        $payment = $db->get_payment($invoice);
        if (!$payment)
            $this->postback_error(sprintf(_PLUG_PAY_PAYPALPRO_ERROR2, $invoice));
        
        if ($vars['confirm'] <= 0){ 
            list($amount, $title, $products, $u, $invoicex) =
                $this->get_payment_params($invoice);
            $t = new_smarty();
            $t->assign('payment', $payment);
            $t->assign('member', $u);
            $t->assign('products', $products);
            $subtotal = 0;
            foreach ($products as $i => $p){
                $pr = $db->get_product($p['product_id']);
                $products[$i]['price'] = $pr['price'];
                $subtotal += $pr['price'];
            }
            $t->assign('subtotal', $subtotal);
            $t->assign('total', $payment['amount']);
            
            $t->display(dirname(__FILE__)."/confirm.html");
            exit();
        }
            
        
        if($details['AMT'] == 0 ){
            list($txn_id,$amt) = array("Free Trial", 0);
        }else{
            list($txn_id, $amt) = $this->doExpressCheckout($invoice, $details);
        }
        
        if ($txn_id != '') {
            $err = $db->finish_waiting_payment(
                $invoice, $this->get_plugin_name(),
                $txn_id, $amt, $vars=array());
            if ($err) {
                fatal_error($err);
                return false;
            } else {
                $this->createRecurringBillingProfiles($invoice, $details['TOKEN'], '', $details);
                header("Location: $config[root_url]/thanks.php?payment_id=$invoice");
                return true;
            }
        } else {
            fatal_error(_PLUG_PAY_PAYPALPRO_FERROR8);
            return false;
        }
    }
    

    function paypal_validate_ipn($vars){
    	if ($this->config['dont_verify']){
    		return ;
    	}
        global $config;
        $vars['cmd'] = '_notify-validate';
        foreach ($vars as $k => $v)
            $req .= urlencode($k) . "=" . urlencode ($v) . '&';

        if (extension_loaded('curl') || $config['curl'] ){
            $ret = get_url("https://{$this->paypal_domain}/cgi-bin/webscr", $req);
        } else {
            $header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
            $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $header .= "Content-Length: " . strlen ($req) . "\r\n\r\n";
            $fp = fsockopen ($this->paypal_domain, 80, $errno, $errstr, 30);
            if (!$fp)
                return sprintf(_PLUG_PAY_PAYPALR_ERROR, $this->paypal_domain, $errstr, $errno);
            fputs ($fp, $header . $req);
            $res = '';
            while (!feof($fp))
                $res .= fgets ($fp, 1024);
            fclose ($fp);
            preg_match('/\r\n\r\n(.+)$/m', $res, $regs);
            $ret = $regs[1];
        }
        if ($ret == '')
            return sprintf(_PLUG_PAY_PAYPALR_ERROR, $this->paypal_domain, $errstr, $errno);
        if ($ret != 'VERIFIED')
            return sprintf(_PLUG_PAY_PAYPALR_ERROR2, $ret);
    }


    function addToPaymentLog($payment_id, $vars){
        global $db;
        $payment = $db->get_payment($payment_id);
        $payment['data'][] = $vars; 
        $db->update_payment($payment_id,$payment);
    }
    function ipnRecurringPaymentProfileCreated($vars){
        global $db;
// Find original payment and save data to it 
        list($payment_id, $product_id) = split("-", $vars['rp_invoice_id']);
        $payment = $db->get_payment($this->findPaymentId($payment_id, $product_id));
        $payment['data']['PAYPAL_PROFILE_ID'] = $vars['recurring_payment_id'];
        $payment['data'][] = $vars;
        $db->update_payment($payment['payment_id'], $payment);
        // Nothing to do.

    }

    function ipnRecurringPayment($vars){
        // First check if payment already handled.
        global $db;
        list($payment_id, $product_id) = split("-",$vars['rp_invoice_id']);
        $payment = $db->get_payment($this->findPaymentId($payment_id, $product_id));
        if(!$payment) $this->postback_error("Payment not found ".$vars['rp_invoice_id']);
        // make sure that payment is really our. Check paypal_profile_id 
        if($payment['data']['PAYPAL_PROFILE_ID'] != $vars['recurring_payment_id'])
            $this->postback_error('PAYPAL_PROFILE_ID is not correct. Possible payment was not created in aMember');
        if($vars['payment_status'] != 'Completed')  $this->postback_error('Payment is not completed');

        $product_id = $payment['product_id'];
        $member_id = $payment['member_id'];
        // Make sure that TXN is not processed yet.
        foreach($db->get_user_payments($member_id,1) as $p){
            if($p['data'][PAYPAL_TXN_ID] == $vars['txn_id']){
                $this->postback_error("IPN message already processed");
            }
        }
        if(!($begin_tm = strtotime($vars['payment_date']))){
            $begin_tm = time();
        }
        $begin_date = date('Y-m-d',$begin_tm);
        $product = get_product($product_id);
        $newp = array();
        $newp['member_id']   = $payment['member_id'];
        $newp['product_id']  = $payment['product_id'];
        $newp['paysys_id']   = $payment['paysys_id'];
        $newp['receipt_id']  = $vars['txn_id'];
        $newp['begin_date']  = $begin_date;
        $newp['expire_date'] = $product->get_expire($newp['begin_date']);
        $newp['amount']      = $vars['amount'];
        $newp['completed']   = 1;
        $newp['data']['PAYPAL_TXN_ID'] = $vars['txn_id'];
        $newp['data']['PAYPAL_PROFILE_ID'] = $vars['recurring_payment_id'];
        $newp['data'][]      = $vars;
        $newp_id = $db->add_payment($newp);
        $newp = $db->get_payment($newp_id);
        $newp['tax_amount'] = $vars['tax'];
        $db->update_payment($newp_id, $newp);


    }

function ipnRefund($vars){
    global $db;
    // Now we can find payment by receipt_id
    $payment_id = $db->query_one("select payment_id from {$db->config[prefix]}payments where receipt_id='".$vars['parent_txn_id']."'");
    if(!$payment_id)
        $this->postback_error("Payment not found with such ID:".$vars['parent_txn_id']);
    $payment = $db->get_payment($payment_id);
    $payment['data'][] = $vars;
    $payment['completed'] = 0;
    $db->update_payment($payment_id, $payment);
    
}
function process_postback($vars){
        if (($err = $this->paypal_validate_ipn($vars)) != '')
            $this->postback_error(sprintf(_PLUG_PAY_PAYPALR_ERROR3, $err));
        switch($vars['txn_type']){
            case "recurring_payment_profile_created"    :   $this->ipnRecurringPaymentProfileCreated($vars); break;
            case "recurring_payment"                    :   $this->ipnRecurringPayment($vars); break;   
        }
        if($vars['payment_status']=='Refunded'){
            $this->ipnRefund($vars);
        }
    }

    function init(){
        parent::init();
        add_product_field('paypal_currency',
            'PayPal Currency',
            'select',
            'valid only for PayPal processing.<br /> You should not change it<br /> if you use
            another payment processors',
            '',
            array('options' => array(
                '' => 'USD',
                'GBP' => 'GBP',
                'EUR' => 'EUR',
                'CAD' => 'CAD',
                'AUD' => 'AUD',
                'JPY' => 'JPY'
            ))
            );

        if($this->config['locale'])
            $this->locale = $this->config['locale'];
        if ($this->config['testing'])
            $this->paypal_domain = "www.sandbox.paypal.com";
        else
            $this->paypal_domain = "www.paypal.com";



    }
}

global $paypal_pro_pl;
$paypal_pro_pl = instantiate_plugin('payment', 'paypal_pro');

/* Direct Payment */
$plugins['payment'][] = 'paypal_pro_cc';
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_paypal_pro_cc extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('paypal_pro_cc', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db, $config, $paypal_pro_pl;

	return $paypal_pro_pl->get_cancel_link($payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['paypal_pro']['title'] ? $config['payment']['paypal_pro']['title'] : _PLUG_PAY_PAYPALPRO_TITLE2,
            'description' => $config['payment']['paypal_pro']['description'] ? $config['payment']['paypal_pro']['description'] : _PLUG_PAY_PAYPALPRO_DESC2,
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
            //'no_recurring'  =>1, // Will be done through postback.
            'phone' => 1,
            'code'  => 1,
        );
    }

    function get_rand($length){
        $all_g = "ABCDEFGHIJKLMNOPQRSTWXZ";
        $pass = "";
        srand((double)microtime()*1000000);
        for($i=0;$i<$length;$i++) {
            srand((double)microtime()*1000000);
            $pass .= $all_g[ rand(0, strlen($all_g) - 1) ];
        }
        return $pass;
    }

    function validate_cc_form($vars){
        global $db;
        
        if($vars['action'] != 'renew_cc') return;
        $member_id = $_SESSION['_amember_id'];
        $member = $db->get_user($member_id);
        foreach((array)$db->get_user_payments($member_id, 1) as $p){
            if(($p['paysys_id'] == "paypal_pro_cc") && $p['expire_date'] >=date("Y-m-d") && !$p['data']['CANCELLED']){
                $e = $this->update_profile($p, $vars);
                if($e) return $e;
            }
        }
    }

    function update_profile($payment, $cc_info){
        global $paypal_pro_pl;
        // Profile ID is not set, nothing to do

        if(!$payment['data']['PAYPAL_PROFILE_ID']) return;
        $cc_info['cc-expire'] = sprintf('%02d%02d', $cc_info['cc_expire_Month'], substr($cc_info['cc_expire_Year'], 2, 2));
        $vars = array(
                'METHOD'            =>  'UpdateRecurringPaymentsProfile',
                'PROFILEID'         =>  $payment['data']['PAYPAL_PROFILE_ID'],
                'NOTE'              =>  'CC INFO UPDATE',
                'CREDITCARDTYPE'    =>  $cc_info['cc_type'],
                'ACCT'              =>  $cc_info['cc_number'],
                'EXPDATE'           =>  substr($cc_info['cc-expire'],0,2).'20'.substr($cc_info['cc-expire'], 2,2),
                'CVV2'              =>  $cc_info['cc_code'],
                'FIRSTNAME'         =>  $cc_info['cc_name_f'],
                'LASTNAME'          =>  $cc_info['cc_name_l'],
                'STREET'            =>  $cc_info['cc_street'],
                'CITY'              =>  $cc_info['cc_city'],
                'STATE'             =>  $cc_info['cc_state'],
                'ZIP'               =>  $cc_info['cc_zip'],
                'COUNTRYCODE'       =>  $cc_info['cc_country'],
                'PHONENUM'          =>  $cc_info['cc_phone']
        );
        $resp = $paypal_pro_pl->paypalAPIRequest($payment['payment_id'], $vars,1);
        $error = $paypal_pro_pl->get_errors($resp);

        if($error)
            return $error;
    
    }
    /**************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){

        global $config;
        $log = array();
        
        global $paypal_pro_pl;            

    if ($cc_info['cc_country'] == 'UK') $cc_info['cc_country'] = 'GB';

        $vars = array(
            'METHOD'            =>  'DoDirectPayment',
            'PAYMENTACTION'     =>  'Sale',
            'IPADDRESS'         =>  $_SERVER['REMOTE_ADDR'],
            'RETURNFMFDETAILS'  =>  0,
            'CREDITCARDTYPE'    =>  $cc_info['cc_type'],
            'ACCT'              =>  $cc_info['cc_number'],
            'EXPDATE'           =>  substr($cc_info['cc-expire'],0,2).'20'.substr($cc_info['cc-expire'], 2,2),
            'CVV2'              =>  $cc_info['cc_code'],
            'EMAIL'             =>  $member['email'],
            'FIRSTNAME'         =>  $cc_info['cc_name_f'],
            'LASTNAME'          =>  $cc_info['cc_name_l'],
            'STREET'            =>  $cc_info['cc_street'],
            'CITY'              =>  $cc_info['cc_city'],
            'STATE'             =>  $cc_info['cc_state'],
            'ZIP'               =>  $cc_info['cc_zip'],
            'COUNTRYCODE'       =>  $cc_info['cc_country'],
            'PHONENUM'          =>  $cc_info['cc_phone'],
            'CURRENCYCODE'      =>  $currency,
            'DESC'              =>  $product_dscription,
            'INVNUM'            =>  $invoice
        );
         list($amount, $title, $products, $u, $invoicex) =
                $paypal_pro_pl->get_payment_params($invoice);

        $terms = $paypal_pro_pl->getBillingTerms($invoice, $products);
        $total = 0;$total_tax = 0;
        usort($terms, "recurring_first");

        foreach($terms as $k=>$v){
            $total += $v['initial'];
            $total_tax += $v['initial_tax'];
            $p = $v['product'];
            $vars['L_NAME'.$k]  = $p['title'];
            $vars['L_DESC'.$k]  = $v['terms_description'];
            $vars['L_AMT'.$k]   = $v['initial'];
            $vars['L_TAXAMT'.$k]   = $v['initial_tax'];
        }
        $vars['AMT'] = $total+$total_tax;
        $vars['ITEMAMT']    = $total;
        $vars['TAXAMT'] = $total_tax;

        if(!$total){
// Nothing to bill just validate CC
            $vars['AMT'] = $vars['L_AMT0'] = 1;
            $vars['PAYMENTACTION'] = 'Authorization';

        }
            $resp = $paypal_pro_pl->paypalAPIRequest($invoice, $vars,1);
            $error = $paypal_pro_pl->get_errors($resp);

    if($error){
            return array(CC_RESULT_DECLINE_PERM, $error, "", $log);
    }else{
            if($vars['PAYMENTACTION'] == 'Authorization'){
                $paypal_pro_pl->doVoid($invoice, $resp['TRANSACTIONID']);
            }
            $paypal_pro_pl->createRecurringBillingProfiles($invoice, "", $cc_info, "");
            return array(CC_RESULT_SUCCESS, "", $resp['TRANSACTIONID'], $log);
    }
    }
}

function paypal_pro_cc_get_member_links($user){
    return cc_core_get_member_links('paypal_pro_cc', $user);
}

function paypal_pro_cc_rebill(){
// Nothing to rebill
    return;
//     return cc_core_rebill('paypal_pro_cc');
}

cc_core_init('paypal_pro_cc');

add_payment_field("PAYPAL_PROFILE_ID", "PayPal recurring profileID", "text");
add_payment_field("PAYPAL_TXN_ID", "PayPal TXN_ID","text");
