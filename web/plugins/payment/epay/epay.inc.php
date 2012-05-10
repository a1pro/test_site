<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: The installation file
*    FileName $RCSfile$
*    Release: 3.0.8PRO ($Revision: 3081 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


// need to configure products in clickbank and set thanks page to ./thanks.php
class payment_epay extends amember_payment {
    var $title = 'ePay';
    var $description = '';
    var $no_recurring = 1;

    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars)
    {

        global $db, $config;
        
        $product = $db->get_product($product_id);
        
        # XXX Secret word with which merchant make CHECKSUM on the ENCODED packet
        $secret     = $this->config['secret'];

        $min        = $this->config['min'];
        $invoice    = $payment_id; 
        $sum        = $price;                                   
        $exp_date   = strftime("%d.%m.%Y",
                                time()+7*24*3600);              # XXX Expiration date '01.08.2020'
        $descr      = strip_tags( $product['description'] );    # XXX Description

        $data = "MIN={$min}\nINVOICE={$invoice}\nAMOUNT={$sum}\nEXP_TIME={$exp_date}\nDESCR={$descr}\nDATA";

        $ENCODED  = base64_encode($data);
        $CHECKSUM = $this->_hmac('sha1', $ENCODED, $secret);
        
        $request['PAGE']       = 'paylogin';
        $request['ENCODED']    = $ENCODED;
        $request['CHECKSUM']   = $CHECKSUM;
        $request['URL_OK']     = $config['root_url'].'/thanks.php';
        $request['URL_CANCEL'] = $config['root_url'].'/cancel.php';

        $request['SUBMIT_URL'] = $this->submit_url;
        $request['AMOUNT']     = $price;
        
        $this->_request($request);
    }



    function validate_ipn(&$vars){

        $ENCODED  = $vars['encoded'];
        $CHECKSUM = $vars['checksum'];
        
        if (!$vars['encoded'] || !$vars['checksum'])
            return 0;
        
        $secret = $this->config['secret'];
        $hmac   = $this->_hmac('sha1', $ENCODED, $secret);

        if ($hmac == $CHECKSUM)
            return 1;
    	else
    	    return 0;

    }


    function process_ipn(&$vars){
        global $db, $config;
        $db->log_error("ePay DEBUG: process_thanks \$vars=<br />".$this->get_dump($vars));

        if (!$this->validate_ipn($vars))
            $this->_response("ERR=Not valid CHECKSUM\n");

        $data = base64_decode( $vars['encoded'] );
        $lines_arr = split("\n", $data);
        $info_data = '';

        foreach ($lines_arr as $line) {
            if (preg_match("/^INVOICE=(\d+):STATUS=(PAID|DENIED|EXPIRED)(:PAY_TIME=(\d+):STAN=(\d+):BCODE=([0-9a-zA-Z]+))?$/", $line, $regs)) {
                $invoice  = $regs[1];
                $status   = $regs[2];
                $pay_date = $regs[4]; # XXX if PAID
                $stan     = $regs[5]; # XXX if PAID
                $bcode    = $regs[6]; # XXX if PAID

                # XXX process $invoice, $status, $pay_date, $stan, $bcode here
                $payment = $db->get_payment($invoice);
                if (!$payment['payment_id']) {
                    $info_data .= "INVOICE=$invoice:STATUS=NO\n";
                } elseif ('PAID'==$status) {
                
                    $err = $db->finish_waiting_payment($invoice, 'epay', $stan, '', $vars);
                    $info_data .= ($err) ? "INVOICE=$invoice:STATUS=ERR\n" :
                                           "INVOICE=$invoice:STATUS=OK\n";
                } else {
                    $info_data .= "INVOICE=$invoice:STATUS=OK\n";
                }

            }
        }
        
        $this->_response($info_data."\n");
        
    }
    

    function _hmac($algo,$data,$passwd) {
        /* md5 and sha1 only */
        $algo=strtolower($algo);
        $p=array('md5'=>'H32','sha1'=>'H40');
        if(strlen($passwd)>64)
            $passwd=pack($p[$algo],$algo($passwd));
        if(strlen($passwd)<64)
            $passwd=str_pad($passwd,64,chr(0));

        $ipad=substr($passwd,0,64) ^ str_repeat(chr(0x36),64);
        $opad=substr($passwd,0,64) ^ str_repeat(chr(0x5C),64);

        return($algo($opad.pack($p[$algo],$algo($ipad.$data))));
    }
    
    function _response($msg) {
        global $db;
        $db->log_error("ePay DEBUG: $msg");
        echo $msg;
        exit();
    }
    
    function _request($request) {
        $t = & new_smarty();
        $t->assign('request', $request);
        $t->display(dirname(__FILE__).'/epay.html');
        exit();
    }
    
    function init(){
        parent::init();
        if ($this->config['testing']) {
            $this->submit_url = 'https://devep2.datamax.bg/ep2/epay2_demo/';
        } else {
            $this->submit_url = 'https://www.epay.bg/';
        }
    }
    
}


$pl = & instantiate_plugin('payment', 'epay');
?>