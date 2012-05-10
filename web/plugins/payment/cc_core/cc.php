<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: CC CORE payment plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4893 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

require_once "../../../config.inc.php";

function get_charge_type($payment){
    global $db;
    $product_ids = (array)$payment['data'][0]['BASKET_PRODUCTS'];
    $product_ids[] = $payment['product_id'];
    $product_ids = array_unique($product_ids);
    $recurring = $price_sum = 0;
    foreach ($product_ids as $product_id){
        $pr = $db->get_product($product_id);
        $recurring += (int)$pr['is_recurring'];
        $price_sum += $pr['price'];
    }
    if ($payment['amount'] <= 0.0)
        return CC_CHARGE_TYPE_TEST;
    elseif ($recurring) 
        return CC_CHARGE_TYPE_START_RECURRING;
    else
        return CC_CHARGE_TYPE_REGULAR;
        
}

function get_visible_cc_number($cc){
    global $plugins;
    $cc = preg_replace('/\D+/', '', $cc);
    if (in_array('manual_cc', $plugins['payment'])) {
        return $cc; // don't hide code if "manual_cc" enabled
    } else {
        return '**** **** **** '.substr($cc, -4);
    }
}

function user_has_recurring_subscriptions($member){
    global $config, $db;
    $core_cc_plugins = cc_core_get_plugins($only_recurring=true);
    foreach ($db->get_user_payments($member['member_id'], 1) as $p){
        if ($p['expire_date'] < date('Y-m-d')) continue;
        if ($p['data']['CANCELLED']) continue;
        if (!in_array($p['paysys_id'], $core_cc_plugins)) continue;
        $pr = $db->get_product($p['product_id']);
        if (!$pr['is_recurring']) continue;
        return 1;
    }
    return 0;
}

///////////////// M A I N //////////////////////////////////////////////
$t = & new_smarty();
$vars = get_input_vars();

if ($vars['enc_info'])
    foreach (unserialize($vars['enc_info']) as $k=>$v)
        $vars[$k] = $v;

settype($vars['member_id'], 'integer');
if (!$vars['member_id']) 
    fatal_error(_PLUG_PAY_CC_CORE_FERROR2, 0);

$phone_verified = false;
if ($vars['action'] == 'verify_code'){
    // check telephone verification code

    if ($vars['cc_verify_code'] != $_SESSION['ccfd_verify_code']){
        
        $errors[] = _TPL_WRONG_VERIFY_CODE;
        
        $member = $db->get_user($vars['member_id']);
        $payment = $db->get_payment($vars['payment_id']);
        
        $t->assign('member_id', $member['member_id']);
        $t->assign('payment_id', $payment['payment_id']);
        $t->assign('error', $errors);
        $t->assign('phone_number', $member['cc_phone']);
        $t->display('cc/cc_telephone_verification.html');
        exit;
        
    } else {
        
        $vars = $_SESSION['stored_vars'];
        $_SESSION['stored_vars'] = '';
        $_SESSION['ccfd_verify_code'] = '';
        $phone_verified = true;
        
    }
}

$member = $db->get_user($vars['member_id']);

if (get_cc_info_hash($member, $vars['action']) != $vars['v'])
    fatal_error(_PLUG_PAY_CC_CORE_FERROR1);


/// member is verified, lets continue
switch ($vars['action']){
    case 'mfp': // make first payment
        settype($vars['payment_id'], 'integer');
        if (!$vars['payment_id'])
            fatal_error(_PLUG_PAY_CC_CORE_FERROR3);        
        $payment = $db->get_payment($vars['payment_id']);
        if ($payment['member_id'] != $vars['member_id'])
            fatal_error(_PLUG_PAY_CC_CORE_FERROR4);
        if ($payment['completed']) 
            fatal_error(sprintf(_PLUG_PAY_CC_CORE_FERROR5, "<a href='$config[root_url]/member.php'>","</a>"), 0);
        // 
        if ($vars['do_cc']) {
            $errors = validate_cc_info($member, $payment, $vars);
            
            // MaxMind Credit Card Fraud Detection
            if ($config['use_credit_card_fraud_detection'] && !$errors && !$phone_verified){
                $risk_score = credit_card_fraud_detection($member, $payment, $vars, $errors); // returns 0-10 or 99 if error
                $payment = $db->get_payment($payment['payment_id']); // could be updated
                
                if ($config['use_telephone_verification'] && !$phone_verified &&
                    $risk_score >= $config['ccfd_risk_score'] && $risk_score <= 10){
                    telephone_verification($member, $payment, $vars, $errors);
                    //exit;
                }
                if ($risk_score > $config['ccfd_risk_score']){
                    $errors[] = _TPL_CC_DECLINED;
                    $db->log_error("MaxMind ERROR: Risk Score returned $risk_score  > maximum allowed " . $config['ccfd_risk_score']);
                }
                $errors = array_unique ($errors);
                
            }
            // end of MaxMind Credit Card Fraud Detection
            
            if (!$errors) {
                // determine kind of processing
                // returns CC_CHARGE_TYPE_REGULAR OR CC_CHARGE_TYPE_START_RECURRING
                $charge_type = get_charge_type($payment);
                $pr = get_product($payment['product_id']);
                // product has a trial, check it
                // check if member can be qualified for trial
                if ($pr->config['trial_group']){ 
                    $product_ids_used = array();
                    foreach ($db->get_user_payments($payment['member_id'], 1) as $p)
                        $product_ids_used[$p['product_id']]++;
                    // same trial products
                    $product_ids_denied = array();
                    foreach ($db->get_products_list() as $p)
                        if ($p['trial_group'] == $pr->config['trial_group'])
                            $product_ids_denied[] = $p['product_id'];
                    // now check
                    if (array_intersect($product_ids_denied,
                            array_keys($product_ids_used))){
                        ask_cc_info($member, $payment, $vars, 0, 
                        array(sprintf(_PLUG_PAY_CC_CORE_FERROR6, $config[root_url])));
                        break;
                    }
                    // now check for the same credit card
                    $payer_id = cc_core_get_payer_id($vars, $member);
                    $s = join(',', $product_ids_denied);
                    $q = $db->query($s = "SELECT COUNT(*)
                    FROM {$db->config[prefix]}payments
                    WHERE payer_id='$payer_id' AND product_id IN ($s)
                      AND completed > 0
                    ");
                    list($count_denied) = mysql_fetch_row($q);
                    if ($count_denied){
                        ask_cc_info($member, $payment, $vars, 0, 
                        array(sprintf(_PLUG_PAY_CC_CORE_FERROR6, $config[root_url])));
                        break;
                    }
                }
                /// run payment
    			$plugin = & instantiate_plugin('payment', $payment['paysys_id']);
    			if (!method_exists($plugin, 'cc_bill')) fatal_error("This plugin ({$payment['paysys_id']}) is not handled by cc_core!");
                $title = (count((array)$payment['data'][0]['BASKET_PRODUCTS']) > 1) ? 
                    $config['multi_title'] : $pr->config['title'];
                // check that payment is not yet completed
                $p = $db->get_payment($payment['payment_id']);
                if ($p['completed']) 
                    fatal_error(sprintf(_PLUG_PAY_CC_CORE_FERROR5, "<a href='$config[root_url]/member.php'>","</a>"), 0);
                $vars1 = $vars;

                $vars1['cc-expire'] = sprintf('%02d%02d', $vars['cc_expire_Month'], substr($vars['cc_expire_Year'], 2, 2));
                $vars1['cc_startdate'] = sprintf('%02d%02d', $vars['cc_startdate_Month'], substr($vars['cc_startdate_Year'], 2, 2));

                $vars1['cc'] = get_visible_cc_number($vars['cc_number']);
                $product = $db->get_product($payment['product_id']);
                $x = list($res, $err_msg, $receipt_id, $log) = $plugin->cc_bill($vars1, $member, $payment['amount'],
                    $product[$payment['paysys_id'] . '_currency'], 
                    $title, $charge_type, $payment['payment_id'], $payment);
                $payment = $db->get_payment($payment['payment_id']);
                foreach ($log as $v)
                    $payment['data'][] = $v;
                $db->update_payment($payment['payment_id'], $payment);
                if ($res == CC_RESULT_SUCCESS){
                    $err = $db->finish_waiting_payment(
                        $payment['payment_id'], $payment['paysys_id'], 
                            $receipt_id, $payment['amount'], '', cc_core_get_payer_id($vars, $member));
                    if ($err) {
                        fatal_error($err . ": payment_id = $payment[payment_id]");
                    }
                    
                    $member = $db->get_user($member['member_id']); // get possible changes does in site.inc.php
                    
                    /// save cc info to db
                    if ($charge_type != CC_CHARGE_TYPE_REGULAR){
                        $features = $plugin->get_plugin_features();
                        if (!$features['no_recurring'])
                            save_cc_info($vars, $member, $payment['paysys_id']);
                    }
                    /// display thanks page
                    $product = $db->get_product($payment['product_id']);
                    $member  = $db->get_user($payment['member_id']);
                    $t->assign('payment', $db->get_payment($payment['payment_id']));
                    $t->assign('product', $product);
                    $t->assign('user', $member);
                    if (!($prices = $payment['data'][0]['BASKET_PRICES'])){
                        $prices = array($payment['product_id'] => $payment['amount']);
                    }
                    $pr = array();
                    $subtotal = 0;
                    foreach ($prices as $product_id => $price){
                        $v  = $db->get_product($product_id);
//                        $v['price'] = $price;
                        $subtotal += $v['price'];
                        $pr[$product_id] = $v;
                    }
                    $t->assign('subtotal', $subtotal);
                    $t->assign('total', array_sum($prices));
                    $t->assign('products', $pr);
                    $t->display("thanks.html");
                } else {
                    ask_cc_info($member, $payment, $vars, 0, array("Payment failed: ".$err_msg));
                }
            } else
                ask_cc_info($member, $payment, $vars, 0, $errors);
        } else
            ask_cc_info($member, $payment, $vars, 0); 
    break;


    case 'cancel_recurring': // cancel recurring payment
        settype($vars['payment_id'], 'integer');
        if (!$vars['payment_id'])
            fatal_error("Payment_id empty");        
        $payment = $db->get_payment($vars['payment_id']);
        if ($payment['member_id'] != $vars['member_id'])
            fatal_error(_PLUG_PAY_CC_CORE_FERROR4);
        // 
        $p = $db->get_payment($vars['payment_id']);
        $p['data']['CANCELLED'] = 1;
        $p['data']['CANCELLED_AT'] = strftime($config['time_format'], time());
        $db->update_payment($vars['payment_id'], $p);

        if (!user_has_recurring_subscriptions($member) && !$config['enable_resubscribe'])
            clean_cc_info($member);

        // email to member if configured
        if ($config['mail_cancel_admin']){
            $t->assign('user', $member);            
            $t->assign('payment', $p);
            $t->assign('product', $db->get_product($p['product_id']));
            $et = & new aMemberEmailTemplate();
            $et->name = "mail_cancel_admin";
            mail_template_admin($t, $et);
        }
        if ($config['mail_cancel_member']){
            $t->assign('user', $member);            
            $t->assign('payment', $p);
            $t->assign('product', $db->get_product($p['product_id']));
            $et = & new aMemberEmailTemplate();
            $et->name = "mail_cancel_member";
            mail_template_user($t, $et, $member);
        }

        $t->assign('title', _PLUG_PAY_CC_CORE_SBSCNCL);
        $t->assign('msg', _PLUG_PAY_CC_CORE_SBSCNCL2);
        $t->display("msg_close.html");
    
    break;

    case 'renew_cc': // make first payment
        if ($vars['do_cc']) {
            $errors = validate_cc_info($member, array('paysys_id' => $vars['paysys_id']), $vars);
            if (!$errors) {
                save_cc_info($vars, $member, $vars['paysys_id']);
                html_redirect("$config[root_surl]/member.php?cc_renew_done=1", 
                0, _PLUG_PAY_CC_CORE_CCINFOCHNG, _PLUG_PAY_CC_CORE_REDIR);

            } else 
                ask_cc_info($member, array('paysys_id' => $vars['paysys_id']), $vars, 1, $errors);    
        } else 
            ask_cc_info($member, array('paysys_id' => $vars['paysys_id']), $vars, 1, $errors);    
    break;

    default:
        fatal_error(_PLUG_PAY_CC_CORE_FERROR7);
}

?>
