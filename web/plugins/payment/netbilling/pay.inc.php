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


function netbilling_get_url($url, $post=''){
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

function netbilling_payment($payment_id, $member_id){
    set_time_limit(600);
    ignore_user_abort(true);
    // first prepare all variables
    global $db, $config, $plugin_config;
    $this_config   = $plugin_config['payment']['netbilling'];
    if (!$this_config['login']) fatal_error(_PLUG_PAY_NETBILLING_FERROR5);
    $payment = $db->get_payment($payment_id);
    if (!$payment)
        return array(sprintf(_PLUG_PAY_NETBILLING_PNOTFOUND, $payment_id), -900);
    if ($payment['completed'])
        return array(sprintf(_PLUG_PAY_NETBILLING_PCOMPLETED, $payment_id), 3);
    if ($payment['paysys_id'] != 'netbilling')
        return array(_PLUG_PAY_NETBILLING_ANOTHERPSYS." '$payment[paysys_id]' (#$payment_id)", 3);
    if ($payment['member_id'] != $member_id)
        return array(sprintf(_PLUG_PAY_NETBILLING_ANOTHERMEMBER, $payment_id), 3);

    $member = $db->get_user($payment['member_id']);
    if (!$member) 
        return array(sprintf(_PLUG_PAY_NETBILLING_MEMNOTFOUND, $member_id), -904); 
    $cc_number = amember_decrypt($member['data']['cc-hidden']);
    $cc_expire = $member['data']['cc-expire'];

    ///// !!! run transaction
    $url_proc = "https://secure.netbilling.com/gw/native/direct2.1";
    
    if (!$_SESSION['_amember_payment_try'] || ($_SESSION['_amember_payment_try']>87))
        $_SESSION['_amember_payment_try'] = 65;
    $vars = array(
        "GEN_ACCOUNT"    => $this_config['login'],
        "GEN_AMOUNT" =>   $payment['amount'],
        "GEN_TRANS_TYPE" => "SALE",
        "GEN_PAYMENT_TYPE" => "C",
        "GEN_USER_DATA" => "payment#: $payment_id",

        "CARD_NUMBER" => $cc_number,
        "CARD_EXPIRE" => $cc_expire,

        "CUST_PHONE" =>    $member['data']['phone'],
        "CUST_EMAIL" =>    $member['email'],
        "CUST_NAME1" =>  $member['data']['cc_name_f'],
        "CUST_NAME2" =>   $member['data']['cc_name_l'],

        "CUST_ADDR_STREET" =>  $member['data']['cc_street'],
        "CUST_ADDR_CITY" =>     $member['data']['cc_city'],
        "CUST_ADDR_STATE" =>    $member['data']['cc_state'],
        "CUST_ADDR_ZIP" =>      $member['data']['cc_zip'],
        "CUST_ADDR_COUNTRY" =>  $member['data']['cc_country']

    );
    if ($_SESSION['_amember_card_code'])
        $vars['CARD_CVV2'] = $_SESSION['_amember_card_code'];
    $_SESSION['_amember_card_code'] = '';
    unset($_SESSION['_amember_card_code']);

    $vars = $vars + array(
    );
    foreach ($vars as $kk=>$vv){
        $v = urlencode($vv);
        $k = urlencode($kk);
        $vars1[] = "$kk=$vv";
    }

    $vars_cc = $vars; 
    $vars_cc['CARD_NUMBER'] = $member['data']['cc'];
    unset($vars_cc['CARD_CVV2']);
    $payment['data'][] = $vars_cc;
    $db->update_payment($payment_id, $payment);

    $buffer = netbilling_get_url($url_proc, join('&', $vars1));
    parse_str($buffer, $res);

    $return = $res;
    $return['RESULT']      = ($res['RET_STATUS'] == 1) ? 1 : 2;
    $return['RESPMSG']   = $res['RET_AUTH_MSG'] ;
    $return['AVS']       = $res['RET_AVS_CODE'];
    $return['PNREF']     = $res['RET_TRANS_ID'];
    $return['CVV_VALID'] = $res['RET_CVV2_CODE'];

    ////////////// check transaction result   ////////////////////////////
    if (!isset($return['RESULT'])){
        $db->log_error("Empty result, payment handling failed #$payment_id");
        return array(_PLUG_PAY_NETBILLING_EMPTYRES, 3);
    } elseif ($return['RESULT'] == 1) {
        $db->finish_waiting_payment($payment_id, 'netbilling', 
            $return['PNREF'], '', $return);
        return array('', $return['RESULT']);
    } elseif ($return['RESULT'] == 2) {
        if (0) {
            $m = $db->get_user($member_id);
            $member['data']['cc-hidden'] = '';
            $member['data']['cc-expire'] = '';
            $member['data']['cc'] = '';
            $db->update_user($member_id, $m);
        }

        $payment['data'][] = $return;
        $db->update_payment($payment_id, $payment);
        return array($return['RESPMSG'], $return['RESULT']);
    } else  { //$return > 2 or unknown
        $payment['data'][] = $return;
        $db->log_error($return['RESPMSG']);
        $db->update_payment($payment_id, $payment);
        return array("Payment processor error: ".
        (($return['RESPMSG']) ? ($return['RESPMSG']) : $buffer), 
        $return['RESULT']);
    }
    return array($return['RESPMSG'], $return['RESULT']); //dummy!
}

?>
