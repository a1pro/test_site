<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: echo payment plugin
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
require_once($config['root_dir']."/plugins/payment/echo/echophp.class.php");

class payment_echo extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('echo', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('echo', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['echo']['title'] ? $config['payment']['echo']['title'] : _PLUG_PAY_ECHO_TITLE,
            'description' => $config['payment']['echo']['description'] ? $config['payment']['echo']['description'] : _PLUG_PAY_ECHO_DESC,
            'phone' => 2,
            'code' => 1,
            'name_f' => 2,
            'company' => 1
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
        $echoPHP = new EchoPHP;

        $echoPHP->set_EchoServer("https://wwws.echo-inc.com/scripts/INR200.EXE");
        $echoPHP->set_order_type("S");
        $echoPHP->set_merchant_echo_id($this->config['merchant_id']);   // use your own id here
        $echoPHP->set_merchant_pin($this->config['pin']);         // use your own pin here
        $echoPHP->set_billing_phone($cc_info['cc_phone']);
        $echoPHP->set_billing_first_name($cc_info['cc_name_f']);
        $echoPHP->set_billing_last_name($cc_info['cc_name_l']);
        $echoPHP->set_billing_address1($cc_info['cc_street']);
        $echoPHP->set_billing_city($cc_info['cc_city']);
        $echoPHP->set_billing_state($cc_info['cc_state']);
        $echoPHP->set_billing_zip($cc_info['cc_zip']);
        $echoPHP->set_billing_country($cc_info['cc_country']);
        $echoPHP->set_billing_phone($cc_info['cc_phone']);
        $echoPHP->set_billing_email($member['email']);

        $echoPHP->set_cc_number($cc_info['cc_number']);
        $echoPHP->set_ccexp_month(substr($cc_info['cc-expire'], 0, 2));
        $echoPHP->set_ccexp_year(substr($cc_info['cc-expire'], 2, 2));

        $echoPHP->set_counter($echoPHP->getRandomCounter());

        switch ($charge_type){
            case CC_CHARGE_TYPE_TEST:
                $echoPHP->set_transaction_type("AD");
                $echoPHP->set_billing_ip_address($_SERVER['REMOTE_ADDR']);
                $echoPHP->set_cnp_security($cc_info['cc_code']);
            break;
            default:
                $echoPHP->set_transaction_type("EV");
                $echoPHP->set_grand_total($amount);
                if ($charge_type != CC_CHARGE_TYPE_RECURRING){
                    $echoPHP->set_billing_ip_address($_SERVER['REMOTE_ADDR']);
                    $echoPHP->set_cnp_security($cc_info['cc_code']);
                } else
                    $echoPHP->set_cnp_recurring('Y');
        };

        $x = $echoPHP->getURLData();
        $x = str_replace($cc_info['cc_number'], $cc_info['cc'], $x);
        $x = str_replace('cnp_security='.$cc_info['cc_code'], 'cnp_security='.preg_replace('/./', '*', $cc_info['cc_code']).'&', $x);
        $x = preg_replace('/(merchant_pin\=)(\d+)/', '\\1********', $x);
        parse_str($x, $x);
        $log[] = $x;

        $ECHO_ERROR = (!($echoPHP->Submit()));
        // find out reply and parse it
        preg_match('/<ECHOTYPE3>(.+?)<\/ECHOTYPE3>/', $echoPHP->EchoResponse, $regs);
        preg_match_all('/<(.+?)>(.+?)<\/(.+?)>/', $regs[1], $matches);
        $res = array();
        foreach ($matches[1] as $k=>$fname)
            $res[$fname] = $matches[2][$k];

        $log[] = $res;
        if ($ECHO_ERROR) {
            if ($echoPHP->decline_code == "1013") {
                return array(CC_RESULT_INTERNAL_ERROR, 
                "Configuration error: Your ECHO-ID or PIN is missing from this form, or is not setup correctly. Check if you can login with your ECHO-ID and PIN to <a href=\"https://wwws.echo-inc.com/Review\">Transaction Review</a>.", 
                "", $log);
            }
            else {
                return array(CC_RESULT_DECLINE_PERM, "Verification of your account FAILED", "", $log);
            }
        } else { 
            return array(CC_RESULT_SUCCESS, "", $echoPHP->get_order_number(), $log);
        }

    }
}

function echo_get_member_links($user){
    return cc_core_get_member_links('echo', $user);
}

function echo_rebill(){
    return cc_core_rebill('echo');
}

cc_core_init('echo');
?>
