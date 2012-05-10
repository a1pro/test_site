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
*    Release: 3.0.8PRO ($Revision: 5460 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


// need to configure products in clickbank and set thanks page to ./thanks.php
class payment_clickbank extends amember_advanced_payment {
    var $title = _PLUG_PAY_CLICKBANK_TITLE;
    var $description = _PLUG_PAY_CLICKBANK_DESC;
    var $fixed_price = 0;
    var $recurring = 1;
    
    function do_auto_login($payment_id){
        global $db, $config;
        
        $p = $db->get_payment($payment_id);
        $u = $db->get_user($p['member_id']);

        if ($config['auto_login_after_signup'] && $u){
            $_SESSION['_amember_login']     = $u['login'];
            $_SESSION['_amember_pass']      = $u['pass'];
        }

    }

    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars){

        global $config;
        $product = & get_product($product_id);

        $c_product_id = $product->config['clickbank_id'];

        if (!$c_product_id)
            fatal_error("Clickbank Product ID empty for Product# $product_id");

        $vars = array(
            'link'      =>
               sprintf("%s/%d/%s",
                $this->config['account'],
                $c_product_id,
                urlencode(trim(strip_tags($product->config['title'])))),
//                $payment_id),
//                urlencode("Order #" . $payment_id . ". " . $product->config['title'])),
            'seed'     => $payment_id
        );
        
        if ($product->config['clickbank_cbskin'])
            $vars['cbskin'] = $product->config['clickbank_cbskin'];

        global $db;
        $member = $db->get_user($member_id);
        $vars['name'] = trim($member['name_f']." ".$member['name_l']);
        $vars['email'] = $member['email'];
        $vars['country'] = $member['country'];
        $vars['zipcode'] = $member['zip'];
        //$vars['detail'] => $product->config['title']; // http://www.clickbank.com/payment_link_faq.html


        $this->encode_and_redirect('http://www.clickbank.net/sell.cgi', $vars);
    }

    /***
      Validation function from ClickBank
    ***/
    function clickbank_valid($vars, $key){
        $rcpt=$vars['cbreceipt'];
        $time=$vars['time'];
        $item=$vars['item'];
        $cbpop=$vars['cbpop'];

        $xxpop=sha1("$key|$rcpt|$time|$item");
        $xxpop=strtoupper(substr($xxpop,0,8));

        if ($cbpop==$xxpop)
            return 1;
        else
            return 0;
    }

    function validate_thanks(&$vars){
        return
        $this->clickbank_valid($vars, $this->config['secret']) ? '' : sprintf(_PLUG_PAY_CLICKBANK_INCORRECT, $vars['seed']);
    }

    function process_thanks(&$vars){
        global $db;

        $db->log_error("ClickBank DEBUG: process_thanks \$vars=<br />".$this->get_dump($vars));

        $payment_id = intval($vars['seed']);
        $pm = array();
        if ($payment_id){
	        $pm = $db->get_payment($payment_id);
	    } else {
            $count = 0;
            while ($pm['receipt_id'] == ''){
                sleep(1);

                $pid = $db->query_one($s = "SELECT payment_id
                    FROM {$db->config['prefix']}payments
                    WHERE receipt_id = '".$db->escape($vars['cbreceipt'])."'
		            AND paysys_id = 'clickbank'
                    ");
                if ($pid)
                    $pm = $db->get_payment($pid);
                if (++$count > 15) break;
            }
        }

	    $test_mode = (substr($vars['cbreceipt'], 0, 4) == 'TEST');

        if ($pm['payment_id'] && $pm['completed']){

            $db->log_error("ClickBank DEBUG: Payment #" . $pm['payment_id'] . " already completed through IPN");
            $payment_id = $pm['payment_id'];
            
            $this->do_auto_login($payment_id);

        } elseif ($payment_id) {

            $err = $db->finish_waiting_payment($payment_id, 'clickbank', $vars['cbreceipt'], '', $vars);
            if ($err)
                return _PLUG_PAY_CLICKBANK_ERROR . $err;
            
            $this->do_auto_login($payment_id);

        } elseif (!$test_mode) {
		    $db->log_error("ClickBank DEBUG: Payment not found");
		    return "Error. Payment not found";
	    }

        $GLOBALS['vars']['payment_id'] = $payment_id;

    }

    function validate_ipn($vars) {

// IPN 2.0
// http://www.clickbank.com/help/account-help/account-tools/instant-notification-service/
        $pop = "";
        $ipnFields = array();
        foreach ($vars as $key => $value) {
            if ($key == "cverify") {
                continue;
            }
            $ipnFields[] = $key;
        }
        sort($ipnFields);
        foreach ($ipnFields as $field) {
            $pop = $pop . $vars[$field] . "|";
        }
        $pop = $pop . $this->config['secret'];
        //$calcedVerify = sha1($pop);
        $calcedVerify = sha1(mb_convert_encoding($pop, "UTF-8"));
        $calcedVerify = strtoupper(substr($calcedVerify,0,8));

/// IPN 1.0

    	$key = $this->config['secret'];
    	$ccustname = $vars['ccustname'];
    	$ccustemail = $vars['ccustemail'];
    	$ccustcc = $vars['ccustcc'];
    	$ccuststate = $vars['ccuststate'];
    	$ctransreceipt = $vars['ctransreceipt'];
    	$cproditem = $vars['cproditem'];
    	$ctransaction = $vars['ctransaction'];
    	$ctransaffiliate = $vars['ctransaffiliate'];
    	$ctranspublisher = $vars['ctranspublisher'];
    	$cprodtype = $vars['cprodtype'];
    	$cprodtitle = $vars['cprodtitle'];
    	$ctranspaymentmethod = $vars['ctranspaymentmethod'];
    	$ctransamount = $vars['ctransamount'];
    	$caffitid = $vars['caffitid'];
    	$cvendthru = $vars['cvendthru'];
    	$cbpop = $vars['cverify'];
    	
    	$ccustaddr1 = $vars['ccustaddr1'];
    	$ccustaddr2 = $vars['ccustaddr2'];
    	$ccustcity = $vars['ccustcity'];
    	$ccustcounty = $vars['ccustcounty'];
    	$ccustzip = $vars['ccustzip'];
    	
    	$add_shipping = "";
    	if ($ccustaddr1)
    	    $add_shipping = "|$ccustaddr1|$ccustaddr2|$ccustcity|$ccustcounty|$ccustzip";

//    	$xxpop = sha1("$ccustname|$ccustemail|$ccustcc|$ccuststate|$ctransreceipt|$cproditem|$ctransaction|"
//    		."$ctransaffiliate|$ctranspublisher|$cprodtype|$cprodtitle|$ctranspaymentmethod|$ctransamount|$caffitid|$cvendthru|$key");

        $xxpop = "$ccustname|$ccustemail|$ccustcc|$ccuststate|$ctransreceipt|$cproditem|$ctransaction|"
            ."$ctransaffiliate|$ctranspublisher|$cprodtype|$cprodtitle|$ctranspaymentmethod|$ctransamount|$caffitid|$cvendthru".$add_shipping."|$key";
        $xxpop = sha1(mb_convert_encoding($xxpop, "UTF-8")); // for special characters in the Title


        $xxpop=strtoupper(substr($xxpop,0,8));

        //check both IPN versions
        if ($cbpop == $xxpop || $vars['cverify'] == $calcedVerify)
            return 1;
    	else
    	    return 0;

    }

    function find_last_payment_id($receipt_id, $ctransreceipt=''){
        global $db;
        $receipt_id = $db->escape($receipt_id);
        $ctransreceipt = $db->escape($ctransreceipt);
        $payment_id = $db->query_one($s = "
            SELECT payment_id
            FROM {$db->config[prefix]}payments
            WHERE receipt_id <> '' AND (receipt_id like '".$receipt_id."-B%' OR receipt_id='".$receipt_id."')
            AND completed=1 AND receipt_id <> '".$ctransreceipt."'
            ORDER BY payment_id DESC
		");
        return intval($payment_id);
    }

    function get_value_from_vars($var, &$vars){
        global $db;
        if($var == 'name_f' || $var =='name_l'){
            $name = trim($vars['ccustname']);
            $name = explode (" ", $name);
            $name_f = trim($name[0]);
            $name_l = trim($name[1]);

        }

        switch($var){
            case 'name_f'       :   $ret = $name_f; break;
            case 'name_l'       :   $ret = $name_l; break;
            case 'email'        :   $ret = $vars['ccustemail']; break;
            case 'state'        :   $ret = $vars['ccuststate']; break;
            case 'country'      :   $ret = $vars['ccustcc']; break;
            case 'product_id'   :   $ret = $this->find_product_by_field("clickbank_id", $vars['cproditem']);
                                    break;
                        default : $ret = '';
        }
        return trim($ret);
    }



    function process_postback($vars){
        global $db, $config;

        if ($vars['ctranspublisher']!=$this->config['account']){
            $this->postback_error("ClickBank DEBUG (process_postback): ctranspublisher is for other account '$vars[ctranspublisher]'.");
	        //$db->log_error("ClickBank DEBUG (process_postback): ctranspublisher is for other account '$vars[ctranspublisher]'.");
            //return;
        }
        
        if (!$this->validate_ipn($vars))
            $this->postback_error("IPN validation failed.");

	    $ctransreceipt = split("-", $vars['ctransreceipt']);
	    $ctransreceipt = $ctransreceipt[0];
	    $last_invoice = $this->find_last_payment_id($ctransreceipt, $vars['ctransreceipt']);

        $cvendthru = $vars['cvendthru'];
        if (preg_match ("/seed=(\d+)&/i", $cvendthru, $matches)){
        	$invoice = intval($matches[1]);
        } else {
            $invoice = $last_invoice;
        }

        $p = $db->get_payment($invoice);
        if (!$p['payment_id']){                    // No such payment found. Check is member exists
            $invoice = $this->create_new_payment($vars);
        }

        if (!$invoice){
	        $db->log_error("ClickBank DEBUG (process_postback): invoice [$invoice] not found.");
            return;
	    }

        $yesterday = date('Y-m-d', time()-3600*24);

        switch ($vars['ctransaction']){
            case 'SALE': //The purchase of a standard product or the initial purchase of recurring billing product.
            case 'TEST': //Triggered by using the test link on the site page.
            case 'TEST_SALE':
                $p = $db->get_payment($invoice);

		        $pr = $db->get_product($p['product_id']);
		        if ($pr['trial1_price'] && $pr['trial1_days'] && $pr['is_recurring']){

		            //$duration = $this->get_days($pr['trial1_days']) * 3600 * 24;
		            //$begin_date = strtotime($p['begin_date']);
		            //$expire_date = date('Y-m-d', $begin_date + $duration);
		            $expire_date = $this->get_expire_date($p['begin_date'], $pr['trial1_days'], $is_rebilling=false);

		            $p['expire_date'] = $expire_date;
		            $p['amount'] = $pr['trial1_price'];
		            $db->update_payment($p['payment_id'], $p);
		        }

                if (!$p['completed']){
                    $err = $db->finish_waiting_payment($invoice, $this->get_plugin_name(), $vars['ctransreceipt'], '', $vars);
                    if ($err)
                        $this->postback_error("finish_waiting_payment error: $err");
                }
                break;
           case 'BILL': //A rebill for a recurring billing product.
                $p = $db->get_payment($invoice);
                $last_p = $db->get_payment($last_invoice);

                if (!$p['payment_id'])
                    $this->postback_error("Cannot find original payment for [$invoice]");
                
                if ($last_p['receipt_id'] != $vars['ctransreceipt']){ //to avoid duplicates

		            $pr = $db->get_product($p['product_id']);
		            $begin_date = $this->get_next_begin_date($invoice);

            		//$duration = $this->get_days($pr['expire_days']) * 3600 * 24;
            		//$expire_date = date('Y-m-d', strtotime($begin_date) + $duration);
            		$expire_date = $this->get_expire_date($begin_date, $pr['expire_days'], $is_rebilling=true);

                    $newp = array();
                    $newp['member_id']   = $p['member_id'];
                    $newp['product_id']  = $p['product_id'];
                    $newp['paysys_id']   = $this->get_plugin_name();
                    $newp['receipt_id']  = $vars['ctransreceipt'];
                    $newp['begin_date']  = $begin_date;
                    $newp['expire_date'] = $expire_date;
                    $newp['amount']      = $pr['price']; //$vars['ctransamount'] / 100;
                    $newp['completed']   = 1;
                    $newp['data'][0]['RENEWAL_ORIG'] = "RENEWAL ORIG: $invoice";
                    $newp['data'][]      = $vars;


    //                if ($p['expire_date'] > $yesterday) $p['expire_date'] = $yesterday;
    //                $p['data'][] = $vars;
    //                $db->update_payment($p['payment_id'], $p);

                    foreach ($db->get_user_payments($newp['member_id'], 1) as $p){
                        if (($p['product_id'] == $newp['product_id'])
                            && (($p['data']['RENEWAL_ORIG'] == "RENEWAL ORIG: $invoice" || $p['data'][0]['RENEWAL_ORIG'] == "RENEWAL ORIG: $invoice") || ($p['payment_id'] == $invoice))
                            ){

                            if ($p['expire_date'] > $yesterday) $p['expire_date'] = $yesterday;
                            $p['data'][] = $vars;
                            $db->update_payment($p['payment_id'], $p);
                        }
                    }

                    $db->add_payment($newp);
                }

                break;
           case 'RFND': //The refunding of a standard or recurring billing product. Recurring billing products that are refunded also result in a "CANCEL-REBILL" action.
           case 'CGBK': //A chargeback for a standard or recurring product.
           case 'INSF': //An eCheck chargeback for a standard or recurring product.
                $orig_p = $db->get_payment($invoice);
                if (!$orig_p['payment_id'])
                    $this->postback_error("Cannot find original payment for [$invoice]");

                foreach ($db->get_user_payments($orig_p['member_id'], 1) as $p){
                    if (($p['product_id'] == $orig_p['product_id'])
                        && (($p['data']['RENEWAL_ORIG'] == "RENEWAL ORIG: $invoice" || $p['data'][0]['RENEWAL_ORIG'] == "RENEWAL ORIG: $invoice") || ($p['payment_id'] == $invoice))
                        && ($p['expire_date'] >= $yesterday)){

                        $p['expire_date'] = $yesterday;
                        $p['data'][] = $vars;
                        $db->update_payment($p['payment_id'], $p);
                    }
                }
                break;
           case 'CANCEL-REBILL': //The cancellation of a recurring billing product. Recurring billing products that are canceled do not result in any other action.
           case 'CANCEL-TEST-REBILL':
                $p = $db->get_payment($invoice);
                $p['data']['CANCELLED'] = 1;
                $p['data']['CANCELLED_AT'] = strftime($config['time_format'], time());
                $db->update_payment($p['payment_id'], $p);
                break;

           case 'UNCANCEL-REBILL': //Reversing the cancellation of a recurring billing product.
           case 'UNCANCEL-TEST-REBILL':
                $p = $db->get_payment($invoice);
                unset($p['data']['CANCELLED']);
                unset($p['data']['CANCELLED_AT']);
                $db->update_payment($p['payment_id'], $p);
                break;
           default: $this->postback_error("Unknown status: [$vars[ctransaction]]");
        }
    }

    function get_next_begin_date($invoice){

        return date("Y-m-d");

        /*
        global $db;
        $orig_p = $db->get_payment($invoice);
        $ret = $orig_p['expire_date'];
        foreach ($db->get_user_payments($orig_p['member_id'], 1) as $p){
            if (($p['product_id'] == $orig_p['product_id'])
                && ($p['data']['RENEWAL_ORIG'] == "RENEWAL ORIG: $invoice" || $p['data'][0]['RENEWAL_ORIG'] == "RENEWAL ORIG: $invoice")
                && ($p['expire_date'] > $ret))
                $ret = $p['expire_date'];
        }
	    if ($ret >= '2012-12-31')
            	$ret = date("Y-m-d");
        return $ret;
        */
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


    function get_expire_date($begin_date, $expire_days, $is_rebilling=false){
        $ret = date("Y-m-d", time() + 3600 * 24); // tomorrow. just in case.
        
        if ($expire_days == date("Y-m-d", strtotime($expire_days)))
            $ret = $expire_days; // in case if exact date used or Lifetime
        
        if (preg_match('/^\s*(\d+)\s*([y|Y|m|M|w|W|d|D]{0,1})\s*$/', $expire_days, $regs)){
            $period = $regs[1];
            $period_unit = $regs[2];
            if (!strlen($period_unit)) $period_unit = 'd';
            $period_unit = strtoupper($period_unit);
            
            list($y, $m, $d) = explode("-", $begin_date);
            
            if (!$is_rebilling){
                // an old way calculation for initial payments
                $duration = $this->get_days($expire_days) * 3600 * 24;
                $ret = date('Y-m-d', strtotime($begin_date) + $duration);
                
            } elseif ($period_unit == 'M' && $period == 1){
                // Monthly Billing
                $m_next = $m + 1;
                $expected_date = mktime(0, 0, 0, $m_next, $d, $y);
                if (date("d", $expected_date) != $d){
                    $last_day = date("t", strtotime($y . "-" . $m_next . "-1"));
                    $expected_date = mktime(0, 0, 0, $m_next, $last_day, $y);
                }
                $ret = date("Y-m-d", $expected_date);
                
            } elseif (($period_unit == 'D' && $period == 14) || ($period_unit == 'W' && $period == 2)){
                // Bi-Weekly Billing
                $d_next = $d + 14;
                $expected_date = mktime(0, 0, 0, $m, $d_next, $y);
                $ret = date("Y-m-d", $expected_date);

            } elseif ($period_unit == 'M' && $period == 3){
                // Quarterly Billing
                $m_next = $m + 3;
                $expected_date = mktime(0, 0, 0, $m_next, $d, $y);
                if (date("d", $expected_date) != $d){
                    $last_day = date("t", strtotime($y . "-" . $m_next . "-1"));
                    $expected_date = mktime(0, 0, 0, $m_next, $last_day, $y);
                }
                $ret = date("Y-m-d", $expected_date);

            } elseif ($period_unit == 'Y' && $period == 1){
                // Annual Billing
                if ($m == '02' && $d == '29')
                    $d = 28; // Expire Date will be different if Begin Date == Feb, 29
                $y_next = $y + 1;
                $expected_date = mktime(0, 0, 0, $m, $d, $y_next);
                $ret = date("Y-m-d", $expected_date);

            } else {
                // an old way calculation
                $duration = $this->get_days($expire_days) * 3600 * 24;
                $ret = date('Y-m-d', strtotime($begin_date) + $duration);
            }
        }
        return $ret;
    }


    function init(){
        parent::init();
        add_product_field(
                    'clickbank_id', 'ClickBank ID',
                    'text', 'You must create this same product<br />in Clickbank and enter its number here',
                    'validate_clickbank_id'
        );
        add_product_field(
                    'clickbank_cbskin', 'ClickBank Skin ID',
                    'text', 'an ID if your custom skin (cbskin parameter) for an order page'
        );
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

    }

    function handle_cancel($vars, $mid=0){
        global $db, $config;

        settype($vars['payment_id'], 'integer');
	    $mid = intval($mid);
	
        if (!$vars['payment_id'])
            fatal_error("Payment_id empty");        
        $payment = $db->get_payment($vars['payment_id']);
	    if (!$mid)
	        $mid = $_SESSION['_amember_id'];
        if ($payment['member_id'] != $mid)
            fatal_error(_PLUG_PAY_CC_CORE_FERROR4);

        $p = $db->get_payment($vars['payment_id']);
        $member = $db->get_user($p['member_id']);

        $url = "https://api.clickbank.com/rest/1.2/tickets/".$p['receipt_id'];
        $post = "type=cncl&reason=ticket.type.cancel.7&comment=cancellation%20request%20from%20aMember%20user%20(".$member['login'].")";
        $url = $url . "?" . $post;

        $headers = array();
        $headers[] = "Accept: application/xml";
        $headers[] = "Authorization: " . $this->config['developer_key'].":".$this->config['clerk_user_key'];
        $res = $this->get_url($url, $headers);
        $res_body = $res;

        if (preg_match("/\r\n\r\n(.*)/i", $res, $matches))
            $res_body = $matches[1];

        $msg = _PLUG_PAY_CC_CORE_SBSCNCL2;
        $title = _PLUG_PAY_CC_CORE_SBSCNCL;

        if (!preg_match("/HTTP\/1\.1 200 OK/i", $res)){
            $msg = "An error occured while cancellation request.";
            if ($res_body) $msg .= "<br /><font color=red><b>".$res_body."</b></font>";
            $title = "Subscription cancellation ERROR";
        } else {
            $xml = $this->parse_xml($res_body);
            $p['data'][] = $xml;
            $db->update_payment($p['payment_id'], $p);
            
            $response = array();
            if ($xml['ticketid']) $response[] = "Ticket: " . $xml['ticketid'];
            if ($xml['type']) $response[] = "Type: " . $xml['type'];
            if ($xml['action']) $response[] = "Action: " . $xml['action'];
            $response = implode ("<br />", $response);
            if ($response)
                $msg .= "<br /><b>Response from Clickbank</b><br />" . $response;
        }

	    $t = & new_smarty();
        $member = $db->get_user($p['member_id']);
        // email to member if configured
        if ($config['mail_cancel_admin']){
            $t->assign('user', $member);            
            $t->assign('payment', $p);
            $t->assign('product', $db->get_product($p['product_id']));
            $et = & new aMemberEmailTemplate();
            $et->name = "mail_cancel_admin";
            mail_template_admin($t, $et);
        }
        if ($config['mail_cancel_member']){
            $t->assign('user', $member);            
            $t->assign('payment', $p);
            $t->assign('product', $db->get_product($p['product_id']));
            $et = & new aMemberEmailTemplate();
            $et->name = "mail_cancel_member";
            mail_template_user($t, $et, $member);
        }

        $t = & new_smarty();
        $t->assign('title', $title);
        $t->assign('msg', $msg);
        $t->display("msg_close.html");

    }
    
    function parse_xml($xml){
        $res = array();
        $fields = array('ticketid', 'receipt', 'status', 'type', 'date', 'comment', 'action', 'description');
        foreach ($fields as $field){
            if (preg_match("/<".$field.">(.+)<\/".$field.">/i", $xml, $matches))
                $res[$field] = $matches[1];
        }
        return $res;
    }

    function get_cancel_link($payment_id){

        global $config, $db;
        $url = "";
        $p = $db->get_payment($payment_id);
        $pr = $db->get_product($p['product_id']);
        if ($pr['is_recurring'])
            $url = $config['root_url']."/plugins/payment/clickbank/cancel.php?payment_id=".$payment_id;
        return $url;
    }

    function get_url($url="", $headers=array()){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);

        if(curl_errno($ch))
            $error = "CURL ERROR: (".curl_errno($ch).") - ".curl_error($ch);

        if (!$result && $error)
            $result = $error;

        curl_close($ch);
        return $result;
    }
}

function validate_clickbank_id(&$p, $field){
    if (intval($p->config[$field]) <= 0) {
        return "You MUST enter Clickbank Product ID while you're using Clickbank Plugin";
    }
    return '';
}

$pl = & instantiate_plugin('payment', 'clickbank');
?>
