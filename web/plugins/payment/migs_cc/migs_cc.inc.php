<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: migs_cc payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3289 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_migs_cc extends payment {
    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('migs_cc', $payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, $vars);
    }

    function get_cancel_link($payment_id){
        global $db;
        return cc_core_get_cancel_link('migs_cc', $payment_id);
    }

    function get_plugin_features(){
        return array(
            'title' => $config['payment']['migs_cc']['title'] ? $config['payment']['migs_cc']['title'] : _PLUG_PAY_MIGS_CC_TITLE,
            'description' => $config['payment']['migs_cc']['description'] ? $config['payment']['migs_cc']['description'] : _PLUG_PAY_MIGS_CC_DESC,
            'phone' => 2,
            'company' => 1,
            'code' => 1,
            'name_f' => 2
        );
    }

    function get_rand($length){
        $all_g = "ABCDEFGHIJKLMNOPQRSTWXZ";
        $pass = "";
        srand((double)microtime()*1000000);
        for($i=0;$i<$length;$i++) {
            srand((double)microtime()*1000000);
            $pass .= $all_g[ rand(0, strlen($all_g) - 1) ];
        }
        return $pass;
    }

    function run_transaction($vars){
        global $db;

        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url("https://migs.mastercard.com.au/vpcdps", $vars1);

        parse_str($ret, $arr);

        $result = -1; // undefined
        $response = "";
        switch ($arr['vpc_TxnResponseCode']){        	case '0':
        	    $result = 1;
        	    $response = "Transaction approved";
        	    break;        	case '1':
        	    $response = "Transaction could not be processed";
        	    break;
            case '2':
        	    $result = 2;
                $response = "Transaction declined - contact issuing bank";
        	    break;
            case '3':
                $response = "No reply from Processing Host";
        	    break;
            case '4':
                $response = "Card has expired";
        	    break;
            case '5':
                $response = "Insufficient credit";
        	    break;
            case '6':
                $response = "Error Communicating with Bank";
        	    break;
            case '7':
                $response = "Message Detail Error Invalid PAN, Invalid Expiry Date";
        	    break;
            case '8':
                $response = "Transaction declined – transaction type not supported";
        	    break;
            case '9':
                $response = "Bank Declined Transaction – Do Not Contact Bank";
        }

        if ($arr['vpc_TxnResponseCode'] != '0')
            $db->log_error("MIGS CC Error: payment_id=" . $arr['vpc_OrderInfo'] . ", code=" . $arr['vpc_TxnResponseCode'] . " (" . $response . "), message='" . $arr['vpc_Message']."'");

        $res = array(
            'RESULT'      => $result,
            'RESPMSG'     => $response,
            'AMOUNT'      => $arr['vpc_Amount'],
            'INVOICE'     => $arr['vpc_OrderInfo'],
            'PNREF'       => $arr['vpc_TransactionNo']
        );
        return $res;
    }

    function void_transaction($pnref, &$log){
        return; // do nothing
    }

    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, $currency, $product_description, $charge_type, $invoice, $payment){
        global $db, $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        if ($charge_type == CC_CHARGE_TYPE_TEST){
            //$amount = "1.00";
	    }

        $vars = array(
            'vpc_Version'     => '1',
            'vpc_Command'     => 'pay',
            'vpc_MerchTxnRef' => $invoice.'-'.$this->get_rand(3),
            'vpc_AccessCode'  => $this->config['access_code'],
            'vpc_Merchant'    => $this->config['merchant_id'],
            'vpc_OrderInfo'   => $invoice,
            'vpc_Amount'      => intval($amount * 100),
            'vpc_CardNum'     => $cc_info['cc_number'],
            'vpc_CardExp'     => $cc_info['cc-expire']
        );

        if ($cc_info['cc_code']){
            $vars['vpc_CardSecurityCode'] = $cc_info['cc_code'];

            // You may set this value to the minimum CSC level that you are willing to accept for this
            // transaction. If you do not set a value, your default value will be used.
            //$vars['vpc_CSCLevel'] = ''; // Optional.
        }

        // prepare log record
        $vars_l = $vars;
        $vars_l['vpc_CardNum'] = $cc_info['cc']; // get_visible_cc_number($cc_info['cc_number']);
        if ($vars['vpc_CardSecurityCode'])
            $vars_l['vpc_CardSecurityCode'] = preg_replace('/./', '*', $vars['vpc_CardSecurityCode']);
        $vars_l['vpc_AccessCode'] = preg_replace('/./', '*', $vars['vpc_AccessCode']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['RESULT'] == '1'){
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res['PNREF'], $log);
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif ($res['RESULT'] == '2') {
            return array(CC_RESULT_DECLINE_PERM, $res['RESPMSG'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['RESPMSG'], "", $log);
        }
    }
}

function migs_cc_get_member_links($user){
    return cc_core_get_member_links('migs_cc', $user);
}

function migs_cc_rebill(){
    return cc_core_rebill('migs_cc');
}

cc_core_init('migs_cc');
?>