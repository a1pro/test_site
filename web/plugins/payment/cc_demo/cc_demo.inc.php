<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: cc_demo payment plugin
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

class payment_cc_demo extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('cc_demo', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('cc_demo', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['cc_demo']['title'] ? $config['payment']['cc_demo']['title'] : _PLUG_PAY_CC_DEMO_TITLE,
            'description' => $config['payment']['cc_demo']['description'] ? $config['payment']['cc_demo']['description'] : _PLUG_PAY_CC_DEMO_DESCR,
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
        if ($cc_info['cc_number'] == '4111111111111111'){
            return array(CC_RESULT_SUCCESS, "", "123456", $log);
        } else {
            return array(CC_RESULT_DECLINE_PERM, _PLUG_PAY_CC_DEMO_CCNUM, "", $log);
        }
    }
}

function cc_demo_get_member_links($user){
    return cc_core_get_member_links('cc_demo', $user);
}

function cc_demo_rebill(){
    return cc_core_rebill('cc_demo');
}

cc_core_init('cc_demo');
?>
