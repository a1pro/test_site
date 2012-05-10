<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: innovative payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2898 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_innovative extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('innovative', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('innovative', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => 'Innovative',
            'description' => 'Credit card payment',
            'type_options' => array(
                'visa' => 'Visa',
                'mc' => 'MasterCard',
                'amex' => 'AMEX',
                'diners' => 'Diners Club',
                'discover' => 'Discover',
                'jcb' => 'JCB'
            ),
            'phone' => 2,
//            'code' => 1,
            'name_f' => 2
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
            return array(CC_RESULT_SUCCESS, "", "", array('test transaction' => 'no validation'));
        $vars = array(
            "target_app" => "WebCharge_v5.06",
            "response_mode" => "simple",
            "response_fmt" => "delimited",
            "upg_auth" => "zxcvlkjh",
            "delimited_fmt_field_delimiter" => "=",
            "delimited_fmt_include_fields" => "true",
            "delimited_fmt_value_delimiter" => "|",
            
            "username" => $this->config['user'],
            "pw"       => $this->config['pass'],
            "trantype" => 'sale',
            "reference" => "",
            "trans_id" => "",


            'cardtype' => $cc_info['cc_type'],
            'ccname' => $cc_info['cc_name_f'] . " " . $cc_info['cc_name_l'],
            'ccnumber' => $cc_info['cc_number'],
            'month' => substr($cc_info['cc-expire'], 0, 2),
            'year' => substr($cc_info['cc-expire'], 2, 2),
            'fulltotal' => $amount,
            'email' => $member['email'],
            'bphone' => $cc_info['cc_phone'],
            'baddress' => $cc_info['cc_street'],
            'bcity' => $cc_info['cc_city'],
            'bstate' => $cc_info['cc_state'],
            'bzip' => $cc_info['cc_zip'],
            'bcountry' => $cc_info['cc_country'],
        );

	
        // prepare log record
        $vars_l = $vars; 
        $vars_l['ccnumber'] = $cc_info['cc'];
        $log[] = $vars_l;
        /////

	if ($cc_info['cc_code']) $vars['ccidentifier1'] = $cc_info['cc_code'];


        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url("https://transactions.innovativegateway.com/servlet/com.gateway.aai.Aai", $vars1);
        $rArr = explode("|",$ret);
        $res=array();
        for($i=0;$i<count($rArr);$i++)
        {
            $tmp2 = explode("=", $rArr[$i]);
            // YES, we put all returned field names in lowercase
            $tmp2[0] = strtolower($tmp2[0]);
            // YES, we strip out HTML tags.
            $res[$tmp2[0]] = strip_tags($tmp2[1]);
        }

        
        $log[] = $res;

        if ($res['approval'] && !$res['error']){
            return array(CC_RESULT_SUCCESS, "", $res['anatransid'], $log);
        } else {
            return array(CC_RESULT_DECLINE_PERM, $res['error'], "", $log);
        }
    }
}

function innovative_get_member_links($user){
    return cc_core_get_member_links('innovative', $user);
}

function innovative_rebill(){
    return cc_core_rebill('innovative');
}

cc_core_init('innovative');
?>
