<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alexander Smith
*      Email: alexander@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: gate2shop payment plugin
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

class payment_gate2shop extends amember_payment {
    var $title = 'Gate2Shop';
    var $description = 'Redirect';
    var $fixed_price = 0;
    var $recurring = 0;
//    var $supports_trial1 = 0;
    var $built_in_trials = 0;

    function encode_and_redirect($url, $vars){
        $vars1 = array();
        foreach ($vars as $k=>$v)
            $vars1[] = urlencode($k) . '=' . urlencode($v);
        $x = join('&', $vars1);

        header ("Location: $url?$x");
        exit;

//        html_redirect("$url?$x", 0, "Redirecting you to secure payment page", 
//        "You will be automatically redirected to secure payment page in about 2 seconds");
    }

    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars){
        global $config, $db, $plugin_config;

        $payment = $db->get_payment($payment_id);
        $member = $db->get_user($member_id);
        $product = & get_product($product_id);

        $this_config   = $plugin_config['payment']['gate2shop'];

        if ($this_config['testing']) // currently HTTP POST requests use version=3.0.0 while HTTP GET requests use version=1.0.0
            $version = '1.0.0';
        else
            $version = '3.0.0';

        $time_stamp = gmdate("Y-m-d.H:i:s"); // current GMT time in the following format: YYYY-MM-DD.HH:MM:SS

        $vars = array(
            'version'          => $version,
            'merchant_id'      => $this_config['merchant_id'],
            'merchant_site_id' => $this_config['site_id'],
            'currency'         => $product->config['gate2shop_currency'] ? $product->config['gate2shop_currency'] : 'USD',
            'numberofitems'    => '1',
            'item_name_1'      => $product->config['title'],
            'item_amount_1'    => $product->config['price'],
            'item_quantity_1'  => 1,
            'total_amount'     => $price,
            'time_stamp'       => $time_stamp,

            'total_tax'        => $payment['data']['TAX_AMOUNT'], // For statistics use only
            'productId'        => $product_id,
            'merchantLocale'   => 'en_US', // en_US, it_IT, es_ES, fr_FR, iw_IL, de_DE, ar_AA, ru_RU, nl_NL, bg_BG, zh_CN, ja_JP, ko_KR, en_CA, en_AU

            'userid'           => $member['member_id'],

            'first_name'       => $member['name_f'],
            'last_name'        => $member['name_l'],
            'address1'         => $member['street'],
            'city'             => $member['city'],
            'state'            => $member['state'],
            'country'          => $member['country'],
            'zip'              => $member['zip'],
            'email'            => $member['email'],
            'invoice_id'       => $payment['payment_id']."-".$this->get_rand(3),
            'customField1'     => $payment['payment_id'],
            'customData'       => $payment['payment_id'],
        );

        $vars['checksum'] = md5($this_config['secret'] . $vars['merchant_id'] . $vars['currency'] . $vars['total_amount'] . $vars['item_name_1'] . $vars['item_amount_1'] . $vars['item_quantity_1'] . $vars['time_stamp']);

        if ($this_config['method'] == 'GET'){        	$this->encode_and_redirect('https://secure.Gate2Shop.com/ppp/purchase.do', $vars);
        	exit;        } else {        	$t = & new_smarty();
    		$t->assign('vars', $vars);
    		$t->display(str_replace("c:\\", '/', dirname(__FILE__).'/form.html'));
    		exit;
		}
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

    function validate_thanks(&$vars){
        global $plugin_config;
        $this_config   = $plugin_config['payment']['gate2shop'];
        $checksum = md5($this_config['secret'] . $vars['TransactionID'] . $vars['ErrCode'] . $vars['ExErrCode'] . $vars['Status']);

        if ($vars['responsechecksum'] == $checksum)
            return ""; // OK
        else
            return "Validation error [".$vars['customField1']."]";
    }

    function process_thanks(&$vars){
        global $db;

        $db->log_error("Gate2Shop DEBUG: process_thanks \$vars=<br />".$this->get_dump($vars));

        $payment_id = intval($vars['customField1']);
        $pm = $db->get_payment($payment_id);

        if ($pm['payment_id'] && $pm['completed']){

            $db->log_error("Gate2Shop DEBUG: Payment #" . $pm['payment_id'] . " already completed");
            $payment_id = $pm['payment_id'];

        } elseif ($payment_id && $vars['Status'] == 'APPROVED') {

            $err = $db->finish_waiting_payment($payment_id, 'gate2shop', $vars['TransactionID'], '', $vars);
            if ($err)
                return "Gate2Shop ERROR: " . $err;

        } else {
		    $db->log_error("Gate2Shop ERROR: ".$vars['Status']." [".$vars['ErrCode']."] ".$vars['ExErrCode']);
		    return "Error. Payment not found";
		}

    }


    function validate_ipn($vars) {

        global $plugin_config;
        $this_config   = $plugin_config['payment']['gate2shop'];
        $checksum = md5($this_config['secret'] . $vars['ppp_status'] . $vars['PPP_TransactionID']);


        if ($checksum == $vars['responsechecksum'])
            return 1;
        else
            return 0;
    }

    function process_postback($vars){
        global $db, $config;

        if (!$this->validate_ipn($vars))
            $this->postback_error("IPN validation failed.");

        $payment_id = intval($vars['customData']);

        $p = $db->get_payment($payment_id);

        if (!$p['payment_id']){
	        $db->log_error("Gate2Shop DEBUG (process_postback): Payment [" . $payment_id . "] not found.");
            return;
	    }

	    if ($vars['ppp_status'] != 'OK'){
	        $db->log_error("Gate2Shop DEBUG (process_postback): DMN status = " . $vars['ppp_status']);
            return;
	    }

        if (!$p['completed']){
            $err = $db->finish_waiting_payment($payment_id, 'gate2shop', $vars['PPP_TransactionID'], '', $vars);
            if ($err)
                $this->postback_error("finish_waiting_payment error: $err");
        } else {        	$db->log_error("Gate2Shop DEBUG (process_postback): Payment [" . $payment_id . "] already completed.");        }
    }


    function init(){
        parent::init();

        add_product_field(
            'gate2shop_currency', 'Gate2Shop Currency',
            'select', 'currency for Gate2Shop gateway',
            '',
            array('options' => array(
                'USD' => 'US Dollar',
                'EUR' => 'Euro',
                'GBP' => 'Pound Sterling'
            ))
        );
    }

}

$pl = & instantiate_plugin('payment', 'gate2shop');
?>
