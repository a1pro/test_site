<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: TheInternetCommerce AIM payment interface 
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1781 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember is free for both commercial and non-commercial use providing that the
* copyright headers remain intact and the links remain on the html pages.
* Re-distribution of this script without prior consent is strictly prohibited.
*
*/


function theinternetcommerce_get_url($url, $post=''){
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

function theinternetcommerce_payment($payment_id, $member_id){
    set_time_limit(600);
    ignore_user_abort(true);
    // first prepare all variables
    global $db, $config, $plugin_config;
    $this_config   = $plugin_config['payment']['theinternetcommerce'];
    if (!$this_config['login']) fatal_error("No username configured for TheInternetCommerce");
    if (!$this_config['pass']) fatal_error("No transaction key configured for TheInternetCommerce");
    $payment = $db->get_payment($payment_id);
    if (!$payment)
        return array(sprintf(_PLUG_PAY_INETCOM_ERROR10, $payment_id), -900);
    if ($payment['completed'])
        return array(sprintf(_PLUG_PAY_INETCOM_ERROR11, $payment_id), -901);
    if ($payment['paysys_id'] != 'theinternetcommerce')
        return array(sprintf(_PLUG_PAY_INETCOM_ERROR12, $payment[paysys_id], $payment_id), -902);
//    if ($payment['member_id'] != $member_id)
//        return array("Payment created for another member: #$payment_id ({$payment[member_id]},$member_id)", -903);

    if ($payment['amount'] <= 0 ) { // seems it is a trial subscription
        $db->finish_waiting_payment($payment_id, 'theinternetcommerce', 
            'free trial', '', $return);
        return array('', 1);
    }

    $member = $db->get_user($payment['member_id']);
    if (!$member) 
        return array(sprintf(_PLUG_PAY_INETCOM_ERROR13, $member_id), -904); 
    $cc_number = amember_decrypt($member['data']['cc-hidden']);
    $cc_expire = $member['data']['cc-expire'];
    $product = $db->get_product($payment['product_id']);


    ///// !!! run transaction
    $MerchantID = $this_config['login'];
    $Password = $this_config['pass'];

    #############################################################

    $Amount = $payment['amount'];
    $MerchantDesc = $product['title'];
    $CustomerEmail = $member['email'];
    $Var1 = $member['data']['cc_name_f'] . " " . $member['data']['cc_name_l'];
    $Var2 = $member['email'];
    $Var3 = $product['title'];
    $Var4 = "{$member[cc_street]} {$member[cc_city]} {$member[cc_zip]} {$member[cc_country]}";
    $Var5 = "$comments";
    $Var6 = "$keeplog";
    $Var7 = "Server Time: $timestamp";
    $Var8 = "IP: $REMOTE_ADDR";
    $Var9 = "Host: $REMOTE_HOST";
    $CCN = $cc_number;
    $Expdate = $cc_expire;
    #$CVCCVV = "123";
    $CVCCVV = $_SESSION['_amember_card_code'];
    $InstallmentOffset = 0;
    $InstallmentPeriod = 0;

    include ("inc_newtransaction.php");

    ////////////// check transaction result   ////////////////////////////
    if ($INCREDIBLE_CLEARANCE_STATUS == 0) {
        $db->finish_waiting_payment($payment_id, 'theinternetcommerce', 
            $return['PNREF'], '', $return);
        return array('', 1);
    } else {
        return array("$INCREDIBLE_CLEARANCE_STATUS: $INCREDIBLE_CLEARANCE_ERROR", 2);
    }
    return array($return['RESPMSG'], $return['RESULT']); //dummy!
}

?>
