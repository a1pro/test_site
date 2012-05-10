<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: payvision payment plugin
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


function pvstartElement($parser, $name, $attrs)
{
global $depth,$lname;
$lname=$name;
$depth++;
}

function pvendElement($parser, $name)
{
global $depth;
$depth--;
}

function pvcharacterData($parser, $data)
{
global $depth,$lname,$pv_arrs;
if($depth>1)
	$pv_arrs[$lname] = $data;
}




class payment_payvision extends payment {
    var $paurl=null; // Web service location
	//ISO 3166
	var $countries = 
		array ("AF"=>"4","AL"=>"8","DZ"=>"12","AS"=>"16","AD"=>"20","AO"=>"24","AI"=>"660","AQ"=>"","AG"=>"28","AR"=>"32","AM"=>"51","AW"=>"533","AU"=>"36","AT"=>"40","AZ"=>"31","BS"=>"44","BH"=>"48","BD"=>"50","BB"=>"52","BY"=>"112","BE"=>"56","BZ"=>"84","BJ"=>"204","BM"=>"60","BT"=>"64","BO"=>"68","BA"=>"70","BW"=>"72","BV"=>"","BR"=>"76","IO"=>"","BN"=>"96","BG"=>"100","BF"=>"854","BI"=>"108","KH"=>"116","CM"=>"120","CA"=>"124","CV"=>"132","KY"=>"136","CF"=>"140","TD"=>"148","CL"=>"152","CN"=>"156","CX"=>"","CC"=>"","CO"=>"170","KM"=>"174","CG"=>"178","CD"=>"180","CK"=>"184","CR"=>"188","CI"=>"384","HR"=>"191","CU"=>"192","CY"=>"196","CZ"=>"203","DK"=>"208","DJ"=>"262","DM"=>"212","DO"=>"214","EC"=>"218","EG"=>"818","SV"=>"222","GQ"=>"226","ER"=>"232","EE"=>"233","ET"=>"231","FK"=>"238","FO"=>"234","FJ"=>"242","FI"=>"246","FR"=>"250","GF"=>"254","PF"=>"258","TF"=>"","GA"=>"266","GM"=>"270","GE"=>"268","DE"=>"276","GH"=>"288","GI"=>"292","GR"=>"300","GL"=>"304","GD"=>"308","GP"=>"312","GU"=>"316","GT"=>"320","GN"=>"324","GW"=>"624","GY"=>"328","HT"=>"332","HM"=>"","VA"=>"336","HN"=>"340","HK"=>"344","HU"=>"348","IS"=>"352","IN"=>"356","ID"=>"360","IR"=>"364","IQ"=>"368","IE"=>"372","IL"=>"376","IT"=>"380","JM"=>"388","JP"=>"392","JO"=>"400","KZ"=>"398","KE"=>"404","KI"=>"296","KP"=>"408","KR"=>"410","KW"=>"414","KG"=>"417","LA"=>"418","LV"=>"428","LB"=>"422","LS"=>"426","LR"=>"430","LY"=>"434","LI"=>"438","LT"=>"440","LU"=>"442","MO"=>"446","MK"=>"807","MG"=>"450","MW"=>"454","MY"=>"458","MV"=>"462","ML"=>"466","MT"=>"470","MH"=>"584","MQ"=>"474","MR"=>"478","MU"=>"480","YT"=>"","MX"=>"484","FM"=>"583","MD"=>"498","MC"=>"492","MN"=>"496","MS"=>"500","MA"=>"504","MZ"=>"508","MM"=>"104","NA"=>"516","NR"=>"520","NP"=>"524","NL"=>"528","AN"=>"530","NC"=>"540","NZ"=>"554","NI"=>"558","NE"=>"562","NG"=>"566","NU"=>"570","NF"=>"574","MP"=>"580","NO"=>"578","OM"=>"512","PK"=>"586","PW"=>"585","PS"=>"","PA"=>"591","PG"=>"598","PY"=>"600","PE"=>"604","PH"=>"608","PN"=>"612","PL"=>"616","PT"=>"620","PR"=>"630","QA"=>"634","RE"=>"638","RO"=>"642","RU"=>"643","RW"=>"646","SH"=>"654","KN"=>"659","LC"=>"662","PM"=>"666","VC"=>"670","WS"=>"882","SM"=>"674","ST"=>"678","SA"=>"682","SN"=>"686","CS"=>"","SC"=>"690","SL"=>"694","SG"=>"702","SK"=>"703","SI"=>"705","SB"=>"90","SO"=>"706","ZA"=>"710","GS"=>"","ES"=>"724","LK"=>"144","SD"=>"736","SR"=>"740","SJ"=>"744","SZ"=>"748","SE"=>"752","CH"=>"756","SY"=>"760","TW"=>"158","TJ"=>"762","TZ"=>"834","TH"=>"764","TL"=>"","TG"=>"768","TK"=>"772","TO"=>"776","TT"=>"780","TN"=>"788","TR"=>"792","TM"=>"795","TC"=>"796","TV"=>"798","UG"=>"800","UA"=>"804","AE"=>"784","GB"=>"826","US"=>"840","UM"=>"","UY"=>"858","UZ"=>"860","VU"=>"548","VE"=>"862","VN"=>"704","VG"=>"92","VI"=>"850","WF"=>"876","EH"=>"732","YE"=>"887","ZM"=>"894","ZW"=>"716");
	
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('payvision', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('payvision', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['payvision']['title'] ? $config['payment']['payvision']['title'] : "Payvision",
            'description' => $config['payment']['payvision']['description'] ? $config['payment']['payvision']['description'] : "Credit card payment",
            'code' => 1,
            'name' => 1
        );
    }

    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){	
        if ( CC_CHARGE_TYPE_REGULAR==$charge_type ) {
              return $this->payvision_regular($cc_info, $member, $amount,
        $currency, $product_description,
        $charge_type, $invoice, $payment);
        } else
		{
              return $this->payvision_recurring($cc_info, $member, $amount,
        $currency, $product_description,
        $charge_type, $invoice, $payment);
        }
	}
		
	
    //regular payment
    function payvision_regular($cc_info, $member, $amount, 
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
			//"op" => "Payment",
            "memberId"    => $this->config['memberid'],
			"memberGuid"    => $this->config['memberguid'],
            "countryId"  => $this->countries[$cc_info['cc_country']],
            "amount" => $amount,
            "currencyId" => $this->config['currency'],
            "trackingMemberCode" => $member['member_id'].' '.$product_description.time(),
            "cardNumber" => $cc_info['cc_number'],
            "cardholder" => $cc_info['cc_name'],
            "cardExpiryMonth" => $cc_info['cc_expire_Month'],
            "cardExpiryYear" => $cc_info['cc_expire_Year'],
            "cardCvv" => $cc_info['cc_code'],
            "merchantAccountType" =>  "1",
			"dynamicDescriptor" => "",
			"cardType" => "",
			"issueNumber" => "",
			"avsAddress" => "",
			"avsZip" => ""
        );

        // prepare log record
        $vars_l = $vars;
		$vars_l['cardNumber'] = $cc_info['cc'];
        if ($vars['cardCvv'])
            $vars_l['cardCvv'] = preg_replace('/./', '*', $vars['cardCvv']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction('/gateway/basicoperations.asmx/Payment',$vars);
        $log[] = $res;

        if ($res['Result'] == '0'){
            return array(CC_RESULT_SUCCESS, "", $res['TransactionId'], $log);
        }elseif (!is_array($res)) {
            return array(CC_RESULT_INTERNAL_ERROR, $res, "", $log);
        } else{
            return array(CC_RESULT_DECLINE_PERM, $res['Message']." (Error code - $res[Result])", "", $log);
		}
    }

	//recurring payment
    function payvision_recurring($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config,$db;
        $log = array();
        //////////////////////// cc_bill /////////////////////////
		if (!$member['data']['payvision_cardid'] || !$member['data']['payvision_cardguid'])
		{
			$result=$this->save_cc_info($cc_info, $member);
			if (is_array($result)) return $result;
		}
		$member=$db->get_user($member['member_id']);
		if (!$member['data']['payvision_cardid'] || !$member['data']['payvision_cardguid'])return array(CC_RESULT_DECLINE_PERM, 'payvision CardGuid or CardId is not defined', "", $log);
        srand(time());

		if(floatval($amount)==0)
			return array(CC_RESULT_SUCCESS, "", "FREE TRIAL", $log);
        if(!$product_description){
	         global $db;
	         $product = $db->get_product($payment['product_id']);
	         $product_description = $product['title'];
		}
		$country = $member['country'] ? $this->countries[$member['country']] : $this->countries[$cc_info['cc_country']];
		$country = $country ? $country : '0';
        $vars = array(
			//"op" => "Payment",
            "memberId"    => $this->config['memberid'],
			"memberGuid"    => $this->config['memberguid'],
            "countryId"  => $country,
            "amount" => $amount,
            "currencyId" => $this->config['currency'],
            "trackingMemberCode" => $member['member_id'].' '.$product_description.time(),
            "cardId" => $member['data']['payvision_cardid'],
            "cardGuid" => $member['data']['payvision_cardguid'],
            "merchantAccountType" =>  "4",
			"dynamicDescriptor" => "",
			"cardType" => "",
			"issueNumber" => ""
        );

        // prepare log record
        $vars_l = $vars;
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction('/gateway/recurringoperations.asmx/Payment',$vars);
        $log[] = $res;

        if ($res['Result'] == '0'){
            return array(CC_RESULT_SUCCESS, "", $res['TransactionId'], $log);
        }elseif (!is_array($res)) {
            return array(CC_RESULT_INTERNAL_ERROR, $res, "", $log);
        } else{
            return array(CC_RESULT_DECLINE_PERM, $res['Message']." (Error code - $res[Result])", "", $log);
		}
    }

    
	
	function save_cc_info($cc_info, & $member) {
        global $db;
        ////validate user profile, update if incorrect, create if no exists
        $member['data']['cc'] = '**** **** **** '.substr($cc_info['cc_number'], -4);
        if (isset($cc_info['cc_expire_Month'])) {
            $member['data']['cc-expire'] = sprintf('%02d%02d', $cc_info['cc_expire_Month'], substr($cc_info['cc_expire_Year'], 2));
        } else {
            $member['data']['cc-expire'] = $cc_info['cc-expire'];
        }
        $vars = array(
			//"op" => "RegisterCard",
            "memberId"    => $this->config['memberid'],			
			"memberGuid"    => $this->config['memberguid'],
            "number" => $cc_info['cc_number'],
            "holder" => $cc_info['cc_name'],
            "expiryMonth" => $cc_info['cc_expire_Month'],
            "expiryYear" => $cc_info['cc_expire_Year'],
			"cardType" => "",
			"issueNumber" => ""
        );
        $vars_l = $vars;
		$vars_l['number'] = $member['data']['cc'];
        $log[] = $vars_l;
		$res = $this->run_transaction('/gateway/recurringoperations.asmx/RegisterCard',$vars);
		$log[] = $res;
        if ($res['Result'] == '0'){
			$member['data']['payvision_cardid'] = $res['CardId'];
			$member['data']['payvision_cardguid'] = $res['CardGuid'];
			$db->update_user($member['member_id'], $member);
			return '';
        }elseif (!is_array($res)) {
            return array(CC_RESULT_INTERNAL_ERROR, $res, "", $log);
        } else{
            return array(CC_RESULT_DECLINE_PERM, $res['Message']." (Error code - $res[Result])", "", $log);
		}
    }
	
	
    function run_transaction($url,$vars){
		global $pv_arrs;
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $xml_response = cc_core_get_url($this->paurl.$url, $vars1);

		$pv_arrs = array();
		$xml_parser = xml_parser_create(); 
		xml_set_element_handler($xml_parser, "pvstartElement", "pvendElement");
		xml_set_character_data_handler($xml_parser, "pvcharacterData");
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		xml_parse($xml_parser, $xml_response);
		xml_parser_free ($xml_parser);
		if(count($pv_arrs))
			return $pv_arrs;
		else
		return $xml_response;
    }
    function payment_payvision($config) {
        parent::payment($config);
        
        add_member_field('payvision_cardid', '', 'hidden');
		add_member_field('payvision_cardguid', '', 'hidden');
        if ($this->config['testing'])
            $this->paurl="https://testprocessor.payvisionservices.com";
		else
			$this->paurl="https://testprocessor.payvisionservices.com";

    }
	
}

function payvision_get_member_links($user){
    return cc_core_get_member_links('payvision', $user);
}

function payvision_rebill(){
    return cc_core_rebill('payvision');
}
                                        
cc_core_init('payvision');