<?php 

/*
*  User's signup page
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Affiliates Signup Page
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5455 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/
include('./config.inc.php');

if (!$config['use_affiliates'])
    fatal_error(_AFF_DISABLED_MESSAGE);
    
###############################################################################
# CHECK_FORM
#
# set $GLOBAL[error] if needed
# return 1/0 (valid/not-valid)
#
function check_form(){
    global $error;
    global $vars;
    global $db;
    global $config;

    //
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
    if (preg_match('/[^0-9a-zA-Z_ ]+/', $vars['login'])){
        $error[] = _SIGNUP_INVALID_USERNAME;
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

    if (!strlen($vars['aff_payout_type']) && count(aff_get_payout_methods(1)) > 1){
        $error[] = _AFF_SIGNUP_PLEASE_PAYOUT_TYPE;
    }

    $error = array_merge($error, plugin_validate_signup_form($vars, 'affiliate_signup'));
    return !count($error);
}

###############################################################################
# SHOW_FORM
#
# get vars from database and plugins
# display $GLOBAL[error] if it set
# substitute previous entered parameters using Smarty
#
function show_form(){
    global $t;
    global $error;
    global $db, $config, $vars;

    $t->assign('error', $error);

    plugin_fill_in_signup_form($_REQUEST);
            
    $t->assign('additional_fields_html', get_additional_fields_html($vars, 'affiliate_signup'));
    $t->assign('aff_payout_types', aff_get_payout_methods(1));
    
    $is_affiliate = '2';
    $newsletter_threads = $db->get_signup_threads_c($is_affiliate);
    $t->assign('newsletter_threads', $newsletter_threads);
    
    $t->display('aff_signup.html');
}

function auto_login_and_move_subscriptions ($member_id){
    global $db, $config;
    settype($member_id, 'integer');
    if ($member_id <= 0)
        return;
    
    $u = $db->get_user($member_id);
    if (!$u)
        return;
          
    if ($config['auto_login_after_signup']){
        $_SESSION['_amember_login']     = $u['login'];
        $_SESSION['_amember_pass']      = $u['pass'];
    }
    
    if($config['aff']['mail_signup_user']) check_aff_signup_email_sent($member_id);

    $g = $db->get_guest_by_email($u['email']);
    if (count($g) > 0 && $g['guest_id'] > 0){
                
        $guest_id = $g['guest_id'];
        $threads = $db->get_guest_threads($guest_id);
        $threads = array_keys($threads);
        if (count($threads) > 0){
            $db->add_member_threads($member_id, $threads);
            $db->delete_guest_threads($guest_id);
        }
        $db->delete_guest($guest_id);
                
    }
}

###############################################################################
##
##                             M  A  I  N 
##
###############################################################################
$t = & new_smarty();
$error = '';
$vars = & get_input_vars();
plugin_display_signup_form();
$error = array();
unset($member_id);

if ($vars['continue_signup']){
    $u = $db->get_user($vars['member_id']);
    if (!$u['member_id'])
        fatal_error(_SIGNUP_INCORRECT_LINK, 1);
    $md5 = md5($u['login'].$u['pass'].$vars['member_id']);
    if ($md5 != $vars['md5'])        
        fatal_error(_SIGNUP_INCORRECT_LINK, 1);
    $u['email_verified'] = 1;
    $db->update_user($u['member_id'], $u);        
    
    auto_login_and_move_subscriptions($u['member_id']);

    header("Location: ".$config['root_url']."/thanks.php");

    exit();    
}

// verificate CAPTCHA
if (($vars['do_affiliate'] || $vars['do_agreement']) 
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


if (!$config['login_dont_lowercase'])
    $vars['login'] = strtolower($vars['login']);
if ($config['generate_login'])
    $vars['login'] = generate_login($vars);
if ($config['generate_pass'])
    $vars['pass'] = $vars['pass0'] = $vars['pass1'] = generate_password($vars);

///// 

if ( $vars['do_affiliate'] && check_form() ){

    /*
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
    */

    $login      = $vars['login'];

    do { // to easy exit using break()

        $member_id = $db->check_uniq_login($vars['login'], $vars['email'], $vars['pass0'], 1);
        $member_id_exists = 0;
        if ($config['verify_email'])
            $vars['email_verified'] = -1;
        if ($GLOBALS['_LANG_SELECTED'] != get_default_lang()){
            $vars['selected_lang'] = $GLOBALS['_LANG_SELECTED'];
        }
        
        if (!$vars['aff_payout_type']){ // if payout type not selected
            $payout_methods = aff_get_payout_methods(1);
            if (count($payout_methods) == 1) { // if there is only one payout type
                $payout_methods = array_keys($payout_methods);
                $vars['aff_payout_type'] = $payout_methods[0];
            }
        }
        
        if ($member_id < 0) {
            $member_id = $db->add_pending_user($vars);

            $is_affiliate = '2'; //only affiliate
            if ($db->get_signup_threads_c($is_affiliate) && $vars['to_subscribe'])
                $db->subscribe_member ($member_id, $is_affiliate);

            $member = $db->get_user($member_id);
            $member['is_affiliate'] = $is_affiliate;
            /* No unsubscribe new members!
            if (!$vars['to_subscribe']) $member['unsubscribed']='1';
            */
            $db->update_user($member_id, $member);
        }
        elseif (!$member_id)
            die(_SIGNUP_LOGIN_EXISTS);
        else {
            $member_id_exists++; //we found existing user with the same params
            // then will clean CC parameters if any
            $member = $db->get_user($member_id);
            
            $is_affiliate = '1'; //member & affiliate
            if ($db->get_signup_threads_c('2') && $vars['to_subscribe'])
                $db->subscribe_member ($member_id, '2'); // subscribe ONLY as affiliate !!!
            $member['is_affiliate'] = $is_affiliate;
            if (!$vars['to_subscribe']) $member['unsubscribed']='1';
            
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

        $additional_values = array();
        foreach ($payment_additional_fields as $f){
            $fname = $f['name'];
            if (isset($vars[$fname])) 
                $additional_values[$fname] = $vars[$fname];
        }
        
        if ($error) {
            $db->delete_user($member_id);
            break;
        }

        if ($config['verify_email']){
            global $db;
            $u = $db->get_user($member_id);
            $md5 = md5($u['login'].$u['pass'].$member_id);
            mail_verification_email($u, $config['root_url']."/aff_signup.php?continue_signup=1&member_id=$member_id&member_id_exists=$member_id_exists&md5=$md5");
            $t->assign('user', $u);
            $t->display("email_verify.html");
            exit();
        }
        unset($_SESSION['amember_captcha_verified']);

        auto_login_and_move_subscriptions ($member_id);
          
        //header("Location: ".$config['root_url']."/aff_member.php");
        $url = $config['root_url'] . "/aff_member.php";
        html_redirect($url, 0, _AFF_MEMBER_THANK_YOU, _AFF_MEMBER_REDIRECTING);
        exit();
                      
    } while (0);
} 

show_form();
?>

