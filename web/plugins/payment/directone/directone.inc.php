<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: directone payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1781 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $config;


require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_directone extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('directone', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('directone', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['directone']['title'] ? $config['payment']['directone']['title'] : _PLUG_PAY_DIRECTONE_TITLE,
            'description' => $config['payment']['directone']['description'] ? $config['payment']['directone']['description'] : _PLUG_PAY_DIRECTONE_DESC,
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
            'vendor_name' => $this->config['account_name'],
            'vendor_password' => $this->config['account_pass'],
            'card_number' => $cc_info['cc_number'],
            'card_type' => 'AUTO',
            'card_expiry' => $cc_info['cc-expire'],
            'card_holder' => $cc_info['cc_name_f'] . " " . $cc_info['cc_name_l'],
            'payment_amount' => round($amount * 100),
            'payment_reference' => $payment['payment_id']
        );
        // prepare log record
        $vars_l = $vars; 
        $vars_l['card_number'] = $cc_info['cc'];
        $vars_l['vendor_password'] = preg_replace('/./', '*', $vars_l['vendor_password']);
        $log[] = $vars_l;
        /////
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        if ($this->config['testing'])
            $ret = cc_core_get_url("https://vault.safepay.com.au/cgi-bin/direct_test.pl?$vars1");
        else
            $ret = cc_core_get_url("https://vault.safepay.com.au/cgi-bin/direct_process.pl?$vars1");
        preg_match_all('!^(\w+)\=(.+?)$!m', $ret, $regs);
        $res = array();
        foreach ($regs[1] as $i=>$k){
            $res[$k] = $regs[2][$i];
        }
        $log[] = $res;

        if ($res['summary_code'] == '0'){
            return array(CC_RESULT_SUCCESS, "", $res['payment_number'], $log);
        } elseif ($res['summary_code'] == 3) {
                $err = $res['response_text'] ? $res['response_text'] : "internal error, please repeat payment later";
                return array(CC_RESULT_INTERNAL_ERROR, $err, "", $log);
        } elseif ($res['summary_code'] == 2) {
                $err = $res['response_text'] ? $res['response_text'] : "card declined";
                return array(CC_RESULT_DECLINE_PERM, $err, "", $log);
        } else { // ($res['summary_code'] == 1) {
                $err = $res['response_text'] ? $res['response_text'] : "card declined";
                return array(CC_RESULT_DECLINE_PERM, $err, "", $log);
        }
    }
}

function directone_get_member_links($user){
    return cc_core_get_member_links('directone', $user);
}

function directone_rebill(){
    return cc_core_rebill('directone');
}

cc_core_init('directone');
?>
