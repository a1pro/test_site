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
*    Release: 3.0.8PRO ($Revision: 4747 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


// need to configure products in clickbank and set thanks page to ./thanks.php
class payment_fastspring extends amember_payment {
    var $title = _PLUG_PAY_FASTSPRING_TITLE;
    var $description = _PLUG_PAY_FASTSPRING_DESC;
    var $fixed_price = 1;
    var $recurring = 0;
    

    function do_payment($payment_id, $member_id, $product_id, $price, $begin_date, $expire_date, &$vars){

        global $config;
        $product = & get_product($product_id);

        $c_product_id = $product->config['fastspring_id'];

        if (!$c_product_id)
            fatal_error("FastSpring Product ID empty for Product# $product_id");
        
        $url = "https://sites.fastspring.com/" . $this->config['company'] . "/instant/" . $c_product_id . "?referrer=" . $payment_id;
        if ($this->config['testmode'])
            $url .= "&mode=test&member=new&sessionOption=new";
        
        html_redirect($url, $print_header=0, $title='Please wait', $text='Please wait');

    }


    function validate_ipn($vars) {

        $privatekey = $this->config['private_key'];

        if (md5($vars['security_data'] . $privatekey) != $vars['security_hash'])
            return 0;  // FAILED CHECK
        else
            return 1; // SUCCESS

    }


    function process_postback($vars){
        global $db, $config;

        if (!$this->validate_ipn($vars))
            $this->postback_error("IPN validation failed.");

        $invoice = intval($vars['OrderReferrer']);
        if (!$invoice){
	        $db->log_error("FastSpring DEBUG (process_postback): invoice [$invoice] not found.");
            return;
	    }
        $p = $db->get_payment($invoice);
	    
	    if ($vars['OrderIsTest'] == 'true' && !$this->config['testmode'])
	        $this->postback_error("Test Mode is not enabled.");
	    
	    $amount = $vars['OrderTotalUSD'];
	    settype($amount, 'double');
	    if ($amount && $amount != $p['amount']){
	        $p['amount'] = $amount;
	        $db->update_payment($p['payment_id'], $p);
	    }
	    
	    $member = $db->get_user($p['member_id']);
	    if (!$member['data']['cc_name_f']  && $vars['CustomerFirstName'])   $member['data']['cc_name_f']    = $vars['CustomerFirstName'];
	    if (!$member['data']['cc_name_l']  && $vars['CustomerLastName'])    $member['data']['cc_name_l']    = $vars['CustomerLastName'];
	    if (!$member['data']['cc_country'] && $vars['AddressCountry'])      $member['data']['cc_country']   = $vars['AddressCountry'];
	    
	    $state = $vars['AddressRegionDisplay'] ? $vars['AddressRegionDisplay'] : $vars['AddressRegion'];
	    if (!$member['data']['cc_state']   && $state)       $member['data']['cc_state']     = $state;
	    
	    if (!$member['data']['cc_zip']     && $vars['AddressPostalCode'])   $member['data']['cc_zip']       = $vars['AddressPostalCode'];
	    if (!$member['data']['cc_city']    && $vars['AddressCity'])         $member['data']['cc_city']      = $vars['AddressCity'];
	    if (!$member['data']['cc_street']  && $vars['AddressStreet1'])      $member['data']['cc_street']    = trim($vars['AddressStreet1']." ".$vars['AddressStreet2']);
	    if (!$member['data']['cc_phone']   && $vars['CustomerPhone'])       $member['data']['cc_phone']     = $vars['CustomerPhone'];
	    $db->update_user($member['member_id'], $member);
	    


        if (!$p['completed']){
            $err = $db->finish_waiting_payment($invoice, $this->get_plugin_name(), $vars['OrderReference'], '', $vars);
            if ($err)
                $this->postback_error("finish_waiting_payment error: $err");
        }

    }

    function init(){
        parent::init();
        add_product_field(
                    'fastspring_id', 'FastSpring Product ID',
                    'text', "You can get an ID from your FastSpring account -> Product Pages -> Option 1: View Product Detail Page 
                    <br />For example ID is 'testmembership' for an URL http://sites.fastspring.com/your_company/product/testmembership",
                    'validate_fastspring_id'
        );
    }

}

function validate_fastspring_id(&$p, $field){
    if ($p->config[$field] == '') {
        return "You MUST enter FastSpring Product ID while you're using FastSpring Plugin";
    }
    return '';
}

$pl = & instantiate_plugin('payment', 'fastspring');
?>
