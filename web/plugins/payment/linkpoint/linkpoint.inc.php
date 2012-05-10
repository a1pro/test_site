<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: linkpoint payment plugin
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
include_once(dirname(__FILE__)."/lpphp.php");


class payment_linkpoint extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('linkpoint', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('linkpoint', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['linkpoint']['title'] ? $config['payment']['linkpoint']['title'] : _PLUG_PAY_LINKPOINT_TITLE,
            'description' => $config['payment']['linkpoint']['description'] ? $config['payment']['linkpoint']['description'] : _PLUG_PAY_LINKPOINT_DESC,
            'currency' => array('usd' => 'USD', 'eur' => 'EUR'),
            'phone' => 2,
            'code' => 1,
            'name' => 2
        );
    }

    function void_transaction($pnref, &$log){
        $vars = array(
            "host"      => $this->config["host"],
            "port"      => $this->config["port"],
            "storename" =>  $this->config["storename"],
            "keyfile"   =>  $this->config["keypath"],
            "result"    =>  $this->config["testing"] ? "GOOD" : "LIVE",
            'orderID' => $pnref,
        );
        $vars_l = $vars;
        $log[] = $vars_l;
        $mylpphp = new lpphp;
        $res = $mylpphp->VoidSale($vars);
        $log[] = $res;
        return $res;
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
            $amount = "1.00";
        srand(time());
        $invoice .= "-" . rand(100,999);
        $vars = array(
            "host"      => $this->config["host"],
            "port"      => $this->config["port"],
            "storename" =>  $this->config["storename"],
            "keyfile"   =>  $this->config["keypath"],
            "result"    =>  $this->config["testing"] ? "GOOD" : "LIVE",

            'name' => $cc_info['cc_name'] ? $cc_info['cc_name'] :
                      ($member['name_f'] . ' ' . $member['name_l']),
            'cardNumber' => $cc_info['cc_number'],
            'cardExpMonth' => substr($cc_info['cc-expire'], 0, 2),
            'cardExpYear' => substr($cc_info['cc-expire'], 2, 2),
            'orderID' => $invoice,
            'amount' => $amount,
            'email' => $member['email'],
            'phone' => $cc_info['cc_phone'],
            'address' => $cc_info['cc_street'],
            'city' => $cc_info['cc_city'],
            'state' => $cc_info['cc_state'],
            'zip' => $cc_info['cc_zip'],
            'country' => $cc_info['cc_country'],
            'Ip' => $member['remote_addr']
        );
        if ($cc_info['cc_code']) 
            $vars['cvmvalue'] = $cc_info['cc_code'];
        // prepare log record
        $vars_l = $vars; 
        $vars_l['cardNumber'] = $cc_info['cc'];
        if ($vars['cvmvalue'])
            $vars_l['cvmvalue'] = preg_replace('/./', '*', $vars['cvmvalue']);
        $log[] = $vars_l;
        /////
        $mylpphp = new lpphp;
        $res = $mylpphp->ApproveSale($vars);
        $log[] = $res;

        if ($res['statusCode'] == 1){
            if ($charge_type == CC_CHARGE_TYPE_TEST) 
                $this->void_transaction($invoice, $log);
            return array(CC_RESULT_SUCCESS, "", $res['trackingID'], $log);
        } else {
            return array(CC_RESULT_DECLINE_PERM, $res['statusMessage'], "", $log);
        }
    }
}

function linkpoint_get_member_links($user){
    return cc_core_get_member_links('linkpoint', $user);
}

function linkpoint_rebill(){
    return cc_core_rebill('linkpoint');
}

cc_core_init('linkpoint');
?>
