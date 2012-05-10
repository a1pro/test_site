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
*    Release: 3.2.3PRO ($Revision: 5263 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*/

class payment_twocheckout_r extends amember_payment {
    var $title       = _PLUG_PAY_24eKOUT_TITLE;
    var $description = _PLUG_PAY_24eKOUT_DESC;
    var $fixed_price = 0;
    var $recurring   = 1;
    
    function get_cancel_link($payment_id){
        global $config, $db;
        
        if (!$this->config['api_username']) return;
        
        $p = $db->get_payment($payment_id);

        // Do not show cancel link if subscription was rebilled already
        foreach($db->get_user_payments($p[member_id], 1) as $op){
           if($op[data][0][RENEWAL_ORIG] == "RENEWAL_ORIG: ".$payment_id) return;
        }

        $member = $db->get_user($p['member_id']);
        $action='cancel_recurring';
        $v = md5($member['pass'].$action.($member['member_id'] * 12));
        if (!$p['data']['CANCELLED']) {
            return "{$config[root_surl]}/plugins/payment/twocheckout_r/cancel.php?"
                ."action=$action&payment_id=$payment_id&"
                ."member_id={$p[member_id]}&v=$v";
        }
    }
    
    function cancel_payment($payment_id)
    {

    }

    function do_not_recurring_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        global $config;
        global $db;
        $product = & get_product($product_id);
        
        $u = & $db->get_user(intval($member_id));

        $vars = array(
            'x_login'         => $this->config['seller_id'],
            'x_amount'        => $price,
            'x_invoice_num' => $payment_id,
//            'x_First_Name' => $u['name_f'],
//            'x_Last_Name' => $u['name_l'],
            'x_Address' => $u['street'],
            'x_City' => $u['city'],
            'x_State' => $u['state'],
            'x_Zip' => $u['zip'],
            'x_Country' => $u['country'],
            'x_Email' => $u['email'],
            'x_Receipt_Link_URL' => $config['root_url']."/plugins/payment/twocheckout_r/thanks.php",
            
            // new parameters
            'c_prod'        => $product_id,
            'id_type'       => 1,
            'c_name'        => $product->config['title'],
            'c_description' => $product->config['title'],
            'c_price'       => $price,
            'c_tangible'    => 'N',
            'merchant_order_id' => $payment_id
            
        );
      //if ($this->config['demo'] == 'Y')
      //    $vars['demo'] = 'Y';
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $varsx = join('&', $vars1);
        if (($this->config['seller_id'] >= 200000) || ($this->config['use_v2']))
            header($s="Location: https://www2.2checkout.com/2co/buyer/purchase?fixed=Y&".$varsx);
        else
            header("Location: https://www.2checkout.com/cgi-bin/Abuyers/purchase.2c?".$varsx);
        exit();
    }

    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config;
        global $db;
        $product = & get_product($product_id);
        if(!$product->config['is_recurring'])
            return $this->do_not_recurring_payment($payment_id, $member_id, $product_id,
                 $price, $begin_date, $expire_date, $vars);

        $varsx = array(
            'product_id' => $product->config['twocheckout_id'],
            'sid'        => $this->config['seller_id'],
            'amem_payment_id' => $payment_id,
            'verify'     => 
                md5(
                    $this->config['seller_id'].
                    $product->config['twocheckout_id'].
                    $price
                ),
            // new parameters
            'c_prod'        => $product_id,
            'id_type'       => 1,
            'c_name'        => $product->config['title'],
            'c_description' => $product->config['title'],
            'c_price'       => $price,
            'c_tangible'    => 'N',
            'merchant_order_id' => $payment_id
        );
        $varsx['recuring'] = 'Y';
      //if ($this->config['demo'] == 'Y')
      //              $varsx['demo'] = 'Y';
        $vars1 = array();
        foreach ($varsx as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$kk=$vv";
        }
        $varsx = join('&', $vars1);
        if (($this->config['seller_id'] >= 200000) || ($this->config['use_v2']))
            header($s="Location: https://www2.2checkout.com/2co/buyer/purchase?fixed=Y&".$varsx);
        else
            header($s="Location: https://www.2checkout.com/cgi-bin/crbuyers/recpurchase.2c?".$varsx);
        return '';
    }
    
    function log_debug($vars){
        global $db;
        $s = "2checkout DEBUG:<br />\n";
        foreach ($vars as $k=>$v)
            $s .= "[$k] => '$v'<br />\n";
        $db->log_error($s);
    }

    function validate_thanks(&$vars){
        global $db;
        $this->log_debug($vars);
        if ($vars['amem_payment_id']){ // it is recurring sale
            if($vars['demo'] == "Y" && $this->config[demo]!="Y")
                return _PLUG_PAY_24eKOUT_ERROR;
            if($vars['credit_card_processed'] != "Y")
                return _PLUG_PAY_24eKOUT_ERROR2;
            $str = $this->config['secret'].
                   $this->config['seller_id'].
                   $vars['order_number'].
                   sprintf('%.2f', $vars['total']);
            if(strtoupper(md5($str))!=$vars['key']){
                $db->log_error("2CO verirication hash doesn't match<br />
                md5($str) != $vars[key]<br />
                Please check that secret word configured in 2CO settings<br />
                matches one configured at aMember CP -> Setup -> 2CO
                ");
                return _PLUG_PAY_24eKOUT_ERROR3;
            }                
        } else { // not recurring sale
            if(intval($vars['x_trans_id']) == 0 && $this->config['demo'] != "Y" )
                    return "Tried to use demo mode, but demo mode not enabled";
            if(intval($vars['x_response_code'])!=1)
                return _PLUG_PAY_24eKOUT_ERROR4;
//            if(floor($vars['x_amount'])==$vars['x_amount'])
//                $vars['x_amount']=floor($vars['x_amount']); 
            $str = $this->config['secret'].
                   $this->config['seller_id'].
                   $vars['x_trans_id'].
                   sprintf('%.2f', $vars['x_amount']);
            if(strtoupper(md5($str))!=$vars['x_MD5_Hash']){
                $db->log_error("2CO verirication hash doesn't match<br />
                md5($str) != $vars[x_MD5_Hash]<br />
                Please check that secret word configured in 2CO settings<br />
                matches one configured at aMember CP -> Setup -> 2CO
                ");
                return _PLUG_PAY_24eKOUT_ERROR3;
            }                
        }
        return '';
    }

    function process_thanks(&$vars){
            global $db;
            $payment_id = intval($vars['amem_payment_id']?$vars['amem_payment_id']:$vars['x_invoice_num']);
            
            $pm = $db->get_payment($payment_id);
            $GLOBALS['vars']['payment_id'] = $pm['payment_id'];
            if ($pm['completed']){

            $db->log_error("2Checkout Rec. DEBUG: Payment #" . $pm['payment_id'] . " already completed through IPN");

            } else {
            
                $err = $db->finish_waiting_payment($payment_id,
                    'twocheckout_r', 
                    $vars['order_number']?$vars['order_number']:$vars['x_trans_id'],
                     '', $vars);
                if ($err)
                    return "finish_waiting_payment error: $err";
                $p = $db->get_payment($payment_id);
                $GLOBALS['vars']['payment_id'] = $payment_id;
                /*
                $product = & get_product($p['product_id']);
                if ($product->config['is_recurring'] && $vars['amem_payment_id']){
                    ////// set expire date to infinite
                    ////// admin must cancel it manually!
                    $p['expire_date'] = '2012-12-31';
                    $db->update_payment($payment_id, $p);
                }
                */
            
            }
    }
    
    function validate_ipn($vars){

        $md5_hash = strtoupper( md5( $vars['sale_id'] . $vars['vendor_id'] . $vars['invoice_id'] . $this->config['secret'] ) );
        
        if ($md5_hash==$vars['md5_hash'])
            return 1;
    	else
    	    return 0;

    }
    
    function process_postback($vars){
        global $db, $config;
		$this->log_debug($vars);
        if (!$this->validate_ipn($vars))
            $this->postback_error("IPN validation failed.");
            
        $p = $db->get_payment((int)$vars['vendor_order_id']);
        
        if (!$p['payment_id']){
	    $db->log_error("2Checkout Rec. DEBUG (process_postback): invoice [$vars[vendor_order_id]] not found.");
            return;
	    }
	    
	    
	    $yesterday = date('Y-m-d', time()-3600*24);
	    
	    switch ($vars['message_type']) {
	    
	    case 'ORDER_CREATED' : //Order creation is a new order placed online by a customer
            if (!$p['completed']){

                $db->finish_waiting_payment($p['payment_id'], $this->get_plugin_name(), $vars['invoice_id'], '', $vars);
                
            }
            break;
        case 'FRAUD_STATUS_CHANGED' :
            //nothing need to do there
            break;
        case 'SHIP_STATUS_CHANGED' :
            //nothing need to do there
            break;
        case 'INVOICE_STATUS_CHANGED' :
            //nothing need to do there
            break;
        case 'REFUND_ISSUED' :
            foreach ($db->get_user_payments($p['member_id'], 1) as $payment){
                    if (($payment['product_id'] == $p['product_id'])
                        && (($payment['data']['RENEWAL_ORIG'] == "RENEWAL ORIG: $p[payment_id]") || ($payment['payment_id'] == $p['payment_id']))
                        && ($payment['expire_date'] >= $yesterday)){

                        $payment['expire_date'] = $yesterday;
                        $payment['data'][] = $vars;
                        $db->update_payment($payment['payment_id'], $payment);
                    }
                }
        
            break;
        case 'RECURRING_INSTALLMENT_SUCCESS' :
              $pr = $db->get_product($p['product_id']);
    		  $last_payment = $this->get_last_payment($p['payment_id']);

		      //$begin_date = $this->get_next_begin_date($p['payment_id']);
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
			  if($error) $db->log_error("2Checkout Rec. finish_waiting_payment error - $error");
        
            break;
        case 'RECURRING_INSTALLMENT_FAILED' :
              //nothing need to do there
            break;
        case 'RECURRING_STOPPED' :
                $last_payment = $this->get_last_payment($p['payment_id']);
                $last_payment['data']['CANCELLED'] = 1;
                $last_payment['data']['CANCELLED_AT'] = strftime($config['time_format'], time());
                $db->update_payment($last_payment['payment_id'], $last_payment);
            break;
        case 'RECURRING_COMPLETE' :
                //nothing need to do there

                break;
        case 'RECURRING_RESTARTED' :
                $last_payment = $this->get_last_payment($p['payment_id']);
                unset($last_payment['data']['CANCELLED']);
                unset($last_payment['data']['CANCELLED_AT']);
                $db->update_payment($last_payment['payment_id'], $last_payment);
            break;
	    
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
        add_product_field(
            'twocheckout_id', '2Checkout ID',
            'text', 'you must create the same product<br />
             in 2Checkout and enter its number here<br />
             it can be found at 2CO -> PRODUCTS -> VIEW: 2CO ID',
             'validate_twocheckout_id'
           );
    }

}


function validate_twocheckout_id(&$p, $field){  
    if ((intval($p->config[$field]) <= 0) && $p->config['is_recurring'] && ($p->config['paysys_id'] == 'twocheckout_r' || $p->config['paysys_id'] == '')) {
        return "You MUST enter 2Checkout Product ID while you're using 2Checkout Recurring payment";
    }
    return '';
}

$pl = & instantiate_plugin('payment', 'twocheckout_r');

?>
