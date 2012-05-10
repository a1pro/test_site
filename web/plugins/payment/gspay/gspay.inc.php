<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3601 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*/






class payment_gspay extends amember_payment {
    var $title       = "GSpay";
    var $description = "Credit card payment";
    var $fixed_price = 0;
    var $recurring   = 1;
    var $built_in_trials   = 1;

    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config;
        global $db;
        $product = $db->get_product($product_id);

        $vars = array(
            'SITEID' => $this->config['siteid'],
            'ORDERID'        => $payment_id,
			'DESCRIPTION' => $product['title'],
			'RETURNURL' => $config['root_url']."/plugins/payment/gspay/thanks.php",
			'TEST' => $this->config['testing'] ? '<INPUT TYPE="HIDDEN" NAME="TransactionMode" VALUE="test">' : '',
        );
		$vars['RECURRING_FIELDS']='';
		
		
		//*******************
		$pc = & new PriceCalculator();
		$pc->setTax(get_member_tax($u['member_id']));
		$pc->setPriceFields(array('trial1_price'));
		
		$p = $db->get_payment($payment_id);
		$coupon_code = $p['data'][0]['COUPON_CODE'];
		if ($config['use_coupons'] && $coupon_code != ''){
			$coupon = $db->coupon_get($coupon_code);
			$pc->setCouponDiscount($coupon['discount'], split(',',trim($coupon['product_id'])));
		}
		$pc->addProduct($product_id);
		$terms = $pc->calculate();
		$trial_amount = $terms->total;
		//*******************
		$pc = & new PriceCalculator();
		$pc->setTax(get_member_tax($u['member_id']));
		$pc->setPriceFields(array('price'));
		if ($config['use_coupons'] && $coupon_code != ''){
			$coupon = $db->coupon_get($coupon_code);
			$pc->setCouponDiscount($coupon['discount'], split(',',trim($coupon['product_id'])));
		}
		$pc->addProduct($product_id);
		$terms = $pc->calculate();
		$amount = $terms->total;
		//*******************
		
		//print_r($terms);die;

		if($product['trial1_days'])$product['trial1_days']=$this->get_days($product['trial1_days']);
		$product['expire_days']=$this->get_days($product['expire_days']);
		
	
		if($product->config['is_recurring'])
		{
			if($product['trial1_days'])
			{
				if($product['trial1_price'])
				{
					if ($config['use_coupons'] && $coupon_code != '')
						$vars['RECURRING_FIELDS'].="
						<input type=\"hidden\" name=\"Amount\" value=\"$trial_amount\">";
					else
						$vars['RECURRING_FIELDS'].="
						<input type=\"hidden\" name=\"Amount\" value=\"$product[trial1_price]\">";
				}
				else
				{
					if ($config['use_coupons'] && $coupon_code != '' && $coupon['is_recurring'])
						$vars['RECURRING_FIELDS'].="
						<input type=\"hidden\" name=\"Amount\" value=\"$amount\">
						<input type=\"hidden\" name=\"TrialDuration\" value=\"$product[trial1_days]\">";
					else
						$vars['RECURRING_FIELDS'].="
						<input type=\"hidden\" name=\"Amount\" value=\"$product[price]\">
						<input type=\"hidden\" name=\"TrialDuration\" value=\"$product[trial1_days]\">";
				}
				if ($config['use_coupons'] && $coupon_code != '' && $coupon['is_recurring'])
					$vars['RECURRING_FIELDS'].="
					<input type=\"hidden\" name=\"RebillAmount\" value=\"$amount\">
					<input type=\"hidden\" name=\"Duration\" value=\"$product[duration]\">
					";
				else
					$vars['RECURRING_FIELDS'].="
					<input type=\"hidden\" name=\"RebillAmount\" value=\"$product[price]\">
					<input type=\"hidden\" name=\"Duration\" value=\"$product[duration]\">
					";
					
			}
			else
			{
				if ($config['use_coupons'] && $coupon_code != '' )
				{
					if( $coupon['is_recurring'])
					$vars['RECURRING_FIELDS'].="
					<input type=\"hidden\" name=\"Amount\" value=\"$amount\">
					<input type=\"hidden\" name=\"RebillAmount\" value=\"$amount\">
					<input type=\"hidden\" name=\"Duration\" value=\"$product[duration]\">
					";
					else
					$vars['RECURRING_FIELDS'].="
					<input type=\"hidden\" name=\"Amount\" value=\"$amount\">
					<input type=\"hidden\" name=\"RebillAmount\" value=\"$product[price]\">
					<input type=\"hidden\" name=\"Duration\" value=\"$product[duration]\">
					";
				}
				else
				$vars['RECURRING_FIELDS'].="
				<input type=\"hidden\" name=\"Amount\" value=\"$product[price]\">
				<input type=\"hidden\" name=\"RebillAmount\" value=\"$product[price]\">
				<input type=\"hidden\" name=\"Duration\" value=\"$product[duration]\">
				";
			}
		}
		else
		{
			if ($config['use_coupons'] && $coupon_code != '')
			$vars['RECURRING_FIELDS'].="
			<input type=\"hidden\" name=\"Amount\" value=\"$amount\">
			";
			else
			$vars['RECURRING_FIELDS'].="
			<input type=\"hidden\" name=\"Amount\" value=\"$product[price]\">
			";
		}
		
		$t = &new_smarty();
		$t->assign('vars', $vars);
		$t->display(dirname(__FILE__) . '/gspay.html');
    }
    
    function log_debug($vars){
        global $db;
        $s = "GSPAY DEBUG:<br />\n";
        foreach ($vars as $k=>$v)
            $s .= "[$k] => '$v'<br />\n";
        $db->log_error($s);
    }

    function process_thanks(&$vars){
		global $db;
        //$this->log_debug($vars);
		$t = new_smarty();
		$p=$db->get_payment($vars['customerOrderID']);
		$pr=$db->get_payment($p['product_id']);
		$t->assign('payment', $p);
		$t->assign('product', $pr);
		$t->assign('member',  $db->get_user($p['member_id']));
		
		/*if ($vars['intStatus'] != '1'){   
			$t->display("cancel.html");
			exit();
		}*/
		$t->display("thanks.html");
    }
    function handle_postback(&$vars){
		global $db;
        $this->log_debug($vars);
		if ($vars['key'] != $this->config['key'])
		{
			$db->log_error("GSpay validation error. Please check callback script key.");
			return "GSpay validation error. Please check callback script key.]";
		}
		if ($vars['transactionType'] != 'sale' 
			|| ($vars['transactionStatus'] != 'approved' && $vars['transactionStatus'] != 'test')
			|| $vars['action'] != 'adduser')
		{
			$db->log_error("GSpay error: $vars[transactionReturnMsg]");
			return "GSpay error: $vars[transactionReturnMsg]";
		}
		$p=$db->get_payment($vars['OrderID']);
		if (!$p['completed']){
			$error = $db->finish_waiting_payment($p['payment_id'], "gspay", $vars['transactionID'], '', $vars);
			if($error) $db->log_error("Gspay finish_waiting_payment error - $error");
		}
		else
		{
			$pr = $db->get_product($p['product_id']);
			$last_payment = $this->get_last_payment($p['payment_id']);
			$begin_date = $last_payment['expire_date'];
			
			$duration = $this->get_days($pr['expire_days']) * 3600 * 24;
			$expire_date = date('Y-m-d', strtotime($begin_date) + $duration);
			$newp = array();
			$newp['member_id']   = $p['member_id'];
			$newp['product_id']  = $p['product_id'];
			$newp['paysys_id']   = "gspay";
			$newp['receipt_id']  = $vars['transactionID'];
			$newp['begin_date']  = $begin_date;
			$newp['expire_date'] = $expire_date;
			$newp['amount']      = $pr['price'];
			
			$newp['completed']   = 1;
			$newp['data']['RENEWAL_ORIG'] = "RENEWAL ORIG: $last_payment[payment_id]";
			$new_payment_id = $db->add_waiting_payment(
				$newp['member_id'],
				$newp['product_id'],
				$newp['paysys_id'],
				$newp['amount'],
				$newp['begin_date'],
				$newp['expire_date'],
				$newp['data']
				);
			$error = $db->finish_waiting_payment($new_payment_id, "gspay", $vars['transactionID'], '', $vars);
			if($error) $db->log_error("Gspay finish_waiting_payment error - $error");
		}
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
	
    function init(){
        parent::init();
    }
}

$pl = & instantiate_plugin('payment', 'gspay');
?>
