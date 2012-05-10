<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: ipayment payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3525 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");


class payment_ipayment extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('ipayment', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('ipayment', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['ipayment']['title'] ? $config['payment']['ipayment']['title'] : _PLUG_PAY_IPAYMENT_TITLE,
            'description' => $config['payment']['ipayment']['description'] ? $config['payment']['ipayment']['description'] : _PLUG_PAY_IPAYMENT_DESC,
            'phone' => 2,
            'code' => 1,
            'name_f' => 2
        );
    }

    function run_transaction($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);

        $ret = cc_core_get_url($s = "https://ipayment.de/merchant/".$this->config["account_id"]."/processor.php", $vars1);
        if (preg_match('/^Status=(.+)\s?[\r\n]/', $ret, $args))
            $status = $args[1];
        $ret = preg_replace("/^Status=.+?Params=/ms", "", $ret);
        parse_str($ret, $res);
        $res['status'] = $status;
        return $res;
    }
    function get_orig_payment_ref($payment){
        if (preg_match('/RENEWAL_ORIG:\s+(\d+)$/', $payment['data'][0]['RENEWAL_ORIG'], $regs)){
            $i = $regs[1];
            if ($i > 0) {
                global $db;
                $p = $db->get_payment($i);
                if ($p['payment_id']) {
                    return $p['receipt_id'];
                } else {
                }
            }
        } 
        return '';
    }

    function validate_cc_form($vars){
		global $db;        	

		// Should be run only when user update CC info.
		if($vars[action] != 'renew_cc') return;

		$member_id = $_SESSION[_amember_id];			
		$member = $db->get_user($member_id);

		foreach((array)$db->get_user_payments($member_id, 1) as $p){
			if($p[paysys_id] == "ipayment" && $p[expire_date] >=date("Y-m-d") && !$p[data][CANCELLED]){
				$payments[] = $p;
			}
		}
		
	    $vars['cc-expire'] = sprintf('%02d%02d', $vars['cc_expire_Month'], substr($vars['cc_expire_Year'], 2, 2));

		$ret = $this->cc_bill($vars, $member, "1.00", "USD", "Update CC INFO", CC_CHARGE_TYPE_TEST, $payments[0][payment_id], $payments[0]);
		if($ret[0] != CC_RESULT_SUCCESS){
			return array($ret[1]);
		}else{
			foreach($payments as $p){
				$p[receipt_id] = $ret[2];
		        foreach ($ret[3] as $v)
		            $p['data'][] = $v;
				$db->update_payment($p[payment_id], $p);
			}		     
		}
    }
    
    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/

    
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config, $db;
        $log = array();
		$product = $db->get_product($payment[product_id]);
		if(!$product['ipayment_currency'])
		{
			if(!$this->config['currency'])
			{
				$lcurrency = $this->config['currency'];
			}
			else $lcurrency = "USD";
		}
		else $lcurrency = $product['ipayment_currency'];
        //////////////////////// cc_bill /////////////////////////
        if ($charge_type == CC_CHARGE_TYPE_RECURRING){
            $orig_id = $this->get_orig_payment_ref($payment);
            if (!$orig_id){
                    $db->log_error($e = "Cannot find payment origin ($i) for renewal payment #$payment[payment_id]");
                    $log[] = array("ERR" => "Cannot find payment origin ($i) for renewal payment #$payment[payment_id]");
                    return array(CC_RESULT_DECLINE_PERM, "", $e, $log);
            }
            $vars = array(
                'account_id'   => $this->config['account_id'],
                'trxuser_id'   => $this->config['user_id'],
                'trxpassword'  => $this->config['pass'],
                "trx_amount" =>   str_replace('.', '', sprintf('%.2f', $amount)),
                "gateway"      => 1,
                "trx_typ"        => "re_auth",
                "send_confirmation_email" => "TRUE",
                "return_paymentdata_details" => "TRUE", 
                "trx_paymenttyp" => "cc",
                "adminactionpassword" =>  $this->config['actionpass'],
                "orig_trx_number" => $orig_id
            );
        } else {
            $vars = array(
                'account_id'   => $this->config['account_id'],
                'trxuser_id'   => $this->config['user_id'],
                'trxpassword'  => $this->config['pass'],
                "gateway"      => 1,
                "trx_typ"        => "auth",
                "send_confirmation_email" => "TRUE",
                "return_paymentdata_details" => "TRUE", 
                "trx_paymenttyp" => "cc",
                "trx_amount" =>   str_replace('.', '', sprintf('%.2f', $amount)),
                "trx_currency" => $lcurrency,
                "cc_number" => $cc_info['cc_number'],
                "cc_expdate_month" => substr($cc_info['cc-expire'], 0, 2),
                "cc_expdate_year" => '20'.substr($cc_info['cc-expire'], 2, 2),
                "addr_email"    =>    $member['email'],
                "addr_name" =>     $cc_info['cc_name_f']  . ' ' . $cc_info['cc_name_l'],
                "addr_street" =>   $cc_info['cc_street'],
                "addr_city" =>     $cc_info['cc_city'],
                "addr_state" =>    $cc_info['cc_state'],
                "addr_zip" =>      $cc_info['cc_zip'],
                "addr_country" =>  $cc_info['cc_country'],
                "from_ip" => $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'],
            );
        }
        if ($charge_type == CC_CHARGE_TYPE_TEST){
            $vars['trx_typ'] = "check_save";
        	$vars["trx_amount"]  = 100;
		}

       
        if ($cc_info['cc_code'])
            $vars['cc_checkcode'] = $cc_info['cc_code'];

        // prepare log record
        $vars_l = $vars; 
        $vars_l['cc_number'] = $cc_info['cc'];
        if ($vars['cc_checkcode'])
            $vars_l['cc_checkcode'] = preg_replace('/./', '*', $vars['cc_checkcode']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;
        if ($res['status'] == '0'){
            return array(CC_RESULT_SUCCESS, "", $res['ret_booknr'], $log);
        } elseif ($res['status'] == '-1') {
            return array(CC_RESULT_DECLINE_PERM, $res['ret_errormsg']."<br/>".$res['ret_additionalmsg'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['ret_errormsg']."<br/>".$res['ret_additionalmsg'], "", $log);
        }
    }
    function payment_ipayment($config) {
        parent::payment($config);
        add_product_field('ipayment_currency',
            'iPayment Currency',
            'text',
            'valid only for iPayment processing.<br /> You should not change it<br /> if you use
            another payment processors'
            );        
    }

}

function ipayment_rebill(){
    return cc_core_rebill('ipayment');
}
function ipayment_get_member_links($user){
    return cc_core_get_member_links('ipayment', $user);
}

cc_core_init('ipayment');
?>