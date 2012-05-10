<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: eprocessingnetwork payment plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3933 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_eprocessingnetwork extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('eprocessingnetwork', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('eprocessingnetwork', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['eprocessingnetwork']['title'] ? $config['payment']['eprocessingnetwork']['title'] : _PLUG_PAY_EPROCESSING_TITLE,
            'description' => $config['payment']['eprocessingnetwork']['description'] ? $config['payment']['eprocessingnetwork']['description'] : _PLUG_PAY_EPROCESSING_DESC,
            'code' => 1,
            'name_f' => 2
        );
    }

    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////
        if ($charge_type == CC_CHARGE_TYPE_TEST) 
            return array(CC_RESULT_SUCCESS, "", "", array('test transaction' => 'no validation'));
        $vars = array(
            'ePNAccount' => $this->config['account'],
            'CardNo' => $cc_info['cc_number'],
            'ExpMonth' => substr($cc_info['cc-expire'], 0, 2),
            'ExpYear' => substr($cc_info['cc-expire'], 2, 2),
            'Total' => $amount,
            'Address' => $cc_info['cc_street'],
            'Zip' => $cc_info['cc_zip'],
            'FirstName' => $cc_info['cc_name_f'],
            'LastName'  => $cc_info['cc_name_l'],
       );
	if($this->config['restrictkey']){
	    $vars['RestrictKey'] = $this->config['restrictkey'];
	}

        if ($cc_info['cc_code'] != ''){
            $vars['CVV2Type'] = 1;
            $vars['CVV2'] = $cc_info['cc_code'];
        } else {
            $vars['CVV2Type'] = 0;
            $vars['CVV2'] = '';
        }
        // prepare log record
        $vars_l = $vars; 
        $vars_l['CardNo'] = $cc_info['cc'];
        if ($vars['CVV2'])
            $vars_l['CVV2'] = preg_replace('/./', '*', $vars['CVV2']);
        $log[] = $vars_l;
        /////
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url("https://www.eProcessingNetwork.Com/cgi-bin/tdbe/transact.pl", $vars1);

        $log[] = $res = array('R' => $ret);
        if (preg_match('/\>"Y/', $ret)){
            return array(CC_RESULT_SUCCESS, "", $res['payment_number'], $log);
        } else { // ($res['summary_code'] == 1) {
                $err = $res['response_text'] ? $res['response_text'] : "card declined";
                return array(CC_RESULT_DECLINE_PERM, $err, "", $log);
        }
    }
}

function eprocessingnetwork_get_member_links($user){
    return cc_core_get_member_links('eprocessingnetwork', $user);
}

function eprocessingnetwork_rebill(){
    return cc_core_rebill('eprocessingnetwork');
}

cc_core_init('eprocessingnetwork');
?>
