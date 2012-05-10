<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: The installation file
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3683 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember is free for both commercial and non-commercial use providing that the
* copyright headers remain intact and the links remain on the html pages.
* Re-distribution of this script without prior consent is strictly prohibited.
*
*/



class payment_moneybookers extends amember_payment {
    var $title       = "MoneyBookers";
    var $description = "Credit Card Payment";
    var $fixed_price = 0;
    var $recurring   = 1;
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $products = $product_id;
        $orig_product_id = $product_id;
        if (is_array($product_id))
             $product_id = $product_id[0];

        $product = & get_product($product_id);
        if (count($orig_product_id)>1)
            $product->config['title'] = $config['multi_title'];

        $member = $db->get_user($member_id);

        $vars = array(
            'pay_to_email'    => $this->config['business'],
            'pay_from_email'  => $member['email'],
            'transaction_id'  => $payment_id,
            'return_url'      => 
               sprintf("%s/thanks.php?payment_id=%d",
                $config['root_url'],
                $payment_id),
            'cancel_url' => $config['root_url']."/cancel.php",
            'status_url'  => $config['root_url'].
                             "/plugins/payment/moneybookers/ipn.php",
            'amount'      => sprintf('%.2f', $price),
            'detail1_text' => $product->config['title'],

            'firstname' => $member['name_f'],
            'lastname'  => $member['name_l'],
            'address'   => $member['street'],
            'postal_code' => $member['zip'],
            'city'      => $member['city'],
            'state'     => $member['state'],
            'country'   => $member['country']
        );

        // add currency code
        if (strlen($product->config['moneybookers_currency'])){
            $vars['currency'] = $product->config['moneybookers_currency'];
        } else {
            $vars['currency'] = 'USD';
        }
		
		if ($product->config['is_recurring']){
			unset($vars['amount']);
			$vars += array(
				'rec_amount' => sprintf('%.2f', $price),
				'rec_cycle'=> "day",
				'rec_period'=> $this->get_days($product->config['expire_days']),
				//'rec_status_url' => $config['root_url']."/plugins/payment/moneybookers/ipn.php"
            );

		}

        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://www.moneybookers.com/app/payment.pl?$vars");
        exit();
    }
    function log_debug($vars){
        global $db;
        $s = "Moneybookers DEBUG:<br />\n";
        foreach ($vars as $k=>$v)
            $s .= "[$k] => '$v'<br />\n";
        $db->log_error($s);
    }
	function validate_ipn($vars){
		$val = 
			$vars['merchant_id'] . 
			$vars['transaction_id'] . 
			strtoupper(md5($this->config['password'])) . 
			$vars['mb_amount'] . 
			$vars['mb_currency'] . 
			$vars['status'];
		if (($m=strtoupper(md5($val))) != $vars['md5sig'])
			return 0;
		else
			return 1;			
	}
	function process_postback($vars){
        global $db, $config;
		//$this->log_debug($vars);
        if (!$this->validate_ipn($vars))
            $this->postback_error(_PLUG_PAY_MONEYBOOK_ERROR3);
		if ($vars['pay_to_email'] != $this->config['business']) 
			$this->postback_error(sprintf(_PLUG_PAY_MONEYBOOK_ERROR4, $vars['pay_to_email']).$this->config['business']);
		if ($vars['status'] != 2)
			$this->postback_error(_PLUG_PAY_MONEYBOOK_ERROR5.$vars['status']);
        $p = $db->get_payment((int)$vars['transaction_id']);
        
        if (!$p['payment_id']){
			$db->log_error("Moneybookers Rec. DEBUG (process_postback): invoice [$vars[transaction_id]] not found.");
            return;
	    }
		if (!$vars['rec_payment_id'])
		{
			// process payment
			$err = $db->finish_waiting_payment($vars['transaction_id'], 'moneybookers', 
					$vars['mb_transaction_id'], $vars['amount'], $vars);
			if ($err) 
				$this->postback_error("finish_waiting_payment error: $err");
		}
		else
		{
			//process further recurring payment
			$pr = $db->get_product($p['product_id']);
			$last_payment = $this->get_last_payment($p['payment_id']);
			if($p['payment_id']!=$last_payment['payment_id'])
			{
				$begin_date = $last_payment['expire_date'];
				
				$duration = $this->get_days($pr['expire_days']) * 3600 * 24;
				$expire_date = date('Y-m-d', strtotime($begin_date) + $duration);
				$newp = array();
				$newp['member_id']   = $p['member_id'];
				$newp['product_id']  = $p['product_id'];
				$newp['paysys_id']   = $this->get_plugin_name();
				$newp['receipt_id']  = $vars['invoice_id'];
				$newp['begin_date']  = $begin_date;
				$newp['expire_date'] = $expire_date;
				$newp['amount']      = $pr['price'];
				
				$newp['completed']   = 1;
				$newp['data']['RENEWAL_ORIG'] = "RENEWAL ORIG: $last_payment[payment_id]";
				//$newp['data'][]      = $vars;
				$new_payment_id = $db->add_waiting_payment(
					$newp['member_id'],
					$newp['product_id'],
					$newp['paysys_id'],
					$newp['amount'],
					$newp['begin_date'],
					$newp['expire_date'],
					$newp['data']
					);
				$error = $db->finish_waiting_payment($new_payment_id, $this->get_plugin_name(), $vars['invoice_id'], '', $vars);
				if($error) $db->log_error("Moneybookers finish_waiting_payment error - $error");
			}
			else
			{
				//process first recurring payment
				$err = $db->finish_waiting_payment($vars['transaction_id'], 'moneybookers', 
						$vars['mb_transaction_id'], $vars['amount'], $vars);
				if ($err) 
					$this->postback_error("Moneybookers finish_waiting_payment error: $err");
			}
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
		add_product_field('moneybookers_currency', 
			'moneybookers Currency',
			'select',
			'valid only for moneybookers processing.<br /> You should not change it<br /> if you use 
			another payment processors',
			'',
			array('options' => array(
				''    => 'USD',
				'GBP' => 'GBP',
				'EUR' => 'EUR',
				'CAD' => 'CAD',
				'JPY' => 'JPY'
			))
			);
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

}
$pl = & instantiate_plugin('payment', 'moneybookers');
?>
