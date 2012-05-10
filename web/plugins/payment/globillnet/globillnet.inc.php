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
class payment_globillnet extends amember_payment {
    var $title = '';
    var $description = '';
    var $fixed_price = 0;
    var $recurring = 1;
    var $built_in_trials = 1;

    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars){

        global $db, $config;
        $product = & get_product($product_id);
        $member = $db->get_user($member_id);

        $g_product_id = $product->config['globillnet_id'];


        if (!$g_product_id)
            fatal_error("Globill.net Product ID empty for Product# $product_id");

        $vars = array(
            'MEWid'    => $this->config['mewid'],
            'subcode'  => $g_product_id,
            'fullname' => $member['name_f'].' '.$member['name_l'],
            'email'    => $member['email'],
            'Cid'      => $payment_id
        );

        $this->encode_and_redirect('https://secure.globill.net/pay/', $vars);
    }



    function validate_ipn(&$vars){

        if ('TRIAL'==$vars['UserAccess']) {
            $hash = strtoupper( md5($vars['PaymentRefNo'] . $vars['UserId'] . $vars['Email'] . $this->config['mewkey_trial']) );
        } else {
            $hash = strtoupper( md5($vars['PaymentRefNo'] . $vars['UserId'] . $vars['Email'] . $this->config['mewkey']) );
        }

        if (!$vars['hash'] || !$vars['UserId'] || !$vars['Email'])
            return 0;

        if ($vars['hash']==$hash)
            return 1;
    	else
    	    return 0;

    }


    /*
    ** find_product  find product with globillnet_id
    ** return product_id or flase
    */

    function find_product($globillnet_id) {

        global $db;

        $product_id = null;
        $products   = $db->get_products_list();

        foreach ($products as $product) {
            if (isset($product['globillnet_id']) && $product['globillnet_id']==$globillnet_id) {
                $product_id = $product['product_id'];
                break;
            }
        }

        $result = ( $product_id ) ? $product_id : false ;

        return $result;
    }


    function process_ipn(&$vars){
        global $db, $config;
        $vars = (array)$vars;
        $db->log_error("GlobillNet DEBUG: process_thanks \$vars=<br />".$this->get_dump($vars));

        if (!$this->validate_ipn($vars))
            $this->postback_error("IPN validation failed.");

        $first_payment_id = intval($vars['Cid']);
        $first_payment    = $db->get_payment($first_payment_id);
        
        if (!$first_payment['payment_id'])
                    $this->postback_error('Cannot find original payment for [' . $vars['Cid'] . ']');

        if ($first_payment['payment_id'] && $first_payment['completed']){
          //recurring payment
          $last_payment_id = $first_payment['data']['LAST_PAYMENT_ID']; //first payment store info about next payments
          $member_id       = $first_payment['member_id'];
          $last_payment    = $db->get_payment($last_payment_id); //get last payment
          switch ($vars['UserAccess']) {
          
              case 'NONE' : //cancel subscription
                            $last_payment['data']['CANCELLED'] = 1;
                            $last_payment['data']['CANCELLED_AT'] = strftime($config['time_format'], time());
                            $db->update_payment($last_payment_id, $last_payment);
                            break;
              default     : //recurring
                            $product_id  = $this->find_product($vars['SubCode']);
                            if (!$product_id)
                                $this->postback_error('Globill.NET : Cannot find product for [' . $vars['SubCode'] . ']');

                            $product     = get_product($product_id);
                            $begin_date  = $last_payment['expire_date'];
                            $expire_date = $product->get_expire($begin_date);
                            $log[]       = $vars;
                            $payment = array (
                                        'member_id'   => $member_id,
                                        'product_id'  => $product_id,
                                        'begin_date'  => $begin_date,
                                        'expire_date' => $expire_date,
                                        'paysys_id'   => 'globillnet',
                                        'receipt_id'  => $vars['PaymentRefNo'],
                                        'amount'      => $vars['Amount'],
                                        'completed'   => '1',
                                        'data'        => $log
                                        );
                            $db->add_payment($payment);
                            $first_payment['data']['LAST_PAYMENT_ID'] = $GLOBALS['_amember_added_payment_id'];
                            $db->update_payment($first_payment_id, $first_payment);
          }
        } elseif($vars['UserAccess']!='NONE') {
            //first payment
            $err = $db->finish_waiting_payment($first_payment_id, 'globillnet', $vars['PaymentRefNo'], $vars['Amount'], $vars);

            if ($err)
                $this->postback_error('finish_waiting_payment error : '.$err);

            $first_payment    = $db->get_payment($first_payment_id);
            $first_payment['data']['LAST_PAYMENT_ID'] = $first_payment_id;
            $db->update_payment($first_payment_id, $first_payment);
        }
        
    }

    function get_cancel_link() {
        return 'https://secure.globill.net/sub/?MEWid='.$this->config['mewid'];
    }

    function init(){
        parent::init();
        add_product_field(
                    'globillnet_id', 'Globill.net ID',
                    'text', 'You must create this same subscription<br />in Globill.net and enter its number here',
                    'validate_globillnet_id'
        );
    }
}

function validate_globillnet_id(&$p, $field){
    if ($p->config[$field] == '') {
        return "You MUST enter Globill.net Product ID while you're using Globill.net Plugin";
    }
    return '';
}

$pl = & instantiate_plugin('payment', 'globillnet');
?>