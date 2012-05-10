<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: beanstream payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1892 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_beanstream extends amember_payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('beanstream', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('beanstream', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['beanstream']['title'] ? $config['payment']['beanstream']['title'] : _PLUG_PAY_BEANSTREAM_TITLE,
            'description' => $config['payment']['beanstream']['description'] ? $config['payment']['beanstream']['description'] : _PLUG_PAY_BEANSTREAM_DESC,
            'currency' => array('usd' => 'USD', 'eur' => 'EUR'),
            'phone' => 2,
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
            'requestType'  => 'BACKEND',
            'errorPage'    => $config['root_surl'] . "/cancel.php?payment_id={$payment[payment_id]}",
            'merchant_id'  => $this->config['merchant_id'] ,
            'username'     => $this->config['username'],
            'password'     => $this->config['password'],
            
            'trnCardOwner' => $cc_info['cc_name_f'] . " " . $cc_info['cc_name_l'],
            'trnCardNumber' => $cc_info['cc_number'],
            'trnExpMonth' => substr($cc_info['cc-expire'], 0, 2),
            'trnExpYear' => substr($cc_info['cc-expire'], 2, 2),
            'trnOrderNumber' => $invoice,
            'trnAmount' => $amount,
            'ordEmailAddress' => $member['email'],
            'ordName' => $member['name_f'] . " " . $member['name_l'],
            'ordPhoneNumber' => $cc_info['cc_phone'],
            'ordAddress1' => $cc_info['cc_street'],
            'ordCity' => $cc_info['cc_city'],
            'ordProvince' => $cc_info['cc_state'],
            'ordPostalCode' => $cc_info['cc_zip'],
            'ordCountry' => $cc_info['cc_country'],
            'trnComments' => $product_description,
            'vbvEnabled'  => 0,
        );
        // VBV
        if ($charge_type != CC_CHARGE_TYPE_RECURRING) {
            $vars['TermURL'] = $config['root_surl']."/plugins/payment/beanstream/vbv.php?payment_id=$invoice";
            $vars['vbvEnabled'] = 1;
        }
        if ($cc_info['cc_code']) 
            $vars['trnCardCvd'] = $cc_info['cc_code'];
        // prepare log record
        $vars_l = $vars; 
        $vars_l['trnCardNumber'] = $cc_info['cc'];
        if ($vars['trnCardCvd'])
            $vars_l['trnCardCvd'] = preg_replace('/./', '*', $vars['trnCardCvd']);
        $vars_l['password'] = preg_replace('/./', '*', $vars['password']);
        $log[] = $vars_l;
        /////
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url("https://www.beanstream.com/scripts/process_transaction.asp?$vars1");
        parse_str($ret, $res);
        $log[] = $res;

        if ($res['trnApproved']){
            return array(CC_RESULT_SUCCESS, "", $res['trnId'], $log);
        } else {
            if ($res['responseType'] == 'R') {
                if ($charge_type == CC_CHARGE_TYPE_RECURRING)
                    return array(CC_RESULT_INTERNAL_ERROR, "VBV response returned for recurring transaction", "", $log);
                global $db;
                $res['pageContents'] = str_replace('\"', '"', $res['pageContents']);
                echo $res['pageContents'];
                
                $payment = $db->get_payment(intval($invoice));
                $log[count($log)-1]['pageContents'] = 
                    str_replace(array('<', '>'), array('&lt;', '&gt;'), 
                    $log[count($log)-1]['pageContents']);
                
                foreach ($log as $v)
                    $payment['data'][] = $v;
                $payment['data']['vbv_redirect'] = 1;
                $db->update_payment(intval($invoice), $payment);                    
                
                
                $vars = get_input_vars();
                $member = $db->get_user($payment['member_id']);
                
                if (($charge_type != CC_CHARGE_TYPE_REGULAR) && 
                    ($charge_type != CC_CHARGE_TYPE_RECURRING)){
                    save_cc_info($vars, $member, $payment['paysys_id']);
                }
                exit();

            } elseif ($res['errorType'] == 'S') 
                return array(CC_RESULT_INTERNAL_ERROR, $res['messageText'], "", $log);
            else 
                return array(CC_RESULT_DECLINE_PERM, $res['messageText'], "", $log);
        }
    }

    // VBV
    function process_postback($vars){
        global $db;
        
        $this->config['disable_postback_log'] = 1;
        
        $vars['PaRes'] = urlencode($vars['PaRes']);
        $vars['MD']    = urlencode($vars['MD']);
        $log = array();
        $log[] = $vars;
        $s = cc_core_get_url("https://www.beanstream.com/scripts/process_transaction_auth.asp".
            "?PaRes=$vars[PaRes]&MD=$vars[MD]");
        parse_str($s, $ret);
        $log[] = $ret;
        if ($ret['trnApproved']){
            $x   = array(CC_RESULT_SUCCESS, "", $ret['trnId'], $log);
        } else {
            if ($ret['errorType'] == 'S') 
                $x   = array(CC_RESULT_INTERNAL_ERROR, $ret['messageText'], "", $log);
            else 
                $x   = array(CC_RESULT_DECLINE_PERM, $ret['messageText'], "", $log);
        }        
        list($res, $err_msg, $receipt_id, $log) = $x;
        
        $payment = $db->get_payment($ret['trnOrderNumber']);
        $member  = $db->get_user($payment['member_id']);
        foreach ($log as $v)
            $payment['data'][] = $v;
        $db->update_payment($payment['payment_id'], $payment);
        if ($res == CC_RESULT_SUCCESS){
            $cc_info = array('cc_number' => amember_decrypt($member['cc-hidden']));
            $err = $db->finish_waiting_payment(
                $payment['payment_id'], $payment['paysys_id'], 
                    $receipt_id, $payment['amount'], '', cc_core_get_payer_id($cc_info, $member));
            if ($err) {
                fatal_error($err . ": payment_id = $payment[payment_id]");
            }
            /// save cc info to db
//            if ($charge_type != CC_CHARGE_TYPE_REGULAR){
//                save_cc_info($cc_info, $member, $payment['paysys_id']);
//            }
            /// display thanks page
            $product = $db->get_product($payment['product_id']);
            $t = &new_smarty();
		    $t->assign('payment', $payment);
		    if ($payment) {
		        $t->assign('product', $db->get_product($payment['product_id']));
		        $t->assign('member', $db->get_user($payment['member_id']));
		    }
		    if (!($prices = $payment['data'][0]['BASKET_PRICES'])){
		        $prices = array($payment['product_id'] => $payment['amount']);
		    }
		    $pr = array();
		    $subtotal = 0;
		    foreach ($prices as $product_id => $price){
		        $v  = $db->get_product($product_id);
//		        $v['price'] = $price;
		        $subtotal += $v['price'];
		        $pr[$product_id] = $v;
		    }
		    $t->assign('subtotal', $subtotal);
		    $t->assign('total', array_sum($prices));
		    $t->assign('products', $pr);

            $t->display("thanks.html");
        } else {
            $member  = $db->get_user($payment['member_id']);
            $v = get_cc_info_hash($member, $action = "mfp");
            $_GET = $_POST = $vars = array(
                'action' => 'mfp',
                'payment_id' => $payment['payment_id'],
                'paysys_id' => $payment['paysys_id'],
                'member_id' => $member_id,
                'v' => $v,
            );
            global $t;
            $t = new_smarty();
            foreach ($vars as $k=>$v)
                $t->_smarty_vars['request'][$k] = $v;
            ask_cc_info($member, $payment, $vars, 0, array(_PLUG_PAY_BEANSTREAM_PFAILED.$err_msg));
        }
        
    }    
}

function beanstream_get_member_links($user){
    return cc_core_get_member_links('beanstream', $user);
}

function beanstream_rebill(){
    return cc_core_rebill('beanstream');
}

cc_core_init('beanstream');
?>
