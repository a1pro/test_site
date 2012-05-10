<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alexander Smith
*      Email: alexander@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: gtbill payment plugin
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

class payment_gtbill extends amember_payment {
    var $title = 'GTBill';
    var $description = 'Redirect';
    var $fixed_price=0;
    var $recurring=1;
//    var $supports_trial1=0;
    var $built_in_trials=0;

    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars){
        global $config, $db, $plugin_config;

        $payment = $db->get_payment($payment_id);
        $member = $db->get_user($member_id);
        $product = & get_product($product_id);

        $this_config   = $plugin_config['payment']['gtbill'];

        $vars = array(
            'merchant_id'      => $this_config['merchant_id'],
            'site_id'          => $this_config['site_id'],
            'price_id'         => $product->config['gtbill_price_id'],
            'currency_id'      => $product->config['gtbill_currency'] ? $product->config['gtbill_currency'] : 'USD',
            'name_f'           => $member['name_f'],
            'name_l'           => $member['name_l'],
            'street'           => $member['street'],
            'city'             => $member['city'],
            'state'            => $member['state'],
            'country'          => $member['country'],
            'zip'              => $member['zip'],
            'email'            => $member['email'],
            'login'            => $member['login'].'-'.$this->get_rand(3),
            'pass'             => $member['pass'],
            'payment_id'       => $payment['payment_id']
        );

    	$t = & new_smarty();
		$t->assign('vars', $vars);
		$t->display(str_replace("c:\\", '/', dirname(__FILE__).'/form.html'));
		exit();
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
										
    function validate_ipn($vars) {

//        $ips = get_url("https://billing.GTBill.com/ip_list.txt"); //69.7.25.25|69.7.25.36|69.7.25.27
//        $ips = explode("|", $ips);
        $ips = array('216.109.158.98','216.109.158.99','216.109.158.100','216.109.158.101','216.109.158.102','216.109.158.103',
            '216.109.158.104','216.109.158.105','216.109.158.106','216.109.158.107','216.109.158.108','216.109.158.109','216.109.158.110'); // allowed IPs

        if (in_array($_SERVER['REMOTE_ADDR'], $ips))
            return 1;
        else
            return 0;
    }

    function process_postback($vars){
        global $db, $config;

        if (!$this->validate_ipn($vars)){
            $this->response("IPN validation failed", 0);
	    exit;
	}

        $action      = $vars['action'];
        $pnref       = $vars['TransactionID'];
        $invoice     = intval($vars['MerchantReference']);

        if (!$invoice && $action == 'Add'){
            $db->log_error("GTBill DEBUG (process_postback): invoice [$invoice] not found.");
            return;
        }

        $p = $db->get_payment($invoice);


        $yesterday = date('Y-m-d', time()-3600*24);

        switch ($action){
            case 'Add': //Add Member Postback Fields

                if (!$p['completed']){
                    $err = $db->finish_waiting_payment($invoice, $this->get_plugin_name(), $pnref, $p['amount'], $vars);
                    if ($err){
                        $this->response("finish_waiting_payment error [$err]", 0);
			exit;
		    }
                }
		
		$p = $db->get_payment($invoice);
		$product = & get_product($p['product_id']);
		
		if ($p['begin_date'] >= '2012-12-31')
		    $p['begin_date'] = date("Y-m-d"); //  avoid RECURRING - RECURRING subscriptions
		    
        	if ($product->config['is_recurring']){
        	    $p['expire_date'] = '2012-12-31'; // set lifetime for recurring subscriptions
        	}
		$db->update_payment($p['payment_id'], $p);
		
		$this->response("Subscription added successfully", 1);
													
                break;
           case 'Cancel':     //Cancel Member Postback Fields
           case 'Deactivate': //Deactivate Member Postback Fields

		
		$found = false;
		
		if (!$invoice)
		    $invoice = intval($vars['OMerchantReference']);
	
		if (!$invoice){ // find member by Username or Email
		    $username = $vars['Username'];
		    $email = $vars['Email'];
		    $rows = $db->users_find_by_string($username, $q_where='login', $exact=1);
		    $member_id = $rows[0]['member_id'];
		    if (!$member_id){
			$rows = $db->users_find_by_string($email, $q_where='email', $exact=1);
			$member_id = $rows[0]['member_id'];
		    }
		    if (!$member_id){
			$db->log_error("GTBill DEBUG (process_postback): member $username $email not found.");
			return;
		    }
		    foreach ($db->get_user_payments($member_id, 1) as $p){
                	if ($p['expire_date'] >= $yesterday && $p['paysys_id']==$this->get_plugin_name()){

                    	    if($action == 'Deactivate') $p['expire_date'] = $yesterday;
                    	    $p['data'][] = $vars;
    			    $p['data']['CANCELLED'] = 1;
	    		    $p['data']['CANCELLED_AT'] = strftime($config['time_format'], time());
                    	    $db->update_payment($p['payment_id'], $p);
			    $found = true;
                	}
		    }
		} else {
            	    $orig_p = $db->get_payment($invoice);
            	    if (!$orig_p['payment_id']){
			$this->response("Cannot find original payment for [$invoice]", 0);
			return;
		    }
		    $member_id = $orig_p['member_id'];

		    foreach ($db->get_user_payments($member_id, 1) as $p){
                	if (($p['product_id'] == $orig_p['product_id'])
                    	    && (($p['data']['RENEWAL_ORIG'] == "RENEWAL ORIG: $invoice") || ($p['payment_id'] == $invoice))
                    	    && ($p['expire_date'] >= $yesterday)){

                    	    if($action == 'Deactivate') $p['expire_date'] = $yesterday;
                    	    $p['data'][] = $vars;
    			    $p['data']['CANCELLED'] = 1;
	    		    $p['data']['CANCELLED_AT'] = strftime($config['time_format'], time());
                    	    $db->update_payment($p['payment_id'], $p);
			    $found = true;
                	}
            	    }
		}

                
		if ($found)
		    $this->response("User cancelled/deactivated successfully", 1);
		else
		    $this->response("User not found", 0);
                break;
           case 'Password': //Password Change Postback Fields
                //do nothing
		$this->response("Password has not been changed", 0);
                break;
           default: $this->response("Unknown status [$action]", 0);
        }
    }

    function response($msg='', $code=0){
	global $db;
	$db->log_error("GTBill response ".$code.":".$msg);
	echo $code . ":" . $msg;
    }
	    
    function get_cancel_link($payment_id){
	global $config;
	return $config['root_url']."/plugins/payment/gtbill/cancel.php?pid=".$payment_id;
    }
		

    function init(){
        parent::init();

        add_product_field(
            'gtbill_price_id', 'GTBill Price ID',
            'text', 'Subscription plan PriceID.<br />Can be sent in as a comma delimited string, Ex. 1,2,3',
            'validate_gtbill_price_id'
        );

        add_product_field(
            'gtbill_currency', 'GTBill Currency',
            'select', 'currency for GTBill gateway',
            '',
            array('options' => array(
                'USD' => 'US Dollar',
                'EUR' => 'Euro',
                'GBP' => 'Pound Sterling'
            ))
        );
    }

}

function validate_gtbill_price_id(&$p, $field){
    if ($p->config[$field] == '') {
        return "You MUST enter GTBill Price ID while you're using GTBill Plugin";
    }
    return '';
}

$pl = & instantiate_plugin('payment', 'gtbill');
?>
