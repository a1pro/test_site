<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: PayPal Payment Plugin
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5229 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/

class payment_paypal_r extends amember_advanced_payment {
    var $title = _PLUG_PAY_PAYPALR_TITLE;
    var $description = _PLUG_PAY_PAYPALR_DESC;
    var $fixed_price=0;
    var $recurring=1;
    var $built_in_trials=0;//1;
    var $paypal_domain = null;
    ///
    function get_days($orig_period){
        if (preg_match('/^\s*(\d+)\s*([y|Y|m|M|w|W|d|D]{0,1})\s*$/',
                $orig_period, $regs)){
            $period = $regs[1];
            $period_unit = $regs[2];
            if (!strlen($period_unit)) $period_unit = 'd';
            $period_unit = strtoupper($period_unit);
            switch ($period_unit){
                case 'Y':
                    if (($period < 1) or ($period > 5))
                        fatal_error(_PLUG_PAY_PAYPALR_FERROR);
                    break;
                case 'M':
                    if (($period < 1) or ($period > 24))
                        fatal_error(_PLUG_PAY_PAYPALR_FERROR2);
                    break;
                case 'W':
                    if (($period < 1) or ($period > 52))
                        fatal_error(_PLUG_PAY_PAYPALR_FERROR3);
                    break;
                case 'D':
                    if (($period < 1) or ($period > 90))
                         fatal_error(_PLUG_PAY_PAYPALR_FERROR4);
                    break;
                default:
                    fatal_error(sprintf(_PLUG_PAY_PAYPALR_FERROR5, $period_unit));
            }
        } else {
            fatal_error(_PLUG_PAY_PAYPALR_FERROR6.$orig_period);
        }
        return array($period, $period_unit);
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

    function do_recurring_bill($amount, $title, $products, $u, $invoice){
        global $db, $config;
        
        $paypal_account = $this->config['business'];
        $payment = $db->get_payment($invoice);
        $product = $db->get_product($payment['product_id']);
        if ($product['paypal_other_account'])
            $paypal_account = $product['paypal_other_account'];
        
        $vars = array(
            'cmd'         => '_xclick-subscriptions',
            'business'    => $paypal_account,
            'return'      =>
               "$config[root_url]/thanks.php?payment_id=$invoice",
            'notify_url'  =>
               $this->notify_url ? $this->notify_url :
               "$config[root_url]/plugins/payment/paypal_r/ipn.php",
            'item_name'   => $title,
            'no_shipping' => 1,
            'shipping'    => '0.0',
            'cancel_return' => "$config[root_url]/cancel.php?payment_id=$invoice",
            'no_note'     => 1,
            'custom'      => '',
            'invoice'     => $invoice.'-'.$this->get_rand(3),
            'bn'          => 'CgiCentral.aMemberPro',
            ////////////////////////////////////////////////////  Member info
            'first_name'  => $u['name_f'],
            'last_name'   => $u['name_l'],
            'address1'    => $u['street'],
            'city'        => $u['city'],
            'state'       => ($u['country'] == 'CA') ? $config['states'][$u['state']] : $u['state'],
            'zip'         => $u['zip'],
            'country'     => $u['country'],
            'lc'          => $this->config['lc'],
        /////////////////////////////////////////////////////////////////
            'sra'     => 1,
///// Ticket #HPU-80211-470: paypal_r plugin not passing the price properly (or at all)?
/////            'src'     => 1,
            'rm'      => 2
        );
        list($a, $p, $t, $rebill_times,$taxes) = $this->build_subscription_params($products, $amount, $u, $invoice);
        if ($p[1] != '')
            $vars['a1'] = sprintf('%.2f', $a[1]); $vars['p1'] = $p[1]; $vars['t1'] = $t[1];
        if ($p[2] != '')
            $vars['a2'] = sprintf('%.2f', $a[2]); $vars['p2'] = $p[2]; $vars['t2'] = $t[2];
        $vars['a3'] = sprintf('%.2f', $a[3]); $vars['p3'] = $p[3]; $vars['t3'] = $t[3];
        $vars['currency_code'] = $this->get_common_currency($products);
        if ($rebill_times > 1){
                $vars['srt'] = $rebill_times;
                $vars['src'] = 1; // Ticket #HPU-80211-470: paypal_r plugin not passing the price properly (or at all)?
        } elseif (!$rebill_times){
                $vars['src'] = 1;
        }
        /*
        If src=1, srt equaling 1 will fail, and 0 or any other number will
        rebill until cancelled or X amount of times, respectively.
        */

        /// save subscription params for future checking
        $pv = array();
        for ($i=1;$i<=3;$i++){
            if (isset($vars["p$i"])) {
                $pv["p$i"] = $vars["p$i"];
                $pv["t$i"] = $vars["t$i"];
                $pv["a$i"] = $vars["a$i"];
                $pv["tax$i"] = sprintf('%.2f', $taxes[$i]);
            }
        }
        $pv['currency_code'] = $vars['currency_code'];
        $payment = $db->get_payment($invoice);
        $payment['data']['paypal_vars'] = serialize($pv);
        $db->update_payment($payment['payment_id'], $payment);
        ////
        return $this->encode_and_redirect("https://$this->paypal_domain/cgi-bin/webscr", $vars);
    }
    function build_subscription_params($products, $total_price, $u, $invoice){
        global $config, $db;
        
        $a = $p = $t = array(1 => '', 2 => '', 3 => '');
        $was_recurring = 0;
        
        $pc = & new PriceCalculator();
        $pc->setTax(get_member_tax($u['member_id']));
        
        $payment = $db->get_payment($invoice);
        $coupon_code = $payment['data'][0]['COUPON_CODE'];
        
        $coupon = array();
        if ($config['use_coupons'] && $coupon_code != ''){
            $coupon = $db->coupon_get($coupon_code);
            if ( $coupon['coupon_id'] > 0 )
                $pc->setCouponDiscount($coupon['discount'], split(',',trim($coupon['product_id'])));
            else
                $coupon = array();                
        }
        $rebill_times = $products[0]['rebill_times'];
        foreach ($products as $pr){
            $pp = $pt = array(1 => '', 2 => '', 3 => '');
            if ($pr['trial1_days'] != '')
                list($pp[1], $pt[1]) = $this->get_days($pr['trial1_days']);
            if ($pr['expire_days'] != '')
                list($pp[3], $pt[3]) = $this->get_days($pr['expire_days']);
            if (!$pr['is_recurring']){
                //fatal_error(_PLUG_PAY_PAYPALR_FERROR7);
                //$a[1] += $pa[3];
                // there is at least one recurring product if we went here
            } else { // recurring
                if ($was_recurring){ // check if it was compatible
                    if (array_diff($p, $pp) || array_diff($t, $pt))
                        fatal_error(_PLUG_PAY_PAYPALR_FERROR8);
                    if ($pr['rebill_times'] != $rebill_times)
                        fatal_error(_PLUG_PAY_PAYPALR_FERROR8);
                }
                $p[1] =$pp[1]; $p[3] =$pp[3];
                $t[1] =$pt[1]; $t[3] =$pt[3];
                $was_recurring++;
            }
        }
        
        // calculate first trial - add both recurring, and non-recurring products here
        $pc->setPriceFields(array('trial1_price', 'price'));
        $need_trial1 = false;
        foreach ($products as $pr){
            if ($pr['trial1_price'] || !$pr['is_recurring'] || ($coupon['coupon_id'] && !$coupon['is_recurring'])) 
                $need_trial1 = true;
            $pc->addProduct($pr['product_id']);
        }
        if ($need_trial1){
            $terms[1] = $pc->calculate();
            $a[1] = $terms[1]->total;
            
            if (!$p[1]){ // we added trial because of discount or non-recurring product
                if ($rebill_times) $rebill_times--; // lets decrease rebill_times then!
            }
        }

        // calculate regular rate
        $pc->emptyProducts();    
        $pc->setPriceFields(array('price'));
        foreach ($products as $pr){
            if ($pr['is_recurring'])
                $pc->addProduct($pr['product_id']);
            if (!$coupon['is_recurring']) $pc->setCouponDiscount(null);
        }
        $terms[3] = $pc->calculate();
        $a[3] = $terms[3]->total;
                
        if ($a[1] && !$p[1]){ // trial1 price set, but trial 1 period did not 
            $p[1] = $p[3];
            $t[1] = $t[3];
        }
        $taxes = array();
        foreach (array(1,2,3) as $k)
            if ($terms[$k])
                $taxes[$k] = $terms[$k]->tax;
        return array($a, $p, $t, $rebill_times, $taxes);
    }
    function do_not_recurring_bill($amount, $title, $products, $u, $invoice){
        global $config,$db;
	    $payment = $db->get_payment($invoice);
	    
	    $paypal_account = $this->config['business'];
        $payment = $db->get_payment($invoice);
        $product = $db->get_product($payment['product_id']);
        if ($product['paypal_other_account'])
            $paypal_account = $product['paypal_other_account'];
	    
        $vars = array(
            'cmd'         => '_xclick',
            'business'    => $paypal_account,
            'return'      =>
               "$config[root_url]/thanks.php?payment_id=$invoice",
            'notify_url'  =>
               $this->notify_url ? $this->notify_url :
               "$config[root_url]/plugins/payment/paypal_r/ipnr.php",
            'cancel_return' =>
               "$config[root_url]/cancel.php?payment_id=$invoice",
            'item_name'   => $title,
            'shipping'    => '0.0',
            'no_shipping' => 1,
            'no_note'     => 1,
            'bn'          => 'CgiCentral.aMemberPro',
            'custom'      => '',
            'tax'         => $tax = sprintf('%.2f', $payment[tax_amount]),
            'amount'      => sprintf('%.2f', $amount - $tax),
            'invoice'     => $invoice.'-'.$this->get_rand(3),
            ////////////////////////////////////////////////////  Member info
            'first_name'  => $u['name_f'],
            'last_name'   => $u['name_l'],
            'address1'    => $u['street'],
            'city'        => $u['city'],
            'state'       => ($u['country'] == 'CA') ? $config['states'][$u['state']] : $u['state'],
            'zip'         => $u['zip'],
            'country'     => $u['country'],
            'lc'          => $this->config['lc'],
        );
        // add currency code
        $vars['currency_code'] = $this->get_common_currency($products);
        return $this->encode_and_redirect("https://$this->paypal_domain/cgi-bin/webscr", $vars);
    }
    function get_common_currency($products){
        $c = '';
        foreach ($products as $p){
            if ($p['paypal_currency'] == '')
                $p['paypal_currency'] = 'USD';
            if (($c != '') && ($c != $p['paypal_currency']))
                fatal_error(_PLUG_PAY_PAYPALR_FERROR9, 0);
            $c = $p['paypal_currency'];
        }
        return $c;
    }


    function do_bill($amount, $title, $products, $u, $invoice){
        global $config;
        
        $_SESSION['_amember_payment_id'] = $invoice;
        foreach ($products as $p)
            if ($p['is_recurring'])
                return $this->do_recurring_bill($amount, $title, $products, $u, $invoice);
        return $this->do_not_recurring_bill($amount, $title, $products, $u, $invoice);
    }

    function get_begin_date($member_id, $product_id){
        global $db;
        $payments = & $db->get_user_payments(intval($member_id));
        $date = date('Y-m-d');
        foreach ($payments as $p){
            if (($p['product_id'] == $product_id) &&
                ($p['expire_date'] > $date) &&
                ($p['completed'] > 0)
                )
                $date = $p['expire_date'];
        }
        list($y,$m,$d) = split('-', $date);
        $date = date('Y-m-d', mktime(0,0,0,$m, $d, $y));
        return $date;
    }

    function resend_postback($url, $vars){
        $s = array();
        foreach ($vars as $k => $v)
            $s[] = urlencode($k) . '=' . urlencode($v);
        get_url($url, join('&', $s));
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

    function process_postback($vars){
        global $db, $config;

        $is_recurring = $GLOBALS['amember_is_recurring'];

        if (get_magic_quotes_gpc())
            foreach ($vars as $k=>$v)
                $vars[$k] = stripslashes($v);

        /// resend postback if necessary
        if ($this->config['resend_postback'])
            foreach (preg_split('/\s+/', $this->config['resend_postback']) as $url){
                $url = trim($url);
                if ($url == '') continue;
                $this->resend_postback($url, $vars);
            }

        // validate if it is true PayPal IPN
        if (($err = $this->paypal_validate_ipn($vars)) != '')
            $this->postback_error(sprintf(_PLUG_PAY_PAYPALR_ERROR3, $err));

        // check business
        $emails = array_merge(
                array($this->config['business']),
                (array)preg_split('/\r\n/', $this->config['alt_business']));

        $products = $db->get_products_list();
        foreach ($products as $pr){
            if ($pr['paypal_other_account'])
                $emails[] = $pr['paypal_other_account'];
        }


        $email_ok = 0;
        foreach ($emails as $e)
            if (($e != '') && (!strcasecmp($e, $vars['receiver_email'])))
                $email_ok++;
        if (!$email_ok && $vars['receiver_email']){
            $emails_text = join("\n", $emails);
            $inv = intval($vars['invoice']);
            mail_admin("Dear Admin,
    There is probably a problem with PayPal plugin coniguration in aMember Pro
    $config[root_url]/admin/

    PayPal sent a payment record with primary email address:
       $vars[receiver_email]
    However, you have only the following e-mail addresses configured in
    aMember Pro CP -> Setup/Configuration -> PayPal
       $emails_text

    If it is really your transaction and your primary PayPal email address
    is $vars[receiver_email], go to aMember CP -> Setup -> PayPal
    and set configure PayPal email address as $vars[receiver_email]

    This payment won't be handled automatically. Please go to
    aMember CP -> Payments, find payment #{$inv}, click Edit and mark it as
    Paid. Also, if it is recurring transaction, set expiration date to
    Dec/31/2012. Expiration date will be corrected automatically when
    subscription will be expired or terminated.

    --
    Your aMember Pro script
    P.S. If you have any questions, resend this email to support@cgi-central.net
    with server access details.",
    "*** aMember - PayPal payment error");


            $this->postback_error(sprintf(_PLUG_PAY_PAYPALR_ERROR4, $vars[receiver_email]).
                sprintf(_PLUG_PAY_PAYPALR_ERROR5, $vars[receiver_email]));
        }
        if ($is_recurring){
            return $this->process_recurring_postback($vars);
        } else {
            return $this->process_not_recurring_postback($vars);
        }

    }
    function process_not_recurring_postback($vars){
        global $db;
        $invoice = intval($vars['invoice']);

        // now check payment status
        if ($vars['payment_status'] != 'Completed')
            $this->postback_error(sprintf(_PLUG_PAY_PAYPALR_ERROR6, $vars[payment_status]).
             _PLUG_PAY_PAYPALR_ERROR7);

        // check payment currency
        $payment = $db->get_payment($invoice);

        if (!$payment['payment_id']){
            $invoice = $this->create_new_payment($vars);
            $payment = $db->get_payment($invoice);
        }

        if ($payment['data'][0]['BASKET_PRODUCTS'])
            $product_ids = (array)$payment['data'][0]['BASKET_PRODUCTS'];
        else
            $product_ids = array($payment['product_id']);
        $products = array();
        foreach ($product_ids as $product_id)
            $products[] = $db->get_product($product_id);
        if (($c1=$vars['mc_currency']) != ($c2=$this->get_common_currency($products)))
            $this->postback_error(sprintf(_PLUG_PAY_PAYPALR_ERROR8, $c1, $c2));

        $amt = ($vars['payment_gross'] > 0.0) ? $vars['payment_gross'] : $vars['mc_gross'];
        if ($amt != $payment['amount'])
            $this->postback_error(sprintf(_PLUG_PAY_PAYPALR_ERROR9, $amt, $payment[amount]));
        // fwp
        $err = $db->finish_waiting_payment(
            $invoice, $this->get_plugin_name(),
            $vars['txn_id'], $amt, $vars);
        if ($err)
            $this->postback_error("finish_waiting_payment error: $err");
        // shift dates if e-check transaction was pending:
        if ($vars['payment_type'] == 'echeck'){
            $p = $db->get_payment($invoice);
            if ($p['begin_date'] != '') {
                $days_diff = $this->days_diff($p['begin_date'], $vars['payment_date']);
                $days_diff = $this->days_diff($p['begin_date'], $vars['payment_date']);
                if ($days_diff > 0)
                    $this->shift_payment_expiration($invoice, $days_diff);
            }
        }
        return true;
    }
    function get_lock(){
        global $db;
        register_shutdown_function(array($this, 'release_lock'));
        $this->shutdown_function_set = true;
        return $db->query_one("SELECT GET_LOCK('".$db->config['prefix']."payments', 30)");
    }
    function release_lock(){
        global $db;
        return $db->query("DO RELEASE_LOCK('".$db->config['prefix']."payments')");
    }

    function process_recurring_postback($vars){
        global $db,$config, $t;
        switch ($vars['txn_type']){
            case 'subscr_signup':
                $this->get_lock();
                $invoice = intval($vars['invoice']);
                $payment = $db->get_payment($invoice);

                if (!$payment['payment_id']){
                    $invoice = $this->create_new_payment($vars);
                    $payment = $db->get_payment($invoice);
                }

                if ($err = $this->check_periods($vars, $payment))
                    $this->postback_error($err);
                // update customer e-mail if option enabled
                if ($this->config['rewrite_email'] && ($vars['payer_email'] != '')){
                    $u = $db->get_user($payment['member_id']);
                    if (!$u['data']['paypal_email_rewritten']){
                        $u['data']['paypal_email_rewritten'] = 1;
                        $u['email'] = $vars['payer_email'];
                        $db->update_user($u['member_id'], $u);
                    }                        
                }
		if(isset($vars['mc_amount1']) && $vars['mc_amount1']==0){
		// Free trial period, need to activate payment because next subscr_payment will be sent only for first payment. 
                    $err = $db->finish_waiting_payment(
                        $invoice, $this->get_plugin_name(),
                        $vars['subscr_id'], $vars['mc_gross'], $vars, $vars['payer_id']);
                    if ($err)
                        $this->postback_error("finish_waiting_payment error: $err");	
                    $payment = $db->get_payment($invoice);
                    $payment['data']['txn_id'] = 'subscr_signup';
                    $db->update_payment($invoice, $payment);
                    
		
		}else{
		    // Just save IPN message for debug;
		    $payment['data'][] = $vars; 
		    $db->update_payment($invoice, $payment);
		}
                // handle transaction                
//                $p = $db->get_payment($invoice);
//                $p['begin_date']  = date('Y-m-d');
//                $p['expire_date'] = '2012-12-31';
//                $db->update_payment($invoice, $p);
                $this->release_lock();
                break;
            case 'subscr_eot':
            case 'subscr_failed':
                $payment_id = $this->find_last_payment_id($vars['subscr_id'], $vars['invoice']);
                if (!$payment_id)
                    $this->postback_error(_PLUG_PAY_PAYPALR_ERROR11);
                $p = $db->get_payment($payment_id);
                $new_expire = date('Y-m-d', time() - 3600 * 24 ); //yesterday date
		
                if ($p['expire_date'] && ($new_expire < $p['expire_date'])){
                    $p['expire_date'] = $new_expire;
                    if(!$p['data']['failed_orig_expiration']) $p['data']['failed_orig_expiration'] = $p['expire_date'];
		}
                $p['data'][] = $vars;
                $db->update_payment($payment_id, $p);
                break;
            case 'subscr_cancel':
                $payment_id = $this->find_last_payment_id($vars['subscr_id'], $vars['invoice']);
                if (!$payment_id)
                    $this->postback_error(_PLUG_PAY_PAYPALR_ERROR11);
                $p = $db->get_payment($payment_id);
                $p['data'][] = $vars;
                $p['data']['CANCELLED'] = 1;
                $p['data']['CANCELLED_AT'] = strftime($config['time_format'], time());
                $db->update_payment($payment_id, $p);
        if(!$t) $t =& new_smarty();
            if ($config['mail_cancel_admin']){
                $t->assign('user', $db->get_user($p[member_id]));
                $t->assign('payment', $p);
                $t->assign('product', $db->get_product($p['product_id']));
                $et = & new aMemberEmailTemplate();
                $et->name = "mail_cancel_admin";
                mail_template_admin($t, $et);
            }
    
            if ($config['mail_cancel_member']){
                $t->assign('user', $member = $db->get_user($p[member_id]));
                $t->assign('payment', $p);
                $t->assign('product', $db->get_product($p['product_id']));
                $et = & new aMemberEmailTemplate();
                $et->name = "mail_cancel_member";
                mail_template_user($t, $et, $member);
            }
        break;
            case 'subscr_payment':
	        if ($vars['payment_status'] != 'Completed')
	            $this->postback_error(sprintf(_PLUG_PAY_PAYPALR_ERROR6, $vars[payment_status]).
	             _PLUG_PAY_PAYPALR_ERROR7);

                $invoice = intval($vars['invoice']);
                $payment_id = $this->find_last_payment_id($vars['subscr_id'], $invoice);
                
                if(!$payment_id){
                    $payment_id = $this->create_new_payment($vars);
                    $invoice = $payment_id;
                }
                if (!$payment_id)
                    $this->postback_error(_PLUG_PAY_PAYPALR_ERROR11);
                $this->get_lock();
                $p = $db->get_payment($payment_id);
                // if that is a NEW RECURRING payment,
                if ($p['data']['txn_id'] == $vars['txn_id']){
                    // just record a payment for debug
                    $p['data'][] = $vars;
                    $db->update_payment($p['payment_id'], $p);
                    $this->release_lock();
                    return true;
                }
                // it is a first payment for this subscription
                // if !payment written, write to current
                // 
                
                if (($p['payment_id'] == $invoice) && !$p['data']['txn_id']){
		    if(!$p['completed']){
                        $err = $db->finish_waiting_payment($invoice, $this->get_plugin_name(),
							$vars['subscr_id'], $vars['mc_gross'], $vars, $vars['payer_id']);
                        if ($err)
                            $this->postback_error("finish_waiting_payment error: $err");
			$p = $db->get_payment($invoice);
			$p['data']['txn_id'] = $vars['txn_id'];
			$db->update_payment($invoice, $p);
		    }else{
                        $p['data'][] = $vars;
                        $p['amount'] = $vars['mc_gross'];
                        $p['data']['txn_id'] = $vars['txn_id'];
                        if($p['data']['failed_orig_expiration']){
                        	$p['expire_date'] = $p['data']['failed_orig_expiration'];
                        	$p['data']['failed_orig_expiration'] = '';
                        }
    //                    $p['expire_date'] = '2012-12-31'; // set to 'Recurring' again. This can be possible re-try for recently failed payment.
                        $db->update_payment($p['payment_id'], $p);
		    }

                    $this->release_lock();
//                    if ($vars['txn_type'] == 'subscr_payment'){
//                        add_affiliate_commission($payment_id, $vars['txn_id'], $vars['mc_gross']);
//                    }
                } else {
                    //   1. set previous payment expire_date to yesterday
                    //   2. add a new payment with
                    $p['expire_date'] = date('Y-m-d');
                    $db->update_payment($p['payment_id'], $p);
                    $this->release_lock();
		    $pr = get_product($p[product_id]);
                    $newp = array();
                    $newp['member_id']   = $p['member_id'];
                    $newp['product_id']  = $p['product_id'];
                    $newp['paysys_id']   = 'paypal_r';
                    $newp['receipt_id']  = $vars['subscr_id'];
                    $newp['begin_date']  = date('Y-m-d');
		    $newp['expire_date'] = $pr->get_expire(date('Y-m-d'),'expire_days');
                    $newp['amount']      = $vars['mc_gross'];
                    $newp['completed']   = 1;
                    $newp['data']        = 
                        array('txn_id' => $vars['txn_id'],
                              'paypal_vars' => $p['data']['paypal_vars'],
                        );
					$newp['tax_amount'] = $p['data']['paypal_vars']['tax3'];                        
                    $newp['data'][]      = $vars;
                    $db->add_payment($newp);
                }
                break;
            default: /// handle and register other events
                if (in_array($vars['payment_status'], array('Reversal', 'Refunded')))
                   return $this->process_refund($vars, $is_recurring=1);
                $this->get_lock();
                $payment_id = $this->find_last_payment_id($vars['subscr_id'], $vars['invoice']);
                if (!$payment_id)
                    $this->postback_error(_PLUG_PAY_PAYPALR_ERROR11);
                $p = $db->get_payment($payment_id);
                $p['data'][] = $vars;
                $data = $db->encode_data($p['data']);
                $data = $db->escape($data);
                $db->query(
                $s = "UPDATE {$db->config[prefix]}payments
                SET data = '$data',
                    amount = '{$p[amount]}'
                WHERE payment_id=$payment_id");
                $this->release_lock();
        }
        return true;
    }

    
    function get_value_from_vars($var, &$vars){
        global $db; 
        switch($var){
            case 'name_f'       :   $ret = $vars['first_name']; break;
            case 'name_l'       :   $ret = $vars['last_name']; break;
            case 'email'        :   $ret = $vars['payer_email']; break;
            case 'street'       :   $ret = $vars['addres_street']; break;
            case 'zip'          :   $ret = $vars['address_zip']; break;
            case 'state'        :   $ret = $vars['address_state']; break;
            case 'country'      :   $ret = $vars['address_country_code']; break;
            case 'city'         :   $ret = $vars['address_city']; break;
            case 'product_id'   :                           
                                    $ret = $this->find_product_by_field('paypal_id', intval($vars["item_number"]) ? intval($vars["item_number"]) :
                                                                            (intval($vars["item_number1"]) ? intval($vars["item_number1"]) : ""));
                                    break;

            case 'receipt_id'   :   $ret = $vars['subscr_id']; break;
            case 'amount'       :   $ret = $vars['mc_amount1'] ? $vars['mc_amount1'] :
                                                    ($vars['mc_amount3'] ? $vars['mc_amount3'] :
                                                        ($vars['payment_gross'] ? $vars['payment_gross'] : $vars['mc_gross'])
                                                    );
                                    break;

                        default : $ret = '';
        }
        return trim($ret);
    }
    function get_payment_signup_date($p){
        foreach ($p['data'] as $r){
            if (!is_array($r)) continue;
            if ($r['txn_type'] != 'subscr_signup') continue;
            return strtotime($r['subscr_date']);
        }
    }

    function find_last_payment_id($subscr_id, $invoice){
        global $db;
        $subscr_id = $db->escape($subscr_id);
        $invoice = intval($invoice);
        $payment_id = $db->query_one($s = "SELECT payment_id FROM {$db->config[prefix]}payments
            WHERE (receipt_id <> '' AND receipt_id='$subscr_id') OR payment_id='$invoice'
            ORDER BY expire_date='2012-12-31' DESC, payment_id DESC");
        return $payment_id;
    }

    function find_payment_id($vars){
        $x = intval($vars['invoice']);
        if ($x) return $x;
        global $db;  // try to find payment with this subscription id
        $s = $db->escape($vars['subscr_id']);
        if (!$s) return;
        $q = $db->query("SELECT payment_id
            FROM {$db->config[prefix]}payments
            WHERE receipt_id='$s' AND paysys_id='paypal_r'
        ");
        list($x) = mysql_fetch_row($q);
        if ($x) return $x;
    }
    function find_payments_by_subscr_id($subscr_id, $txn_id = null){
        global $db;
        $subscr_id = $db->escape($subscr_id);
        $invoice = $db->escape($txn_id);
        return $db->query_all("SELECT payment_id
            FROM {$db->config[prefix]}payments
            WHERE receipt_id = '$subscr_id' ");

    }

    function check_periods($vars, $payment){
        global $db;

        $terms = $payment['data']['paypal_vars'];
        if (!$terms) return;
        $terms = @unserialize($terms);
        if (!$terms) return;

        //check currency
        $curr = $terms['currency_code'] ? $terms['currency_code'] : "USD";
        if ($vars['mc_currency'] != $curr)
            return "Incorrect currency: '$vars[mc_currency]', must be '$curr'";

        $prefix = ($curr != 'USD') ? 'mc_' : '';

        // check rates and periods
        for ($i=1;$i<=3;$i++){
            $a = $terms["a$i"];
            $p = $terms["p$i"];
            $t = strtolower($terms["t$i"]);
            $aa = $vars["{$prefix}amount{$i}"];
            list($pp, $tt) = preg_split('/\s+/', strtolower($vars["period$i"]));
            if ($a != $aa)
                return "a$i != aa$i: '$a' != '$aa'";
            if ($p != $pp)
                return "p$i != pp$i: '$p' != '$pp'";
            if ($t != $tt)
                return "t$i != tt$i: '$t' != '$tt'";
        }
    }

    function refund_payment($vars, $payment_id, $set_completed=null){
        global $db;
        $p = $db->get_payment($payment_id);
        $p['data'][] = $vars;
        if (!is_null($set_completed))
            $p['completed'] = $set_completed;
        $db->update_payment($payment_id, $p);
    }

    function process_refund($vars, $is_recurring){
        global $db;
        if ($vars['payment_status'] == 'Reversal'){
            foreach ($this->find_payments_by_subscr_id($vars['subscr_id']) as $p)
                $this->refund_payment($vars, $p['payment_id'], 0);
        } elseif ($vars['payment_status'] == 'Refunded') {
            //$payment_id = $this->find_payment_id($vars);
            //$this->refund_payment($vars, $payment_id, 0);
            // not clear affiliates commission handling
            // so it is better to do manually yet
        }
    }
    function get_cancel_link($payment_id){
        return
        "https://$this->paypal_domain/cgi-bin/webscr?cmd=_subscr-find&alias=".urlencode($this->config['business']);
    }
    function init(){
        parent::init();
        add_payment_field('paypal_vars', 'PayPal subscription terms', 'hidden', '');
        add_product_field('trial1_days',
            'Trial 1 Duration',
            'period',
            'read PayPal docs for explanation, leave empty to not use trial',
            'validate_period'
            );

        add_product_field('trial1_price',
            'Trial 1 Price',
            'money',
            'set 0 for free trial'
            );

        add_product_field('rebill_times',
            'Recurring Times',
            'text',
            'Recurring Times. This is the number of payments which<br />
             will occur at the regular rate. If omitted, payment will<br />
             continue to recur at the regular rate until the subscription<br />
             is cancelled.<br />
             NOTE: this option is working for particular payment processing methods only'
            );

        if ($this->config['allow_create']){
            add_product_field(
                        'paypal_id', 'PayPal Product ID',
                        'text', 'an ID of corresponding product in your PayPal account (item_number)'
            );
        }

        if ($this->config['other_account']){
            add_product_field(
                        'paypal_other_account', 'Other PayPal Account',
                        'text', 'email address of an other PayPal account<br />which will be used with this product'
            );
        }

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
        add_payment_field('txn_id', 'PayPal Transaction Id',
            'readonly', 'internal');
        add_payment_field('failed_orig_expiration', 'Failed payment original expiration date',
            'readonly', 'internal');
        add_member_field('paypal_email_rewritten', 'PayPal had reset user e-mail',
            'hidden', 'internal');
        if ($this->config['testing'])
            $this->paypal_domain = "www.sandbox.paypal.com";
        else
            $this->paypal_domain = "www.paypal.com";
    }
    function days_diff($dat1, $dat2){
        // returns $dat1 - $dat2 (days)
        return -intval((strtotime($dat1) - strtotime($dat2))/(3600*24));
    }
    function shift_payment_expiration($payment_id, $days){
        global $db;
        $p = $db->get_payment($payment_id);
        $e = date('Y-m-d', strtotime($p['expire_date']) + 3600*24*$days);
        $p['expire_date'] = $e;
        $db->update_payment($payment_id, $p);
    }
}

$pl = & instantiate_plugin('payment', 'paypal_r');

?>
