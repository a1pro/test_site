<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Sagepayments payment plugin
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 3289 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_sagepayments extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('sagepayments', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('sagepayments', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['sagepayments']['title'] ? $config['payment']['sagepayments']['title'] : "Sagepayments",
            'description' => $config['payment']['sagepayments']['description'] ? $config['payment']['sagepayments']['description'] : "Credit card",
            'code' => 1,
            'name_f' => 1,
			"no_recurring" => 1
        );
    }
    function run_transaction($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url("https://www.sagepayments.net/cgi-bin/eftBankcard.dll?transaction", $vars1);
        $res = array(
            'ApprovalIndicator' => @substr($ret,1,1),
            'Code'  => @substr($ret,2,6),
            'Message' => @substr($ret,8,32),
            'CVVIndicator' => @substr($ret,42,1),
            'AVSIndicator' => @substr($ret,43,1),
            'RiskIndicator' => @substr($ret,44,2),
            'Reference' => @substr($ret,46,10),
        );
        return $res;
    }
    /*********************************************
      cc_bill - do real cc bill
    *********************************************/
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        srand(time());
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
            "M_id"    => $this->config['id'],
            "M_key"  => $this->config['key'],
            "C_name" => $cc_info['cc_name_f'],
            "C_address" => $cc_info['cc_street'],
            "C_city" => $cc_info['cc_city'],
            "C_state" => $cc_info['cc_state'],
            "C_zip" =>   $cc_info['cc_zip'],
            "C_country" => $cc_info['cc_country'],
            "C_email"     => $member['email'],
            "C_cardnumber" => $cc_info['cc_number'],
            "C_exp" => $cc_info['cc-expire'],
            "T_amt" => sprintf('%0.2f', $amount),
            "T_code" => "01",
			);
        
        /*if ($cc_info['cc_code'])
            $vars['C_cardnumber'] = $cc_info['cc_code'];*/

        // prepare log record
        $vars_l = $vars; 
        if ($vars['C_cardnumber'])
            $vars_l['C_cardnumber'] = preg_replace('/./', '*', $vars['C_cardnumber']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['ApprovalIndicator'] == 'A'){
            return array(CC_RESULT_SUCCESS, "", $res['Reference'], $log);
        } elseif ($res['ApprovalIndicator'] == 'E') {
            return array(CC_RESULT_DECLINE_PERM, $res['Message'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['Message'], "", $log);
        }
    }
}

function sagepayments_get_member_links($user){
    return cc_core_get_member_links('sagepayments', $user);
}

function sagepayments_rebill(){
    return cc_core_rebill('sagepayments');
}
                                        
cc_core_init('sagepayments');
?>
