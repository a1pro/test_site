<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: CC CORE payment plugin
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5228 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

/// charge type constants (to pass to plugin function)


define('CC_CHARGE_TYPE_REGULAR', 1);
define('CC_CHARGE_TYPE_START_RECURRING', 2);
define('CC_CHARGE_TYPE_RECURRING', 3);
define('CC_CHARGE_TYPE_TEST', 4);

// result of processing (from the plugin call)
define('CC_RESULT_INTERNAL_ERROR', -1); // error of the plugin or paysystem
define('CC_RESULT_SUCCESS', 0);          // successfull
define('CC_RESULT_DECLINE_TEMP', 1); // temporary problem with customer cc info, 
                                   // like insufficient funds
define('CC_RESULT_DECLINE_PERM', 2); // permanent problem with customer cc info, 
                                   // like credit card is expired or locked
define('CC_RESULT_IGNORE', 3); // don't do nothing

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 function cc_core_init($plugin)

 Adds necessary fields to member, product and possible payment
    
* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
$GLOBALS['cc_core'] = array();
function cc_core_init($plugin){
    global $config, $cc_core;
    // get features array
    $pl = & instantiate_plugin('payment', $plugin);
    $features = $pl->get_plugin_features();
    $cc_core[$plugin] = & $pl;
    
    ///
    add_paysystem_to_list(
    array(
                'paysys_id'   => $plugin,
                'title'       => $pl->config['title'] ? $pl->config['title'] : $features['title'],
                'description' => $pl->config['description'] ? $pl->config['description'] : $features['description'],
                'public'      => 1,
                'recurring'   => $features['no_recurring'] ? 0 : 1,
            )
    );
    // add product fields 
    if (!$features['no_recurring']){
        add_product_field(
                    'is_recurring', 'Recurring Billing',
                    'checkbox', 'should user be charged automatically<br />
                     when subscription expires', ''
        );
        add_product_field('trial1_days', 
            'Trial 1 Duration',
            'period',
            'Trial 1 duration', 'validate_period'
            );

        add_product_field('trial1_price', 
            'Trial 1 Price',
            'money',
            'set 0 for free trial', ''
            );
        add_product_field('trial_group', 
            'Trial Group',
            'text',
            "If this field is filled-in, user will be unable to order the product<br />
             twice. It is extermelly useful for any trial product. This field<br />
             can have different values for different products, then 'trial history'<br />
             will be separate for these groups of products.<br />
             If your site offers only one level of membership,<br />
             just enter \"1\" to this field. 
            "
            );
        add_product_field('rebill_times', 
            'Recurring Times',
            'text',
            'Recurring Times. This is the number of payments which<br />
             will occur including first payment. If omitted, payment will<br />
             continue to recur at the regular rate until the subscription<br />
             is cancelled.<br />
             NOTE: not for all payment processing this option is working'
            );
    }
    if ($features['currency'])
        add_product_field($plugin.'_currency',  $pl->config['title'] . " Currency",
             'select', "valid only for $plugin payments", '', array('options' => $features['currency']));

    /// add member fields
    add_member_field(
            'cc_country',
            'Billing Country',
            'select', 
            "",
            '', 
            array('options' => db_getCountryList(true))
    );
    add_member_field(
            'cc_street',
            'Billing Street Address',
            'text', 
            "",
            '',
            array('hidden_anywhere' => 1)
    );
    add_member_field(
            'cc_city',
            'Billing City',
            'text', 
            "",
            ''
    );
    add_member_field(
            'cc_state',
            'Billing State',
            'state', 
            "",
            '', 
            array('options' => array(), 'require_value'=>array(array('name'=>'cc_country')))
    );
    add_member_field(
            'cc_zip',
            'Billing ZIP',
            'text', 
            "",
            ''
    );
    if ($features['name_f']){
        add_member_field('cc_name_f', 'Billing First Name', 'text', '');
        add_member_field('cc_name_l', 'Billing Last Name', 'text', '');
    } elseif ($features['name']) {
        add_member_field('cc_name', 'Billing Name', 'text', '');
    }
    if ($features['company'])
        add_member_field('cc_company', 'Billing Company', 'text', '');
    if ($features['phone']) 
        add_member_field('cc_phone', 'Billing Phone#', 'text', '');
    if ($features['housenumber']) 
        add_member_field('cc_housenumber', 'Billing Housenumber', 'text', '');

    add_member_field(
            'cc',
            'Credit Card # (visible)',
            'readonly', 
            'credit card number',
            '',
            array('hidden_anywhere' => 1)
    );

    add_member_field(
            'cc-hidden',
            'Credit Card # (crypted)',
            'hidden', 
            '',
            '',
            array('hidden_anywhere' => 1)
    );

    add_member_field(
            'cc-expire',
            'Credit Card Expire',
            'readonly', 
            'Expiration date (mmyy)',
            '',
            array('hidden_anywhere' => 1)
    );

    if ($features['code'] == 2)
        add_member_field(
                'cc_code',
                'Credit Card Code (crypted)',
                'hidden', 
                '',
                '',
                array('hidden_anywhere' => 1)
        );

    if ($features['province_outside_of_us']){
        add_member_field(
                'cc_province',
                'Billing International Province',
                'text', 
                "for international provinces outside of US & Canada include the province name here",
                ''
        );
    }

    if ($features['maestro_solo_switch']){
        add_member_field(
                'cc_issuenum',
                'Card Issue #',
                'text', 
                "is required for Maestro/Solo/Switch credit cards only",
                ''
        );
        add_member_field(
                'cc_startdate',
                'Card Start Date',
                'text', 
                "is required for Maestro/Solo/Switch credit cards only",
                ''
        );
    }

    if ($features['type_options'])
        add_member_field('cc_type',  'CC Type',
             'select', 'credit card type', '', array('options'=>$features['type_options']));

    /// setup hooks
    if (!$features['no_recurring']){
        setup_plugin_hook('get_member_links', $plugin . '_get_member_links');
        setup_plugin_hook('hourly', $plugin . '_rebill');
    }
}
setup_plugin_hook('daily', 'cc_core_check_expire_dates');

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 Add necessary config items to make aMember CP -> Setup -> PLUGIN
 working
* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
function cc_core_add_config_items($plugin, $notebook_page){
    global $config, $_config_fields;

    $pl = & instantiate_plugin('payment', $plugin);
    if (!is_object($pl)) return;
    $features = $pl->get_plugin_features();


    add_config_field("payment.$plugin.title", "Payment system title",
        'text', "to be displayed on signup.php and member.php pages",
        $notebook_page, 
        '','','',
        array('default' => $features['title']));
    add_config_field("payment.$plugin.description", "Payment system description",
        'text', "to be displayed on signup page",
        $notebook_page, 
        '','','',
        array('default' => $features['description']));
    
    add_config_field("payment.$plugin.reattempt", 'Reattempt on Failure',
        'text', 
    "Enter list of days to reattempt failed credit card charge, for example: 3,8<br />
     <br />
     The reattempting failed payments option allows you to reattempt failed<br />
     payments before cancelling the subscription. Scheduled payments may fail<br />    
     due to several reasons, including insufficient funds. Payments will be<br />
     reattempted 3 days after the failure date. If it fails again, we will try once<br />
     more 5 days later (it is for sample above: 3,8). Failure on this last attempt<br />
     leads to cancellation of the subscription.<br />
     <br />
     NOTE: this time user will have FREE access to your site. If it is not acceptable<br />
     for your site, please don't enable this feature",
        $notebook_page, 
        '','','',
        array('options' => array(
            '' => 'No',
            1  => 'Yes'
        )));


    add_config_field("ccfd.##7", '<a href="http://www.maxmind.com/app/ccfd_promo?promo=CGICEN591" target="_blank">MaxMind</a> Credit Card Fraud Detection',
        'header', '', $notebook_page);
        
    add_config_field("use_credit_card_fraud_detection", 'MaxMind Credit Card Fraud Detection',
        'checkbox', "Enable <a href='http://www.maxmind.com/app/ccfd_promo?promo=CGICEN591' target=\"_blank\">Credit Card Fraud Detection</a> service",
        $notebook_page,
        '', '', '',
        array(
        ));
    
    if ($config['use_credit_card_fraud_detection']){
        add_config_field("ccfd_license_key", 'MaxMind License Key',
            'text', "<a href='http://www.maxmind.com/app/ccfd_promo?promo=CGICEN591' target=\"_blank\">Obtain a Free or Premium license key</a>",
            $notebook_page,
            '', '', '',
            array(
            ));
        
        add_config_field("ccfd_requested_type", 'Requested Type',
            'select', "To be used if you have multiple plans in one account<br />and wish to select type of query you wish to make.<br />By default the service uses the highest level available",
            $notebook_page,
            '', '', '',
            array(
                'options' => array(
                    ""          => 'Default',
                    "free"      => 'free',
                    "city"      => 'city (standard paid service)',
                    "premium"   => 'premium (premium paid service)'
                )
            ));
        
        add_config_field("ccfd_risk_score", 'Risk Score',
            'text', "Overall <a href=\"http://www.maxmind.com/app/web_services_score2\" target=\"_blank\">Risk Score</a> (decimal from 0 to 10)<br />For orders that return a fraud score of 2.5 and above, it is recommended<br /> to hold for review, or require the validation with the Telephone Verification service",
            $notebook_page,
            'validate_risk_level', '', '',
            array('default' => '2.5'));
        
        add_config_field("use_telephone_verification", 'Telephone Verification',
            'checkbox', "Enable <a href=\"http://www.maxmind.com/app/telephone_overview\" target=\"_blank\">Telephone Verification</a> service",
            $notebook_page,
            '', '', '',
            array(
            ));
        
        if ($config['use_telephone_verification']){
            add_config_field("use_number_identification", 'Number Identification',
                'checkbox', "Enable <a href=\"http://www.maxmind.com/app/phone_id\" target=\"_blank\">Telephone Number Identification (TNI)</a> service",
                $notebook_page,
                '', '', '',
                array(
                ));
            
            if ($config['use_number_identification']){
                add_config_field("tni_phone_types", 'Allowed Phone Types',
                    'multi_select', "The TNI service is able to categorize customer inputted US and Canadian<br />phone numbers into <a href=\"http://www.maxmind.com/app/phone_id_codes\" target=\"_blank\">eight different phone types</a><br />such as fixed land line, mobile, VoIP, and invalid phone numbers",
                    $notebook_page,
                    '','','',
                    array(
                        'options' => array(
                            '0' => 'Undetermined (Medium Risk Level)',
                            '1' => 'Fixed Line (Low Risk Level)',
                            '2' => 'Mobile (Low-Medium Risk Level)',
                            '3' => 'PrePaid Mobile (Medium-High Risk Level)',
                            '4' => 'Toll-Free (High Risk Level)',
                            '5' => 'Non-Fixed VoIP (High Risk Level)',
                            '8' => 'Invalid Number (High Risk Level)',
                            '9' => 'Restricted Number (High Risk Level)'
                        ),
                        'default' => array(0, 1, 2),
                        'store_type' => 1
                    ));
            }
        }
        
        add_config_field("allow_country_not_matched", 'Allow payment if country not matched',
            'checkbox', "Whether country of IP address matches billing address country<br />(mismatch = higher risk)",
            $notebook_page,
            '', '', '',
            array(
            ));
        
        add_config_field("allow_high_risk_country", 'Allow payment if high risk countries',
            'checkbox', "Whether IP address or billing address country is in<br />Egypt, Ghana, Indonesia, Lebanon, Macedonia, Morocco, Nigeria,<br />Pakistan, Romania, Serbia and Montenegro, Ukraine, or Vietnam",
            $notebook_page,
            '', '', '',
            array(
            ));
        
        add_config_field("allow_anonymous_proxy", 'Allow payment if anonymous proxy',
            'checkbox', "Whether IP address is <a href=\"http://www.maxmind.com/app/proxy#anon\" target=\"_blank\">Anonymous Proxy</a><br />(anonymous proxy = very high risk)",
            $notebook_page,
            '', '', '',
            array(
            ));
        
        add_config_field("allow_free_mail", 'Allow payment if free e-mail',
            'checkbox', "Whether e-mail is from free e-mail provider<br />(free e-mail = higher risk)",
            $notebook_page,
            '', '', '',
            array(
            ));

        add_config_field('cc_input_bin', 'Bank Identification',
            'checkbox', "Enable bank identification fields on credit card info page",
            $notebook_page,
            '', '', '',
            array(
            ));
    }

}

function get_cc_info_hash($member, $action){
    return md5($member['pass'].$action.($member['member_id'] * 12));
}

function clean_cc_info($member){
    global $db;
    $cc_fields = array(
        '', '-expire', '-hidden',
        '_code', '_type',
        '_name_f', '_name_l', '_name', 
        '_phone', '_company', '_housenumber',
        '_street', '_city', '_state', '_zip','_country',
        '_issuenum', '_startdate', '_province');
    $m = $db->get_user($member['member_id']);
    foreach ($cc_fields as $f)
        $m['data']['cc'.$f] = '';
    $db->update_user($m['member_id'], $m);
}

function cc_core_do_payment($plugin, $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars){
    global $db, $config;
    $member = $db->get_user($member_id);
    $v = get_cc_info_hash($member, $action = "mfp");
    html_redirect("$config[root_surl]/plugins/payment/cc_core/cc.php?action=$action&payment_id=$payment_id&member_id=$member_id&v=$v",
        0, _PLUG_PAY_CC_CORE_REDIR, 
        _PLUG_PAY_CC_CORE_REDIRDESC);
}

function cc_core_get_payer_id($cc_info, $member){
    return "cc:" . md5($cc_info['cc_number']);
}

function cc_core_get_cancel_link($plugin, $payment_id){
    global $config, $db;
    $p = $db->get_payment($payment_id);

    // Do not show cancel link if subscription was rebilled already
    foreach($db->get_user_payments($p[member_id], 1) as $op){
        if($op[data][0][RENEWAL_ORIG] == "RENEWAL_ORIG: ".$payment_id) return;
    }
    
    
    $member = $db->get_user($p['member_id']);
    $action='cancel_recurring';
    $v = get_cc_info_hash($member, $action);
    if (!$p['data']['CANCELLED'])
        return "{$config[root_surl]}/plugins/payment/cc_core/cc.php?"
            ."action=$action&payment_id=$payment_id&"
            ."member_id={$p[member_id]}&v=$v";
}

function cc_core_get_member_links($plugin, $user){
    global $config;
    $action = "renew_cc";
    $v = get_cc_info_hash($user, $action);
    if ($user['data']['cc'])
        return array("$config[root_surl]/plugins/payment/cc_core/cc.php?action=$action&paysys_id=$plugin&member_id={$user[member_id]}&v=$v" => 'Update CC info');
}

function cc_core_check_rebill_times($times, $payment){
    global $db;
    if ($payment_id = $payment['data'][0]['RENEWAL_ORIG']) {
        $count = 1;
        do {
            $count++;
            $x = preg_split('/ /', $payment_id);
            $payment_id = $x[1];
            $payment = $db->get_payment($payment_id);
        } while ($payment_id = $payment['data'][0]['RENEWAL_ORIG']);
    } else {
        $payment_id = $payment['payment_id'];
        $count = 1; // made payments
    }
    return $count < $times;
}


function cc_core_rebill($plugin, $dat='', $running_from_cron = true, $repeat_declined=false){
    global $config, $db, $t;
    if (!$config['use_cron'] && $running_from_cron)
        $db->log_error("$plugin rebill can be run only with external cron");
    $amDb = & amDb();
    if ($dat == '') 
        $dat = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime($dat) + 3600 * 24);

    $pl = & instantiate_plugin('payment', $plugin);
    if (!method_exists($pl, 'cc_bill')) fatal_error("This plugin ({$plugin}) is not handled by cc_core!");

    // check if another rebilling process is active 
	// last status_tm / added_tm in rebill_log is < 5 minutes ago
	// to avoiding starting new process while a PHP rebill script runned
	// less than 10 minutes ago is still running
	if ($running_from_cron){
		if ($last_rebill_log_id = $amDb->selectCell("SELECT MAX(rebill_log_id) FROM ?_rebill_log")){
			$last_tm_diff = $amDb->selectCell("SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(IFNULL(status_tm, added_tm)) 
				FROM ?_rebill_log WHERE rebill_log_id=?", $last_rebill_log_id);
			if ($last_tm_diff < 5 * 60){
				$db->log_error("[Notice] cc_core_rebill($plugin, $dat) skipped because previous rebilling process still working ($last_tm_diff seconds ago)");
				return;
			}
		}			
        print ".\n"; // to avoid Apache's timeout
	}
    
    $payments = $db->get_expired_payments($dat, $dat, $plugin);
    
    $renewed = array();
    $log = "$plugin Rebill\n";

    foreach ($payments as $p){ 
        if ($p['data']['CANCELLED']) 
            continue;
        $member_id = $p['member_id'];
        $member = $db->get_user($member_id);
        $product_id = $p['product_id'];
        if ($renewed[$member_id][$product_id]++) continue;
        $product = & get_product($product_id);
        if (!$product->config['is_recurring']) continue;
        if ($product->config['rebill_times'] &&
            !cc_core_check_rebill_times($product->config['rebill_times'], $p))
            continue;
		// check if we've already tried to rebill the customer today
		$check = $repeat_declined ? "SUM(status = 0)" : "MAX(status IS NOT NULL)" ;
		if ($amDb->selectCell("SELECT $check FROM ?_rebill_log
			WHERE payment_id = ? AND payment_date = ? ", 
				$p['payment_id'], $dat)) // retry on payment processor failure? todo, real tests needed
			continue;
			
        $vars = array( 'RENEWAL_ORIG' => "RENEWAL_ORIG: $p[payment_id]" );

        $pc = & new PriceCalculator();
        $pc->setTax(get_member_tax($member_id));
        $coupon_code = $p['data'][0]['COUPON_CODE'];
        if ($config['use_coupons'] && $coupon_code != ''){
            $coupon = $db->coupon_get($coupon_code, null, 1);
            if ( $coupon['coupon_id'] && $coupon['is_recurring'] ){
                $pc->setCouponDiscount($coupon['discount'], split(',',trim($coupon['product_id'])));
                $vars['COUPON_CODE'] = $coupon_code;
            }
        }
        $pc->addProduct($product_id);
        $terms = & $pc->calculate();

        $additional_values = array();
        $additional_values['COUPON_DISCOUNT'] = $terms->discount;
        $additional_values['TAX_AMOUNT'] = $terms->tax;
        
        $payment_id = $db->add_waiting_payment($member_id, $product_id, 
             $plugin, $terms->total, 
             $dat, $product->get_expire($dat), 
             $vars, $additional_values);
             
		$rebill_log_id = $amDb->query("INSERT INTO ?_rebill_log 
		(payment_id, added_tm, payment_date, amount, rebill_payment_id)
		VALUES
		(?d, ?, ?, ?f, ?d)", 
		$p['payment_id'], date('Y-m-d H:i:s'), $dat, $terms->total, $payment_id);             
		
        $payment = $db->get_payment($payment_id);
        $cc_info = $member['data'];
        $cc_info['cc_number'] = amember_decrypt($cc_info['cc-hidden']);

        $x = list($res, $err_msg, $receipt_id, $log) = $pl->cc_bill($cc_info, 
            $member, $payment['amount'],
            $product->config[$payment['paysys_id'] . '_currency'], 
            $product->config['title'], CC_CHARGE_TYPE_RECURRING, 
            $payment['payment_id'], $payment);
        foreach ($log as $v)
            $payment['data'][] = $v;
        $db->update_payment($payment['payment_id'], $payment);
        $amDb->query("UPDATE ?_rebill_log 
        	SET status = ?, status_tm = ?, status_msg = ? 
        	WHERE rebill_payment_id = ?d", 
        	$res, date('Y-m-d H:i:s'), $err_msg, 
        	$payment_id);
        
        switch ($res){
        case CC_RESULT_SUCCESS:
            $err = $db->finish_waiting_payment(
                $payment['payment_id'], $payment['paysys_id'], 
                    $receipt_id, $payment['amount'], '', cc_core_get_payer_id($vars, $member));
            if ($err) {
                $db->log_error($err . ": payment_id = $payment[payment_id] (rebilling)");
            }
            if ($config['cc_rebill_success'])
                mail_rebill_success_member($member, $payment_id, $product);
        break;
        case CC_RESULT_INTERNAL_ERROR:
        case CC_RESULT_DECLINE_TEMP:
            if ($pl->config['reattempt'] != '')
                $new_expire = cc_core_prorate_subscription($p['payment_id'], $pl->config['reattempt'], $dat);
            if ($config['cc_rebill_failed']){
                mail_rebill_failed_member($member, $payment_id, $product, "$err_msg", $new_expire);
            }
            if ($config['cc_rebill_failed_admin']){
                mail_rebill_failed_admin($member, $payment_id, $product, "$err_msg", $new_expire);
            }
        break;
        case CC_RESULT_DECLINE_PERM:
            if ($pl->config['reattempt'] != '')
                $new_expire = cc_core_prorate_subscription($p['payment_id'], $pl->config['reattempt'], $dat);
            if ($config['cc_rebill_failed']) {
                mail_rebill_failed_member($member, $payment_id, $product, "$err_msg", $new_expire);
            }
            if ($config['cc_rebill_failed_admin']){
                mail_rebill_failed_admin($member, $payment_id, $product, "$err_msg", $new_expire);
            }
//            clean_cc_info($member);
        break;
        case CC_RESULT_IGNORE:
        break;
        default:
            $db->log_error("Unknown return from plugin_bill: $res");
        };
    }
}

function cc_core_check_expire_dates(){
    global $db, $config;
    
    $dat = date('Y-m-d', time() + 3600*24* 10 ); // 10 days later
    
    $plugins = array();
	foreach ((array)cc_core_get_plugins($only_recurring=true) as $plugin){    	
    	$payments = $db->get_expired_payments($dat, $dat, $plugin);
    	foreach ($payments as $p){
    		if ($p['data']['CANCELLED'])
    		continue;
    		$member_id = $p['member_id'];
    		$member = $db->get_user($member_id);
    		$product_id = $p['product_id'];
    		if ($renewed[$member_id][$product_id]++) continue;
    		$product = & get_product($product_id);
    		if (!$product->config['is_recurring']) continue;
    		if ($product->config['rebill_times'] &&
    		!cc_core_check_rebill_times($product->config['rebill_times'], $p))
    		continue;
    		/// do check
    		$e = $member['data']['cc-expire'];
    		if ($e == '') continue;
    		$edat = date("Y-m-t", strtotime('20'.substr($e, 2, 2).'-'.substr($e, 0, 2).'-01'));
    		if ($edat < $dat){
    			$expires = substr($e, 0, 2) . '/20' . substr($e, 2, 2);
    			if ($config['card_expires'])
    			mail_card_expires_member($member, $p['payment_id'], $product, $expires);
    		}
    	}
    }
}


function cc_core_prorate_subscription($payment_id, $reattempt, $dat){
    global $db;
    ///
    $r = preg_split('/[,;-\s]+/', $reattempt);
    foreach ($r as $k=>$v) if ($v <= 0) unset($r[$k]);
    sort($r);
    if (!$r) return;
    ///
    $p = $db->get_payment($payment_id);
    if ($dat != $p['expire_date']) return;
    $orig_expire = $p['data']['orig_expire_date'];
    if ($orig_expire == '') 
        $orig_expire = $p['expire_date'];
    $ddiff = ceil((strtotime($dat) - strtotime($orig_expire)) / (3600*24));
    foreach ($r as $d){    
        $days_to_extend = $d;
        if ($d > $ddiff) break;
    }
    if (!$days_to_extend) return;
    $new_expire = date('Y-m-d',strtotime($orig_expire) + $days_to_extend * 24 * 3600);
    if ($new_expire <= $p['expire_date']) return; // don't set less expiration
    $p['expire_date'] = $new_expire;
    $p['data']['orig_expire_date'] = $orig_expire;
    if ($p['data']['prorated'] == '')
        $p['data']['prorated'] = $days_to_extend;
    else 
        $p['data']['prorated'] .= ", $days_to_extend";
    $db->update_payment($payment_id, $p);
    return $new_expire;    
}

function ask_cc_info($member, $payment, $vars=array(), $renew_cc_info=0, $errors=array()){
    global $t, $db, $config;
    // get a plugin config
    $plugin = & instantiate_plugin('payment', $payment['paysys_id']);
    if (!method_exists($plugin, 'cc_bill')) fatal_error("This plugin ({$payment['paysys_id']}) is not handled by cc_core!");
    $features = $plugin->get_plugin_features();
    ///

    /// 
    $c = $config;
    foreach (array('name', 'name_f', 'code', 'company', 'phone', 'housenumber', 'maestro_solo_switch', 'province_outside_of_us') as $f)
        $c['cc_' . $f] = intval($features[$f]);        
    if ($features['type_options'])
        $c['cc_type_options'] = $features['type_options'];
    $t->assign('config', $c);

    $cc_fields = array(
        'name_f', 'name_l', 'name', 
        'phone', 'company', 'housenumber',
        'street', 'city', 'state', 'zip','country', 'type');
    $cc_address = array(); // prefilled fields
    foreach ($cc_fields as $f){
        $v = $vars['cc_' . $f];
        if (!isset($vars['cc_'. $f])) {
            if ($renew_cc_info) 
                if (!$v) $v = $member['data']['cc_'.$f];
            if (!$v) $v = $member[$f];
            if (!$v && ($f == 'name'))
                $v = $member['name_f'] . ' ' . $member['name_l'];
            if (!$v && ($f == 'phone'))
                $v = $member['data']['phone'];
            if (!$v && ($f == 'company'))
                $v = $member['data']['company'];
        }
        $cc_address['cc_'.$f] = $v;
    }

    $add_fields = array('enc_info' => serialize($xx = array(
        'member_id' => $member['member_id'], 
        'payment_id' => $payment['payment_id'],
        'v' => $vars['v'],
        'action' => $vars['action'],
        'paysys_id' => $vars['paysys_id']
    )));            
    $t->assign('cc_address', $cc_address);
    $t->assign('add_fields', $add_fields);
    $t->assign('error', $errors);
    $t->assign('member', $member);
    $t->assign('payment', $payment);
    $t->assign('renew_cc', $renew_cc_info);
    if (!$renew_cc_info)
        $t->assign('display_receipt', true);

    if ($vars['cc_expire_Month'] && $vars['cc_expire_Year'])
        $time = $vars['cc_expire_Year']."-".$vars['cc_expire_Month']."-01";
    else
        $time = date("Y-m-d");
    $t->assign('time', $time);


    $t->display('cc/cc_info.html');
}

function validate_cc_info($member, $payment, &$vars){
    global $t, $db, $config;
    // get a plugin config
    $plugin = & instantiate_plugin('payment', $payment['paysys_id']);
    if (!method_exists($plugin, 'cc_bill')) fatal_error("This plugin ({$payment['paysys_id']}) is not handled by cc_core!");
    $features = $plugin->get_plugin_features();
    ///
    $errors = array();
    // check credit card
    $vars['cc_number'] = preg_replace('/\D+/', '', $vars['cc_number']);
    $cc = $vars['cc_number'];
    if (strlen($cc) == 0) {
        $errors[] = _PLUG_PAY_CC_CORE_ERROR1;
    } elseif (strlen($cc) < 12) {
        $errors[] = _PLUG_PAY_CC_CORE_ERROR2;
    } elseif (!$features['skip_cc_number_validation']) {
        // pre-validate credit card info    
        require "$config[root_dir]/plugins/payment/cc_core/ccvs.inc.php";
        $validator = new CreditCardValidationSolution;
        if (!$validator->validateCreditCard($cc, 'en', $features['cc_types_accepted']))
            $errors[] = _PLUG_PAY_CC_CORE_ERROR3. $validator->CCVSError;
    }
    if (($vars['cc_expire_Month'] < 1) or ($vars['cc_expire_Month']>12)){
        $errors[] = _PLUG_PAY_CC_CORE_ERROR4;
    }

    if ($vars['cc_expire_Year'] < date('Y')){
        $errors[] = _PLUG_PAY_CC_CORE_ERROR5;
    }

    if ($features['maestro_solo_switch']){
        if (($vars['cc_startdate_Month'] < 1) or ($vars['cc_startdate_Month']>12)){
            $errors[] = _PLUG_PAY_CC_CORE_ERROR19;
        }
    
        if ($vars['cc_startdate_Year'] > date('Y')){
            $errors[] = _PLUG_PAY_CC_CORE_ERROR20;
        }
    }

    if (!strlen($vars['cc_type']) && $features['type_options'])
        $errors[] = _PLUG_PAY_CC_CORE_ERROR6;
    if (!strlen($vars['cc_code']) && $features['code'] && !$vars['renew_cc'])
        $errors[] = _PLUG_PAY_CC_CORE_ERROR7;
    if (!strlen($vars['cc_name']) && $features['name'])
        $errors[] = _PLUG_PAY_CC_CORE_ERROR8;
    if (!strlen($vars['cc_name_f']) && $features['name_f'])
        $errors[] = _PLUG_PAY_CC_CORE_ERROR9;
    if (!strlen($vars['cc_name_l']) && $features['name_l'])
        $errors[] = _PLUG_PAY_CC_CORE_ERROR10;
    if (!strlen($vars['cc_company']) && ($features['company']>1))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR11;
    if (!strlen($vars['cc_phone']) && ($features['phone']==2))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR12;
    if (!strlen($vars['cc_housenumber']) && ($features['housenumber']))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR13;


    if (!strlen($vars['cc_street']))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR14;
    if (!strlen($vars['cc_city']))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR15;
    if (!strlen($vars['cc_state']))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR16;
    if (!strlen($vars['cc_zip']))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR17;
    if (!strlen($vars['cc_country']))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR18;

    if (method_exists($plugin,'validate_cc_form'))
        $errors = array_merge((array)$plugin->validate_cc_form($vars), (array)$errors);
        
    
    return $errors;
}

function save_cc_info($cc_info, $member, $paysys_id){
    global $t, $db, $config;
    // get a plugin config
    $plugin = & instantiate_plugin('payment', $paysys_id);
    if (!method_exists($plugin, 'cc_bill')) fatal_error("This plugin ({$paysys_id}) is not handled by cc_core!");

    if ( method_exists($plugin, 'save_cc_info') ) {
        $plugin->save_cc_info($cc_info, $member);
    } else {
        $features = $plugin->get_plugin_features();

        $member['data']['cc-hidden'] = amember_crypt(preg_replace('/\D+/', '', $cc_info['cc_number']));
        $member['data']['cc'] = get_visible_cc_number($cc_info['cc_number']);
        $member['data']['cc-expire'] = sprintf('%02d%02d', $cc_info['cc_expire_Month'], substr($cc_info['cc_expire_Year'], 2, 2));

        if ($features['maestro_solo_switch']){
            $member['data']['cc_startdate'] = sprintf('%02d%02d', $cc_info['cc_startdate_Month'], substr($cc_info['cc_startdate_Year'], 2, 2));
            $member['data']['cc_issuenum'] = $cc_info['cc_issuenum'];
        }

        if ($features['type_options'])
            $member['data']['cc_type'] = $cc_info['cc_type'];
        if ($features['code'] > 1)
            $member['data']['cc_code'] = $cc_info['cc_code'];
        if ($features['name'])
            $member['data']['cc_name'] = $cc_info['cc_name'];
        if ($features['name_f']) {
            $member['data']['cc_name_f'] = $cc_info['cc_name_f'];
            $member['data']['cc_name_l'] = $cc_info['cc_name_l'];
        }
        if ($features['company'])
            $member['data']['cc_company'] = $cc_info['cc_company'];
        if ($features['phone'])
            $member['data']['cc_phone'] = $cc_info['cc_phone'];
        if ($features['housenumber'])
            $member['data']['cc_housenumber'] = $cc_info['cc_housenumber'];

        $member['data']['cc_street'] = $cc_info['cc_street'];
        $member['data']['cc_city'] = $cc_info['cc_city'];
        $member['data']['cc_state'] = $cc_info['cc_state'];
    
        if ($features['province_outside_of_us']){
            $member['data']['cc_province'] = $cc_info['cc_province'];
        }
    
        $member['data']['cc_zip'] = $cc_info['cc_zip'];
        $member['data']['cc_country'] = $cc_info['cc_country'];

        $db->update_user($member['member_id'], $member);
    }
}

function cc_core_get_url($url, $post='', $add_referrer=''){
    return get_url($url, $post, $add_referrer);
}


function credit_card_fraud_detection($member, $payment, $vars, &$errors){
    global $config, $db;

    require_once "$config[root_dir]/includes/ccfd/CreditCardFraudDetection.php";
    
    // Create a new CreditCardFraudDetection object
    $ccfs = new CreditCardFraudDetection;
    
    // Set inputs and store them in a hash
    // See http://www.maxmind.com/app/ccv for more details on the input fields
    
    // Enter your license key here (non registered users limited to 20 lookups per day)
    $h["license_key"]       = $config['ccfd_license_key'];
    
    // Which level (free, city, premium) of CCFD to use
    $h["requested_type"]    = $config['ccfd_requested_type'];
    
    /*
    i
    Client IP Address (IP address of customer placing order)
    
    forwardedIP
    IP address of end user, as forwarded by transparent proxy. Transparent proxies set the HTTP headers X-Forwarded-For or Client-IP,
    which contain the IP address of the end user. These IP addresses can be typically be accessed through the environment
    variables HTTP_X_FORWARDED_FOR and HTTP_CLIENT_IP. Note that the forwarded IP should be passed to the forwardedIP input field
    instead of the i input field, because we check that the IP address passed to the i input field is a legitimate transparent proxy
    before using the value in the forwardedIP input field.
    */
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
    
    //$db->log_error ("MaxMind debug: HTTP_X_FORWARDED_FOR=".$_SERVER["HTTP_X_FORWARDED_FOR"].", HTTP_CLIENT_IP=".$_SERVER["HTTP_CLIENT_IP"].", REMOTE_ADDR=".$_SERVER["REMOTE_ADDR"]);
    
    // Required fields
    $h["i"]         = $client_ip;                // set the client ip address
    $h["city"]      = $vars['cc_city'];          // set the billing city
    $h["region"]    = $vars['cc_state'];         // set the billing state
    $h["postal"]    = $vars['cc_zip'];           // set the billing zip code
    $h["country"]   = $vars['cc_country'];       // set the billing country
    
    $country = db_getCountryByCode($vars['cc_country']);
    if (!$country) $country = $vars['cc_country'];
    
    $domain = $member['email'];
    $pos = strpos($domain, '@');
    if ($pos !== false){
        $domain = substr($domain, $pos + 1);
    }
    
    $cc_num = preg_replace('/\D+/', '', $vars['cc_number']);
    
    // Recommended fields
    $h["domain"]        = $domain;              // Email domain
    $h["bin"]           = substr($cc_num, 0, 6);// bank identification number
    
    //$h["forwardedIP"]   = $proxy_ip;            // X-Forwarded-For or Client-IP HTTP Header
    if ($forwarded_ip)
        $h["forwardedIP"]   = $forwarded_ip;            // X-Forwarded-For or Client-IP HTTP Header
    
    $h["custPhone"]     = $vars['cc_phone'];    // Area-code and local prefix of customer phone number

    // Optional fields
    $h["binName"]       = $vars['cc_bin_name']; // bank name
    $h["binPhone"]      = $vars['cc_bin_phone'];// bank customer service phone number on back of credit card
    
    $h["emailMD5"] = md5(strtolower($member['email'])); // CreditCardFraudDetection.php will take MD5 hash of e-mail address passed to emailMD5
                                                        //if it detects '@' in the string

    // added 04/10/2006
    /* MaxMind:
    * We have added the following two new input fields: "usernameMD5" and "passwordMD5".
    * These fields can be used by sites that require their customers to login before making a purchase.
    * Once the customer makes a purchase, the two new inputs would be passed along with the other input fields.
    * As with e-mails, carders will often use the same login and password to sign up at different online sites to reduce overhead
    * in managing their different accounts. These two new fields will affect the risk score and will offer additional reference points
    * for predictive analysis. Please note that this information will be encoded to ensure user privacy, but still allow us to compare
    * a unique identifier to help prevent fraud. These fields are optional inputs, and while we recommend using these valuable tools against fraud,
    * we also recognize the importance of keeping your customers' information secure. As such, it is possible to enter both, either,
    * or neither of these fields.
    */
    $h["usernameMD5"] = md5(strtolower($member['login'])); // MD5 hash in hexadecimal form of lowercase version of your customer's user name. Used by highRiskUsername output to check against database of high risk user names.
    
    //$h["shipAddr"]      = $vars['cc_housenumber']." ".$vars['cc_street']; // Shipping Address
    $h["shipAddr"]      = $vars['cc_street'];                               // Shipping Address
    $h["shipCity"]      = $vars['cc_city'];                                 // the City to Ship to
    $h["shipRegion"]    = $vars['cc_state'];                                // the Region to Ship to
    $h["shipPostal"]    = $vars['cc_zip'];                                  // the Postal Code to Ship to
    $h["shipCountry"]   = $vars['cc_country'];                              // the country to Ship to
    
    $h["txnID"]     = $payment['payment_id'];   // Transaction ID
    $h["sessionID"] = md5(session_id());        // Session ID
    
    // If you want to disable Secure HTTPS or don't have Curl and OpenSSL installed
    // uncomment the next line
    // $ccfs->isSecure = 0;
    
    // set the timeout to be five seconds
    $ccfs->timeout = 5;
    
    // uncomment to turn on debugging
    //$ccfs->debug = 1;
    
    // how many seconds to cache the ip addresses
    // $ccfs->wsIpaddrRefreshTimeout = 3600*5;
    
    // file to store the ip address for www.maxmind.com and www2.maxmind.com
    // $ccfs->wsIpaddrCacheFile = "/tmp/maxmind.ws.cache";
    
    // if useDNS is 1 then use DNS, otherwise use ip addresses directly
    $ccfs->useDNS = 1;
    
    $ccfs->isSecure = 0;
    
    // next we set up the input hash
    $ccfs->input($h);
    
    // then we query the server
    $ccfs->query();
    
    // then we get the result from the server
    $h = $ccfs->output();
    
    $was_errors = false;
    $payment_records_edit_log = array();
    
    $risk_score = $h['riskScore'];
    
    if ($h['carderEmail'] == 'Yes'){
        $risk_score = 99;
        $db->log_error ( _TPL_CC_ERROR_CARDEREMAIL );
        $payment_records_edit_log[] = _TPL_CC_ERROR_CARDEREMAIL;
        $was_errors = true;
    }
    
    if ($h['countryMatch'] == 'No' && !$config['allow_country_not_matched']){
        $risk_score = 99;
        $db->log_error ( _TPL_CC_ERROR_COUNTRYNOTMATCH . " (login=".$member['login'].", ip=".$client_ip.", country=".$country.")" );
        $payment_records_edit_log[] = _TPL_CC_ERROR_COUNTRYNOTMATCH;
        $was_errors = true;
    }

    if ($h['highRiskCountry'] == 'Yes' && !$config['allow_high_risk_country']){
        $risk_score = 99;
        $db->log_error ( _TPL_CC_ERROR_HIGHRISKCOUNTRY . " (login=".$member['login'].", country=".$country.")" );
        $payment_records_edit_log[] = _TPL_CC_ERROR_HIGHRISKCOUNTRY;
        $was_errors = true;
    }

    if ($h['anonymousProxy'] == 'Yes' && !$config['allow_anonymous_proxy']){
        $risk_score = 99;
        $db->log_error ( _TPL_CC_ERROR_ANONYMOUSPROXY . " (login=".$member['login'].", HTTP_X_FORWARDED_FOR=".$_SERVER["HTTP_X_FORWARDED_FOR"].", HTTP_CLIENT_IP=".$_SERVER["HTTP_CLIENT_IP"].", REMOTE_ADDR=".$_SERVER["REMOTE_ADDR"].")" );
        $payment_records_edit_log[] = _TPL_CC_ERROR_ANONYMOUSPROXY;
        $was_errors = true;
    }

    if ($h['freeMail'] == 'Yes' && !$config['allow_free_mail']){
        $risk_score = 99;
        $db->log_error ( _TPL_CC_ERROR_FREEMAIL . " (login=".$member['login'].", email=".$member['email'].")" );
        $payment_records_edit_log[] = _TPL_CC_ERROR_FREEMAIL;
        $was_errors = true;
    }

    if ($config['cc_input_bin']){
        if ($h['binMatch'] != 'Yes'){
            $risk_score = 99;
            $db->log_error ( _TPL_CC_ERROR_BIN );
            $payment_records_edit_log[] = _TPL_CC_ERROR_BIN;
            $was_errors = true;
        }
        
        if ($h['binNameMatch'] != 'Yes'){
            $risk_score = 99;
            $db->log_error ( _TPL_CC_ERROR_BIN_NAME );
            $payment_records_edit_log[] = _TPL_CC_ERROR_BIN_NAME;
            $was_errors = true;
        }
        
        if ($h['binPhoneMatch'] != 'Yes'){
            $risk_score = 99;
            $db->log_error ( _TPL_CC_ERROR_BIN_PHONE );
            $payment_records_edit_log[] = _TPL_CC_ERROR_BIN_PHONE;
            $was_errors = true;
        }
    }
    
    if ($h['queriesRemaining'] > 0 && $h['queriesRemaining'] < 10) $db->log_error ("MaxMind queriesRemaining: ".$h['queriesRemaining']);
    
    $ccfd_warnings = array(
        'IP_NOT_FOUND',
        'COUNTRY_NOT_FOUND',
        'CITY_NOT_FOUND',
        'CITY_REQUIRED',
        'POSTAL_CODE_REQUIRED',
        'POSTAL_CODE_NOT_FOUND'
        );
    $ccfd_fatal_errors = array(
        'INVALID_LICENSE_KEY',
        'MAX_REQUESTS_PER_LICENSE',
        'IP_REQUIRED',
        'LICENSE_REQUIRED',
        'COUNTRY_REQUIRED',
        'MAX_REQUESTS_REACHED'
        );
    if ($h['err']) {
        
        if (in_array($h['err'], $ccfd_warnings)){
            $db->log_error ("MaxMind warning: ".$h['err']." maxmindID: ".$h['maxmindID']);
        }
        if (in_array($h['err'], $ccfd_fatal_errors)){
            $db->log_error ("MaxMind error: ".$h['err']." maxmindID: ".$h['maxmindID']);
            $was_errors = true;
        }
    }

    if ($was_errors){
        $errors[] = _TPL_CC_DECLINED;

        if ($payment_records_edit_log){
            $payment = $db->get_payment($payment['payment_id']);
            $payment['data']['ccfd_errors'] = $payment_records_edit_log;
            $db->update_payment($payment['payment_id'], $payment);
        }
        
    }
    
    //if ($h['err']) $errors[] = $h['err'];
    
    return $risk_score;
    
}

function telephone_verification($member, $payment, $vars, &$errors){
    global $config, $t, $db;
    
    require_once "$config[root_dir]/includes/ccfd/TelephoneVerification.php";
    
    $phone_number = $vars['cc_phone'];
    $phone_number = preg_replace ("/[^\d]+/i", "", $phone_number); // only digits allowed
    
    $was_error = false;
    $error = "";
    $payment_records_edit_log = array();
    if ($config['use_number_identification'] && !number_identification($phone_number, $error)){
        if ($error)
            $error = _TPL_CC_ERROR_TNI . " (" . $error . ")"; 
        else
            $error = _TPL_CC_ERROR_TNI;
        $was_error = true;
        $db->log_error ( $error );
        $payment_records_edit_log[] = $error;
    }

    $tv = new TelephoneVerification;
    $h = array();
    
    // Set inputs and store them in a hash
    // See http://www.maxmind.com/app/telephone_form for more details on the input fields
    
    // Enter your license key here
    $h["l"] = $config['ccfd_license_key'];
    
    // Enter your telephone number here
    $h["phone"] = $phone_number;
    
    $verify_code = '';
    $acceptedChars = '09271324537458567879809';
    $max = strlen($acceptedChars) - 1;
    for($i=0; $i < 4; $i++) $verify_code .= $acceptedChars{mt_rand(0, $max)};
    $verify_code = $verify_code * mt_rand(2, 9);
    $verify_code = substr($verify_code, 0, 4);
    $h["verify_code"] = $verify_code;
    $_SESSION['ccfd_verify_code'] = $verify_code;
    
    // If you want to disable Secure HTTPS or don't have Curl and OpenSSL installed
    // uncomment the next line
    // $tv->isSecure = 0;
    
    //set the time out to be 30 seconds
    $tv->timeout = 30;
    
    //uncomment to turn on debugging
    //$tv->debug = 1;
    
    //how many seconds the cache the ip addresses
    $tv->wsIpaddrRefreshTimeout = 3600*5;
    
    //where to store the ip address
    $tv->wsIpaddrCacheFile = "/tmp/maxmind.ws.cache";
    
    // if useDNS is 1 then use DNS, otherwise use ip addresses directly
    $tv->useDNS = 1;
    
    // next we set up the input hash to be passed to the server
    $tv->input($h);
    
    // then we query the server
    $tv->query();
    
    // then we get the result from the server
    $h = $tv->output();

    if ($h['err']){
        $error = _TPL_CC_ERROR_TV . " (" . $h['err'] . ")";
        $was_error = true;
        $db->log_error ( $error );
        $payment_records_edit_log[] = $error;
    }
    
    if ($payment_records_edit_log){
        $payment = $db->get_payment($payment['payment_id']);
        $payment['data']['tv_errors'] = $payment_records_edit_log;
        $db->update_payment($payment['payment_id'], $payment);
    }

    if ($was_error){
        
        $errors[] = _TPL_CC_ERROR_TV;
        
    } else {

        // display confirmation form and exit
        // save $vars to session
        $_SESSION['stored_vars'] = $vars;
        $t->assign('member_id', $member['member_id']);
        $t->assign('payment_id', $payment['payment_id']);
        $t->assign('error', $errors);
        $t->assign('phone_number', $member['cc_phone']);
        $t->display('cc/cc_telephone_verification.html');
        exit;
        
    }
}

function number_identification($phone_number='', &$error){
    // returns TRUE if phone are valid
    global $config;
    
    $phone_number = preg_replace ("/[^\d]+/i", "", $phone_number); // only digits allowed
    
    require_once "$config[root_dir]/includes/ccfd/NumberIdentification.php";
    
    $ni = new NumberIdentification;
    $h = array();
    
    // Set inputs and store them in a hash
    // See http://www.maxmind.com/app/telephone_form for more details on the input fields
    
    // Enter your license key here
    $h["l"] = $config['ccfd_license_key'];
    
    // Enter your telephone number here
    $h["phone"] = $phone_number;

    // If you want to disable Secure HTTPS or don't have Curl and OpenSSL installed
    // uncomment the next line
    // $ni->isSecure = 0;
    
    //set the time out to be 30 seconds
    $ni->timeout = 30;
    
    //uncomment to turn on debugging
    //$ni->debug = 1;
    
    //how many seconds the cache the ip addresses
    $ni->wsIpaddrRefreshTimeout = 3600*5;
    
    //where to store the ip address
    $ni->wsIpaddrCacheFile = "/tmp/maxmind.ws.cache";
    
    // if useDNS is 1 then use DNS, otherwise use ip addresses directly
    $ni->useDNS = 1;
    
    // next we set up the input hash to be passed to the server
    $ni->input($h);
    
    // then we query the server
    $ni->query();
    
    // then we get the result from the server
    $h = $ni->output();
    
    if ($h['err']){
        $error = $h['err'];
    }

    // check return values
    if (in_array($h['phoneType'], $config['tni_phone_types']))
        return true;
    else
        return false;
}

function cc_core_get_plugins($only_recurring = false){
    global $cc_core;
    foreach ($cc_core as $plugin => $pl){
    	if ($only_recurring){
	    	$features = $pl->get_plugin_features();
    		if ($features['no_recurring']) continue;
    	}    		
    	$res[] = $plugin;
    }	
    return $res;
}

