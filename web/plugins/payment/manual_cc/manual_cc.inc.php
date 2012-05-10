<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: PayFlow PRO payment plugin class
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3311 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember is free for both commercial and non-commercial use providing that the
* copyright headers remain intact and the links remain on the html pages.
* Re-distribution of this script without prior consent is strictly prohibited.
*
*/

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_manual_cc extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('manual_cc', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('manual_cc', $payment_id);
    }
    function get_plugin_features(){
        global $config;
        return array(
            'title' => $config['payment']['manual_cc']['title'] ? $config['payment']['manual_cc']['title'] : _PLUG_PAY_MANCC_TITLE,
            'description' => $config['payment']['manual_cc']['description'] ? $config['payment']['manual_cc']['description'] : _PLUG_PAY_MANCC_DESC,
            'phone' => 2,
            'name_f' => 2, 
            'name_l' => 2,
			'code' => 2,
			'type_options' => array('VISA' => 'Visa', 'MASTERCARD' => 'MasterCard', 'AMEX' => 'American Express')
        );
    }
    
    function manual_cc_mail($cc_info,$member,$payment,$charge_type)
    {
        global $config,$db;
        $features = $this->get_plugin_features();
        $product = $db->get_product($payment['product_id']);
        $admin_url = $config['root_url'] . '/admin';

		if ($charge_type != CC_CHARGE_TYPE_RECURRING)
		{
			$mail_subject = "*** New Manual Credit Card Signup ***";
		} else {
			$mail_subject = "*** New Manual Credit Card Recurring Billing ***";
		}
		
        $t = & new_smarty();
        $t->assign(array(
                    'this_config' => $cc_info,
					'currency' => $config['currency'],
                    'member' => $member,
                    'product' => $product,
                    'charge_type' => $charge_type,
                    'payment' => $payment,
                    'admin_url' => $admin_url,
                    'cc_info' => $cc_info,
					'cc_type' => $features['type_options'][$cc_info['cc_type']],
                    'cc_number' => get_visible_cc_number($cc_info["cc_number"]),
                    'cc_expire' => sprintf('%02d%02d', $cc_info['cc_expire_Month'], substr($cc_info['cc_expire_Year'], 2, 2))
        ));
        $mail_body = $t->fetch($config['root_dir']."/plugins/payment/manual_cc/email.html");
        
        mail_admin($mail_body,$mail_subject);
    }

    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config,$db;
        $log = array();
        
        if (!$this->config["notification"])
            $this->config["notification"] = 3;  
        
        if ($this->config["notification"] == 1 || $this->config["notification"] == 3)
        $this->manual_cc_mail($cc_info,$member,$payment,$charge_type);
        
        if ($this->config["notification"] == 2 || $this->config["notification"] == 3)
        {
            if ($charge_type != CC_CHARGE_TYPE_RECURRING)
            save_cc_info($cc_info,$member,$payment['paysys_id']);
        }
        
        if ($charge_type != CC_CHARGE_TYPE_RECURRING)
        {
            $t = & new_smarty();
            $product = $db->get_product($payment['product_id']);
            $t->assign('payment', $payment);
            $t->assign('product', $product);
            $t->assign('user', $member);
            if (!($prices = $payment['data'][0]['BASKET_PRICES'])){
                $prices = array($payment['product_id'] => $payment['amount']);
            }
            $pr = array();
            $subtotal = 0;
            foreach ($prices as $product_id => $price){
                $v  = $db->get_product($product_id);
                $subtotal += $v['price'];
                $pr[$product_id] = $v;
            }
            $t->assign('subtotal', $subtotal);
            $t->assign('total', array_sum($prices));
            $t->assign('products', $pr);
            $t->display($config['root_dir']."/plugins/payment/manual_cc/thanks.html");
            exit;
        }
        
        return array(CC_RESULT_IGNORE, "", "", array());
    }
}

function manual_cc_get_member_links($user){
    return cc_core_get_member_links('manual_cc', $user);
}

function manual_cc_rebill(){
    return cc_core_rebill('manual_cc');
}

cc_core_init('manual_cc');

?>
