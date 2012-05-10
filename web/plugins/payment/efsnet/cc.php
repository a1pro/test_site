<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Credit card info enter page
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1907 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember is free for both commercial and non-commercial use providing that the
* copyright headers remain intact and the links remain on the html pages.
* Re-distribution of this script without prior consent is strictly prohibited.
*
*/
    
require_once('../../../config.inc.php');
require_once('pay.inc.php');


$t = & new_smarty();

function do_main(){
    global $t, $db, $config, $vars;

    
    if ($vars['cc_code'])
        $_SESSION['_amember_card_code'] = $vars['cc_code'];

    $t->assign('renew_cc', $vars['renew_cc']);

    ///
    $member_id  = intval($vars['member_id']);
    if (!$member_id)
        $member_id = $_SESSION['_amember_id'];
    if (!$member_id)
        fatal_error("Member ID isn't specified");
    if ($vars['renew_cc']){ // cc info renewal, require auth, don't req. payment_id
        if ($member_id != $_SESSION['_amember_id']){
            fatal_error("You must be autorized to do it", 0);
        }
    } else { //regular payment
        $payment_id = intval($vars['payment_id']);
        if (!$payment_id)
            fatal_error("Payment ID isn't specified");
    }
    $db->log_error("member_id=$member_id,{$_SESSION[_amember_id]}");
    $member = $db->get_user($member_id);
    /// use old info if it's possible
    if (!$vars['renew_cc'] && !$vars['retry'] &&
        $member['data']['cc-hidden'] && 
        $member['data']['cc-expire'] > 0) {
        if ($vars['cc_number'] || $vars['cc_expire_Month'] || $vars['cc_expire_Year']){
        } else {
            $vars['cc_number'] = amember_decrypt($member['data']['cc-hidden']);
            $vars['cc_expire_Month'] = intval(substr($member['data']['cc-expire'], 0, 2));
            $vars['cc_expire_Year'] = 2000+intval(substr($member['data']['cc-expire'], 2, 2));
            foreach (array('street', 'city', 'state', 'zip','country') as $f)
                $vars['cc_' . $f] = $member['data']['cc_'.$f];
            $vars['do_cc']     = 1;
        }
    }
    while ($vars['do_cc']){
        // validate cc_vars
        $error = validate_cc_info($vars);
        if ($error) break;
        // get member
        $m = $db->get_user($member_id);

        if (!is_array($m)) 
            fatal_error("Can not load member record #$member_id ($payment_id)");
        $m['data']['cc-hidden'] = amember_crypt($vars['cc_number']);
        $m['data']['cc']        = get_visible_cc_number($vars['cc_number']);
        $m['data']['cc-expire'] = sprintf('%02d%02d',
            $vars['cc_expire_Month'],
            substr($vars['cc_expire_Year'], 2, 2));
        foreach (array('street', 'city', 'state', 'zip','country') as $f)
            $m['data']['cc_'.$f] = $vars['cc_'.$f];

        $db->update_user($m['member_id'], $m);
        ////// skip real payment if renew cc specified ///////////////
        if ($vars['renew_cc']) {
            header("Location: $config[root_surl]/member.php?cc_renew_done=1");
        } else {
            header("Location: $config[root_surl]/plugins/payment/efsnet/cc.php?".
                "do_payment=1&payment_id=$payment_id&member_id=$member_id");
        }
        exit();
    } 
    $t->assign('error', $error);    
    if ($vars['payment_id']){
        $payment = $db->get_payment($vars['payment_id']);
        $t->assign('payment', $payment);
    }

    /// handle address 
    $cc_address = array();
    foreach (array('street', 'city', 'state', 'zip','country') as $f){
        $v = $vars['cc_' . $f];
        if (!isset($vars['cc_'. $f])) {
            if (!$v) $v = $member['data']['cc_'.$f];
            if (!$v) $v = $member[$f];
        }
        $cc_address['cc_'.$f] = $v;
    }
    $t->assign('cc_address', $cc_address);
    $t->display('cc/cc_info.html');
}

function do_payment($payment_id, $member_id){
    global $config;
    //////////////////////////////////////////////////////////////    
    global $_IN_PAYMENT;
    session_register('_IN_PAYMENT');
    if ($_IN_PAYMENT == $payment_id){
#        fatal_error("Your payment is being processed - To avoid double charges, please DO NOT PRESS THE SUBMIT BUTTON AGAIN");
    }
    $_IN_PAYMENT = $payment_id;
    session_write_close();
    list($err, $errno) = efsnet_payment($payment_id, $member_id);
    
    session_register('_IN_PAYMENT');
    $_IN_PAYMENT = $payment_id;
    if ($errno != 0){
        global $t;
        $t->assign('error', $err);
        $t->assign('pay_link', "$config[root_surl]/plugins/payment/efsnet/cc.php?payment_id=$payment_id&member_id=$member_id&retry=1");
        $t->display("cc/cc_declined.html");
    } elseif ($err) {
        fatal_error($err);
    } else {
        header("Location: $config[root_surl]/thanks.php?payment_id=$payment_id&member_id=$member_id");
    }
    exit();
}

function validate_cc_info(&$vars){
    $errors = array();
    // check credit card
    $vars['cc_number'] = preg_replace('/\D+/', '', $vars['cc_number']);
    $cc = $vars['cc_number'];
    if (strlen($cc) < 12) {
        $errors[] = "Invalid Credit Card Number";
    }
    if (($vars['cc_expire_Month'] < 1) or ($vars['cc_expire_Month']>12)){
        $errors[] = "Invalid Expiration Date - Month";
    }

    if ($vars['cc_expire_Year'] < date('Y')){
        $errors[] = "Invalid Expiration Date - Year";
    }

    if (!strlen($vars['cc_code']))
        $errors[] = "Please enter Credit Card Code";

    if (!strlen($vars['cc_street']))
        $errors[] = "Please enter street address";
    if (!strlen($vars['cc_city']))
        $errors[] = "Please enter city";
    if (!strlen($vars['cc_state']))
        $errors[] = "Please select state";
    if (!strlen($vars['cc_zip']))
        $errors[] = "Please enter ZIP code";
    if (!strlen($vars['cc_country']))
        $errors[] = "Please select country";
    return $errors;
}

function get_visible_cc_number($cc){
    $cc = preg_replace('/\D+/', '', $cc);
    return '**** **** **** '.substr($cc, -4);
}

$vars = get_input_vars();
if ($_GET['renew_cc']) {
    $vars['renew_cc']++;
}
if ($vars['renew_cc']){
    $t = & new_smarty();
    $_product_id = array('ONLY_LOGIN');
    include($config['plugins_dir']['protect'] . '/php_include/check.inc.php');
}
if (!$vars['do_payment']){
    do_main();
} else {
    do_payment(intval($vars['payment_id']), intval($vars['member_id']));
}

?>
