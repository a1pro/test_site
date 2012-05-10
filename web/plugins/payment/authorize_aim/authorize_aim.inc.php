<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: authorize_aim payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3498 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_authorize_aim extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('authorize_aim', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('authorize_aim', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['authorize_aim']['title'] ? $config['payment']['authorize_aim']['title'] : _PLUG_PAY_AUTHORIZE_AIM_TITLE,
            'description' => $config['payment']['authorize_aim']['description'] ? $config['payment']['authorize_aim']['description'] : _PLUG_PAY_AUTHORIZE_AIM_DESC,
            'phone' => 2,
            'company' => 1,
            'code' => 1,
            'name_f' => 2
        );
    }
    function run_transaction($vars){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        $ret = cc_core_get_url("https://secure.authorize.net/gateway/transact.dll", $vars1);
        $ret = str_replace ("\"", "", $ret);
        $arr = preg_split('/\|/', $ret);
        $res = array(
            'RESULT'      => $arr[0],
            'RESULT_SUB'  => $arr[1],
            'REASON_CODE' => $arr[2],
            'RESPMSG'     => $arr[3],
            'AVS'         => $arr[5],
            'PNREF'       => $arr[6],
            'CVV_VALID'   => $arr[48]
        );
        return $res;
    }
    function void_transaction($pnref, &$log){
        $vars = array(
            "x_Login"    =>   $this->config['login'],
            "x_Version"  =>   "3.1",
            "x_Delim_Data" => "True",
            "x_Tran_Key" =>   $this->config['tkey'],
            "x_Delim_Char" => "|",
            "x_Type"     =>   "VOID",
            "x_Trans_Id" =>   $pnref,
        );
        $vars_l = $vars;
        $log[] = $vars_l;
        $res = $this->run_transaction($vars);
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

        srand(time());
	$x_Type = "AUTH_CAPTURE";

        if ($charge_type == CC_CHARGE_TYPE_TEST){
            $amount = "1.00";
	    $x_Type = "AUTH_ONLY";
	}
        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }

        if(!$product_description){
	    global $db;
	    $product = $db->get_product($payment[product_id]);
	    $product_description = $product[title];
	}
        $vars = array(
            "x_Login"    => $this->config['login'],
            "x_Version"  => "3.1",
            "x_Delim_Data" => "True",
            "x_Tran_Key" => $this->config['tkey'],
            "x_Delim_Char" => "|",
            "x_Invoice_Num" => $payment['payment_id'] . '-' . rand(100, 999),
            "x_Amount" =>   $amount,
            "x_Currency_Code" => $currency ? $currency : 'USD',
            "x_Card_Num" => $cc_info['cc_number'],
            "x_Exp_Date" => $cc_info['cc-expire'],
            "x_Type"     => $x_Type,
            "x_Relay_Response" => 'FALSE', 
            "x_Email"    =>    $member['email'],
            "x_Description" => $product_description,
            "x_Cust_ID" =>  $member['member_id'],
            "x_First_Name" =>  $cc_info['cc_name_f'],
            "x_Last_Name" =>   $cc_info['cc_name_l'],
            "x_Address" =>  $cc_info['cc_street'],
            "x_City" =>     $cc_info['cc_city'],
            "x_State" =>    $cc_info['cc_state'],
            "x_Zip" =>      $cc_info['cc_zip'],
            "x_Country" =>  $cc_info['cc_country'],
            "x_Company" =>  $cc_info['cc_company'],
            "x_Customer_IP" => $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'],
            "x_Phone"   => $cc_info['cc_phone']
        );
        
        if ($this->config['testing'])
            $vars['x_Test_Request'] = 'TRUE';
        if ($cc_info['cc_code'])
            $vars['x_Card_Code'] = $cc_info['cc_code'];

        // prepare log record
        $vars_l = $vars; 
        $vars_l['x_Card_Num'] = $cc_info['cc'];
        if ($vars['x_Card_Code'])
            $vars_l['x_Card_Code'] = preg_replace('/./', '*', $vars['x_Card_Code']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['RESULT'] == '1'){
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res['PNREF'], $log);
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif ($res['RESULT'] == '2') {
            return array(CC_RESULT_DECLINE_PERM, $res['RESPMSG'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['RESPMSG'], "", $log);
        }
    }
}

function authorize_aim_get_member_links($user){
    return cc_core_get_member_links('authorize_aim', $user);
}

function authorize_aim_rebill(){
    return cc_core_rebill('authorize_aim');
}
                                        
cc_core_init('authorize_aim');
?>
