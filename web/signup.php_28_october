<?php 

/*
*  User's signup page
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Signup Page
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3914 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/
include('./config.inc.php');


$signup_scope_allowed = array('', 'signup');

###############################################################################
# CHECK_PAYMENT_FORM
#
# set $GLOBAL[error] if needed
# return 1/0 (valid/not-valid)
#
function check_payment_form(){
    global $error;
    global $vars;
    global $db;
    global $config;

    //
    if (is_array($vars['product_id'])) {
        if (count($vars['product_id'])<=0) 
            $error[] = _SIGNUP_PLEASE_SELECT_MEMT;
    } else {
        if (!intval($vars['product_id'])) 
            $error[] = _SIGNUP_PLEASE_SELECT_MEMT;
    }

    if (!strlen($vars['paysys_id'])){   
        $error[] = _SIGNUP_PLEASE_SELECT_PAYSYS;
    }
    if (!strlen($vars['name_f'])){
        $error[] = _SIGNUP_PLEASE_ENTER_FNAME;
    }
    if (preg_match('/[<>"]/', $vars['name_f'])){
        $error[] = _SIGNUP_PLEASE_ENTER_FNAME;
    }
    if (!strlen($vars['name_l'])){
        $error[] = _SIGNUP_PLEASE_ENTER_LNAME;
    }
    if (preg_match('/[<>"]/', $vars['name_l'])){
        $error[] = _SIGNUP_PLEASE_ENTER_LNAME;
    }
    $preg = getLoginRegex();
    if (!preg_match($preg, $vars['login'])){
        $error[] = $config['login_disallow_spaces'] ?
            _SIGNUP_INVALID_USERNAME_W_SPACES : _SIGNUP_INVALID_USERNAME;
    } elseif (strlen($vars['login']) < $config['login_min_length']){
        $error[] = sprintf(_SIGNUP_INVALID_USERNAME_2,$config['login_min_length']);
    } elseif (!$member_id=$db->check_uniq_login($vars['login'], $vars['email'], 
        $vars['pass0'], 1)){
        $error[] = sprintf(_SIGNUP_INVALID_USERNAME_3,$vars[login]);
    }
    if (!check_email($vars['email'])){
        $error[] = _SIGNUP_PLEASE_ENTER_EMAIL;
    } elseif (($config['unique_email'] && $member_id <= 0) && 
        $db->users_find_by_string($vars['email'], 'email', 1)){
        $error[] = _SIGNUP_INVALID_EMAIL_1.'<br />'.sprintf(_SIGNUP_INVALID_EMAIL_2,'<a href="member.php">','</a>','<br />');
    }
    if (!strlen($vars['pass0'])){
        $error[] = _SIGNUP_PLEASE_ENTER_PSWD;
    } elseif (strlen($vars['pass0']) < $config['pass_min_length']){
        $ll = $config[pass_min_length];
        $error[] = sprintf(_SIGNUP_INVALID_PASS_1,$ll);
    }
    if ($vars['pass0'] != $vars['pass1']){
        $error[] = _SIGNUP_INVALID_PASS_2;
    }
    if (($vars['coupon']!='') && $config['use_coupons']){
        $coupon = $db->coupon_get($vars['coupon']);
        if (is_string($coupon))
            $error[] = $coupon;
    }        
    $error = array_merge($error, plugin_validate_signup_form($vars));
    return !count($error);
}

###############################################################################
# SHOW_PAYMENT_FORM
#
# get vars from database and plugins
# display $GLOBAL[error] if it set
# substitute previous entered parameters using Smarty
#
function show_payment_form(){
    global $t;
    global $error;
    global $db, $config, $vars;
    global $signup_scope_allowed;

    $t->assign('error', $error);

    $products = $db->get_products_list();
    if (!count($products)){
        fatal_error(_SIGNUP_SCRIPT_ERROR);
    }
    
    foreach ($products as $k=>$v){
        if (!in_array($v['scope'], $signup_scope_allowed))
            unset($products[$k]);

        if(is_array($vars['price_group'])){
            if(!array_intersect($vars['price_group'], split(',',$v['price_group'])))
                unset($products[$k]);
        }elseif ($vars['price_group']){
            if (!in_array($vars['price_group'], split(',',$v['price_group'])) )
                unset($products[$k]);
        } elseif ($v['price_group'] < 0){
            unset($products[$k]);
        }

        if ($products[$k] && ($products[$k]['terms'] == '')){
    		$pr = & new Product($products[$k]);
    		$products[$k]['terms'] = $pr->getSubscriptionTerms();
    	}    	
    }

    $paysystems = get_paysystems_list();
    //remove paysystems such as manual
    foreach ($paysystems as $k=>$p) 
        if (!$p['public']) unset($paysystems[$k]); 
    //remove free paysystem from select
    if (count($paysystems) > 1)
        foreach ($paysystems as $k=>$p)
            if ($p['paysys_id'] == 'free') unset($paysystems[$k]); 

    plugin_fill_in_signup_form($_REQUEST);
    plugin_fill_in_signup_form($vars); // Fill additional fields
            
    $t->assign('products', $products);
    $t->assign('paysystems', $paysystems);
    $t->assign('additional_fields_html', get_additional_fields_html($vars, 'signup'));

    if ($vars['country']) 
        $t->assign('state_options', db_getStatesForCountry($vars['country'], 1));

    $is_affiliate = '0';
    $newsletter_threads = $db->get_signup_threads_c($is_affiliate);
    $t->assign('newsletter_threads', $newsletter_threads);

    $t->display($config['amember_signup_template'] ? 
                $config['amember_signup_template'] : 'signup.html');
}


function proceed_to_payment($payment_id, $member_id_exists){
    global $config, $db;
    $payment = $db->get_payment($payment_id);
    if (!$payment['payment_id']) 
        fatal_error(sprintf(_SIGNUP_PAYMENT_NOT_FOUND,$payment_id), 1);       
    if ($payment['completed'])
        fatal_error(sprintf(_SIGNUP_PAYMENT_COMPLETED,$payment_id)."<a href='member.php'>"._SIGNUP_PAYMENT_COMPLETED_1."</a>", 1,1);
    extract($payment, EXTR_OVERWRITE);
    if ($pr = $payment['data'][0]['BASKET_PRODUCTS'])
        $product_id = $pr;
    global $error;
    $error = plugin_do_payment($paysys_id, $payment_id, $member_id, 
         is_array($product_id) ? $product_id[0] : $product_id,
         $amount, $begin_date, $expire_date, $vars);
    if ($error) {
        $db->delete_payment($payment_id);
        if (!$member_id_exists)
            $db->delete_user($member_id);
        show_payment_form();
    }
}

###############################################################################
##
##                             M  A  I  N 
##
###############################################################################
unset($GLOBALS['_trial_days']); // trial handling
$t = & new_smarty();
$error = '';
$vars = & get_input_vars();
plugin_display_signup_form($vars);
$error = array();
unset($member_id);
$coupon = null;

if ($vars['cs']){
    list ($payment_id, $code) = explode ("-", $vars['cs']);
    $payment = $db->get_payment($payment_id);
    if (!$payment['payment_id'])
        fatal_error(_SIGNUP_INCORRECT_LINK, 1);

    $member_id = $payment['member_id'];
    
    $email_confirm_code = $payment['data']['email_confirm']['code'];
    $email_confirm_time = $payment['data']['email_confirm']['time'];
    $member_id_exists   = $payment['data']['email_confirm']['member_id_exists'];

    // extract all variables from payment to signup
    $vars['member_id']          = $member_id;
    $vars['payment_id']         = $payment_id;
    $vars['member_id_exists']   = $member_id_exists;
    
    $u = $db->get_user($member_id);
    if (time() - $email_confirm_time > 10 * 24 * 60 * 60) // check that 'time' saved in record is not older than 10 days (to avoid code guessing)
        fatal_error(_SIGNUP_INCORRECT_LINK, 1);
    if (!$u['member_id'])
        fatal_error(_SIGNUP_INCORRECT_LINK, 1);
    if ($email_confirm_code != $code)
        fatal_error(_SIGNUP_INCORRECT_LINK, 1);
    $u['email_verified'] = 1;
    $db->update_user($member_id, $u);        
    
    if ($config['auto_login_after_signup']){
        $_SESSION['_amember_login']     = $u['login'];
        $_SESSION['_amember_pass']      = $u['pass'];
    }
    
    proceed_to_payment($payment_id, $member_id_exists);
    exit();    
}


// verificate CAPTCHA
if (($vars['do_payment'] || $vars['do_agreement']) 
    && $config['use_captcha_signup'] && !$_SESSION['amember_captcha_verified']){
    if (($vars['captcha'] != '') && 
        (strtolower($vars['captcha']) == $_SESSION['amember_captcha'])){
        $_SESSION['amember_captcha_verified'] = true;
    } else {
        $error[] = _SIGNUP_CAPTCHA_ERROR;
    }
}



if ($vars['do_agreement']){
    if (!$vars['i_agree']){
        $error[] = _SIGNUP_USER_AGREEMENT;
        display_agreement($vars['data']);
        exit();
    }
    $vars = unserialize($vars['data']);
    $vars['i_agree']++;
    foreach ($vars as $k=>$v)
        $t->_smarty_vars['request'][$k] = $v;
}

///// 
if ($vars['do_payment']){
    $vars['aff_id'] = $_COOKIE['amember_aff_id'];
    if (!$config['login_dont_lowercase'])
        $vars['login'] = strtolower($vars['login']);
    if ($config['generate_login'])
        $vars['login'] = generate_login($vars);
    if ($config['generate_pass'])
        $vars['pass'] = $vars['pass0'] = $vars['pass1'] = generate_password($vars);
    if ($vars['product_id']){
        $pc = & new PriceCalculator();
        $pc->addProducts($vars['product_id']);
        if ($config['use_coupons'] && $vars['coupon'] != ''){
            $coupon = $db->coupon_get($vars['coupon']);
            if ($coupon['coupon_id'])
                $pc->setCouponDiscount($coupon['discount'], split(',',trim($coupon['product_id'])));
        }
        $pc->setPriceFieldsByPaysys($vars['paysys_id']);
        $pc->setTax(get_member_tax(null));
        $terms = & $pc->calculate();
        $price = $terms->total;

        if ($config['product_paysystem']){
            $pr = get_product(is_array($vars['product_id'])?$vars['product_id'][0]:$vars['product_id']);
            $vars['paysys_id'] = $pr->config['paysys_id'];
        }

        if (!$price && !product_get_trial($vars['product_id']) && 
                in_array('free', $plugins['payment'])) 
            $vars['paysys_id'] = 'free';


    }
}

if (($vars['do_payment'] && check_payment_form())){

    //check for agreement
    $display_agreement = 0;
    foreach ((array)$vars['product_id'] as $pid){   
        $product = $db->get_product($pid);
        if ($product['need_agreement'])
            $display_agreement++;
    }
    if ($display_agreement && !$vars['i_agree']){
        display_agreement(serialize($vars)); // defined in the product.inc.php
        exit();
    }

    // do payment !
    $product_id = $vars['product_id'];
    if (!is_array($product_id)) $product_id = array($product_id);
    $login      = $vars['login'];
    $paysys_id  = $vars['paysys_id'];

    do { // to easy exit using break()
        foreach ((array)$vars['product_id'] as $pid){
            $product = $db->get_product($pid);
            if (!in_array($product['scope'], $signup_scope_allowed)){
                $error = _SIGNUP_INCORRECT_PRODID;
                break;
            }
            ////////////// check products scope
        }
        if ($error = check_product_requirements((array)$vars['product_id']))
            break;
        $member_id = $db->check_uniq_login($vars['login'], $vars['email'], $vars['pass0'], 1);
        $member_id_exists = 0;
        if ($config['verify_email'])
            $vars['email_verified'] = -1;
        if ($GLOBALS['_LANG_SELECTED'] != get_default_lang()){
            $vars['selected_lang'] = $GLOBALS['_LANG_SELECTED'];
        }
        if ($member_id < 0) {
            $member_id = $db->add_pending_user($vars);
            $is_affiliate = '0'; //only member
            if ($db->get_signup_threads_c($is_affiliate) && $vars['to_subscribe'])
                $db->subscribe_member ($member_id, $is_affiliate);
        }
        elseif (!$member_id)
            die(_SIGNUP_LOGIN_EXISTS);
        else {
            $member_id_exists++; //we found existing user with the same params
            // then will clean CC parameters if any
            if ($db->get_user_payments($member_id,1)) {
				$error[] = sprintf(_SIGNUP_INVALID_USERNAME_3,$vars[login]);
            } else {
            $member = $db->get_user($member_id);
            $member['data']['cc-hidden']='';
            $member['data']['cc-expire']='';
            $member['data']['cc']='';
            $member['data']['cc_street']='';
            $member['data']['cc_city']='';
            $member['data']['cc_state']='';
            $member['data']['cc_zip']='';
            $member['data']['cc_country']='';
            foreach ($vars as $k=>$v) $member[$k] = $v;
            $db->update_user($member_id, $member);
            }
        }
        if ($error) {
            break;
        }
        
        $pc = & new PriceCalculator();
        $pc->addProducts($vars['product_id']);
        if ($config['use_coupons'] && $vars['coupon'] != ''){
            $coupon = $db->coupon_get($vars['coupon']);
            if ($coupon['coupon_id'])
                $pc->setCouponDiscount($coupon['discount'], split(',',trim($coupon['product_id'])));
        }
        $pc->setPriceFieldsByPaysys($vars['paysys_id']);
        $pc->setTax(get_member_tax($member_id));
        $terms = & $pc->calculate();
        $price = $terms->total;
        
        if ($terms->discount > 0)
            $vars['COUPON_CODE'] = $vars['coupon'];
            
        $additional_values = array();
        foreach ($payment_additional_fields as $f){
            $fname = $f['name'];
            if (isset($vars[$fname])) 
                $additional_values[$fname] = $vars[$fname];
        }
        $additional_values['COUPON_DISCOUNT'] = $terms->discount;
        $additional_values['TAX_AMOUNT'] = $terms->tax;
        $taxes = $prices = array();
        foreach ($terms->lines as $pid => $line){
            $prices[$pid] = $line->total;
            if ($line->tax) 
                $taxes[$pid] = $line->tax;
        }             
        $additional_values['TAXES'] = $taxes;

        $product       = & get_product($product_id[0]);
        $begin_date    = $product->get_start();
        $expire_date   = $product->get_expire($begin_date, null, $terms); //yyyy-mm-dd

        // add payment
        $payment_id    = $db->add_waiting_payments($member_id, $product_id, 
            $paysys_id, $price, $prices, $begin_date, $expire_date, $vars,
            $additional_values);

        if ($error) {
            $db->delete_user($member_id);
            break;
        }

        $u = $db->get_user($member_id);
        if ($config['verify_email']){
            global $db;
            $code = substr(uniqid(rand(), true), 0, 12);
            $payment = $db->get_payment($payment_id);
            $payment['data']['email_confirm'] = array('code' => $code, 'member_id_exists' => $member_id_exists, 'time' => time());
            $db->update_payment($payment_id, $payment);
            mail_verification_email($u, $config['root_url'] . "/signup.php?cs=" . $payment_id . "-" . $code);

            $t->assign('user', $u);
            $t->assign('payment', $payment);
            $t->display("email_verify.html");
            exit();
        }

        if ($config['auto_login_after_signup']){
            $_SESSION['_amember_login']     = $u['login'];
            $_SESSION['_amember_pass']      = $u['pass'];
        }
        
        unset($_SESSION['amember_captcha_verified']) ;
        proceed_to_payment($payment_id, $member_id_exists);
        exit();
        
    } while (0);
} 

show_payment_form();
?>
