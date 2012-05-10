<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");


/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: 1ShoppingCart Payment Plugin
*    FileName $RCSfile: paypal_r.inc.php,v $
*    Release: 3.2.3PRO ($Revision: 1.8 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
* 
* TODO - tax saving / calculation
*/

class payment_1shoppingcart extends amember_advanced_payment {
    var $title = "1ShoppingCart";
    var $description = "All major credit cards accepted";
    var $fixed_price=1;
    var $recurring=1;
    var $shutdown_function_set = true;



    function get_lock(){
        global $db;
        register_shutdown_function(array($this, 'release_lock'));
        $this->shutdown_function_set = true;
            return $db->query_one("SELECT GET_LOCK('".$db->config['prefix']."payments', 30)");
    }
    function release_lock(){
	global $db;
        return $db->query("DO RELEASE_LOCK('".$db->config['prefix']."payments')");
    }
                                                                
    function do_bill($amount, $title, $products, $u, $invoice){
        global $config;
        
        $_SESSION['_amember_payment_id'] = $invoice;
        $vars = array(
            'MerchantID' => $this->config['merchant_id'],
            'ProductID'  => $products[0]['1shoppingcart_id'],
            'AMemberID'  => $invoice,
            'PostBackURL' => $config['root_url'] . "/plugins/payment/1shoppingcart/ipn.php",
        );
//        return $this->encode_and_redirect("http://www.marketerschoice.com/app/netcart.asp", $vars);
        return $this->encode_and_redirect("http://www.marketerschoice.com/app/javanof.asp", $vars);

    }
    
    function validate_ipn($vars){
        //print_rr($vars, "POST we received from you");
        $sign = $vars['VerifySign'];
        unset($vars['VerifySign']);
        $vars['PostbackPassword'] = $this->config['postback_password'];
        $str = join('', array_values($vars));
        $md5 = md5($str);
        
        if ($md5 != $sign){
            global $db;
            $db->log_error( "DEBUG: Validation error: verifysign incorrect. Make sure you set the same password in both 1ShoppingCart and aMember Pro CP<br>
            [md5($str)] != [$sign] <Br>
            ");
            return "Validation error: verifysign incorrect. Make sure you set the same password in both 1ShoppingCart and aMember Pro CP";
        }
        
        //print_rr($vars, "vars to contactenate");
        //print_rr($str, "str");
        //print_rr($md5, "md5(str) (should be the same as sign)");
        //print_rr($sign, "sign (we received from you), it is anything but MD5 hash of str");
        //exit();
    }

    function process_postback($vars){
        global $db, $config;

        // validate if it is true PayPal IPN
        if (($err = $this->validate_ipn($vars)) != '')
            $this->postback_error($err);
        
	$this->get_lock();
        $invoice = intval($vars['AMemberID']);
	$receipt_id = intval($vars['OrderID']);
	if($db->query_one("select count(*) from {$db->config[prefix]}payments where receipt_id='$receipt_id' and paysys_id='1shoppingcart' and completed=1")){
	    $this->release_lock();
	    $this->postback_error("Payment already processed");
	    
	}

//      $next_rebill = date('Y-m-d', strtotime($vars['NextRebillDate']));

        $p = $db->get_payment($invoice);
        if (!$payment['payment_id']){
            $invoice = $this->create_new_payment($vars);
            $payment = $db->get_payment($invoice);
        }
        $pr = & get_product($p['product_id']);
        $begin_date = $this->get_next_begin_date($invoice);
        $next_rebill = $pr->get_expire($begin_date);
        if (!$next_rebill)
        $next_rebill = date('Y-m-d', strtotime($vars['NextRebillDate']));

/*
        if ($vars['NextRebillDate']){
            $next_rebill = date('Y-m-d', strtotime($vars['NextRebillDate']));
        } else {
            $p = $db->get_payment($invoice);
            $pr = & get_product($p['product_id']);
            $begin_date = $this->get_next_begin_date($invoice);
            $next_rebill = $pr->get_expire($begin_date);
        }
*/

        switch ($vars['Status']){
            case 'start_recurring':
            case 'payment': 
                $err = $db->finish_waiting_payment(
                    $invoice, $this->get_plugin_name(),
                    $vars['OrderID'], '', $vars);
                if ($err)
                    $this->postback_error("finish_waiting_payment error: $err");
                
                if ($vars['Status'] != 'start_recurring') break;
                
                $p = $db->get_payment($invoice);
                $p['expire_date'] = $next_rebill;
                $db->update_payment($p['payment_id'], $p);
                break;
           case 'rebill':
                $p = $db->get_payment($invoice);
                if (!$p['payment_id'])
                    $this->postback_error("Cannot find original payment for [$invoice]");
                $begin_date = $this->get_next_begin_date($invoice);
                $newp = array();
                $newp['member_id']   = $p['member_id'];
                $newp['product_id']  = $p['product_id'];
                $newp['paysys_id']   = $this->get_plugin_name();
                $newp['receipt_id']  = $vars['OrderID'];
                $newp['begin_date']  = $begin_date;
                $newp['expire_date'] = $next_rebill;
                $newp['amount']      = $vars['Amount'];
//                $newp['completed']   = $p['completed'];
                $newp['completed']   = 1;
                $newp['data']        = array('RENEWAL_ORIG' => "RENEWAL ORIG: $invoice");
                $newp['data'][]      = $vars;
                $db->add_payment($newp);
                break;
           case 'recurring_eot':
                $yesterday = date('Y-m-d', time()-3600*24);
                $orig_p = $db->get_payment($invoice);
                if (!$orig_p['payment_id'])
                    $this->postback_error("Cannot find original payment for [$invoice]");
                foreach ($db->get_user_payments($orig_p['member_id'], 1) as $p){
                    if (($p['product_id'] == $orig_p['product_id']) 
                        && (($p['data']['RENEWAL_ORIG'] == "RENEWAL ORIG: $invoice")
                            || ($p['payment_id'] == $invoice))
                        && ($p['expire_date'] >= $yesterday)){
                        $p['expire_date'] = $yesterday;
                        $p['data'][] = $vars;
                        $db->update_payment($p['payment_id'], $p);
                    }
                }
                break;
           default: $this->release_lock(); $this->postback_error("Unknown status: [$vars[Status]]");                
        }
    $this->release_lock();
    }
    
    function get_next_begin_date($invoice){
        global $db;
        $orig_p = $db->get_payment($invoice);
        $ret = $orig_p['expire_date'];
        foreach ($db->get_user_payments($orig_p['member_id'], 1) as $p){
            if (($p['product_id'] == $orig_p['product_id']) 
                && ($p['data']['RENEWAL_ORIG'] == "RENEWAL ORIG: $invoice")
                && ($p['expire_date'] > $ret))
                $ret = $p['expire_date'];
        }
        return $ret;
    }
    /*
    function get_cancel_link($payment_id){
        return "http://www.1shoppingcart.com/cancel_payment.php";
    }
    */

    function api_call($table, $item){
        $post_data = "<Request><Key>".$this->config['api_key']."</Key></Request>";
        $url = $this->config['api_url'].$this->config['merchant_id']."/".$table."/".$item;
	return $this->send_xml($url, $post_data);

    }


    function send_xml($url, $xml){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-POST_DATA_FORMAT: xml'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = curl_exec($ch);
	$err = curl_error($ch);
	curl_close($ch);
	if ($err){
            $db->log_error("1SC API connection error: $err");
            return $err;

        }
        return $data;

    }

    function get_value_from_vars($var, &$vars){
        global $db, $client_info, $order_info;
        if(!$order_info) $order_info = $this->get_order_info($vars['OrderID']);
        if(!$client_info)   $client_info = $this->get_client_info($order_info['Response'][0]['OrderInfo'][0]['ClientId'][0]);
        if($this->is_api_error($order_info)){
                $db->log_error("Can't get data from API: ".$order_info['Response'][0]['Error'][0]);
        }
        if($this->is_api_error($client_info)){
                $db->log_error("Can't get data from API: ".$client_info['Response'][0]['Error'][0]);
        }
        switch($var){
            case 'name_f'       :   $ret = $client_info['Response'][0]['ClientInfo'][0]['FirstName'][0]; break;
            case 'name_l'       :   $ret = $client_info['Response'][0]['ClientInfo'][0]['LastName'][0]; break;
            case 'email'        :   $ret = $client_info['Response'][0]['ClientInfo'][0]['Email'][0]; break;
            case 'street'       :   $ret = $client_info['Response'][0]['ClientInfo'][0]['Address1'][0]; break;
            case 'zip'          :   $ret = $client_info['Response'][0]['ClientInfo'][0]['Zip'][0]; break;
            case 'state'        :   $ret = $client_info['Response'][0]['ClientInfo'][0]['StateName'][0]; break;
            case 'country'      :   $ret = $client_info['Response'][0]['ClientInfo'][0]['CountryName'][0]; break;
            case 'city'         :   $ret = $client_info['Response'][0]['ClientInfo'][0]['City'][0]; break;
            case 'product_id'   :
                                    $ret = $this->find_product_by_field('1shoppingcart_id', $order_info['Response'][0]['OrderInfo'][0]['LineItems'][0]['LineItemInfo'][0]['ProductId'][0]);
                                    break;

            case 'receipt_id'   :   $ret = $order_info['Response'][0]['OrderInfo'][0]['Id'][0]; break;
            case 'amount'       :   $ret = $order_info['Response'][0]['OrderInfo'][0]['GrandTotal'][0];
                                    break;

                        default : $ret = '';
        }
        return trim($ret);
    }


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


    function get_order_info($order_id){
        $order = $this->xml_parsexml($this->api_call("Orders", $order_id));
        return $order;

    }
    function get_client_info($client_id){
        $client = $this->xml_parsexml($this->api_call("Clients", $client_id));
        return $client;

    }
    function is_api_error($xml){
        if($xml['Response'][0]['Error'][0]) return 1;
    }



    function init(){
        parent::init();
        add_product_field('1shoppingcart_id',
            '1ShoppingCart Product ID#',
            'text',
            "please take product ID# from 1ShoppingCart control panel<br>
             and enter here. To avoid confusion, product must have the same<br>
             price and duration settings"
            );
		add_product_field('trial1_days',
		    'Trial 1 Duration',
		    'period',
		    'read docs for explanation, leave empty to not use trial'
		    );

		add_product_field('trial1_price',
		    'Trial 1 Price',
		    'money',
		    'set 0 for free trial'
		    );

      $this->config['api_url'] = 'https://www.mcssl.com/API/';

    }
}

$pl = & instantiate_plugin('payment', '1shoppingcart');

?>