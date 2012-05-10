<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: ccbill payment plugin
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 4936 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


setup_plugin_hook('hourly', 'ccbill_datalink');
setup_plugin_hook('validate_signup_form', 'ccbill_vsf');

add_paysystem_to_list(
array(
            'paysys_id' => 'ccbill',
            'title'     => $config['payment']['ccbill']['title'] ? $config['payment']['ccbill']['title'] : _PLUG_PAY_CC_BILL_TITLE,
            'description' => $config['payment']['ccbill']['description'] ? $config['payment']['ccbill']['description'] : _PLUG_PAY_CC_BILL_DESCR,
            'public'    => 1,
            'recurring' => 1
        )
);

add_product_field(
            'ccbill_id', 'ccBill Product/Subscription ID',
            'text', 'you must create the same product<br />
             in ccbill for CC billing. Enter pricegroup here'
);
add_product_field(
            'ccbill_subaccount_id', 'ccBill SubAccount ID',
            'text', 'keep empty to use default value (from config)'
);
add_product_field(
            'ccbill_cc_form', 'ccBill CC Form ID',
            'text', 'enter ccBill Form id to pay with credit card'
);

if ($GLOBALS['config']['payment']['ccbill']['use_cheques']){
    $plugins['payment'][] = 'ccbill_check';
    add_paysystem_to_list(
    array(
                'paysys_id' => 'ccbill_check',
                'title'     => 'Online Check',
                'description' => _PLUG_PAY_CC_BILL_CHECK_DESCR,
                'public'    => 1,
                'recurring' => 1
            )
    );
    add_product_field(
                'ccbill_check_form', 'ccBill Check Form ID',
                'text', 'enter ccBill Form id to pay with online check'
    );
}
if ($GLOBALS['config']['payment']['ccbill']['use_900']){
    $plugins['payment'][] = 'ccbill_900';
    add_paysystem_to_list(
    array(
                'paysys_id' => 'ccbill_900',
                'title'     => 'ccBill 900',
                'description' => _PLUG_PAY_CC_BILL_TEL_DESCR,
                'public'    => 1,
                'recurring' => 1
            )
    );
    add_product_field(
            'ccbill_900_subaccount_id', 'ccBill 900 SubAccount ID',
            'text', 'enter to specify alternative subaccount ID for ccBill900 billing'
    );
    add_product_field(
                'ccbill_900_form', 'ccBill 900 Form ID',
                'text', 'enter ccBill Form id to pay with ccBill900 phone bill'
    );
}

add_product_field(
            'is_recurring', 'Recurring Billing',
            'select', 'should user be charged automatically<br />
             when subscription expires',
            '',
            array('options' => array(
                '' => 'No',
                1  => 'Yes'
            ))
);

function ccbill_get_url($url, $post=''){
    global $db;
    if (extension_loaded("curl")){
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if ($post)  {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);  
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
        $buffer = curl_exec($ch);
        if ($buffer===false) {
            $db->log_error(curl_error($ch));
        }
        curl_close($ch);
        return $buffer;
    } else {
        global $config;
        $curl = $config['curl'];
        if (!strlen($curl)) {
            $db->log_error("cURL path is not set - cc transaction cannot be completed");
            return;
        }
        if (substr(php_uname(), 0, 7) == "Windows") {
            if ($post)
            $ret = `$curl -d "$post" "$url"`;
            else
            $ret = `$curl "$url"`;
        } else {
            $url  = escapeshellarg($url);
            $post = escapeshellarg($post);
            if ($post)
            $ret = `$curl -d $post $url`;
            else
            $ret = `$curl $url`;
        }
        return $ret;
    }
}

// need to configure products in clickbank and set thanks page to ./thanks.php
class payment_ccbill extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db, $plugin_config;

        $this->config = $config['payment']['ccbill'];

        $product = & $db->get_product($product_id);
        $payment = $db->get_payment($payment_id);
        $member = $db->get_user($member_id);

        $vars = array(
            'clientAccnum' => $this->config['account'],
            'clientSubacc' => 
              ($product['ccbill_subaccount_id'] ?
               $product['ccbill_subaccount_id'] : $this->config['sub_account']),

            'subscriptionTypeId' => $product['ccbill_id'],
            'allowedTypes' => $product['ccbill_id'],

            'username' => $member['login'],
            'password' => $member['pass'],
            'confirm_password' => $member['pass'],
            'email' => $member['email'],
            'customer_fname' => $member['name_f'],
            'customer_lname' => $member['name_l'],
            'address1' => $member['street'],
            'city' => $member['city'],
            'state' => $member['state'],
            'zipcode' => $member['zip'],
            'country' => $member['country'],
            'phone_number' => $member['data']['phone'],

            'payment_id'  => $payment_id
        );
        switch ($payment['paysys_id']){
            case 'ccbill_check' : 
                $vars['formName'] = $product['ccbill_check_form']; break;
            case 'ccbill_900' : 
                if ($product['ccbill_900_subaccount_id'] != '')
                    $vars['clientSubacc'] = $product['ccbill_900_subaccount_id'];
                $vars['formName'] = $product['ccbill_900_form']; 
            break;
                
            default :
                $vars['formName'] = $product['ccbill_cc_form']; 
        }

        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://bill.ccbill.com/jpost/signup.cgi?$vars");
        exit();
    }
    function get_cancel_link($payment_id){
        global $db;
        return "https://www.ccbill.com";
    }
}

class payment_ccbill_check extends payment_ccbill {
}
class payment_ccbill_900 extends payment_ccbill {
}

function ccbill_datalink(){
    global $config, $db;
    $this_config = $config['payment']['ccbill'];
    //
    if (!$this_config['datalink_user'] || !$this_config['datalink_pass']){
        mail_admin("You have not configured DataLink access parameters.
        Please follow instructions at aMember CP -> Setup -> ccBill
        ", "ccBill DataLink Problems (aMember Pro)");
        $db->log_error("ccBill DataLink parameters not entered");
        return;
    }
    //
    define('CCBILL_TIME_OFFSET', -8 * 3600);
    $last_run = $this_config['last_run'];
    if (!$last_run || ($last_run < 19700101033324 ))
        $last_run = gmdate('YmdHis', time()-15*3600*24 + CCBILL_TIME_OFFSET);
    $now_run = gmdate('YmdHis', time() + CCBILL_TIME_OFFSET);
    $last_run_tm = strtotime(ccbill_time_to_sql($last_run));
    $now_run_tm  = strtotime(ccbill_time_to_sql($now_run));
#    print "$last_run_tm;$last_run<br />$now_run_tm;$now_run<br />";
    if (($now_run_tm - $last_run_tm) > 3600*24)
        $now_run_tm = $last_run_tm + 3600*24;
    $now_run = date('YmdHis', $now_run_tm);    
#    print "$last_run;$now_run\n";
    // now lets retrieve info
    $vars = array(
        'startTime' => $last_run,
        'endTime'   => $now_run,
        'transactionTypes' => 'REBILL,REFUND,EXPIRE,CHARGEBACK',
        'clientAccnum' => $this_config['account'],
        'clientSubacc' => $this_config['sub_account'],
        'username' => $this_config['datalink_user'],
        'password' => $this_config['datalink_pass']
    );
    foreach ($vars as $k=>$v)
        $vars1[] = $k . '='. $v;
    $res = ccbill_get_url($s = "https://datalink.ccbill.com/data/main.cgi?".join('&', $vars1));
    $db->log_error("ccBill datalink debug ($last_run, $now_run):<br />\n$s<br />\n". $res);
//    $res = 'REFUND, "925422","001", "123123123", "123", '.(time()-3600*48).', 155'. "\n";
//    $res .= '"EXPIRE", "925422","000", "123123123", "12312312", "12312312"'. "\n";
    if (preg_match('/Error:(.+)/m', $res, $regs)){
        $e = $regs[1];
        $db->log_error("ccBill datalink error: $e");
        mail_admin("ccBill datalink error: $e", "ccBill DataLink Error");
        return;
    }
    if ($res == "1") {
        // no lines found, and ccBill returned "1" 
    } else {
        // handle ccbill output
        foreach (preg_split('/[\r\n]+/', $res) as $line_orig){
            $line = trim($line_orig);
            if (!strlen($line)) continue;
            $line = preg_split('/,/', $line);
            foreach ($line as $k=>$v) 
                $line[$k] = preg_replace('/^\s*"(.+?)"\s*$/', '\1', $v);
            $payment = ccbill_find_payment($line[3]);
            if (!$payment){
                $db->log_error("Cannot find payment by ccBill datalink reference: $line_orig");
                continue;
            }
            switch ($line[0]){
                case 'EXPIRE': 
                    ccbill_expire($payment, $line);
                break;
                case 'REFUND':
                    ccbill_refund($payment, $line);
                break;
                case 'CHARGEBACK':
                    ccbill_refund($payment, $line);
                break;
                case 'RENEW':
                case 'REBILL':
                case 'REBill':
                    $np = ccbill_find_payment($line[5]);
                    if(!$np)
                        ccbill_renew($payment, $line);
                break;
                default:
                    $db->log_error("Unknown record in ccBill datalink: $line_orig");
            }
        }
    }
    // set last run time at end
    $db->config_set('payment.ccbill.last_run', $now_run, 0);
}

function ccbill_find_payment($subscr_id){
    global $db;
    $subscr_id = $db->escape($subscr_id);
    $q = $db->query("SELECT * FROM {$db->config[prefix]}payments 
        WHERE paysys_id LIKE 'ccbill%' 
          AND receipt_id = '$subscr_id'
          AND completed = 1
        ORDER BY begin_date DESC, expire_date DESC
        LIMIT 1");
    $p = mysql_fetch_assoc($q);
    if ($p['payment_id'])
        $p['data'] = $db->decode_data($p['data']);
    return $p;
}

function ccbill_date_to_sql($date){
    if (preg_match('/^\d{14}$/', $date)){
        $s = substr($date, 0, 4) . '-' . 
             substr($date, 4, 2) . '-' . 
             substr($date, 6, 2);
        return $s;
    } else {
        $tm = strtotime($date);
        return date('Y-m-d', $tm);
    }
}

function ccbill_time_to_sql($date){
    $s = substr($date, 0, 4) . '-' . 
         substr($date, 4, 2) . '-' . 
         substr($date, 6, 2) . ' ' .
         substr($date, 8, 2) . ':' .
         substr($date,10, 2) . ':' .
         substr($date,12, 2) . '' ;
    return $s;         
}

function ccbill_expire(&$payment, $line){
    global $db;
    $payment['data'][] = array(
        'posttype' => $line[0],
        'accnum' => $line[1],
        'subacc' => $line[2],
        'subscription_id' => $line[3],
        'expire_date' => $line[4],
        'cancel_date' => $line[5]
    );
    $payment['expire_date'] = ccbill_date_to_sql($line[4]);
    $db->update_payment($payment['payment_id'], $payment);
}

function ccbill_refund(&$payment, $line){
    global $db;
    $payment['data'][] = array(
        'posttype' => $line[0],
        'accnum' => $line[1],
        'subacc' => $line[2],
        'subscription_id' => $line[3],
        'transaction_time' => $line[4],
        'amount' => $line[5]
    );
    $payment['completed'] = 0;
    $db->update_payment($payment['payment_id'], $payment);
}

function ccbill_renew(&$payment, $line){
    global $db;
    $payment['data'][] = $v = array(
        'posttype' => $line[0],
        'accnum' => $line[1],
        'subacc' => $line[2],
        'subscription_id' => $line[3],
        'transaction_time' => $line[4],
        'approval_code' => $line[5],
        'amount' => $line[6]
    );
    $begin_date = $line[4];
    $product = & get_product($payment['product_id']);
    $expire_date = $product->get_expire($begin_date);
    $payment_id = $db->add_waiting_payment($payment['member_id'], 
        $payment['product_id'], $payment['paysys_id'], 
        $line[6], $begin_date, $expire_date, 
        $v);
    if (!$payment_id){
        $db->log_error("ccBill datalink: cannot add waiting payment for $line[3]");
        return;
    }
    $err = $db->finish_waiting_payment($payment_id, $payment['paysys_id'], 
            $line[5], '', array());
    if ($err){
        $db->log_error("ccBill datalink: cannot finish waiting payment #$payment_id");
        return;
    }
}

function ccbill_vsf(&$vars){
    $err = array();
    //if (preg_match('/^[a-zA-Z]+$/', $vars['login'])){
    //    $err[] = "Username must contain digits (along with letters)";
    //}
    if ($vars['login'] == $vars['pass0']){
        $err[] = _PLUG_PAY_CC_BILL_ERROR;
    }
    return $err;
}

?>
