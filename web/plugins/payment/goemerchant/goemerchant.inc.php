<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: goemerchant payment plugin
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

function goemerchant_get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function goemerchant_parse_xml ($xml){
    preg_match_all ('|<FIELD KEY="([^"]+)">([^<]+)</FIELD>|im', $xml, $matches);
    $res = array();
    foreach ($matches[1] as $k=>$v){
        $res[$v] = $matches[2][$k];
    }
    return $res;
}



class payment_goemerchant extends amember_payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('goemerchant', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('goemerchant', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['authotize_aim']['title'] ? $config['payment']['goemerchant']['title'] : _PLUG_PAY_GOEMERCHANT_TITLE,
            'description' => $config['payment']['goemerchant']['description'] ? $config['payment']['goemerchant']['description'] : _PLUG_PAY_GOEMERCHANT_DESC,
            'phone' => 1,
            'code' => 1,
            'name_f' => 2,
            'type_options' => array('Visa'       => 'Visa', 
                                    'MasterCard' => 'MasterCard', 
                                    'Discover'   => 'Discover',
                                    'Amex'       => 'American Express')
        );
    }
    function run_transaction($xml){
        global $db;
        
        $ret = cc_core_get_url("https://www.goemerchant4.com/trans_center/gateway/xmlgateway.cgi", $xml);
        
        $res = goemerchant_parse_xml ($ret);
        $db->log_error("GoEmerchant RESPONSE:<br />\n" . goemerchant_get_dump($res));

/* XML result to parse:
<?xml version="1.0" encoding="UTF-8"?>
<RESPONSE>
  <FIELDS>
    <FIELD KEY="status">0-error 1-success 2-declined</FIELD>
    <FIELD KEY="auth_code">character code sent by the bank </FIELD>
    <FIELD KEY="auth_response">message from the bank </FIELD>
    <FIELD KEY="avs_code">avs code from the bank</FIELD>
    <FIELD KEY="cvv2_code">cvv2 code from the bank</FIELD>
    <FIELD KEY="order_id">echoed back from original post</FIELD>
    <FIELD KEY="reference_number">returned for use with credits/voids/settles</FIELD>
    <FIELD KEY="error">error text</FIELD>
  </FIELDS>
</RESPONSE>
*/

        return $res;
    }
    function void_transaction($pnref, &$log){
        global $db;
        
        $vars = array(
            "merchant"                  => $this->config['merchant_id'],
            "password"                  => $this->config['merchant_pass'],
            "operation_type"            => "void",
            "total_number_transactions" => "1",
            "reference_number1"         => $pnref
        );

$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<TRANSACTION>
<FIELDS>
<FIELD KEY=\"merchant\">".$vars['merchant']."</FIELD>
<FIELD KEY=\"password\">".$vars['password']."</FIELD>
<FIELD KEY=\"operation_type\">".$vars['operation_type']."</FIELD>
<FIELD KEY=\"total_number_transactions\">".$vars['total_number_transactions']."</FIELD>
<FIELD KEY=\"reference_number1\">".$vars['reference_number1']."</FIELD>
</FIELDS>
</TRANSACTION>";

        $vars_l = $vars;
        $log[] = $vars_l;
        $db->log_error ("GoEmerchant REQUEST:<br />\n" . goemerchant_get_dump($vars_l));
        $res = $this->run_transaction($xml);
        
/* XML result to parse:
<?xml version="1.0" encoding="UTF-8"?>
<RESPONSE>
<FIELDS>
<FIELD KEY="total_transactions_voided">Total number of transactions voided</FIELD>
<FIELD KEY="status1"> 0-error 1-success 2-rejected</FIELD>
<FIELD KEY="response1">Response text returned by Transaction Center</FIELD>
<FIELD KEY="reference_number1">reference number of credited transaction</FIELD>
<FIELD KEY="error1">error text</FIELD>
</FIELDS>
</RESPONSE>
*/
        $log[] = $res;
        return $res;
    }
    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config, $db;
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        srand(time());
        $operation_type = "sale";
        
        if ($charge_type == CC_CHARGE_TYPE_TEST){
            $amount = "1.00";
            $operation_type = "auth";
        }
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }

        if(!$product_description){
    	    global $db;
    	    $product = $db->get_product($payment['product_id']);
    	    $product_description = $product['title'];
	    }
        $vars = array(
            "merchant"          => $this->config['merchant_id'],
            "password"          => $this->config['merchant_pass'],
            "operation_type"    => $operation_type,
            "order_id"          => $payment['payment_id'] . '-' . rand(100, 999),
            "total"             => $amount,
            "card_name"         => $cc_info['cc_type'], // Visa, Amex, Discover or MasterCard
            "cardnum"           => $cc_info['cc_number'],
            "cardnum1"          => substr($cc_info['cc_number'], 0, 4), // First 4 Numbers on Card
            "cardnum2"          => substr($cc_info['cc_number'], 4, 4), // Second 4 Numbers on Card
            "cardnum3"          => substr($cc_info['cc_number'], 8, 4), // Third 4 Numbers on Card
            "cardnum4"          => substr($cc_info['cc_number'], 12),   // Last 4 Numbers on Card - 3 digits for Amex
            "cardexp"           => $cc_info['cc-expire'],
            "cardexpm"          => substr($cc_info['cc-expire'], 0, 2), // Card Expiration Month - Format MM
            "cardexpy"          => substr($cc_info['cc-expire'], 2, 2), // Card Expiration Year - Format YY
            "nameoncard"        => $cc_info['cc_name_f'] . " " . $cc_info['cc_name_l'],     // Card Holders Name
            "cardstreet"        => $cc_info['cc_street'],   // Card Holders Billing Street
            "cardcity"          => $cc_info['cc_city'],     // Card Holders Billing City
            "cardstate"         => $cc_info['cc_state'],    // Card Holders Billing State *(2 character abbreviation)
            "cardzip"           => $cc_info['cc_zip'],      // Card Holders Billing Zip
            "cardcountry"       => $cc_info['cc_country'],  // Card Holders Billing Country *(2 character abbreviation)
            "email"             => $member['email'],        // Card Holders Email
            "phone"             => $cc_info['cc_phone'],    // Card Holders Phone
            "remote_ip_address" => $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'], // (optional)
            "recurring_type"    => "0" // Set recurring status: daily, weekly, monthly, biweekly, quarterly, semiannually, annually (optional)
        );

        if ($cc_info['cc_code'])
            $vars['CVV2'] = $cc_info['cc_code']; // 3 digits Visa/Mastercard  4 digits American Express (optional)

$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<TRANSACTION>
  <FIELDS>
    <FIELD KEY=\"merchant\">".$vars['merchant']."</FIELD>
    <FIELD KEY=\"password\">".$vars['password']."</FIELD>
    <FIELD KEY=\"operation_type\">".$vars['operation_type']."</FIELD>
    <FIELD KEY=\"order_id\">".$vars['order_id']."</FIELD>
    <FIELD KEY=\"total\">".$vars['total']."</FIELD>
    <FIELD KEY=\"card_name\">".$vars['card_name']."</FIELD>
    <FIELD KEY=\"card_number\">".$vars['cardnum']."</FIELD>
    <FIELD KEY=\"card_exp\">".$vars['cardexp']."</FIELD>
    <FIELD KEY=\"cvv2\">".$vars['CVV2']."</FIELD>
    <FIELD KEY=\"owner_name\">".$vars['nameoncard']."</FIELD>
    <FIELD KEY=\"owner_street\">".$vars['cardstreet']."</FIELD>
    <FIELD KEY=\"owner_city\">".$vars['cardcity']."</FIELD>
    <FIELD KEY=\"owner_state\">".$vars['cardstate']."</FIELD>
    <FIELD KEY=\"owner_zip\">".$vars['cardzip']."</FIELD>
    <FIELD KEY=\"owner_country\">".$vars['cardcountry']."</FIELD>
    <FIELD KEY=\"owner_email\">".$vars['email']."</FIELD>
    <FIELD KEY=\"owner_phone\">".$vars['phone']."</FIELD>
    <FIELD KEY=\"recurring\">0</FIELD>
    <FIELD KEY=\"recurring_type\"></FIELD>
    <FIELD KEY=\"remote_ip_address\">".$vars['remote_ip_address']."</FIELD>
  </FIELDS>
</TRANSACTION>";

        

        // prepare log record
        $vars_l = $vars; 
        $vars_l['cardnum'] = $cc_info['cc'];
        if ($vars['CVV2'])
            $vars_l['CVV2'] = preg_replace('/./', '*', $vars['CVV2']);
        unset ($vars_l['cardnum1']);
        unset ($vars_l['cardnum2']);
        unset ($vars_l['cardnum3']);
        unset ($vars_l['cardnum4']);
        unset ($vars_l['cardexpm']);
        unset ($vars_l['cardexpy']);
        $log[] = $vars_l;
        /////
        $db->log_error ("GoEmerchant REQUEST:<br />\n" . goemerchant_get_dump($vars_l));
        $res = $this->run_transaction($xml);
        $log[] = $res;

        if ($res['status'] == '1'){
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res['reference_number'], $log);
            return array(CC_RESULT_SUCCESS, "", $res['reference_number'], $log);
        } elseif ($res['status'] == '2') {
            return array(CC_RESULT_DECLINE_PERM, $res['error'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['error'], "", $log);
        }
    }
}

function goemerchant_get_member_links($user){
    return cc_core_get_member_links('goemerchant', $user);
}

function goemerchant_rebill(){
    return cc_core_rebill('goemerchant');
}
                                        
cc_core_init('goemerchant');
?>
