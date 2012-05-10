<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: securepay payment plugin
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
include_once(dirname(__FILE__)."/securepay.php");


class payment_securepay extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('securepay', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('securepay', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['securepay']['title'] ? $config['payment']['securepay']['title'] : _PLUG_PAY_SECUREPAY_TITLE,
            'description' => $config['payment']['securepay']['description'] ? $config['payment']['securepay']['description'] : _PLUG_PAY_SECUREPAY_DESC,
            'currency' => array('usd' => 'USD', 'eur' => 'EUR'),
            'phone' => 2,
            'code' => 1,
            'name' => 2
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
            $amount = ".30";
        srand(time());
        $invoice .= "-" . rand(100,999);
        
                $objSPCharge = new SecurePayCharge($this->config["debug"] , $this->config["timeout"] , $this->config["host"]);

                        $objSPCharge->merchID   = $this->config["merchant_id"];
                        $objSPCharge->amount    = $amount;
                        $objSPCharge->custName  = $cc_info['cc_name'] ? $cc_info['cc_name'] : ($member['name_f'] . ' ' . $member['name_l']);
                        $objSPCharge->street    = $cc_info['cc_street'];
                        $objSPCharge->city              = $cc_info['cc_city'];
                        $objSPCharge->state             = $cc_info['cc_state'];
                        $objSPCharge->zip               = $cc_info['cc_zip'];
                        $objSPCharge->country   = $cc_info['cc_country'];
                        $objSPCharge->custEmail = $member['email'];
                        $objSPCharge->avsreq    = $this->config["avsreq"];
                        $objSPCharge->transType = "SALE";
                        $objSPCharge->cvv2              = "";
                if ($cc_info['cc_code']) 
            $objSPCharge->cvv2 = $cc_info['cc_code'];
                        
                        #add this transaction to the recurring database
                        #$objSPCharge->recurring = "YES";
                        #$objSPCharge->timeframe = "WEEK";
                        #$objSPCharge->recamount = "23.32";
                        
                        #card transaction method either "DATAENTRY" or "SWIPED"
                        $objSPCharge->ccMethod = "DATAENTRY";
                        
                        #if data entry (most applications)
                        $objSPCharge->ccNum     = $cc_info['cc_number'];
                        $objSPCharge->month     = substr($cc_info['cc-expire'], 0, 2);
                        $objSPCharge->year      = substr($cc_info['cc-expire'], 2, 2);
                        #if swipe data
                        #$objSPCharge->swipeData = "";  
                        #if void
                        #$objSPCharge->voidRecNum = "";
                        #if force
                        #$objSPCharge->origApprovNumber = "";                   
                        #optional comments
                        $objSPCharge->comment1 = "";
                        $objSPCharge->comment2 = "";    
        
        $vars = array(
            'host'              => $this->config["host"],
            'timeout'           => $this->config["timeout"],
            'merchant_id'       =>  $this->config["merchant_id"],
            'debug'             =>  $this->config["debug"],
            'avsreq'            => $this->config["avsreq"],
            'name'                      => $cc_info['cc_name'] ? $cc_info['cc_name'] : ($member['name_f'] . ' ' . $member['name_l']),
            'cardNumber'        => $cc_info['cc_number'],
            'cardExpMonth' => substr($cc_info['cc-expire'], 0, 2),
            'cardExpYear'       => substr($cc_info['cc-expire'], 2, 2),
            'orderID'           => $invoice, // not used
            'amount'            => $amount,
            'email'                     => $member['email'],
            'phone'                     => $cc_info['cc_phone'], // not used
            'address'           => $cc_info['cc_street'],
            'city'                      => $cc_info['cc_city'],
            'state'                     => $cc_info['cc_state'],
            'zip'                       => $cc_info['cc_zip'],
            'country'           => $cc_info['cc_country'],
            'Ip'                                => $member['remote_addr'] // not used
        );
        
        if ($cc_info['cc_code']) $vars['cvv2'] = $cc_info['cc_code'];
        
        // prepare log record
        $vars_l = $vars; 
        $vars_l['cardNumber'] = $cc_info['cc'];
        if ($vars['cvv2']) $vars_l['cvv2'] = preg_replace('/./', '*', $vars['cvv2']);
        $log[] = $vars_l;

                        #run credit card transaction
                        $objSPCharge->SubmitCharge();
                        $res = array();
                        $res['returnCode']      = $objSPCharge->returnCode;
                        $res['approvNum']       = $objSPCharge->approvNum;
                        $res['cardResponse'] = $objSPCharge->cardResponse;
                        $res['avsResponse']     = $objSPCharge->avsResponse;
                        $res['recordNumber'] = $objSPCharge->recordNumber;
                        $log[] = $res;
                        


        if ($res['returnCode'] == 'Y'){
            if ($charge_type == CC_CHARGE_TYPE_TEST) {
                //$this->void_transaction($invoice, $log);
            }
            return array(CC_RESULT_SUCCESS, "", $res['approvNum'], $log);
        } else {
            return array(CC_RESULT_DECLINE_PERM, $res['cardResponse'], "", $log);
        }

    }
}

function securepay_get_member_links($user){
    return cc_core_get_member_links('securepay', $user);
}

function securepay_rebill(){
    return cc_core_rebill('securepay');
}

cc_core_init('securepay');
?>
