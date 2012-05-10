<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

add_paysystem_to_list(
array(
            'paysys_id' => 'manual_euro_bank',
            'title'     => $config['payment']['manual_euro_bank']['title'],
            'description' => $config['payment']['manual_euro_bank']['description'],
            'public'    => 1,
            'fixed_price' => 0
        )
);

/// add member fields
add_member_field('bank_name','Account Holder Name','text','','',array());
add_member_field('bank_an','Account number','text','','',array());
add_member_field('bank_bic','Bank Identifier Code','text','','',array());
add_member_field('bank_bank_name','Bank Name','text','','',array());
add_member_field('bank_phone', 'Phone','text','','',array());

add_product_field('is_recurring','Recurring Billing','checkbox','should user be charged automatically<br /> when subscription expires');
add_product_field('trial1_days','Trial 1 Duration','period','trial period duration');
add_product_field('trial1_price','Trial 1 Price','money','enter 0.0 to offer free trial');

class payment_manual_euro_bank extends payment {
    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars)
    {
        global $db, $config, $plugin_config;      
		$pm = $db->get_payment($payment_id);
		$member = $db->get_user($member_id);
		
		if (!$vars['name'])
		$vars['name'] = $member['name_f']." ".$member['name_l'];
		if (!$vars['street'])
		$vars['street'] = $member['street'];
		if (!$vars['city'])
		$vars['city'] = $member['city'];
		if ($manual_euro_config['state'])
			if (!$vars['state']) $vars['state'] = $member['state'];
		if (!$vars['zip'])
		$vars['zip'] = $member['zip'];		
		if (!$vars['country'])
		$vars['country'] = $member['country'];

        $t = & new_smarty();
        $t->assign(array(
            'this_config' => $this->config,
            'currency' => $config['currency'],
            'title' => $this->config['title'],
            'description' => $this->config['description'],
            'member_id' => $member['member_id'],
            'payment' => $pm,
            'payment_id' => $pm['payment_id'],
            'product_id' => $pm['product_id'],
            'product' => $db->get_product($pm['product_id']),
            'manual_euro' => $vars,
            'required_address' => $this->config['required_address'],
            'error' => $vars['error'],
            'manual_euro_config' => $this->manual_euro_get_config()
        ));
        $t->display(dirname(__FILE__).'/manual_euro_bank.html');
        exit();
    }
    
    function manual_euro_get_config()
    {
        return array("phone" => true);
    }
    
    function signup_moderator_mail($payment_id,$signup)
    {
        global $config,$db;
        $payment = $db->get_payment($payment_id);
        $member = $db->get_user($payment['member_id']);
        $manual_euro = $member["data"];
        $product = $db->get_product($payment['product_id']);
        $admin_url = $config['root_url'] . '/admin';
        $manual_euro_config = $this->manual_euro_get_config();
        
        if ($signup)
        {
            $mail_subject = _PLUG_PAY_MANUAL_EURO_BANK_MAIL_SUBJ_NEW;
        } else {
            $mail_subject = _PLUG_PAY_MANUAL_EURO_BANK_MAIL_SUBJ_REBILL;
        }

        $t = & new_smarty();
        $t->assign(array(
                    'currency' => $config['currency'],
                    'manual_euro' => $manual_euro,
                    'member' => $member,
                    'product' => $product,
                    'signup' => $signup,
                    'payment' => $payment,
                    'admin_url' => $admin_url,
                    'required_address' => $this->config['required_address'],
                    'manual_euro_config' => $manual_euro_config
        ));
        $mail_body = $t->fetch($config['root_dir']."/plugins/payment/manual_euro_bank/email.html"); 
        mail_admin($mail_body, $mail_subject);
    }
    
    function submit($manual_euro)
    {
        global $db, $config;
        $payment_id = intval($manual_euro['payment_id']);
        if (!$payment_id)
        fatal_error("payment id is empty");
        if (!($payment = $db->get_payment($payment_id)))
        fatal_error("no such payment id");
        $member     = $db->get_user($payment['member_id']);
        $manual_euro_config = $this->manual_euro_get_config();

        $manual_euro["an"] = preg_replace('/\D+/', '', $manual_euro["an"]);
        $manual_euro["bic"] = preg_replace('/\D+/', '', $manual_euro["bic"]);

        $banktransfer_validation = new AccountCheck;
        $banktransfer_result = $banktransfer_validation->CheckAccount($manual_euro["an"], $manual_euro["bic"]);
        
        if ($banktransfer_result != 0)
        {
            $manual_euro["error"][] = _PLUG_PAY_MANUAL_EURO_BANK_ERROR2;
        }
        $address_error = false;
        if (($this->config['required_address']) && ((($manual_euro["street"] == '') || ($manual_euro["city"] == '') || ($manual_euro["zip"] == '') || ($manual_euro["country"] == '')) || ($manual_euro_config['phone'] && ($manual_euro["phone"] == '')) || ($manual_euro_config['state'] && ($manual_euro["state"] == ''))))
        {
            $manual_euro["error"][] = _PLUG_PAY_MANUAL_EURO_BANK_ERROR3;
            $address_error = true;
        }
        // check Account Holder Name
        if ($manual_euro['name'] == '')
        {
            $manual_euro["error"][] = _PLUG_PAY_MANUAL_EURO_BANK_ERROR5;
            $address_error = true;
        }
        
        if ($banktransfer_result == 0 && !$address_error)
        {
            $member['data']['bank_name'] = $manual_euro['name'];
            $member['data']['bank_an'] = $manual_euro['an'];
            $member['data']['bank_bic'] = $manual_euro['bic'];
            $member['data']['bank_bank_name'] = $banktransfer_validation->Bankname;
            if ($manual_euro_config['phone'])
            $member['data']['bank_phone'] = $manual_euro['phone'];

            $member['street'] = $manual_euro['street'];
            $member['city'] = $manual_euro['city'];
            if ($manual_euro_config['state'])
            $member['state'] = $manual_euro['state'];
            $member['zip'] = $manual_euro['zip'];
            $member['country'] = $manual_euro['country'];
            
            $db->update_user($member['member_id'], $member);
            $this->signup_moderator_mail($payment_id,$signup=true);

            $t = & new_smarty();
            $t->assign(array(
                "manual_euro" => $manual_euro,
                'member'  => $member,
                'payment' => $payment,
                "product" => $db->get_product($payment['product_id'])
            ));     
            $t->display(dirname(__FILE__).'/thanks.html');
        } else {
            $manual_euro["error"] = array("Incorrect Account number or Bank Identifier Code");
            $this->do_payment($payment_id, $member["member_id"], $payment['product_id'], $payment['amount'],
            $payment['begin_date'],$payment['expire_date'], $manual_euro);
        }
    }
    
    function rebill($dat=''){
        global $config, $db, $t, $cc_core;
        if ($dat == '') 
            $dat = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime($dat) + 3600 * 24);
        $plugin = 'manual_euro_bank';
        $payments = $db->get_expired_payments($dat, $dat, $plugin);
        foreach ($payments as $p){
            if ($p['data']['CANCELLED']) 
                continue;
            $product = get_product($p['product_id']);
            if (!$product->config['is_recurring']) continue;
            
            $pc = & new PriceCalculator();
            $pc->addProduct($p['product_id']);
            $pc->setTax(get_member_tax($p['member_id']));
            $terms = & $pc->calculate();
            
            $pp = array(
	            'member_id' => $p['member_id'],
	            'product_id' => $p['product_id'],
	            'paysys_id' => $p['paysys_id'],
	            'begin_date' => $dat,
	            'expire_date' => $product->get_expire($dat),
	            'amount' => calculate_price($p['product_id'], $p['member_id'], $vars, $prices),
	        );
	        if ($err=$db->add_payment($pp)) trigger_error("Cannot add payment : $err", E_USER_WARNING);
	        $payment_id = $GLOBALS['_amember_added_payment_id'];
            $this->signup_moderator_mail($payment_id, $signup=false);
        }           
    }
}

function manual_euro_bank_daily(){
    global $db;
    $pl = & instantiate_plugin('payment', 'manual_euro_bank');
    $pl->rebill();
}

setup_plugin_hook('daily', 'manual_euro_bank_daily');
