<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: EFSNET payment interface 
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


function efsnet_payment($payment_id, $member_id){
    set_time_limit(600);
    ignore_user_abort(true);
    // first prepare all variables
    global $db, $config, $plugin_config;
    $this_config   = $plugin_config['payment']['efsnet'];
    if (!$this_config['store_id']) fatal_error("No site_id configured for EFSNET");
    if (!$this_config['store_key']) fatal_error("No transaction key configured for EFSNET");
    $payment = $db->get_payment($payment_id);
    if (!$payment)
        return array(sprintf(_PLUG_PAY_EFSNET_ERROR,$payment_id), -900);
    if ($payment['completed'])
        return array(sprintf(_PLUG_PAY_EFSNET_ERROR2,$payment_id), -901);
    if ($payment['paysys_id'] != 'efsnet')
        return array(sprintf(_PLUG_PAY_EFSNET_ERROR3,$payment[paysys_id],$payment_id), -902);
    if ($payment['member_id'] != $member_id)
        return array(sprintf(_PLUG_PAY_EFSNET_ERROR4,$payment_id), -903);

    $member = $db->get_user($payment['member_id']);
    if (!$member) 
        return array(sprintf(_PLUG_PAY_EFSNET_ERROR5,$member_id), -904); 
    $cc_number = amember_decrypt($member['data']['cc-hidden']);
    $cc_expire = $member['data']['cc-expire'];


    ///// !!! run transaction
    $url_proc = 
        $this_config['testing'] ? 
        "https://testefsnet.concordebiz.com/efsnet.dll" :
        "https://efsnet.concordebiz.com/efsnet.dll";
    
    if (!$_SESSION['_amember_payment_try'] || ($_SESSION['_amember_payment_try']>87))
        $_SESSION['_amember_payment_try'] = 65;
    $vars = array(
        "Method"  => "CreditCardCharge",
        "StoreID" => $this_config['store_id'],
        "StoreKey" => $this_config['store_key'],
        "ApplicationID" => 'AMember Pro v1.9.3',
        "ReferenceNumber" => '19300' . $payment_id ,
        "TransactionAmount" => $payment['amount'],
        "AccountNumber" => $cc_number,
        "ExpirationMonth" => substr($cc_expire, 0, 2),
        "ExpirationYear" => substr($cc_expire, 2, 2),
//        "Track1" => $member['x'],
//        "Track2" => $member['x'],
        "BillingName" => $member['name_f'] . ' ' . $member['name_l'],
        "BillingAddress" => $member['data']['cc_street'],
        "BillingCity" => $member['data']['cc_city'],
        "BillingState" => $member['data']['cc_state'],
        "BillingPostalCode" => $member['data']['cc_zip'],
        "BillingCountry" => $member['data']['cc_country'],
        "BillingEmail" => $member['email']
        
//        ,"ClientIPAddress" => $member['remote_addr']
    );
    if ($_SESSION['_amember_card_code'])
        $vars["CardVerificationValue"] = $_SESSION['_amember_card_code'];
    $_SESSION['_amember_card_code'] = '';
    unset($_SESSION['_amember_card_code']);

    foreach ($vars as $kk=>$vv){
        $v = urlencode($vv);
        $k = urlencode($kk);
        $vars1[] = "$kk=$vv";
    }

//    print_r($vars);

    $vars_cc = $vars; 
    $vars_cc['AccountNumber'] = $member['data']['cc'];
    unset($vars_cc['CardVerificationValue']);
    $payment['data'][] = $vars_cc;
    $db->update_payment($payment_id, $payment);

    $buffer = get_url($url_proc, join('&', $vars1));
    parse_str($buffer, $return);

    ////////////// check transaction result   ////////////////////////////
    if (!isset($return['ResponseCode'])){
        $db->log_error("Empty result, payment handling failed #$payment_id");
        return array(_PLUG_PAY_EFSNET_ERROR6, -1);
    } elseif ($return['ResponseCode'] == 0) {
        $db->finish_waiting_payment($payment_id, 'efsnet', 
            $return['ApprovalNumber'], '', $return);
        return array('', $return['ResponseCode']);
    } else  { 
        $m = $db->get_user($member_id);
        $member['data']['cc-hidden'] = '';
        $member['data']['cc-expire'] = '';
        $member['data']['cc'] = '';
        $db->update_user($member_id, $m);

        $payment['data'][] = $return;
        $db->log_error($return['ResultMessage']);
        $db->update_payment($payment_id, $payment);
        return array("Payment processor error: ".
        (($return['ResultMessage']) ? ($return['ResultMessage']) : $buffer), 
        $return['ResponseCode']);
    }
    return array($return['ResultMessage'], $return['ResponseCode']); //dummy!
}

?>
