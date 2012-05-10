<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: quickpay_cc payment plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3498 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_quickpay_cc extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('quickpay_cc', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('quickpay_cc', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['quickpay_cc']['title'] ? $config['payment']['quickpay_cc']['title'] : "Quickpay_CC",
            'description' => $config['payment']['quickpay_cc']['description'] ? $config['payment']['quickpay_cc']['description'] : "Credit Card Payments",
            'code' => 1,
            'name_f' => 2
        );
    }
	
	function from_res($name,$str)
	{
		$pos=@strpos($str,"<$name>");
		if(!($pos===false))
		{
			$pos+=(strlen($name)+2);
			$res=@substr($str,$pos,@strpos($str,"</$name>",$pos)-$pos);
			return trim($res);
		}
		else
		return "";
	}

    function run_transaction($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url("https://secure.quickpay.dk/api", $vars1);
		$res = array(
			"msgtype",
			"ordernumber",
			"amount",
			"time",
			"state",
			"chstat",
			"qpstat",
			"qpstatmsg",
			"merchantemail",
			"merchant",
			"transaction",
			"cardtype",
			"md5check"			
        );
		foreach($res as $one)
			$result[$one]=$this->from_res($one,$ret);
        return $result;
    }
    function void_transaction($pnref, &$log){
        $vars = array(
            "protocol"    =>   3,
            "msgtype"  =>   "cancel",
            "merchant" => $this->config['merchant_id'],
            "transaction" =>   $pnref,
            "md5check" => md5("3cancel".$this->config['merchant_id'].$pnref.$this->config['secret'])
        );
        $vars_l = $vars;
        $log[] = $vars_l;
        $res = $this->run_transaction($vars);
        $log[] = $res;
        return $res;
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

        srand(time());
        if ($charge_type == CC_CHARGE_TYPE_TEST){
            $amount = "1.00";
		}
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }

        if(!$product_description){
			global $db;
			$product = $db->get_product($payment[product_id]);
			$product_description = $product[title];
		}
		$currency = $this->config['currency'];
		$ordernumber = $payment['payment_id'] . '-' . rand(100, 999);
        $vars = array(
            "protocol"  => "3",
            "msgtype"    => "authorize",
            "merchant" => $this->config['merchant_id'],
            "ordernumber" => $ordernumber,
            "amount" => $amount*100,
            "currency" => $currency ? $currency : 'USD',
            "autocapture" =>   "1",
            "cardnumber" => $cc_info['cc_number'],
            "expirationdate" => $cc_info['cc-expire'],
            "cvd"     => $cc_info['cc_code'],
            "md5check" => md5("3authorize".$this->config['merchant_id'].$ordernumber.
				($amount*100).($currency ? $currency : 'USD')."1".$cc_info['cc_number'].
				$cc_info['cc-expire'].$cc_info['cc_code'].($this->config['testing'] ? "1" : "").$this->config['secret']) 
        );
        
        if ($this->config['testing'])
            $vars['testmode'] = '1';

        // prepare log record
        $vars_l = $vars; 
        $vars_l['cardnumber'] = $cc_info['cc'];
        if ($vars['cvd'])
            $vars_l['cvd'] = preg_replace('/./', '*', $vars['cvd']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['qpstat'] == '000'){
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res['transaction'], $log);
            return array(CC_RESULT_SUCCESS, "", $res['transaction'], $log);
        } elseif (intval($res['qpstat']) < 9) {
            return array(CC_RESULT_DECLINE_PERM, $res['qpstatmsg'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['qpstatmsg'], "", $log);
        }
    }
}

function quickpay_cc_get_member_links($user){
    return cc_core_get_member_links('quickpay_cc', $user);
}

function quickpay_cc_rebill(){
    return cc_core_rebill('quickpay_cc');
}
                                        
cc_core_init('quickpay_cc');
?>
