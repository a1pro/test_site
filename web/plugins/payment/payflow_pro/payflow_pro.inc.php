<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: PayFlow Pro payment plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3864 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_payflow_pro extends payment {
    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars)
	{
        return cc_core_do_payment('payflow_pro', $payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, $vars);
    }
	
    function get_cancel_link($payment_id)
	{
        global $db;                            
        return cc_core_get_cancel_link('payflow_pro', $payment_id);
    }
	
    function get_plugin_features()
	{
        return array(
            'title' => $config['payment']['payflow_pro']['title'] ? $config['payment']['payflow_pro']['title'] : _PLUG_PAY_PAYFLPRO_TITLE,
            'description' => $config['payment']['payflow_link']['description'] ? $config['payment']['payflow_link']['description'] : _PLUG_PAY_PAYFLPRO_DESC,
            'code' => 1,
            'name_f' => 2,
            'currency' => array('USD' => 'US dollar', 'EUR' => 'Euro', 'GBP' => 'UK pound', 'CAD' => 'Canadian dollar', 'JPY' => 'Japanese Yen', 'AUD' => 'Australian dollar')
        );
    }
	
	function payflow_get_url($url, $post='', $request_id='', $response_id='', $transaction_duration='')
	{
	    global $db, $config;
		
			$headers[] = "Content-Type: text/namevalue"; //or maybe text/xml
			$headers[] = "X-VPS-CLIENT-TIMEOUT: 43";
			$headers[] = "X-VPS-VIT-CLIENT-TYPE: PHP/cURL";  // What you are using
			$headers[] = "X-VPS-VIT-INTEGRATION-PRODUCT: aMember";  // For your info, would populate with application name
			if ($this->config['certification_id'])
			    $headers[] = "X-VPS-VIT-CLIENT-CERTIFICATION-ID: " . $this->config['certification_id'];
			$headers[] = "X-VPS-REQUEST-ID: " . $request_id;
			if ($response_id)
			{
				$headers[] = "X-VPS-RESPONSE-ID: " . $response_id;
				if ($transaction_duration < 1)
					$transaction_duration = 0;
				$headers[] = "X-VPS-VIT-CLIENT-DURATION: " . $transaction_duration;
			}
			$user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
		
	    if (extension_loaded("curl"))
		{

	        $ch=curl_init($url);
	        curl_setopt ($ch, CURLOPT_URL, $url);
	        
			curl_setopt($ch, CURLOPT_HEADER, 1); // tells curl to include headers in response
	        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
			curl_setopt($ch, CURLOPT_TIMEOUT, 45); // times out after 45 secs
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
			
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	        if ($post)
	            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	        if ($add_referer)
	            curl_setopt($ch, CURLOPT_REFERER, "$config[root_surl]/signup.php");

/*
	        if (strpos($db->config['host'], ".secureserver.net") > 0){
	            //use GoDaddy proxy
                curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);        
                curl_setopt($ch, CURLOPT_VERBOSE, 1);
                curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt ($ch, CURLOPT_PROXY,"http://proxy.shr.secureserver.net:3128");
	        }
*/
	        
	        $buffer = curl_exec($ch);
	        curl_close($ch);
	        return $buffer;
	    } else {

        $curl = $config['curl'];
					    
	$headstr = ""; 
	foreach($headers as $h){
	    $headstr .= " -H ".escapeshellarg($h)." ";
	}
        if ($add_referer){
	            $params .= " -e ". escapeshellarg($config['root_surl']."/signup.php");
		            }
            $url  = escapeshellarg($url);
	                $post = escapeshellarg($post);
			            if ($post)
				                $ret = `$curl -k $params $headstr -d $post $url`;
						            else
							                $ret = `$curl $params $url`;
//		print "$curl -k $params $headstr -d $post $url\n";
		return $ret; 												    			    
//			$db->log_error("CURL extension not loaded - cc transaction cannot be completed");
			return;
	    }
	}
	
    function run_transaction($vars,$request_id)
	{
        foreach ($vars as $kk=>$vv)
		{
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
		$vars1 = implode('&', $vars1);
		if ($this->config['testing'])
			$url = "https://pilot-payflowpro.verisign.com/transaction";
		else
			$url = "https://payflowpro.verisign.com/transaction";
			
		list($usec, $sec) = explode(" ", microtime());
		$transaction_begin = ((float)$usec + (float)$sec);
		
        $ret = $this->payflow_get_url($url, $vars1, $request_id);
		if (!$ret) fatal_error(sprintf(_PLUG_PAY_PAYFLPRO_ERROR3, $ret));
		
		list($usec, $sec) = explode(" ", microtime());
		$transaction_duration = ((float)$usec + (float)$sec) - $transaction_begin;
				
		if (!$ret) fatal_error(_PLUG_PAY_PAYFLPRO_FERROR);
		$t = explode("\n",$ret);
		foreach ($t as $string)
		{
			if (preg_match("/X-VPS-Response-ID/i",$string))
				$resp_id = trim(substr(strstr($string, ':'),1));
		}
		$ret = strstr($ret, 'RESULT');
		
        if (!preg_match('/\w+=\w+/', $ret))
            return array("RESPMSG" => sprintf(_PLUG_PAY_PAYFLPRO_ERROR3, $ret));
		
        parse_str($ret, $res);
		if (!$resp_id || $res['RESULT'] != '0')
			return array(array(),$res);
		$log = $res;
		$log['X-VPS-Response-ID'] = $resp_id;
		if ($this->config['testing'])
			$url = "https://pilot-payflowpro.paypal.com";
		else
			$url = "https://payflowpro.paypal.com";
			
		$ret = $this->payflow_get_url($url, $vars1, $request_id, $resp_id, $transaction_duration);
		if (!$ret) fatal_error(sprintf(_PLUG_PAY_PAYFLPRO_ERROR3, $ret));
		$t = explode("\n",$ret);
		foreach ($t as $string)
		{
			if (preg_match("/X-VPS-Response-ID/i",$string))
				$resp_id = trim(substr(strstr($string, ':'),1));
		}
		$ret = strstr($ret, 'RESULT');
        if (!preg_match('/\w+=\w+/', $ret))
            return array("RESPMSG" => sprintf(_PLUG_PAY_PAYFLPRO_ERROR3, $ret));
        parse_str($ret, $res);
		$res['X-VPS-Response-ID'] = $resp_id;
		$res['PNREF'] = $resp_id;
        return array($log,$res);
    }

    function void_transaction($pnref, &$log)
	{
    	$request_id = md5($pnref.date('YmdGis'));
        $vars = array(
            "PARTNER"   => $this->config['partner'],
            "USER"      => ($this->config['user'] ? $this->config['user'] : $this->config['login']),
            "VENDOR"    => $this->config['login'],
            "PWD"       => $this->config['password'],
            "TENDER"    => "C",
            "TRXTYPE"   => "V",
            "ORIGID"    => $pnref,
        );
        $vars_l = $vars;
    	$vars_l['X-VPS-REQUEST-ID'] = $request_id;
        $vars_l['PWD'] = preg_replace('/./', '*', $vars['PWD']);
        $log[] = $vars_l;
        list($res2, $res) = $this->run_transaction($vars, $request_id);
		$log[] = $res2;
        $log[] = $res;
        return $res;
    }

    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config;
        $log = array();
		
        //////////////////////// cc_bill /////////////////////////

	$trxtype = "S"; // Sale transaction

        if ($charge_type == CC_CHARGE_TYPE_TEST){ 
            $amount = "1.00";
	    $trxtype = "A"; // Authirization
	}
		
        $vars = array(
            "PARTNER"   => $this->config['partner'],
            "USER"      => ($this->config['user'] ? $this->config['user'] : $this->config['login']),
            "VENDOR"    => $this->config['login'],
            "PWD"       => $this->config['password'],
            "AMT"       => $amount,
            "CURRENCY"  => $currency,
            "ACCT"      => $cc_info['cc_number'],
            "EXPDATE"   => $cc_info['cc-expire'],
            "TENDER"    => "C",
            "TRXTYPE"   => $trxtype,
            "COMMENT2"  => $member['login'],
            "COMMENT1"  => $invoice,
            "NAME"      => $cc_info['cc_name_f'] . ' '. $cc_info['cc_name_l'],
            "STREET"    => $cc_info['cc_street'],
            "ZIP"       => $cc_info['cc_zip']
        );
		$request_id = md5($vars['ACCT'].$vars['AMT'].date('YmdGis').$payment['payment_id']);
		
        if ($cc_info['cc_code']) 
            $vars['CVV2'] = $cc_info['cc_code'];
        // prepare log record
        $vars_l = $vars;
		$vars_l['X-VPS-REQUEST-ID'] = $request_id;
        $vars_l['ACCT'] = $cc_info['cc'];
        if ($vars['CVV2'])
            $vars_l['CVV2'] = preg_replace('/./', '*', $vars['CVV2']);
        $vars_l['PWD'] = preg_replace('/./', '*', $vars['PWD']);
        $log[] = $vars_l;
		// run transaction
        list($res2, $res) = $this->run_transaction($vars,$request_id);
		$log[] = $res2;
        $log[] = $res;
        if ($res['RESULT']=='0'){
            if ($charge_type == CC_CHARGE_TYPE_TEST){
                /// void transaction if transaction type is test
                $this->void_transaction($res['PNREF'], $log, $request_id);
            } elseif ((($res['AVSADDR']=='N') || ($res['AVSZIP'] == 'N') || ($res[CVV2MATCH]=='N'))  && ($charge_type != CC_CHARGE_TYPE_RECURRING)) {
                 /// void transaction if there is AVS mismatch
                 $this->void_transaction($res['PNREF'], $log, $request_id);
                 $msg = _PLUG_PAY_PAYFLPRO_CCDECL;
                 return array(CC_RESULT_DECLINE_PERM, $msg, "", $log);
            }
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif ($res['RESULT'] > 0) {
            return array(CC_RESULT_DECLINE_PERM, $res['RESPMSG'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['RESPMSG'], "", $log);
        }
    }
}

function payflow_pro_get_member_links($user){
    return cc_core_get_member_links('payflow_pro', $user);
}

function payflow_pro_rebill(){
    return cc_core_rebill('payflow_pro');
}

cc_core_init('payflow_pro');
