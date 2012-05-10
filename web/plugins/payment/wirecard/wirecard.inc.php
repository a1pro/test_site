<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 

global $config;


require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_wirecard extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('wirecard', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }

    
    
    function get_url($xml){
		$header = array ("Authorization: Basic "
                     . base64_encode ($this->config[user] . ":" . $this->config[pass] . "\n"),
	         "Content-Type: text/xml");
        $ch=curl_init($this->config[gateway]);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($add_referer)
            curl_setopt($ch, CURLOPT_REFERER, "$config[root_surl]/signup.php");
        
        if (strpos($db->config['host'], ".secureserver.net") > 0){
            //use GoDaddy proxy
            curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE); 
            curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); 
			curl_setopt ($ch, CURLOPT_PROXY,"http://proxy.shr.secureserver.net:3128");
            curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);        
        }
        
        $buffer = curl_exec($ch);
        curl_close($ch);
        return $buffer;
    }
    
    
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('wirecard', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['wirecard']['title'] ? $config['payment']['wirecard']['title'] : "WireCard",
            'description' => $config['payment']['wirecard']['description'] ? $config['payment']['wirecard']['description'] : "Credit Card Payment",
            'currency' => array('USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP'),
            'phone' => 2,
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
        global $config,$db;
        $log = array();
        //////////////////////// cc_bill /////////////////////////
		$product = $db->get_product($payment[product_id]);        	
        if ($charge_type == CC_CHARGE_TYPE_TEST){
			$amount = 1.00;        		
		}        	
		$exp_year = "20".substr($cc_info['cc-expire'], 2, 2);
		$exp_month = substr($cc_info['cc-expire'], 0, 2);
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
		<WIRECARD_BXML xmlns:xsi=\"http://www.w3.org/1999/XMLSchema-instance\"
		xsi:noNamespaceSchemaLocation=\"wirecard.xsd\">
		
		<W_REQUEST>
			<W_JOB>
				<JobID>job $invoice</JobID>
				<BusinessCaseSignature>".$this->config[signature]."</BusinessCaseSignature>
				<FNC_CC_PURCHASE>
					<FunctionID>transaction $invoice</FunctionID>
					<CC_TRANSACTION mode=\"".$this->config[mode]."\">
						<TransactionID>$invoice</TransactionID>
						<CommerceType>eCommerce</CommerceType>
						<Amount minorunits=\"2\">".($amount*100)."</Amount>
						<Currency>".($currency ? $currency : "USD")."</Currency>
						<CountryCode>".$this->config[country]."</CountryCode>
						<RECURRING_TRANSACTION>
							<Type>Initial</Type>
						</RECURRING_TRANSACTION>
						<CREDIT_CARD_DATA>
							<CreditCardNumber>".$cc_info['cc_number']."</CreditCardNumber>
							".($cc_info[cc_code] ? "<CVC2>".$cc_info[cc_code]."</CVC2>" : "")."
							<ExpirationYear>".$exp_year."</ExpirationYear>
							<ExpirationMonth>".$exp_month."</ExpirationMonth>
							<CardHolderName>".$cc_info['cc_name_f']." ".$cc_info['cc_name_l']."</CardHolderName>
						</CREDIT_CARD_DATA>
						<CONTACT_DATA>
							<IPAddress>".$member[remote_addr]."</IPAddress>
						</CONTACT_DATA>
						<CORPTRUSTCENTER_DATA>
							<ADDRESS>
								<FirstName>".$member[name_f]."</FirstName>
								<LastName>".$member[name_l]."</LastName>
								<Address1>".$cc_info['cc_street']."</Address1>
								<City>".$cc_info['cc_city']."</City>
								<ZipCode>".$cc_info['cc_zip']."</ZipCode>
								<State>".$cc_info['cc_state']."</State>
								<Country>".$cc_info['cc_country']."</Country>
								<Email>".$member[email]."</Email>
							</ADDRESS>
						</CORPTRUSTCENTER_DATA>
					</CC_TRANSACTION>
				</FNC_CC_PURCHASE>
			</W_JOB>
		</W_REQUEST>
	</WIRECARD_BXML>
";
        
        if ($charge_type == CC_CHARGE_TYPE_RECURRING){
	    $xml = "
<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<WIRECARD_BXML xmlns:xsi=\"http://www.w3.org/1999/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"wirecard.xsd\">
    <W_REQUEST>
	<W_JOB>
	    <JobID>job $invoice</JobID>
	    <BusinessCaseSignature>".$this->config[signature]."</BusinessCaseSignature>
	    <FNC_CC_TRANSACTION>
	        <FunctionID>transaction $invoice</FunctionID>
		    <CC_TRANSACTION>
			<TransactionID>$invoice</TransactionID>
			<GuWID>".find_parent_payment($invoice)."</GuWID>
			<RECURRING_TRANSACTION>
			    <Type>Repeated</Type>
			</RECURRING_TRANSACTION>
		    </CC_TRANSACTION>
	    </FNC_CC_TRANSACTION>
	</W_JOB>
    </W_REQUEST>
</WIRECARD_BXML>
	    ";	    
	}
		


        $vars_l = str_replace($cc_info['cc_number'], $cc_info['cc'], $xml);
        $vars_l = preg_replace("|<CreditCardNumber>(.+?)</CreditCardNumber>|", 
            "<CreditCardNumber>$cc_info[cc]</CreditCardNumber>", $vars_l);
        $vars_l = str_replace("<CVC2>".$cc_info['cc_code'], '<CVC2>***', $vars_l);
        $log[] = array('xml'=>nl2br(str_replace('<', '&lt;',$vars_l)));
//        $xml = urlencode($xml);
        
        $ret = $this->get_url($xml);
        $res=$this->xml_ParseXML($ret);
        $ret = str_replace('<', '&lt;', $ret);
        $ret = preg_replace('/(&lt;\/\w+>)/', "\\1<br />\n", $ret);
        $log[] = array('ret'=>$ret);

        if($err = $res[WIRECARD_BXML][0][W_RESPONSE][0][W_JOB][0][ERROR]){
            return array(CC_RESULT_INTERNAL_ERROR, $err[0][Message][0]."<br/>".join("<br/>",$err[0][Advice]), "", $log);
        
        }
        
	if ($charge_type == CC_CHARGE_TYPE_RECURRING)
        $ps = $res[WIRECARD_BXML][0][W_RESPONSE][0][W_JOB][0][FNC_CC_TRANSACTION][0][CC_TRANSACTION][0][PROCESSING_STATUS][0];
	else
	$ps = $res[WIRECARD_BXML][0][W_RESPONSE][0][W_JOB][0][FNC_CC_PURCHASE][0][CC_TRANSACTION][0][PROCESSING_STATUS][0];
	
	$err = $ps[ERROR];        
        if (($ps['FunctionResult'][0] == 'ACK') || ($ps['FunctionResult'][0] == 'PENDING')){
            return array(CC_RESULT_SUCCESS, "", $ps['GuWID'][0], $log);
        } elseif ($r['FunctionResult'][0] == 'NOK') {
            return array(CC_RESULT_DECLINE_TEMP, $err[0][Message][0]."<br/>".join("<br/>",(array)$err[0][Advice]), "", $log);
        } else {
            return array(CC_RESULT_DECLINE_PERM, $err[0][Message][0]."<br/>".join("<br/>",(array)$err[0][Advice]), "", $log);
        }
    }


    # Mainfunction to parse the XML defined by URL
    function xml_parsexml ($String) {
     $Encoding=$this->xml_encoding($String);
     $String=$this->xml_deleteelements($String,"?");
     $String=$this->xml_deleteelements($String,"!");
     $Data=$this->xml_readxml($String,$Data,$Encoding);
     return($Data);
    }
    
    # Get encoding of xml
    function xml_encoding($String) {
     if(substr_count($String,"<?xml")) {
      $Start=strpos($String,"<?xml")+5;
      $End=strpos($String,">",$Start);
      $Content=substr($String,$Start,$End-$Start);
      $EncodingStart=strpos($Content,"encoding=\"")+10;
      $EncodingEnd=strpos($Content,"\"",$EncodingStart);
      $Encoding=substr($Content,$EncodingStart,$EncodingEnd-$EncodingStart);
     }else {
      $Encoding="";
     }
     return $Encoding;
    }
    
    # Delete elements
    function xml_deleteelements($String,$Char) {
     while(substr_count($String,"<$Char")) {
      $Start=strpos($String,"<$Char");
      $End=strpos($String,">",$Start+1)+1;
      $String=substr($String,0,$Start).substr($String,$End);
     }
     return $String;
    }
    
    # Read XML and transform into array
    function xml_readxml($String,$Data,$Encoding='') {
     while($Node=$this->xml_nextnode($String)) {
      $TmpData="";
      $Start=strpos($String,">",strpos($String,"<$Node"))+1;
      $End=strpos($String,"</$Node>",$Start);
      $ThisContent=trim(substr($String,$Start,$End-$Start));
      $String=trim(substr($String,$End+strlen($Node)+3));
      if(substr_count($ThisContent,"<")) {
       $TmpData=$this->xml_readxml($ThisContent,$TmpData,$Encoding);
       $Data[$Node][]=$TmpData;
      }else {
       if($Encoding=="UTF-8") { $ThisContent=utf8_decode($ThisContent); }
       $ThisContent=str_replace("&gt;",">",$ThisContent);
       $ThisContent=str_replace("&lt;","<",$ThisContent);
       $ThisContent=str_replace("&quote;","\"",$ThisContent);
       $ThisContent=str_replace("&#39;","'",$ThisContent);
       $ThisContent=str_replace("&amp;","&",$ThisContent);
       $Data[$Node][]=$ThisContent;
      }
     }
     return $Data;
    }
    
    # Get next node
    function xml_nextnode($String) {
     if(substr_count($String,"<") != substr_count($String,"/>")) {
      $Start=strpos($String,"<")+1;
      while(substr($String,$Start,1)=="/") {
       if(substr_count($String,"<")) { return ""; }
       $Start=strpos($String,"<",$Start)+1;
      }
      $End=strpos($String,">",$Start);
      $Node=substr($String,$Start,$End-$Start);
      if($Node[strlen($Node)-1]=="/") {
       $String=substr($String,$End+1);
       $Node=$this->xml_nextnode($String);
      }else {
       if(substr_count($Node," ")){ $Node=substr($Node,0,strpos($String," ",$Start)-$Start); }
      }
     }
     return $Node;
    }


}

function wirecard_get_member_links($user){
    return cc_core_get_member_links('wirecard', $user);
}

function wirecard_rebill(){
    return cc_core_rebill('wirecard');
}

cc_core_init('wirecard');

function find_parent_payment($payment_id){
    global $db;
    $p = $db->get_payment($payment_id);
    if(preg_match('/RENEWAL_ORIG:\s+(\d+)$/', $p['data'][0]['RENEWAL_ORIG'], $regs)){
	return find_parent_payment($regs[1]);
    }else{
	return $p[receipt_id];
    }
}


















?>