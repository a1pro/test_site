<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Authorize AIM payment interface 
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


function payready_get_url($url, $post=''){
    if (extension_loaded("curl")){
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
        $buffer = curl_exec($ch);                       
        curl_close($ch);
        return $buffer;
    } else {
        global $config;
        if (substr(php_uname(), 0, 7) == "Windows") {
            $curl = $config['curl'];
            if (!strlen($curl)) fatal_error("cURL path is not set");
        } else {
            $curl = escapeshellcmd($config['curl']);
            if (!strlen($curl)) fatal_error("cURL path is not set");
//            $post = escapeshellcmd($post);
            $url = escapeshellcmd($url);
        }
        $ret=`$curl -d "$post" $url`;
        return $ret;
    }
}

function payready_payment($payment_id, $member_id){
    set_time_limit(600);
    ignore_user_abort(true);
    // first prepare all variables
    global $db, $config, $plugin_config;
    $this_config   = $plugin_config['payment']['payready'];
    if (!$this_config['login']) fatal_error("No username configured for PayReady");
    $payment = $db->get_payment($payment_id);
    if (!$payment)
        return array(sprintf(_PLUG_PAY_PAYRDY_ERROR10, $payment_id), 3);
    if ($payment['completed'])
        return array(sprintf(_PLUG_PAY_PAYRDY_ERROR11, $payment_id), 3);
    if ($payment['paysys_id'] != 'payready')
        return array(_PLUG_PAY_PAYRDY_ERROR12."'$payment[paysys_id]' (#$payment_id)", 3);
//    if ($payment['member_id'] != $member_id)
//        return array("Payment created for another member: #$payment_id ({$payment[member_id]},$member_id)", 3);

    if ($payment['amount'] <= 0 ) { // seems it is a trial subscription
        $db->finish_waiting_payment($payment_id, 'payready', 
            'free trial', '', $return);
        return array('', 1);
    }

    $member = $db->get_user($payment['member_id']);
    if (!$member) 
        return array("Member not found: #$member_id", -904); 
    $cc_number = amember_decrypt($member['data']['cc-hidden']);
    $cc_expire = $member['data']['cc-expire'];
    $cc_type = $member['data']['cc_type'];
    $product = $db->get_product($payment['product_id']);


    ///// !!! run transaction
    $url_proc = "https://www.payready.net/DMTransaction.asp";
    
    if (!$_SESSION['_amember_payment_try'] || ($_SESSION['_amember_payment_try']>87))
        $_SESSION['_amember_payment_try'] = 65;
    $vars = array(
        "txtPayReadyID"    => urlencode($this_config['login']),
        "txtTotalAmount" =>   $payment['amount'],
        "txtOrderDescription" => urlencode($product['title']),
        "txtShowTranPage" => 0,
        "txtResponseURL" => "TextOnly",
        "txtCreditCardType" => $cc_type,
        "txtCreditCardNumber" => $cc_number,
        "txtCreditCardExpirationMonth" => substr($cc_expire,0,2),
        "txtCreditCardExpirationYear" => substr($cc_expire,2,2),
        
        "txtEmail" =>    urlencode($member['email']),
        "txtConsumerID" =>  $member_id,
        "txtInvoiceNumber" => urlencode($payment_id . ' ' . strtolower(chr($_SESSION['_amember_payment_try']++))),
        "txtConsumerFirstName" =>  urlencode($member['data']['cc_name_f']),
        "txtConsumerLastName" =>   urlencode($member['data']['cc_name_l'])
    );
//    if ($_SESSION['_amember_card_code'])
//        $vars['txtCard_Code'] = $_SESSION['_amember_card_code'];
//    $_SESSION['_amember_card_code'] = '';
//    unset($_SESSION['_amember_card_code']);


    $vars = $vars + array(
    "txtBillingStreet" =>  urlencode($member['data']['cc_street']),
    "txtBillingCity" =>     urlencode($member['data']['cc_city']),
    "txtBillingState" =>    urlencode($member['data']['cc_state']),
    "txtBillingZip" =>      urlencode($member['data']['cc_zip']),
    "txtBillingCountry" =>  urlencode($member['data']['cc_country'])
    );
    $vars['txtPhone']   = urlencode($member['data']['cc_phone']);
    foreach ($vars as $kk=>$vv){
        $v = urlencode($vv);
        $k = urlencode($kk);
        $vars1[] = "$kk=$vv";
    }

    $vars_cc = $vars; 
    $vars_cc['txtCreditCardNumber'] = $member['data']['cc'];
    $payment['data'][] = $vars_cc;
    $db->update_payment($payment_id, $payment);

    $buffer = payready_get_url($url_proc, join('&', $vars1));
    $res = explode('&', $buffer);
    foreach ($res as $k=>$v)
        $res[$k] = urldecode($v);

    $return = $res;
    $return['RESULT']      = $res[2];
    $return['RESPMSG']   = $res[45];
    $return['AVS']       = $res[5];
    $return['PNREF']     = $res[1];

    if ($return['RESULT'] == '0') {
        $return['RESULT'] = 1;
    } elseif ($return['RESULT'] == 1) {
        $return['RESULT'] = 2;
    } else {
        $return['RESULT'] = 3;
    }

    ////////////// check transaction result   ////////////////////////////
    if (!isset($return['RESULT'])){
        $db->log_error("Empty result, payment handling failed #$payment_id");
        return array(_PLUG_PAY_PAYRDY_ERROR13, 3);
    } elseif ($return['RESULT'] == 1) {
        $db->finish_waiting_payment($payment_id, 'payready', 
            $return['PNREF'], '', $return);
        return array('', $return['RESULT']);
    } elseif ($return['RESULT'] == 2) {
        $m = $db->get_user($member_id);
        $member['data']['cc-hidden'] = '';
        $member['data']['cc-expire'] = '';
        $member['data']['cc'] = '';
        $db->update_user($member_id, $m);

        $payment['data'][] = $return;
        $db->update_payment($payment_id, $payment);
        return array($return['RESPMSG'], $return['RESULT']);
    } else  { //$return > 2 or unknown
        return array(_PLUG_PAY_PAYRDY_ERROR14.
        (($return['RESPMSG']) ? ($return['RESPMSG']) : $buffer), 
        $return['RESULT']);
    }
    return array($return['RESPMSG'], $return['RESULT']); //dummy!
}

?>
