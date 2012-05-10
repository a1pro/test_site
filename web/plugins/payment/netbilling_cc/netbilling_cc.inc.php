<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

function netbilling_cc_get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

class payment_netbilling_cc extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('netbilling_cc', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('netbilling_cc', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['netbilling_cc']['title'] ? $config['payment']['netbilling_cc']['title'] : _PLUG_PAY_NETBILLING_TITLE,
            'description' => $config['payment']['netbilling_cc']['description'] ? $config['payment']['netbilling_cc']['description'] : _PLUG_PAY_NETBILLING_DESC,
            'code' => 2,
            'name_f' => 2,
            'phone' => 1
        );
    }
    
    function run_transaction($vars){
        global $db, $config;
        
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        
        $host = "secure.netbilling.com";
        $port = "1402";
        $path = "/gw/sas/direct3.1";
        
        if (extension_loaded('curl') || $config['curl'] ){

            $url = "https://".$host.":".$port.$path;
//	        $ret = cc_core_get_url($url, $vars1);

            $ch = curl_init( $url );
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $vars1 );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_TIMEOUT, 240 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
            curl_setopt ($ch, CURLOPT_URL, $url);
    
/*
            if (is_object($db) && strpos($db->config['host'], ".secureserver.net") > 0){
                //use GoDaddy proxy
                curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);        
                curl_setopt($ch, CURLOPT_VERBOSE, 1);
                curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt ($ch, CURLOPT_PROXY,"http://proxy.shr.secureserver.net:3128");
            }
*/
    
            $ret = curl_exec( $ch );
            
            if( curl_errno( $ch ) != CURLE_OK ) {
                $db->log_error("NetBilling: cURL error [".curl_error( $ch )."]");
            }
            curl_close( $ch );

	    } else {
            $ssl = "ssl://";
            $fp = @fsockopen($ssl . $host, $port, $errnum, $errstr, 30); 
            if(!$fp){
              $db->log_error("NetBilling: Socket error [$errnum: $errstr]\n");
              $ret = 0;
            } else { 
              fputs($fp, "POST ".$path." HTTP/1.1\r\n");
              fputs($fp, "Host: ".$host."\r\n");
              fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n"); 
              fputs($fp, "Content-length: ".strlen($vars1)."\r\n"); 
              //fputs($fp, "Connection: close\r\n\r\n");
              fputs($fp, $vars1 . "\r\n\r\n"); 
              while(!feof($fp))
                $ret .= @fgets($fp, 1024);
              fclose($fp); 
            }
	    }
	    
        parse_str($ret, $res);
        
        $db->log_error("NetBilling RESPONSE:<br />".netbilling_cc_get_dump($res));
        
        $return['RESULT']   = $res['status_code'];
        $return['RESPMSG']  = $res['auth_msg'];
        $return['AVS']      = $res['avs_code'];
        $return['PNREF']    = $res['auth_code'];
        $return['CVV_VALID']= $res['cvv2_code'];
        $return['TRANSID']  = $res['trans_id'];

        return $return;
    }
    function void_transaction($pnref, &$log, $transid, $vars, $cc){
        return '';
    }
    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, $currency, $product_description, $charge_type, $invoice, $payment){
        
        global $db, $config, $plugin_config;
        
        $this_config   = $plugin_config['payment']['netbilling_cc'];
        $product = $db->get_product($payment['product_id']);
        
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        $auth_type = 'S';
        if ($charge_type == CC_CHARGE_TYPE_TEST){
            $amount = "1.00";
            $auth_type = 'A';
        }

        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }
        
        $ip = $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'];

        $vars = array(
            'account_id'        => $this_config['account_id'],
            'pay_type'          => 'C', // Credit Card
            'tran_type'         => $auth_type,
            'purch_order'       => $payment['payment_id'],
            'amount'            => $amount,
            'tax_amount'        => 0,
            'ship_amount'       => 0,
            'description'       => $product['title'],

            'card_number'       => $cc_info['cc_number'],
            'card_cvv2'         => $cc_info['cc_code'],
            'card_expire'       => $cc_info['cc-expire'],
            
            'bill_name1'        => $cc_info['cc_name_f'],
            'bill_name2'        => $cc_info['cc_name_l'],
            'bill_street'       => $cc_info['cc_street'],
            'bill_city'         => $cc_info['cc_city'],
            'bill_zip'          => $cc_info['cc_zip'],
            'bill_country'      => $cc_info['cc_country'],
            'bill_state'        => $cc_info['cc_state'],
            'cust_phone'        => $cc_info['cc_phone'],
            'cust_ip'           => $ip,
            'cust_host'         => gethostbyaddr($ip),
            'cust_browser'      => $_SERVER['HTTP_USER_AGENT'],
            'cust_email'        => $member['email']
        );
        
        // prepare log record
        $vars_l = $vars;
	
        $vars_l['card_number'] = $cc_info['cc'];
        if ($vars['card_cvv2'])
            $vars_l['card_cvv2'] = preg_replace('/./', '*', $vars['card_cvv2']);
	
        $log[] = $vars_l;
        /////
        $db->log_error("NetBilling DEBUG:<br />".netbilling_cc_get_dump($vars_l));
        
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['RESULT'] == '1' || ($res['RESULT'] == 'T' && $charge_type == CC_CHARGE_TYPE_TEST)){
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif ($res['RESULT'] == '0') {
            return array(CC_RESULT_DECLINE_PERM, ($res['RESPMSG'] ? $res['RESPMSG'] : $res['RESULT']), "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, ($res['RESPMSG'] ? $res['RESPMSG'] : $res['RESULT']), "", $log);
        }
    }
}

function netbilling_cc_get_member_links($user){
    return cc_core_get_member_links('netbilling_cc', $user);
}

function netbilling_cc_rebill(){
    return cc_core_rebill('netbilling_cc');
}

cc_core_init('netbilling_cc');
?>