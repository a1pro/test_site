<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: payflow_pro payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1781 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


add_paysystem_to_list(
array(
            'paysys_id' => 'payzip',
            'title'     => $config['payment']['payzip']['title'] ? $config['payment']['payzip']['title'] : _PLUG_PAY_PAYZIP_TITLE,
            'description' => $config['payment']['payzip']['description'] ? $config['payment']['payzip']['description'] : _PLUG_PAY_PAYZIP_DESC,
            'public'    => 1
        )
);

class payment_payzip extends payment {
    function post_xml($host, $path, $port, $data_to_send){
//       global $db;
//       $db->log_error("host=$host;<br />path=$path;<br />post=$port;<br />data_to_send=$data_to_send<br />");

       $ret = "";
       $fp = fsockopen($host,$port,$errno,$errstr,30);
       if($fp)
       {
           fputs($fp, "POST $path HTTP/1.0\n"); 
           fputs($fp, "Host: $host\n"); // write the hostname line of the header
           fputs($fp, "Content-type: application/x-www-form-urlencoded\n");        fputs($fp, "Content-length: " . strlen($data_to_send) . "\n"); // write the content-length of data to send
           fputs($fp, "Connection: close\n\n");
           fputs($fp, $data_to_send);
           while(!feof($fp))  
           {
               $ret .= fgets($fp, 128); 
           }
           fclose($fp); // close the "file"
           $q = stristr($ret,"\r\n\r\n");        
           if($q != false)
               $ret = $q;
       }
       else
       {
           $ret = _PLUG_PAY_PAYZIP_ERROR.$errno." [".$errstr."]";
       }
       return $ret;
    }
    function submit_payment($payment_id, $price){
        global $db;
        $p = $db->get_payment($payment_id);
        $u = $db->get_user($p['member_id']);
        $pr = $db->get_product($p['product_id']);
        $price = sprintf("%.2f", $price) * 100;
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<PAYZIP_XML>
     <REQUEST>
      <Pin>{$this->config[pin]}</Pin>
      <AccID>{$this->config[account]}</AccID>
      <OrderID>$payment_id</OrderID>
      <Function>INSERT_ORDER</Function>
      <PRODUCTS>
          <MailNotify>0</MailNotify>
          <EMailAddress>{$u[email]}</EMailAddress>
          <Description>{$pr[title]}</Description>
          <Currency>USD</Currency>
          <AmountTotal>{$price}</AmountTotal>
          <VATRateTotal>0</VATRateTotal>
          <ProductTotal>{$price}</ProductTotal>
          <PRODUCT>
            <Description>Site Subscription</Description>
            <Price>{$price}</Price>
            <Quantity>1</Quantity>
            <VATRate>0</VATRate>
            <SubTotal>{$price}</SubTotal>
          </PRODUCT>
      </PRODUCTS>
     </REQUEST>
</PAYZIP_XML>";
        $path = $this->config['test'] ? "/testapi/api/apixml.asp" : "/api/apixml.asp";
//        $db->log_error("post tran to $path:<br />" . htmlentities($xml));
        $resp = $this->post_xml("www.payzip.net",$path, 80, $xml, 1);
//        $db->log_error("post tran response: <br />" . htmlentities($resp));
        if (!preg_match('|<Result>OK</Result>|', $resp, $match)){
            return '';
        }
        if (preg_match('|<Reference>(\d+)</Reference>|', $resp, $match)){
            $reference = $match[1];
        } else {
            return '';
        }
        return $reference;
    }

    function check_payment($payment_id){
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<PAYZIP_XML>
    <REQUEST>
      <Pin>{$this->config[pin]}</Pin>
      <AccID>{$this->config[account]}</AccID>
      <OrderID>$payment_id</OrderID>
      <Function>GET_ORDER_STATUS</Function>
    </REQUEST>
</PAYZIP_XML>";
        $path = $this->config['test'] ? "/testapi/api/apixml.asp" : "/api/apixml.asp";
        global $db;
//        $db->log_error('post: ' . htmlentities($xml));
        $resp = $this->post_xml("www.payzip.net",$path, 80, $xml, 1);
//        $db->log_error('resp: ' . htmlentities($resp));

        if (!preg_match('|<ResultCode>0</ResultCode>|', $resp, $match)){
            return 'Payment is not completed - please contact site administrator';
        }
        global $this_receipt_id, $this_vars;
        if (preg_match('|<Reference>(\d+)</Reference>|', $resp, $match)){
            $reference = $match[1];
            $this_receipt_id = $reference;
        } else {
            return '';
        }
        $this_vars = array(
            'R' => str_replace("\n", "<br />\n", str_replace('<', '&lt;', $resp))
        );
    }

    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        global $config, $db;
        
        if (($receipt_id = $this->submit_payment($payment_id, $price)) > 0){
            $p = $db->get_payment($payment_id);
            $p['receipt_id'] = $receipt_id;
            $db->update_payment($payment_id, $p);
        } else {
            fatal_error(_PLUG_PAY_PAYZIP_FERROR);
        }

        $url = $this->config['test'] ?  "https://www.payzip.net/testapi/w2w/default.asp" : "https://www.payzip.net/w2w/default.asp";
        $vars = array(
            'AccID'   => $this->config['account'],
            'OrderID' => $payment_id,
            'URL'     => $config['root_surl'] . "/plugins/payment/payzip/thanks.php"
        );
        $vars1 = array();
        foreach ($vars as $k=>$v)
            $vars1[] = urlencode($k) . "=" . urlencode($v);
        $vars1 = join('&', $vars1);
        header("Location: $url?$vars1");
        exit();
    }

    function validate_thanks(&$vars){
        if (!$vars['OrderID']) 
            return "payment_id is empty - payment failed";
        if ($err = $this->check_payment($vars['OrderID']))
            return $err;
        return '';
    }
    function process_thanks(&$vars){
        global $db;
        global $this_receipt_id, $this_vars;
        $err = $db->finish_waiting_payment(intval($vars['OrderID']),
                'payzip', $this_receipt_id, '', $this_vars);
        if ($err) 
            return "finish_waiting_payment error: $err";
        $GLOBALS['vars']['payment_id'] = intval($vars['x_invoice_num']);
    }
}
?>
