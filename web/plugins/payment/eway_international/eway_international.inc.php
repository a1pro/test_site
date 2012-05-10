<?php
if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

class payment_eway_international extends amember_payment {
    var $title = _PLUG_PAY_EWAY_TITLE;
    var $description = _PLUG_PAY_EWAY_DESC;
    var $fixed_price=0;
    var $recurring=0;
    var $result; // Payment info from eWay
    var $request_urls = array(  'UK'    =>  'https://payment.ewaygateway.com/Request/',
                                'AU'    =>  'https://au.ewaygateway.com/Request/',
                                'NZ'    =>  'https://nz.ewaygateway.com/Request/'
                            );
    var $result_urls = array(   'UK'    =>  'https://payment.ewaygateway.com/Result/',
                                'AU'    =>  'https://au.ewaygateway.com/Result/',
                                'NZ'    =>  'https://nz.ewaygateway.com/Result/'
                            );

    function get_request_url(){
        return $this->request_urls[$this->config['country']];
    }
    function get_result_url(){
        return $this->result_urls[$this->config['country']];
    }
    function do_bill($amount, $title, $products, $u, $invoice){
        global $config, $db;
        $payment = $db->get_payment($invoice);
        $product = $db->get_product($payment['product_id']);
        $vars = array(
            'CustomerID'        =>  $this->config['customer_id'],
            'UserName'          =>  $this->config['username'],
            'Amount'            =>  sprintf('%0.2f',$amount),
            'Currency'          =>  ($product['eway_currency'] ? $product['eway_currency'] : 'GBP'),
            'ReturnURL'         =>  $config['root_url']."/plugins/payment/eway_international/thanks.php?payment_id=".$invoice,
            'CancelURL'         =>  $config['root_url']."/cancel.php",
            'Language'          =>  $this->config['language'],
            'CompanyName'       =>  $this->config['company_name'],
            'CustomerFirstName' =>  $u['name_f'],
            'CustomerlastName'  =>  $u['name_l'],
            'CustomerAddress'   =>  $u['street'],
            'CustomerCity'      =>  $u['city'],
            'CustomerState'     =>  $u['state'],
            'CustomerPostCode'  =>  $u['zip'],
            'CustomerCountry'   =>  $u['country'],
            'CustomerEmail'     =>  $u['email'],
            'InvoiceDescription'=>  $title,
            'MerchantReference' =>  $invoice,
            'MerchantInvoice'   =>  $invoice
        );
        $result = $this->postToEWAY($invoice, $this->get_request_url(), $vars);
        if($result['RESULT'] == 'True'){
            header("Location: ".$result['URI']);
            exit;
        }else{
            fatal_error(_TPL_CC_DECLINED_TITLE." : ".$result['ERROR']);
        }
    }

    function postToEWAY($payment_id, $url, $vars){
        global $db; 
        $varsx = array();
        foreach($vars as $k=>$v){
            $varsx[] = urlencode($k)."=".urlencode($v);
        }
        $result = get_url($url = $url."?".join('&',$varsx));
        $payment = $db->get_payment($payment_id);
        $payment['data'][] = $vars;
        $payment['data'][] = array('result'=>$result);
        // Simple parser
        $parser = xml_parser_create();
        xml_parse_into_struct($parser, $result, $vals, $index);
        xml_parser_free($parser);
        foreach($index as $k=>$v){
            foreach($v as $vv){
                if($vals[$vv]['value']) $ret[$k] = $vals[$vv]['value'];
            }
        }
        $payment['data'][] = $ret;
        $db->update_payment($payment_id, $payment);
        return $ret;
    }
    function validate_thanks(&$vars){
        $payment_id =intval($_GET['payment_id']);
        $varsx =array(
            'CustomerID'        =>  $this->config['customer_id'],
            'UserName'          =>  $this->config['username'],
            'AccessPaymentCode' =>  $vars['AccessPaymentCode']
        );

        $this->result = $this->postToEWAY($payment_id, $this->get_result_url(), $varsx);
        if($this->result['RESPONSECODE']!= '00'){
            return _TPL_CC_DECLINED_TITLE." : ".$this->result['ERRORMESSAGE'];
        }
    }

    function process_thanks(&$vars){
        global $db;
        $payment_id =intval($_GET['payment_id']);
        
        $err = $db->finish_waiting_payment($payment_id, 'eway_international',
                                                $this->result['TRXNNUMBER'], $this->result['RETURNAMOUNT'],
                                                $vars);
        if ($err)
            return _TPL_THX_ERROR_ERROR ." : ".$err;

    }

    function init(){
        parent::init();
        if(!$this->config['country']) $this->config['country'] = 'UK';
        add_product_field(
            'eway_currency', 'EWAY Currency',
            'select', '',
            '',
            array('options' => array(
                'GBP'  => 'GBP'
            ))
        );
    }



}
instantiate_plugin('payment', 'eway_international');
?>
