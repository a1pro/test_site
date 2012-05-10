<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: netregistry payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2078 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_netregistry extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('netregistry', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('netregistry', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['authotize_aim']['title'] ? $config['payment']['netregistry']['title'] : _PLUG_PAY_NETREGISTRY_TITLE,
            'description' => $config['payment']['netregistry']['description'] ? $config['payment']['netregistry']['description'] : _PLUG_PAY_NETREGISTRY_DESC,
        );
    }
    function run_transaction($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url("https://4tknox.au.com/cgi-bin/themerchant.au.com/ecom/external2.pl", $vars1);
		$delim="\n";
		$arr=preg_split("($delim)",$ret);
		$res["RESULT_CODE"]=strtolower($arr[0]);
		if(@strpos($ret,"$delim.$delim")===false)
		{
			$res["RESPONSE_TEXT"]=$arr[1];		
		}
		else
		{
			$ret=substr($ret,@strpos($ret,"$delim.$delim"));
			$ret=str_replace($delim,"&",$ret);
			parse_str($ret,$ret);
			$res["RESPONSE_TEXT"]=$ret["response_text"];
			$res["TXN_REF"]=$ret["txn_ref"];
			$res["RESPONSE_CODE"]=$ret["response_code"];
			$res["RESULT"]=$ret["result"];
		}
		//var_dump($res);
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
		$cc_info['cc-expire'] = sprintf('%02d/%02d', $cc_info['cc_expire_Month'], substr($cc_info['cc_expire_Year'], 2, 2));
        srand(time());

        $vars = array(
            "LOGIN"    => $this->config['login']."/".$this->config['password'],
            "AMOUNT" =>   $amount,
            "CCNUM" => $cc_info['cc_number'],
            "CCEXP" => $cc_info['cc-expire'],
            "COMMAND"  => "purchase",			
            "COMMENT" => "Invoicenum_".$payment['payment_id'] . '-' . rand(100, 999)."__Customerid_".$member['member_id'],
        );

        // prepare log record
        $vars_l = $vars;
		$vars_l['CCNUM'] = preg_replace('/./', '*', $vars['CCNUM']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;
		
        if ($res['RESULT_CODE'] == 'approved'){
            return array(CC_RESULT_SUCCESS, "", $res['TXN_REF'], $log);
        } elseif ($res['RESULT_CODE'] == 'declined') {
            return array(CC_RESULT_DECLINE_PERM, $res['RESPONSE_TEXT'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['RESPONSE_TEXT'], "", $log);
        }
    }
}

function netregistry_get_member_links($user){
    return cc_core_get_member_links('netregistry', $user);
}

function netregistry_rebill(){
    return cc_core_rebill('netregistry');
}
                                        
cc_core_init('netregistry');
?>
