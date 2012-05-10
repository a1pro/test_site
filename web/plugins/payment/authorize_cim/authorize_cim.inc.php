<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: authorize_cim payment plugin
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 2078 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

//parse xml response
function authorize_xml_parse($xml) {
    $pattern1='/<([a-zA-Z]+)>([-_a-zA-Z0-9\.,@\s\|]*)<\/\1>/U';
    $pattern='/<([a-zA-Z]+)>([-_a-zA-Z0-9\.,@\s<>\/\|]*)<\/\1>/U';

    if ( preg_match($pattern1, $xml) ) {
        preg_match_all($pattern, $xml, $match);
        $ar = authorize_xml_clone_calculate($match[1]);
        foreach ($match[2] as $k=>$v) {
            if ($ar[$match[1][$k]]['amount']>1) {
                $ar[$match[1][$k]]['counter']++;
                $result[$match[1][$k]][$ar[$match[1][$k]]['counter']] = authorize_xml_parse($v);
            } else {
                $result[$match[1][$k]] = authorize_xml_parse($v);
            }
        }
    } else {
        $result=$xml;
    }
    return $result;
}


function authorize_xml_clone_calculate($ar) {
    $result=array();
    foreach ($ar as $k=>$v) {
        if (!isset($result[$v])) {
            $result[$v]['amount']=0;
            $result[$v]['counter']=-1;
        }
        $result[$v]['amount']++;
    }
    return $result;
}


class payment_authorize_cim extends payment {

    var $wsl=null; // Web service location (for cim)
    var $tpu=null; // Transaction post url (for aim)

    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('authorize_cim', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('authorize_cim', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['authorize_cim']['title'] ? $config['payment']['authorize_cim']['title'] : _PLUG_PAY_AUTHORIZE_CIM_TITLE,
            'description' => $config['payment']['authorize_cim']['description'] ? $config['payment']['authorize_cim']['description'] : _PLUG_PAY_AUTHORIZE_CIM_DESC,
            'phone' => 2,
            'company' => 1,
            'code' => 1,
            'name_f' => 2
        );
    }

    
    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        
        if ( CC_CHARGE_TYPE_REGULAR==$charge_type ) {
              return $this->cc_bill_aim($cc_info, $member, $amount,
        $currency, $product_description,
        $charge_type, $invoice, $payment);
        } else {
              return $this->cc_bill_cim($cc_info, $member, $amount,
        $currency, $product_description,
        $charge_type, $invoice, $payment);
        }

    }
    
    
    //
    //        AUTHORIZE AIM TRANSACTION (without creating payment and user profiles)
    //
    /////////////////////////////////////////////////////////////////

    function cc_bill_aim($cc_info, $member, $amount,
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
            "x_Login"    => $this->config['login'],
            "x_Version"  => "3.1",
            "x_Delim_Data" => "True",
            "x_Tran_Key" => $this->config['tkey'],
            "x_Delim_Char" => "|",
            "x_Invoice_Num" => $payment['payment_id'] . '-' . rand(100, 999),
            "x_Amount" =>   $amount,
            "x_Currency_Code" => $currency ? $currency : 'USD',
            "x_Card_Num" => $cc_info['cc_number'],
            "x_Exp_Date" => $cc_info['cc-expire'],
            "x_Type"     => "AUTH_CAPTURE",
            "x_Relay_Response" => 'FALSE',
            "x_Email"    =>    $member['email'],
            "x_Description" => $product_description,
            "x_Cust_ID" =>  $member['member_id'],
            "x_First_Name" =>  $cc_info['cc_name_f'],
            "x_Last_Name" =>   $cc_info['cc_name_l'],
            "x_Address" =>  $cc_info['cc_street'],
            "x_City" =>     $cc_info['cc_city'],
            "x_State" =>    $cc_info['cc_state'],
            "x_Zip" =>      $cc_info['cc_zip'],
            "x_Country" =>  $cc_info['cc_country'],
            "x_Company" =>  $cc_info['cc_company'],
            "x_Customer_IP" => $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'],
            "x_Phone"   => $cc_info['cc_phone']
        );

        if ($this->config['testing'])
            $vars['x_Test_Request'] = 'TRUE';
        if ($cc_info['cc_code'])
            $vars['x_Card_Code'] = $cc_info['cc_code'];

        // prepare log record
        $vars_l = $vars;
        $vars_l['x_Card_Num'] = $cc_info['cc'];
        if ($vars['x_Card_Code'])
            $vars_l['x_Card_Code'] = preg_replace('/./', '*', $vars['x_Card_Code']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['RESULT'] == '1'){
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif ($res['RESULT'] == '2') {
            return array(CC_RESULT_DECLINE_PERM, $res['RESPMSG'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['RESPMSG'], "", $log);
        }
    }
    
    function run_transaction($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url($this->tpu, $vars1);
        $arr = preg_split('/\|/', $ret);
        $res = array(
            'RESULT'      => $arr[0],
            'RESULT_SUB'  => $arr[1],
            'REASON_CODE' => $arr[2],
            'RESPMSG'     => $arr[3],
            'AVS'         => $arr[5],
            'PNREF'       => $arr[6],
            'CVV_VALID'   => $arr[48]
        );
        return $res;
    }
    
    
    //
    //        AUTHORIZE CIM TRANSACTION (with creating payment and user profiles)
    //
    /////////////////////////////////////////////////////////////////
    
    
    function cc_bill_cim($cc_info, $member, $amount,
        $currency, $product_description,
        $charge_type, $invoice, $payment){
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        if (CC_CHARGE_TYPE_RECURRING == $charge_type) {

            $result=$this->convert_cc_info($cc_info, $member);
            if (is_array($result))
                return $result;

            if ( !($member['data']['authorize_cim_user_profile_id'] &&
                   $member['data']['authorize_cim_payment_profile_id']) ) {

                return array(CC_RESULT_DECLINE_PERM, 'authorize_cim_user_profile_id or authorize_cim_payment_profile_id is not defined', "", $log);
            }
        } else {
            $result=$this->save_cc_info($cc_info, $member);
            if (is_array($result))
                return $result;
        }

        if (0==$amount)
            return array(CC_RESULT_SUCCESS, "", 'free', array());

        ////do transaction
        $transaction['amount']                       = $amount;
        $transaction['tax']['amount']                = '';
        $transaction['tax']['name']                  = '';
        $transaction['tax']['description']           = '';
        $transaction['customerProfileId']            = $member['data']['authorize_cim_user_profile_id'];
        $transaction['customerPaymentProfileId']     = $member['data']['authorize_cim_payment_profile_id'];
        $transaction['order']['invoiceNumber']       = $invoice;
        $transaction['order']['description']         = $product_description;
        $transaction['order']['purchaseOrderNumber'] = '';
        if (isset($cc_info['cc_code']) && $cc_info['cc_code']!='')
              $transaction['cardCode']=$cc_info['cc_code'];

        $result = $this->createCustomerProfileTransactionRequest($transaction);
        $log[]['XML_REQUEST']=$result['request'];
        $log[]['XML_RESPONSE']=$result['response'];

        if ('Error' == $result['result']['messages']['resultCode']) {
                    return array(CC_RESULT_INTERNAL_ERROR, $result['result']['messages']['message']['code'].":".$result['result']['messages']['message']['text'], "", $log);
            }

        if ('Error' == $result['result']['resultCode']) {
                    return array(CC_RESULT_INTERNAL_ERROR, $result['result']['code'], "", $log);
                }

        $log[]['directResponse'] = $result['result']['directResponse'];

        $res = explode('|', $result['result']['directResponse']);

        return array(CC_RESULT_SUCCESS, "", $res[6], $log);

    }
    

    function _preparation_expire($expire) {
        $pattern = '/(\d\d)(\d\d)/';
        preg_match($pattern, $expire, $match);
        return '20'.$match[2].'-'.$match[1];
    }

    //convert cc_info to authorize_cim_user_profile_id and authorize_cim_user_profile_id
    function convert_cc_info($cc_info, & $member) {

        global $db;

        $fields_to_clear = array ('cc_country',
        'cc_street', 'cc_city', 'cc_state',
        'cc_zip', 'cc_name_f', 'cc_name_l',
        'cc_company', 'cc_phone', 'cc-hidden');
        
        if ( $member['data']['cc-hidden'] ) {

            $result=$this->save_cc_info($cc_info, $member);
            if (is_array($result))
                return $result;

            //clear cc info
            foreach ($fields_to_clear as $f) {
                if ( isset($member['data'][$f]) )
                    $member['data'][$f]='';
            }
            $db->update_user($member['member_id'], $member);
        }
    
        return '';
    }

    function save_cc_info($cc_info, & $member) {
        global $db;
        ////validate user profile, update if incorrect, create if no exists
        $member['data']['cc'] = '**** **** **** '.substr($cc_info['cc_number'], -4);
        if (isset($cc_info['cc_expire_Month'])) {
            $member['data']['cc-expire'] = sprintf('%02d%02d', $cc_info['cc_expire_Month'], substr($cc_info['cc_expire_Year'], 2, 2));
        } else {
            $member['data']['cc-expire'] = $cc_info['cc-expire'];
        }
        
        $hash = md5("$member[member_id]:$member[email]");
        if ($member['data']['authorize_cim_user_profile_id']) {
        //profile already created
            if ($hash!=$member['data']['authorize_cim_user_profile_hash']) {
                $uprofile['merchantCustomerId'] = $member['member_id'];
                $uprofile['description']        = $member['login'];
                $uprofile['email']              = $member['email'];
                $uprofile['customerProfileId']  = $member['data']['authorize_cim_user_profile_id'];

                $result = $this->updateCustomerProfileRequest($uprofile);
                $log[]['XML_REQUEST']=$result['request'];
                $log[]['XML_RESPONSE']=$result['response'];

                if ('Error' == $result['result']['messages']['resultCode']) {
                    return array(CC_RESULT_INTERNAL_ERROR, $result['result']['messages']['message']['code'].":".$result['result']['messages']['message']['text'], "", $log);
                }

                if ('Error' == $result['result']['resultCode']) {
                    return array(CC_RESULT_INTERNAL_ERROR, $result['result']['code'], "", $log);
                }


                $member['data']['authorize_cim_user_profile_hash']=$hash;

                $db->update_user($member['member_id'], $member);
            }

        } else {
            $uprofile['merchantCustomerId'] = $member['member_id'];
            $uprofile['description']        = $member['login'];
            $uprofile['email']              = $member['email'];

            $result = $this->createCustomerProfileRequest($uprofile);
            $log[]['XML_REQUEST']  = $result['request'];
            $log[]['XML_RESPONSE'] = $result['response'];

            if ('Error' == $result['result']['messages']['resultCode']) {
                    return array(CC_RESULT_INTERNAL_ERROR, $result['result']['messages']['message']['code'].":".$result['messages']['message']['text'], "", $log);
            }

            if ('Error' == $result['result']['resultCode']) {
                    return array(CC_RESULT_INTERNAL_ERROR, $result['result']['code'], "", $log);
                }

            $member['data']['authorize_cim_user_profile_id']   = $result['result']['customerProfileId'];
            $member['data']['authorize_cim_user_profile_hash'] = $hash;

            $db->update_user($member['member_id'], $member);
        }


        ////validate payment profile, update if incorrect, create if no exists
        $hash = md5("$cc_info[cc_number]:$cc_info[cc_expire_Month]:$cc_info[cc_expire_Year]:$cc_info[cc_name_f]:$cc_info[cc_name_l]:$cc_info[cc_country]:$cc_info[cc_state]:$cc_info[cc_city]:$cc_info[cc_street]:$cc_info[cc_zip]:$cc_info[cc_phone]");
        if ($member['data']['authorize_cim_payment_profile_id']) {
        //payment profile already created
            if ($hash!=$member['data']['authorize_cim_payment_profile_hash']) {
                $pprofile['customerProfileId']        = $member['data']['authorize_cim_user_profile_id'];
                $pprofile['firstName']                = $cc_info['cc_name_f'];
                $pprofile['lastName']                 = $cc_info['cc_name_l'];
                $pprofile['company']                  = '';
                $pprofile['address']                  = $cc_info['cc_street'];
                $pprofile['city']                     = $cc_info['cc_city'];
                $pprofile['state']                    = $cc_info['cc_state'];
                $pprofile['zip']                      = $cc_info['cc_zip'];
                $pprofile['country']                  = $cc_info['cc_country'];
                $pprofile['phoneNumber']              = $cc_info['cc_phone'];
                $pprofile['faxNumber']                = '';
                $pprofile['cardNumber']               = $cc_info['cc_number'];
                $pprofile['expirationDate']           = $this->_preparation_expire( $member['data']['cc-expire'] );
                $pprofile['customerPaymentProfileId'] = $member['data']['authorize_cim_payment_profile_id'];

                $result = $this->updateCustomerPaymentProfileRequest($pprofile);
                $log[]['XML_REQUEST']  = $result['request'];
                $log[]['XML_RESPONSE'] = $result['response'];
                
                if ('Error' == $result['result']['messages']['resultCode']) {
                    return array(CC_RESULT_INTERNAL_ERROR, $result['result']['messages']['message']['code'].":".$result['result']['messages']['message']['text'], "", $log);
                }

                if ('Error' == $result['result']['resultCode']) {
                    return array(CC_RESULT_INTERNAL_ERROR, $result['result']['code'], "", $log);
                }

                $member['data']['authorize_cim_payment_profile_hash']=$hash;

                $db->update_user($member['member_id'], $member);
            }

        } else {
            $pprofile['customerProfileId']        = $member['data']['authorize_cim_user_profile_id'];
            $pprofile['firstName']                = $cc_info['cc_name_f'];
            $pprofile['lastName']                 = $cc_info['cc_name_l'];
            $pprofile['company']                  = '';
            $pprofile['address']                  = $cc_info['cc_street'];
            $pprofile['city']                     = $cc_info['cc_city'];
            $pprofile['state']                    = $cc_info['cc_state'];
            $pprofile['zip']                      = $cc_info['cc_zip'];
            $pprofile['country']                  = $cc_info['cc_country'];
            $pprofile['phoneNumber']              = $cc_info['cc_phone'];
            $pprofile['faxNumber']                = '';
            $pprofile['cardNumber']               = $cc_info['cc_number'];
            $pprofile['expirationDate']           = $this->_preparation_expire( $member['data']['cc-expire'] );
            $pprofile['validationMode']           = 'liveMode';

            $result = $this->createCustomerPaymentProfileRequest($pprofile);
            $log[]['XML_REQUEST']  = $result['request'];
            $log[]['XML_RESPONSE'] = $result['response'];

            if ('Error' == $result['result']['messages']['resultCode']) {
                    return array(CC_RESULT_INTERNAL_ERROR, $result['result']['messages']['message']['code'].":".$result['result']['messages']['message']['text'], "", $log);
            }

            if ('Error' == $result['result']['resultCode']) {
                    return array(CC_RESULT_INTERNAL_ERROR, $result['result']['code'], "", $log);
                }

            $member['data']['authorize_cim_payment_profile_id']   = $result['result']['customerPaymentProfileId'];
            $member['data']['authorize_cim_payment_profile_hash'] = $hash;

            $db->update_user($member['member_id'], $member);
        }
        return '';
    }

    function _getAnswer($url, $xml){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $response = curl_exec($ch);
        $result['result']   = authorize_xml_parse( $response );
        //prepear to log
        $xml=preg_replace('/<name>[a-zA-Z0-9]*<\/name>/', '<name>*********</name>', $xml);
        $xml=preg_replace('/<transactionKey>[a-zA-Z0-9]*<\/transactionKey>/', '<transactionKey>*********</transactionKey>', $xml);
        $xml=preg_replace('/<cardNumber>[a-zA-Z0-9]*<\/cardNumber>/', '<cardNumber>**** **** **** ****</cardNumber>', $xml);
        $xml=preg_replace('/<cardCode>[a-zA-Z0-9]*<\/cardCode>/', '<cardCode>***</cardCode>', $xml);
        $result['response'] = nl2br( htmlentities(str_replace('><', ">\n<", $response)) );
        $result['request']  = nl2br( htmlentities(preg_replace('/>\s*</', ">\n<", $xml)) );
        return $result;
    }

    function createCustomerProfileRequest($vars) {
    
        $xml='<?xml version="1.0" encoding="utf-8"?>
<createCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>'.$this->config['login'].'</name>
    <transactionKey>'.$this->config['tkey'].'</transactionKey>
  </merchantAuthentication>
  <profile>
    <merchantCustomerId>'.$vars['merchantCustomerId'].'</merchantCustomerId>
    <description>'.$vars['description'].'</description>
    <email>'.$vars['email'].'</email>
  </profile>
</createCustomerProfileRequest>';

       $result=$this->_getAnswer($this->wsl, $xml);
       return $result;
    }
    
    function createCustomerPaymentProfileRequest($vars) {

        $xml='<?xml version="1.0" encoding="utf-8"?>
<createCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>'.$this->config['login'].'</name>
    <transactionKey>'.$this->config['tkey'].'</transactionKey>
  </merchantAuthentication>
  <customerProfileId>'.$vars['customerProfileId'].'</customerProfileId>
  <paymentProfile>
    <billTo>
      <firstName>'.$vars['firstName'].'</firstName>
      <lastName>'.$vars['lastName'].'</lastName>
      <company>'.$vars['company'].'</company>
      <address>'.$vars['address'].'</address>
      <city>'.$vars['city'].'</city>
      <state>'.$vars['state'].'</state>
      <zip>'.$vars['zip'].'</zip>
      <country>'.$vars['country'].'</country>
      <phoneNumber>'.$vars['phoneNumber'].'</phoneNumber>
      <faxNumber>'.$vars['faxNumber'].'</faxNumber>
    </billTo>
    <payment>
      <creditCard>
        <cardNumber>'.$vars['cardNumber'].'</cardNumber>
        <expirationDate>'.$vars['expirationDate'].'</expirationDate>
      </creditCard>
    </payment>
  </paymentProfile>
  <validationMode>'.$vars['validationMode'].'</validationMode>
</createCustomerPaymentProfileRequest>';
        
       $result=$this->_getAnswer($this->wsl, $xml);
       return $result;
    }
    


    
    function createCustomerProfileTransactionRequest($vars) {

        $xml='<?xml version="1.0" encoding="utf-8"?>
<createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>'.$this->config['login'].'</name>
    <transactionKey>'.$this->config['tkey'].'</transactionKey>
  </merchantAuthentication>
  <transaction>
    <profileTransAuthCapture>
      <amount>'.$vars['amount'].'</amount>
      <customerProfileId>'.$vars['customerProfileId'].'</customerProfileId>
      <customerPaymentProfileId>'.$vars['customerPaymentProfileId'].'</customerPaymentProfileId>
      <order>
        <invoiceNumber>'.$vars['order']['invoiceNumber'].'</invoiceNumber>
        <description>'.$vars['order']['description'].'</description>
        <purchaseOrderNumber>'.$vars['order']['purchaseOrderNumber'].'</purchaseOrderNumber>
      </order>';
      if (isset($vars['cardCode'])) {
          $xml.="<cardCode>$vars[cardCode]</cardCode>";
      }
      $xml.='</profileTransAuthCapture>
  </transaction>
</createCustomerProfileTransactionRequest>';

       $result=$this->_getAnswer($this->wsl, $xml);
       return $result;
    }

    function deleteCustomerProfileRequest($vars) {

        $xml='<?xml version="1.0" encoding="utf-8"?>
<deleteCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>'.$this->config['login'].'</name>
    <transactionKey>'.$this->config['tkey'].'</transactionKey>
  </merchantAuthentication>
  <customerProfileId>'.$vars['customerProfileId'].'</customerProfileId>
</deleteCustomerProfileRequest>';

       $result=$this->_getAnswer($this->wsl, $xml);
       return $result;
    }
    
    
    function deleteCustomerPaymentProfileRequest($vars) {

        $xml='<?xml version="1.0" encoding="utf-8"?>
<deleteCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>'.$this->config['login'].'</name>
    <transactionKey>'.$this->config['tkey'].'</transactionKey>
  </merchantAuthentication>
  <customerProfileId>10000</customerProfileId>
  <customerPaymentProfileId>'.$vars['customerPaymentProfileId'].'</customerPaymentProfileId>
</deleteCustomerPaymentProfileRequest>';

       $result=$this->_getAnswer($this->wsl, $xml);
       return $result;
    }
    
    function getCustomerProfileRequest($vars) {

        $xml='<?xml version="1.0" encoding="utf-8"?>
<getCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>'.$this->config['login'].'</name>
    <transactionKey>'.$this->config['tkey'].'</transactionKey>
  </merchantAuthentication>
  <customerProfileId>'.$vars['customerProfileId'].'</customerProfileId>
</getCustomerProfileRequest>';

       $result=$this->_getAnswer($this->wsl, $xml);
       return $result;
    }
    
    function getCustomerPaymentProfileRequest($vars) {

        $xml='<?xml version="1.0" encoding="utf-8"?>
<getCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>'.$this->config['login'].'</name>
    <transactionKey>'.$this->config['tkey'].'</transactionKey>
  </merchantAuthentication>
  <customerProfileId>'.$vars['customerProfileId'].'</customerProfileId>
  <customerPaymentProfileId>'.$vars['customerPaymentProfileId'].'</customerPaymentProfileId>
</getCustomerPaymentProfileRequest>';

       $result=$this->_getAnswer($this->wsl, $xml);
       return $result;
    }
    
    function updateCustomerProfileRequest($vars) {

        $xml='<?xml version="1.0" encoding="utf-8"?>
<updateCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>'.$this->config['login'].'</name>
    <transactionKey>'.$this->config['tkey'].'</transactionKey>
  </merchantAuthentication>
  <profile>
    <merchantCustomerId>'.$vars['merchantCustomerId'].'</merchantCustomerId>
    <description>'.$vars['description'].'</description>
    <email>'.$vars['email'].'</email>
    <customerProfileId>'.$vars['customerProfileId'].'</customerProfileId>
  </profile>
</updateCustomerProfileRequest>';

       $result=$this->_getAnswer($this->wsl, $xml);
       return $result;
    }
    
    function updateCustomerPaymentProfileRequest($vars) {

        $xml='<?xml version="1.0" encoding="utf-8"?>
<updateCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>'.$this->config['login'].'</name>
    <transactionKey>'.$this->config['tkey'].'</transactionKey>
  </merchantAuthentication>
  <customerProfileId>'.$vars['customerProfileId'].'</customerProfileId>
  <paymentProfile>
    <billTo>
      <firstName>'.$vars['firstName'].'</firstName>
      <lastName>'.$vars['lastName'].'</lastName>
      <company>'.$vars['company'].'</company>
      <address>'.$vars['address'].'</address>
      <city>'.$vars['city'].'</city>
      <state>'.$vars['state'].'</state>
      <zip>'.$vars['zip'].'</zip>
      <country>'.$vars['country'].'</country>
      <phoneNumber>'.$vars['phoneNumber'].'</phoneNumber>
      <faxNumber>'.$vars['faxNumber'].'</faxNumber>
    </billTo>
    <payment>
      <creditCard>
        <cardNumber>'.$vars['cardNumber'].'</cardNumber>
        <expirationDate>'.$vars['expirationDate'].'</expirationDate>
      </creditCard>
    </payment>
    <customerPaymentProfileId>'.$vars['customerPaymentProfileId'].'</customerPaymentProfileId>
  </paymentProfile>
</updateCustomerPaymentProfileRequest>';

       $result=$this->_getAnswer($this->wsl, $xml);
       return $result;
    }
    
    function validateCustomerPaymentProfileRequest($vars) {

        $xml='<?xml version="1.0" encoding="utf-8"?>
<validateCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>'.$this->config['login'].'</name>
    <transactionKey>'.$this->config['tkey'].'</transactionKey>
  </merchantAuthentication>
  <customerProfileId>'.$vars['customerProfileId'].'</customerProfileId>
  <customerPaymentProfileId>'.$vars['customerPaymentProfileId'].'</customerPaymentProfileId>
  <validationMode>liveMode</validationMode>
</validateCustomerPaymentProfileRequest>';

       $result=$this->_getAnswer($this->wsl, $xml);
       return $result;
    }
    
    function payment_authorize_cim($config) {
        parent::payment($config);
        
        add_member_field('authorize_cim_user_profile_id', '', 'hidden');
        add_member_field('authorize_cim_user_profile_hash', '', 'hidden');
        add_member_field('authorize_cim_payment_profile_id', '', 'hidden');
        add_member_field('authorize_cim_payment_profile_hash', '', 'hidden');
      
        if ($this->config['testing']) {
            $this->wsl = "https://apitest.authorize.net/xml/v1/request.api";
            $this->tpu = "https://test.authorize.net/gateway/transact.dll";
        } else {
            $this->wsl = "https://api.authorize.net/xml/v1/request.api";
            $this->tpu = "https://secure.authorize.net/gateway/transact.dll";
        }
    
    }

}

function authorize_cim_get_member_links($user){
    return cc_core_get_member_links('authorize_cim', $user);
}

function authorize_cim_rebill(){
    return cc_core_rebill('authorize_cim');
}
                                        
cc_core_init('authorize_cim');