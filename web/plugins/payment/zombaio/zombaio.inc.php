<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Zombaio payment plugin
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 2604 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

class payment_zombaio extends amember_payment {
    var $title = "Zombaio";
    var $description = "Credit card payments";
    var $fixed_price=0;
    var $recurring=1;
    //var $supports_trial1=0;
    //var $built_in_trials=0;
        
    function do_bill($amount, $title, $products, $member, $invoice){
        global $config, $db;
        $product = & get_product($products[0]['product_id']);
        $vars = array(
            'SITE_ID'	=> $this->config['site_id'],
            'PRICING_ID'=> $product->config['zombaio_id'],
            'LANG'		=> $this->config['lang'],
            'FirstName'	=> $member['name_f'],
            'LastName'	=> $member['name_l'],
            'Address'	=> $member['street'],
            'Postal'	=> $member['zip'],
            'City'		=> $member['city'],
            'Email'		=> $member['email'],
            'Username'	=> $member['login'],
            'Password'	=> $member['pass'],
			'INVOICE'	=> $invoice
        );
		$t = &new_smarty();
		$t->assign('vars', $vars);
		$t->display(dirname(__FILE__) . '/zombaio.html');
    }
	
    function process_thanks(&$vars){
    }
	function process_postback($vars){
        global $db, $config;
        if(!$vars['SUBSCRIPTION_ID'] && $vars['SubscriptionID'])$vars['SUBSCRIPTION_ID'] = $vars['SubscriptionID'];
        if($vars['ZombaioGWPass'] != $this->config['password'])
		{
			echo "<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed.</h3>";
			exit;
		}
		switch ($vars['Action']) {
		case 'user.add':
			$payment_id=$vars['variable1'];
			$p=$db->get_payment($payment_id);
			$pr=$db->get_product($p['product_id']);
			if (!$p['completed']){
				$err=$db->finish_waiting_payment($p['payment_id'], $this->get_plugin_name(), $vars['SUBSCRIPTION_ID'].'|'.$vars['TRANSACTION_ID'], '', $vars);
			}
			break;
		case 'rebill' :
			if($payment_id=$vars['variable1'])
				$p = $this->get_last_payment($p['payment_id']);
			else{
				$last_payment_id = $db->query_one($s="SELECT payment_id from {$db->config['prefix']}payments 
					where SUBSTR(receipt_id,1,LOCATE('|',receipt_id)-1)='$vars[SUBSCRIPTION_ID]' order by expire_date desc LIMIT 1");
				$p = $db->get_payment($last_payment_id);
			}
			$begin_date = $p['expire_date'];
			$pr=$db->get_product($p['product_id']);
			
			$duration = $this->get_days($pr['expire_days']) * 3600 * 24;
			$expire_date = date('Y-m-d', strtotime($begin_date) + $duration);

			$newp = array();
			$newp['member_id']   = $p['member_id'];
			$newp['product_id']  = $p['product_id'];
			$newp['paysys_id']   = $this->get_plugin_name();
			$newp['receipt_id']  = $vars['SUBSCRIPTION_ID'].'|'.$vars['TRANSACTION_ID'];
			$newp['begin_date']  = $begin_date;
			$newp['expire_date'] = $expire_date;
			$newp['amount']      = $pr['price'];
			
			$newp['completed']   = 1;
			$newp['data']['RENEWAL_ORIG'] = "RENEWAL ORIG: $p[payment_id]";
			$new_payment_id = $db->add_waiting_payment(
				$newp['member_id'],
				$newp['product_id'],
				$newp['paysys_id'],
				$newp['amount'],
				$newp['begin_date'],
				$newp['expire_date'],
				$newp['data']
				);
			$err = $db->finish_waiting_payment($new_payment_id, $this->get_plugin_name(), $vars['SUBSCRIPTION_ID'].'|'.$vars['TRANSACTION_ID'], '', $vars);
			if($err) $db->log_error("Zombaio finish_waiting_payment error - $err");
            break;
        }
        echo "OK";
	}
    function get_days($orig_period){
    	$ret = 0;
        if (preg_match('/^\s*(\d+)\s*([y|Y|m|M|w|W|d|D]{0,1})\s*$/', $orig_period, $regs)){
            $period = $regs[1];
            $period_unit = $regs[2];
            if (!strlen($period_unit)) $period_unit = 'd';
            $period_unit = strtoupper($period_unit);

            switch ($period_unit){
                case 'Y':
                    $ret = $period * 365;
                    break;
                case 'M':
                    $ret = $period * 30;
                    break;
                case 'W':
                    $ret = $period * 7;
                    break;
                case 'D':
                    $ret = $period;
                    break;
                default:
                    fatal_error(sprintf("Unknown period unit: %s", $period_unit));
            }
        } else {
            fatal_error("Incorrect value for expire days: ".$orig_period);
        }
        return $ret;
    }
	
	
	function get_last_payment($invoice){
        global $db;
        $orig_p = $db->get_payment($invoice);
        $p_id   = $orig_p['payment_id'];
        foreach ($db->get_user_payments($orig_p['member_id'], 1) as $p){
            if (($p['product_id'] == $orig_p['product_id'])
                && ($p['data'][0]['RENEWAL_ORIG'] == "RENEWAL ORIG: $invoice")
                && ($p['expire_date'] > $orig_p['expire_date'])) {
                $p_id = $p['payment_id'];
                }
        }
		if($p_id!=$invoice)
			return $this->get_last_payment($p_id);
		else
		{
        	return $db->get_payment($p_id);
		}
    }
    function get_next_begin_date($invoice){
        global $db;
        $orig_p = $db->get_payment($invoice);
        $ret = $orig_p['expire_date'];
        foreach ($db->get_user_payments($orig_p['member_id'], 1) as $p){
            if (($p['product_id'] == $orig_p['product_id'])
                && ($p['data']['RENEWAL_ORIG'] == "RENEWAL ORIG: $invoice")
                && ($p['expire_date'] > $ret))
                $ret = $p['expire_date'];
        }
	if ($ret >= '2012-12-31')
        	$ret = date("Y-m-d");
        return $ret;
    }

    
    function init(){
        parent::init();
        add_product_field(
            'zombaio_id', 'Zombaio product PRICING ID',
            'text', 'you must create the same product<br />
             in Zombaio and enter its number here',
             'validate_zombaio_id'
           );
		
    }
}
function validate_zombaio_id(&$p, $field){  
    if ((intval($p->config[$field]) <= 0)  && ($p->config['paysys_id'] == 'zombaio' || $p->config['paysys_id'] == '')) {
        return "You MUST enter Zombaio Product ID while you're using Zombaio payment";
    }
    return '';
}

instantiate_plugin('payment', 'zombaio');
?>