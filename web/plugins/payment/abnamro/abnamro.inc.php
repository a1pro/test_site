<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: abnamro payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2078 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_abnamro extends payment {
    var $aburl=null; // Web service location (for cim)

    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('abnamro', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('abnamro', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['abnamro']['title'] ? $config['payment']['abnamro']['title'] : "ABN-AMRO",
            'description' => $config['payment']['abnamro']['description'] ? $config['payment']['abnamro']['description'] : "Credit card payment",
            'phone' => 1,
            'code' => 1,
            'name' => 1
        );
    }

    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){	
        if ( CC_CHARGE_TYPE_REGULAR==$charge_type ) {
              return $this->abnamro_regular($cc_info, $member, $amount,
        $currency, $product_description,
        $charge_type, $invoice, $payment);
        } elseif ( CC_CHARGE_TYPE_RECURRING==$charge_type )
		{
              return $this->abnamro_recurring($cc_info, $member, $amount,
        $currency, $product_description,
        $charge_type, $invoice, $payment);
        }
		{
              return $this->abnamro_recurring_start($cc_info, $member, $amount,
        $currency, $product_description,
        $charge_type, $invoice, $payment);
        }
	}
		
	
    //regular payment
    function abnamro_regular($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////
        srand(time());
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }
        if(!$product_description){
	         global $db;
	         $product = $db->get_product($payment[product_id]);
	         $product_description = $product[title];
	      }
        $vars = array(
            "PSPID"    => $this->config['pspid'],
            "OrderID"  => $payment['payment_id'] . '-' . rand(100, 999),
            "USERID" => $this->config['userid'],
            "PSWD" => $this->config['pswd'],
            "amount" => str_replace('.', '', sprintf('%.2f', $amount)),
            "currency" => $this->config['currency'] ? $this->config['currency'] : "USD",
            "CARDNO" =>   $cc_info['cc_number'],
            "ED" => $cc_info['cc-expire'],
            "COM" => $product_description,
            "CN" => $cc_info['cc_name'],
            "EMAIL"     => $member['email'],
            "CVC"    =>    $cc_info['cc_code'],
            "Ecom_Payment_Card_Verification" => $cc_info['cc_code'],
            "Owneraddress" =>  $cc_info['cc_street'],
            "OwnerZip" =>  $cc_info['cc_zip'],
            "ownertown" =>   $cc_info['cc_city'],
            "ownercty" =>  $cc_info['cc_country'],
            "ownertelno" =>     $cc_info['cc_phone'],
            "Operation" =>  "SAL",
        );

        // prepare log record
        $vars_l = $vars;
        $vars_l['CARDNO'] = $cc_info['cc'];
        if ($vars['CVC'])
            $vars_l['CVC'] = preg_replace('/./', '*', $vars['CVC']);
        if ($vars['Ecom_Payment_Card_Verification'])
            $vars_l['Ecom_Payment_Card_Verification'] = preg_replace('/./', '*', $vars['Ecom_Payment_Card_Verification']);
		$vars_l['PSWD'] = preg_replace('/./', '*', $vars['PSWD']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['STATUS'] == '9' || $res['NCERROR'] == '0' || $res['NCSTATUS'] == '0'){
            return array(CC_RESULT_SUCCESS, "", $res['PAYID'], $log);
        } elseif ($res['STATUS'] == '0') {
            return array(CC_RESULT_DECLINE_PERM, $res['NCERRORPLUS']." (Error code - $res[NCERROR])", "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['NCERRORPLUS']." (Error code - $res[NCERROR])", "", $log);
        }
    }

	//recurring payment
    function abnamro_recurring($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        srand(time());
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }
		if (!$member['data']['abnamro_alias'])return array(CC_RESULT_DECLINE_PERM, 'abnamro_alias is not defined', "", $log);

        if(!$product_description){
	         global $db;
	         $product = $db->get_product($payment[product_id]);
	         $product_description = $product[title];
	      }
        $vars = array(
            "PSPID"    => $this->config['pspid'],
            "OrderID"  => $payment['payment_id'] . '-' . rand(100, 999),
            "USERID" => $this->config['userid'],
            "PSWD" => $this->config['pswd'],
            "amount" => str_replace('.', '', sprintf('%.2f', $amount)),
            "currency" => $this->config['currency'] ? $this->config['currency'] : "USD",
            "COM" => $product_description,
            "Operation" =>  "SAL",
			"ALIAS" => $member['data']['abnamro_alias']
        );
		$strforsha=$vars['OrderID'].$vars['amount'].$vars['currency'].$vars['CARDNO'].$vars['PSPID'].$vars['Operation'].$vars['Mysecretsig'].$vars["ALIAS"];
		//$vars['SHASign'] = sha1($strforsha);

        // prepare log record
        $vars_l = $vars;
		$vars_l['PSWD'] = preg_replace('/./', '*', $vars['PSWD']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['STATUS'] == '9' || $res['NCERROR'] == '0' || $res['NCSTATUS'] == '0')
		{
            return array(CC_RESULT_SUCCESS, "", $res['PAYID'], $log);
        } elseif ($res['STATUS'] == '0') {
            return array(CC_RESULT_DECLINE_PERM, $res['NCERRORPLUS']." (Error code - $res[NCERROR])", "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['NCERRORPLUS']." (Error code - $res[NCERROR])", "", $log);
        }
    }

    
	//recurring payment start
    function abnamro_recurring_start($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        srand(time());
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }

        if(!$product_description){
	         global $db;
	         $product = $db->get_product($payment[product_id]);
	         $product_description = $product[title];
	      }
        $vars = array(
            "PSPID"    => $this->config['pspid'],
            "OrderID"  => $payment['payment_id'] . '-' . rand(100, 999),
            "USERID" => $this->config['userid'],
            "PSWD" => $this->config['pswd'],
            "amount" => str_replace('.', '', sprintf('%.2f', $amount)),
            "currency" => $this->config['currency'] ? $this->config['currency'] : "USD",
            "CARDNO" =>   $cc_info['cc_number'],
            "ED" => $cc_info['cc-expire'],
            "COM" => $product_description,
            "CN" => $cc_info['cc_name'],
            "EMAIL"     => $member['email'],
            "CVC"    =>    $cc_info['cc_code'],
            "Ecom_Payment_Card_Verification" => $cc_info['cc_code'],
            "Owneraddress" =>  $cc_info['cc_street'],
            "OwnerZip" =>  $cc_info['cc_zip'],
            "ownertown" =>   $cc_info['cc_city'],
            "ownercty" =>  $cc_info['cc_country'],
            "ownertelno" =>     $cc_info['cc_phone'],
            "Operation" =>  "SAL",
        );
		$vars["ALIAS"] = $member['name_f'].$member['name_l'].date("mdy");
		$strforsha=$vars['OrderID'].$vars['amount'].$vars['currency'].$vars['CARDNO'].$vars['PSPID'].$vars['Operation'].$vars['Mysecretsig'].$vars["ALIAS"];
		$vars['SHASign'] = sha1($strforsha);

		$member['data']['abnamro_alias'] = $vars["ALIAS"];

        // prepare log record
        $vars_l = $vars;
        $vars_l['CARDNO'] = $cc_info['cc'];
        if ($vars['CVC'])
            $vars_l['CVC'] = preg_replace('/./', '*', $vars['CVC']);
        if ($vars['Ecom_Payment_Card_Verification'])
            $vars_l['Ecom_Payment_Card_Verification'] = preg_replace('/./', '*', $vars['Ecom_Payment_Card_Verification']);
		$vars_l['PSWD'] = preg_replace('/./', '*', $vars['PSWD']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['STATUS'] == '9' || $res['NCERROR'] == '0' || $res['NCSTATUS'] == '0')
		{
            $result=$this->save_cc_info_ab($cc_info, $member);
            if (is_array($result)) return $result;
            return array(CC_RESULT_SUCCESS, "", $res['PAYID'], $log);
        } elseif ($res['STATUS'] == '0') {
            return array(CC_RESULT_DECLINE_PERM, $res['NCERRORPLUS']." (Error code - $res[NCERROR])", "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['NCERRORPLUS']." (Error code - $res[NCERROR])", "", $log);
        }
    }
	
	function save_cc_info_ab($cc_info, & $member) {
        global $db;
        ////validate user profile, update if incorrect, create if no exists
        $member['data']['cc'] = '**** **** **** '.substr($cc_info['cc_number'], -4);
        if (isset($cc_info['cc_expire_Month'])) {
            $member['data']['cc-expire'] = sprintf('%02d%02d', $cc_info['cc_expire_Month'], substr($cc_info['cc_expire_Year'], 2, 2));
        } else {
            $member['data']['cc-expire'] = $cc_info['cc-expire'];
        }
        
        if ($member['data']['abnamro_alias']) {
			$db->update_user($member['member_id'], $member);
				return '';
        } else {
			return array(CC_RESULT_DECLINE_PERM, 'abnamro_alias is not defined', "", $log);
                
		}
    }
	
	
    function run_transaction($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $xml_response = cc_core_get_url($this->aburl, $vars1);

		$parser = xml_parser_create();
		xml_parse_into_struct($parser,$xml_response,$vals,$index);
		xml_parser_free ($parser);

		echo $ret;
        $res = $vals[0]['attributes'];
        return $res;
    }
    function run_transaction_alias($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $res = cc_core_get_url($this->aburl, $vars1);
		echo $res;die;
        return $res;
    }
    function payment_abnamro($config) {
        parent::payment($config);
        
        add_member_field('abnamro_alias', '', 'hidden');
      
        if ($this->config['testing'])
            $this->aburl="https://internetkassa.abnamro.nl/ncol/test/orderdirect.asp";
		else
			$this->aburl="https://internetkassa.abnamro.nl/ncol/prod/orderdirect.asp";
   
    }
	
}

function abnamro_get_member_links($user){
	return;
}

function abnamro_rebill(){
    return cc_core_rebill('abnamro');
}
                                        
cc_core_init('abnamro');