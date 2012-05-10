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
*    Release: 3.2.3PRO ($Revision: 3601 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*/

add_product_field('trial1_days',
    'Trial 1 Duration',
    'period',
    'read docs for explanation, leave empty to not use trial'
    );

add_product_field('trial1_price',
    'Trial 1 Price',
    'money',
    'set 0 for free trial'
    );





class payment_paypoint extends amember_payment {
    var $title       = "Paypoint";
    var $description = "Secure credit card payment";
    var $fixed_price = 0;
    var $recurring   = 1;

    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config;
        global $db;
        $product = $db->get_product($product_id);
		//print_r($product);

        $vars = array(
            'MERCHKEY' => $this->config['intinstid'],
            'AMT' => sprintf("%.2f",$price),
            'CURR' => $this->config['currency'],
            'INVOICE'        => $payment_id,
	    'DESC' => $product['title'],
	    'RECURRING' => $product['is_recurring']
        );
	$vars['RECURRING_FIELDS']='';
	$product['trial1_days']=strtoupper($product['trial1_days']);
	$product['expire_days']=strtoupper($product['expire_days']);
	if($product['trial1_days'])
		$vars['RECURRING_FIELDS'].="
		<input type=\"hidden\" name=\"fltSchAmount1\" value=\"$product[trial1_price]\">
		<input type=\"hidden\" name=\"strSchPeriod1\" value=\"$product[trial1_days]\">
		<input type=\"hidden\" name=\"fltSchAmount2\" value=\"$product[price]\">
		<input type=\"hidden\" name=\"strSchPeriod2\" value=\"$product[expire_days]\">
		";
	else
	{
		if("2037-12-31"!=$product["expire_days"])
			$vars['RECURRING_FIELDS'].="
		<input type=\"hidden\" name=\"strSchPeriod1\" value=\"$product[expire_days]\">";
		$vars['RECURRING_FIELDS'].="
		<input type=\"hidden\" name=\"fltSchAmount1\" value=\"$product[price]\">
		";
	}
	if($product['rebill_times'])
		$vars['RECURRING_FIELDS'].="
		<input type=\"hidden\" name=\"intCancelAfter\" value=\"$product[rebill_times]\">
		";
        if ($this->config['testing'])
		$vars['RECURRING_FIELDS'].="
		<input type=\"hidden\" name=\"intTestMode\" value=\"1\">
		";
	$t = &new_smarty();
	$t->assign('vars', $vars);
	$t->display(dirname(__FILE__) . '/paypoint.html');
    }
    
    function log_debug($vars){
        global $db;
        $s = "PAYPOINT DEBUG:<br />\n";
        foreach ($vars as $k=>$v)
            $s .= "[$k] => '$v'<br />\n";
        $db->log_error($s);
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
    function process_thanks(&$vars){
		global $db;
        //$this->log_debug($vars);
		$t = new_smarty();
		$p=$db->get_payment($vars['cartID']);
		$pr=$db->get_payment($p['product_id']);
		$t->assign('payment', $p);
		$t->assign('product', $pr);
		$t->assign('member',  $db->get_user($p['member_id']));
		
		if ($vars['intStatus'] != '1'){   
			$t->display("cancel.html");
			exit();
		}
		$t->display("thanks.html");
    }
    function handle_postback(&$vars){
		global $db;
        $this->log_debug($vars);
		if ($vars['intStatus'] != '1')
		{
			return "error: $vars[strMessage]";
		}
		$p=$db->get_payment($vars['strCartID']);
		if (!$p['completed']){
			$error = $db->finish_waiting_payment($p['payment_id'], "paypoint", $vars['intTransID'], '', $vars);
			if($error) $db->log_error("Paypoint finish_waiting_payment error - $error");
		}
		elseif(preg_match("/Payment/", $vars['strTransactionType']))
		{
			$pr = $db->get_product($p['product_id']);
			$last_payment = $this->get_last_payment($p['payment_id']);
			$begin_date = $last_payment['expire_date'];
			
			$duration = $this->get_days($pr['expire_days']) * 3600 * 24;
			$expire_date = date('Y-m-d', strtotime($begin_date) + $duration);
			$newp = array();
			$newp['member_id']   = $p['member_id'];
			$newp['product_id']  = $p['product_id'];
			$newp['paysys_id']   = "paypoint";
			$newp['receipt_id']  = $vars['invoice_id'];
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
			$error = $db->finish_waiting_payment($new_payment_id, "paypoint", $vars['intTransID'], '', $vars);
			if($error) $db->log_error("Paypoint finish_waiting_payment error - $error");
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
	
    function init(){
        parent::init();
    }
}

$pl = & instantiate_plugin('payment', 'paypoint');
?>
