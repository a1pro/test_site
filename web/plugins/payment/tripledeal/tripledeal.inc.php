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
*    Release: 3.1.8PRO ($Revision: 1858 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


class payment_tripledeal extends amember_payment {
    var $title = 'TripleDeal';
    var $description = 'Credit Card Payment';
    var $fixed_price=0;
    var $recurring=0;
    var $built_in_trials=0;

    function get_dump($var){
        //dump of array
        $s = "";
        foreach ((array)$var as $k=>$v)
            $s .= "$k => $v<br />\n";
        return $s;
    }

    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $product = & get_product($product_id);
        $member  = $db->get_user($member_id);

        $days = intval((strtotime($expire_date) - time()) / 60 / 60 / 24);
        if (!$days) $days = 1;

        $args = array(
            'command' => 'new_payment_cluster',
            'merchant_name' => $this->config['merchant_name'],
            'merchant_password' => $this->config['merchant_password'],
            'merchant_transaction_id' => $payment_id,
            'profile' => $this->config['profile'],
            'client_id' => $member['member_id'],
            'price' => $price,
            'cur_price' => $product->config['tripledeal_currency'] ? $product->config['tripledeal_currency'] : 'USD',
            'client_email' => $member['email'],
            'client_firstname' => $member['name_f'],
            'client_lastname' => $member['name_l'],
            'client_address' => $member['street'],
            'client_zip' => $member['zip'],
            'client_city' => $member['city'],
            'client_country' => $member['country'],
            'client_language' => $member['country'],
            'description' => $product->config['title'],
            'days_pay_period' => $days
        );

        if ($this->config['debugmode'])
            $db->log_error("TripleDeal DEBUG: " . $this->get_dump($args));

        $url = "https://www.tripledeal.com/ps/com.tripledeal.paymentservice.servlets.PaymentService";
        $args1 = array();
        foreach ($args as $k=>$v)
            $args1[] = urlencode($k) . '=' . urlencode($v);
        $args = join('&', $args1);

        $result = get_url($url . "?" . $args);

        if ($this->config['debugmode'])
            $db->log_error("TripleDeal DEBUG: " . $result);

        $error = "no payment cluster";
        if (preg_match("/<id value=\"(\d+)\"/im", $result, $matches)){        	$payment_cluster_id = $matches[1];
        	preg_match("/<key value=\"([^\"]+)\"/im", $result, $matches);
        	$payment_cluster_key = $matches[1];
        	$error = "";        } elseif (preg_match("/<error msg=\"([^\"]+)\"/im", $result, $matches)){        	$error = $matches[1];        } else {
            $error = "unknown result";        }

        if ($error){        	$error = "TripleDeal Error (" . $error . ")";
        	$db->log_error($error);
        	return $error;        } else {

            $payment = $db->get_payment($payment_id);
            $payment['tripledeal_cluster_id'] = $payment_cluster_id;
            $payment['tripledeal_cluster_key'] = $payment_cluster_key;
            $db->update_payment($payment['payment_id'], $payment);
            $args = array(
                'command' => 'show_payment_cluster',
                'merchant_name' => $this->config['merchant_name'],
                'merchant_transaction_id' => $payment_id,
                'client_language' => $u['country'],
                'payment_cluster_id' => $payment_cluster_id,
                'payment_cluster_key' => $payment_cluster_key,
                'return_url_succes' => $config['root_url'] . "/plugins/payment/tripledeal/thanks.php?seed=".$payment_id,
                'return_url_canceled' => $config['root_url'] . "/plugins/payment/tripledeal/thanks.php?seed=".$payment_id,
                'return_url_pending' => $config['root_url'] . "/plugins/payment/tripledeal/thanks.php?seed=".$payment_id,
                'return_url_error' => $config['root_url'] . "/plugins/payment/tripledeal/thanks.php?seed=".$payment_id
                );

            if ($this->config['debugmode'])
                $db->log_error("TripleDeal DEBUG: " . $this->get_dump($args));

            $this->encode_and_redirect ($url, $args);
            exit;
        }

    }
    function validate_thanks(&$vars){    	global $db;
        $args = array(
            'command' => 'status_payment_cluster',
            'merchant_name' => $this->config['merchant_name'],
            'merchant_password' => $this->config['merchant_password'],
            'report_type' => 'txt_simple',
            'payment_cluster_key' => $payment['tripledeal_cluster_key']
            );

        if ($this->config['debugmode'])
            $db->log_error("TripleDeal DEBUG: " . $this->get_dump($args));

        $url = "https://www.tripledeal.com/ps/com.tripledeal.paymentservice.servlets.PaymentService";
        $args1 = array();
        foreach ($args as $k=>$v)
            $args1[] = urlencode($k) . '=' . urlencode($v);
        $args = join('&', $args1);

        $result = get_url($url . "?" . $args);

        if ($this->config['debugmode'])
            $db->log_error("TripleDeal DEBUG: " . $result);

        if ($result != "Y"){
            $error = "TripleDeal Error (status_payment_cluster)";
            $db->log_error($error);
            return $error;
        }

    }
    function process_thanks(&$vars){
            global $db;
            $payment_id = intval($vars['seed']);
            $payment = $db->get_payment($payment_id);
            $err = $db->finish_waiting_payment($payment_id, 'tripledeal', $payment['tripledeal_cluster_id'], '', $vars);
            if ($err)
                return "TripleDeal Error " . $err;
            $GLOBALS['vars']['payment_id'] = $payment_id;
    }

    function init(){
        parent::init();
        add_product_field('tripledeal_currency',
            'TripleDeal Currency',
            'select',
            'valid only for TripleDeal processing.<br /> You should not change it<br /> if you use
            another payment processors',
            '',
            array('options' => array(
                'USD' => 'USD',
                'EUR' => 'EUR'
                ))
            );

        add_payment_field('tripledeal_cluster_id', 'TripleDeal Payment Cluster Id',
            'readonly', 'internal');
        add_payment_field('tripledeal_cluster_key', 'TripleDeal Payment Cluster Key',
            'readonly', 'internal');
    }

}

$pl = & instantiate_plugin('payment', 'tripledeal');

?>
