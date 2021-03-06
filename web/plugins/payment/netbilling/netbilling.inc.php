<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: PayFlow PRO payment plugin class
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2976 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember is free for both commercial and non-commercial use providing that the
* copyright headers remain intact and the links remain on the html pages.
* Re-distribution of this script without prior consent is strictly prohibited.
*
*/
$config['cc_code'] = 1;
$config['cc_name_f'] = 1;
$config['cc_name_l'] = 1;
require_once("$config[root_dir]/plugins/payment/netbilling/pay.inc.php");

    
setup_plugin_hook('get_member_links', 'netbilling_get_member_links');
setup_plugin_hook('daily', 'netbilling_rebill');

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
add_product_field('rebill_times', 
    'Recurring Times',
    'text',
    'Recurring Times. This is the number of payments which<br />
     will occur at the regular rate. If omitted, payment will<br />
     continue to recur at the regular rate until the subscription<br />
     is cancelled.<br />
     NOTE: not for all payment processing this option is working'
    );


add_member_field(
        'cc_street',
        'Billing Street Address',
        'text', 
        "",
        '',
        array('hidden_anywhere' => 1)
);
add_member_field(
        'cc_city',
        'Billing City',
        'text', 
        "",
        ''
);
add_member_field(
        'cc_state',
        'Billing State',
        'select', 
        "",
        '', 
        array('options' => db_getStatesForCountry('US', true))
);
add_member_field(
        'cc_zip',
        'Billing ZIP',
        'text', 
        "",
        ''
);
add_member_field(
        'cc_country',
        'Billing Country',
        'select', 
        "",
        '', 
        array('options' => db_getCountryList(true))
);

add_member_field(
        'cc',
        'Credit Card # (visible)',
        'readonly', 
        "credit card number (read-only)",
        '',
        array('hidden_anywhere' => 1)
);

add_member_field(
        'cc-hidden',
        'Credit Card # (crypted)',
        'hidden', 
        '',
        '',
        array('hidden_anywhere' => 1)
);

add_member_field(
        'cc-expire',
        'Credit Card Expire',
        'readonly', 
        'Expiration date (mmyy)',
        '',
        array('hidden_anywhere' => 1)
);
add_member_field('cc_name_f', 'Billing First Name', 'text', '');
add_member_field('cc_name_l', 'Billing Last Name', 'text', '');

add_paysystem_to_list(
array(
            'paysys_id' => 'netbilling',
            'title'     => $config['payment']['netbilling']['title'] ? $config['payment']['netbilling']['title'] : _PLUG_PAY_NETBILLING_TITLE,
            'description' => $config['payment']['netbilling']['description'] ? $config['payment']['netbilling']['description'] : _PLUG_PAY_NETBILLING_CC_PAYMENT,
            'public'    => 1,
            'recurring' => 1
        )
);

class payment_netbilling extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        global $config;
        header("Location: $config[root_surl]/plugins/payment/netbilling/cc.php?payment_id=$payment_id&member_id=$member_id");
        return '';
    }
    function get_cancel_link($payment_id){
        global $db;
        $p = $db->get_payment($payment_id);
        if (!$p['data']['CANCELLED'])
        return 
        "member.php?action=cancel_recurring&payment_id=$payment_id";
    }
}

function netbilling_get_member_links($user){
    global $config;
    if ($user['data']['cc'])
        return array("$config[root_surl]/plugins/payment/netbilling/cc.php?renew_cc=1&member_id=$user[member_id]" => 'Update CC info');
}

// return 1 if rebill allowed
function netbilling_check_rebill_times($times, $payment){
    global $db;
    if ($payment_id = $payment['data'][0]['RENEWAL_ORIG']) {
        $count = 1;
        do {
            $count++;
            $x = preg_split('/ /', $payment_id);
            $payment_id = $x[1];
            $payment = $db->get_payment($payment_id);
        } while ($payment_id = $payment['data'][0]['RENEWAL_ORIG']);
    } else {
        $payment_id = $payment['payment_id'];
        $count = 1; // made payments
    }
    return $count < $times;
}

function netbilling_rebill(){
    global $config, $db, $t;
    if (!$config['use_cron'])
        fatal_error("NetBilling rebill can be run only with external cron");
    $dat = date('Y-m-d');
    $tomorrow = date('Y-m-d', time() + 3600 * 24);
    $payments = $db->get_expired_payments($dat, $dat, 'netbilling');
    $renewed = array();
    $log = "NetBilling Rebill\n";
    foreach ($payments as $p){
        if ($p['data']['CANCELLED']) 
            continue;
        $member_id = $p['member_id'];
        $member = $db->get_user($member_id);
        $product_id = $p['product_id'];
        if ($renewed[$member_id][$product_id]++) continue;
        $product = & get_product($product_id);
        if (!$product->config['is_recurring']) continue;
        if ($product->config['rebill_times'] &&
            !netbilling_check_rebill_times($product->config['rebill_times'], $p))
            continue;
        $vars = array(
            'RENEWAL_ORIG' => "RENEWAL_ORIG: $p[payment_id]"
        );
        $payment_id = $db->add_waiting_payment($member_id, $product_id, 
            'netbilling', $product->config['price'], 
             $dat, $product->get_expire($dat), 
             $vars);
        list($err, $errno) = netbilling_payment($payment_id, $member_id);
        if ($errno>1){
            mail_rebill_failed_member($member, $payment_id, $product, "$err ($errno)");
        } elseif (!$errno){
            $db->log_error("no return from NetBilling, payment #$payment_id");
        } elseif ($errno == 1) {
            $err = "COMPLETED";
            mail_rebill_success_member($member, $payment_id, $product);
        }
        $log .= "login: $p[member_login] payment#: $payment_id: $err ($errno)\n";
    }
#    print $log;
}

?>
