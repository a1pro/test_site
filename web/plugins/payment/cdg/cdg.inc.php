<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: cdg payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1781 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $config;


require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_cdg extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('cdg', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('cdg', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['cdg']['title'] ? $config['payment']['cdg']['title'] : _PLUG_PAY_CDG_TITLE,
            'description' => $config['payment']['cdg']['description'] ? $config['payment']['cdg']['description'] : _PLUG_PAY_CDG_DESCR,
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
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////
        if ($charge_type == CC_CHARGE_TYPE_TEST) 
            return array(CC_RESULT_SUCCESS, "", "", array('test transaction' => 'no validation'));
        $vars = array(
            "vendor_id" => $this->config['user'],
            "vendor_password"       => $this->config['pass'],

            'first_name' => $cc_info['cc_name_f'],
            'last_name'  => $cc_info['cc_name_l'],
            'ccnum' => $cc_info['cc_number'],
            'ccmo' => substr($cc_info['cc-expire'], 0, 2),
            'ccyr' => '20'.substr($cc_info['cc-expire'], 2, 2),
            'email' => $member['email'],
            'phone' => $cc_info['cc_phone'],
            'address' => $cc_info['cc_street'],
            'city' => $cc_info['cc_city'],
            'state' => $cc_info['cc_state'],
            'zip' => $cc_info['cc_zip'],
            'country' => $cc_info['cc_country'],
        );
        if ($cc_info['cc_code']){
            $vars['cccode'] = $cc_info['cc_code'];
        }
       ////// fill-in products list
        if ($payment['data'][0]['BASKET_PRODUCTS']){
            $product_ids = (array)$payment['data'][0]['BASKET_PRODUCTS'];
            $prices = $payment['data'][0]['BASKET_PRICES'];
        } else {
            $product_ids = array($payment['product_id']);
            $prices = array($payment['product_id'] => $payment['amount']);
        }
        $products = array();
        foreach ($product_ids as $pid){
            global $db;
            $pr = $db->get_product($pid);
            $pr['price'] = $prices[$pid];
            $products[] = $pr;
        }
        $vars1 = $this->makeXMLRequest($vars, $products);
        
        $vars_l = str_replace($cc_info['cc_number'], $cc_info['cc'], $vars1);
        $vars_l = preg_replace("|<CCNum>(.+?)</CCNum>|", 
            "<CCNum>$cc_info[cc]</CCNum>", $vars_l);
        $vars_l = str_replace("<CVV2Number>".$cc_info['cc_code'], '<CVV2Number>***', $vars_l);
        $vars_l = preg_replace("|<VendorPassword>(.+?)</VendorPassword>|", 
            "<VendorPassword>***</VendorPassword>", $vars_l);
        $log[] = array('xml'=>nl2br(str_replace('<', '&lt;',$vars_l)));
        $vars1 = urlencode($vars1);
        
        $ret = cc_core_get_url("https://secure.paymentclearing.com/cgi-bin/rc/xmltrans.cgi", 'xml='.$vars1);
        $ret = str_replace(" standalone=\"yes\"","",$ret);
        $res=$this->xml_ParseXML($ret);
        $ret = str_replace('<', '&lt;', $ret);
        $ret = preg_replace('/(&lt;\/\w+>)/', "\\1<br />\n", $ret);
        $log[] = array('ret'=>$ret);
        $r = $res['SaleResponse'][0]['TransactionData'][0];
        if ($r['Status'][0] == 'OK'){
            return array(CC_RESULT_SUCCESS, "", $r['XID'][0], $log);
        } elseif ($r['Status'][0] == 'FAILED') {
            return array(CC_RESULT_DECLINE_TEMP, $r['ErrorMessage'][0], "", $log);
        } else {
            return array(CC_RESULT_DECLINE_PERM, $r['ErrorMessage'][0], "", $log);
        }
    }

    function makeXMLRequest($data, $products){
     $rssData = "<?xml version=\"1.0\"?>\n";
     $rssData .= "<SaleRequest>\n";
     $rssData .= "<CustomerData>\n";
     $rssData .= "<Email>".$data["email"]."</Email>\n";
     $rssData .= "<BillingAddress>\n";
     $rssData .= "<Address1>".$data["address"]."</Address1>\n";
     $rssData .= "<FirstName>".$data["first_name"]."</FirstName>\n";
     $rssData .= "<LastName>".$data["last_name"]."</LastName>\n";
     $rssData .= "<City>".$data["city"]."</City>\n";
     $rssData .= "<State>".$data["state"]."</State>\n";
     $rssData .= "<Zip>".$data["zip"]."</Zip>\n";
     $rssData .= "<Country>".$data["country"]."</Country>\n";
     $rssData .= "<Phone>".$data["phone"]."</Phone>\n";
     $rssData .= "</BillingAddress>\n";
     $rssData .= "<AccountInfo>\n";
     $rssData .= "<CardInfo>\n";
     $rssData .= "<CCNum>".$data["ccnum"]."</CCNum>\n";
     $rssData .= "<CCMo>".$data["ccmo"]."</CCMo>\n";
     $rssData .= "<CCYr>".$data["ccyr"]."</CCYr>\n";
     if ($data['cccode'])
         $rssData .= "<CVV2Number>".$data["cccode"]."</CVV2Number>\n";
     $rssData .= "</CardInfo>\n";
     $rssData .= "</AccountInfo>\n";
     $rssData .= "</CustomerData>\n";
     $rssData .= "<TransactionData>\n";
     $rssData .= "<VendorId>$data[vendor_id]</VendorId>\n";
     $rssData .= "<VendorPassword>$data[vendor_password]</VendorPassword>\n";
     $rssData .= "<HomePage>home_page_here</HomePage>\n";
     $rssData .= "<OrderItems>\n";
     foreach ($products as $p){
     $rssData .= "<Item>\n";
     $rssData .= "<Description>".$p['title']."</Description>\n";
     $rssData .= "<Cost>".$p['price']."</Cost>\n";
     $rssData .= "<Qty>1</Qty>\n";
     $rssData .= "</Item>\n";
     }
     $rssData .= "</OrderItems>\n";
     $rssData .= "</TransactionData>\n";
     $rssData .= "</SaleRequest>\n";
     return $rssData;
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

function cdg_get_member_links($user){
    return cc_core_get_member_links('cdg', $user);
}

function cdg_rebill(){
    return cc_core_rebill('cdg');
}

cc_core_init('cdg');
?>
