<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alexander Smith
*      Email: alexander@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: EPX payment plugin
*    FileName $RCSfile$
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_epx extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('epx', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('epx', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['epx']['title'] ? $config['payment']['epx']['title'] : "EPX",
            'description' => $config['payment']['epx']['description'] ? $config['payment']['epx']['description'] : "Credit card payment",
            'code' => 2,
            'name_f' => 2
        );
    }
	function from_res($name,$str)
	{
		$pos=@strpos($str,$name);
		if(!($pos===false))
		{
			$pos+=(strlen($name)+2);
			$res=@substr($str,$pos,@strpos($str,"</FIELD>",$pos)-$pos);
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
        $ret = cc_core_get_url("https://secure.epx.com/", $vars1);
//        $ret = cc_core_get_url("https://secure.epxuap.com/", $vars1);
        $res = array(
            'AUTH_RESP' => $this->from_res("AUTH_RESP",$ret),
            'AUTH_GUID' => $this->from_res("AUTH_GUID",$ret),
            'AUTH_CODE' => $this->from_res("AUTH_CODE",$ret),
            'AUTH_AVS' => $this->from_res("AUTH_AVS",$ret),
            'AUTH_CVV2' => $this->from_res("AUTH_CVV2",$ret),
            'AUTH_RESP_TEXT' => $this->from_res("AUTH_RESP_TEXT",$ret),
            'AUTH_CARD_TYPE' => $this->from_res("AUTH_CARD_TYPE",$ret),
            'TRAN_TYPE' => $this->from_res("TRAN_TYPE",$ret),
        );
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
		$x_Type = "AUTH_CAPTURE";

        if ($charge_type == CC_CHARGE_TYPE_TEST){
            $amount = "1.00";
			$x_Type = "AUTH_ONLY";
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
	        $vars = array(
			"CUST_NBR"=>$this->config['cust_nbr'],
			"MERCH_NBR"=>$this->config['merch_nbr'],
			"DBA_NBR"=>"2",
			"TERMINAL_NBR"=>"1",			
			"TRAN_TYPE"=>"CCE1",
			"ACCOUNT_NBR"=>$cc_info['cc_number'],
			"CVV2"=>$cc_info['cc_code'],
			"EXP_DATE"=>substr($cc_info['cc-expire'], 2, 2).substr($cc_info['cc-expire'], 0, 2),
			"CARD_ENT_METH"=>"X",
			"BATCH_ID"=>date("Ymd"),
			"TRAN_NBR"=>$invoice,
			"AMOUNT"=>sprintf("%.2f",$amount),
			);

        
//        if ($cc_info['cc_code'])
//            $vars['ACCOUNT_NBR'] = $cc_info['cc_code'];

        // prepare log record
        $vars_l = $vars; 
        $vars_l['ACCOUNT_NBR'] = $cc_info['cc'];
        if ($vars['ACCOUNT_NBR'])
            $vars_l['ACCOUNT_NBR'] = preg_replace('/./', '*', $vars['ACCOUNT_NBR']);
        if ($vars['CVV2'])
            $vars_l['CVV2'] = preg_replace('/./', '*', $vars['CVV2']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['AUTH_RESP'] == '00'){
            return array(CC_RESULT_SUCCESS, "", $res['AUTH_GUID'], $log);
        }else{
            return array(CC_RESULT_DECLINE_PERM, $res['AUTH_RESP_TEXT'], "", $log);
        }
    }
}

function epx_get_member_links($user){
    return cc_core_get_member_links('epx', $user);
}

function epx_rebill(){
    return cc_core_rebill('epx');
}
                                        
cc_core_init('epx');



?>
