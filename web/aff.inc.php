<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/**
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Affiliate management routines
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5455 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

global $payout_methods;
$payout_methods = array(
    'paypal'       => 'PayPal payment',
    'check'        => 'Offline check',
    'egold'        => 'E-Gold',
    'stormpay'     => 'StormPay',
    'ikobo'        => 'iKobo',
    'moneybookers' => 'MoneyBookers',
    'safepay'	   => 'SafePaySolutions'
);

function aff_get_payout_methods($only_enabled=0){
    global $config;
    $res = $GLOBALS['payout_methods'];
    if ($only_enabled){
        foreach ($res as $k=>$v)
            if (!in_array($k, (array)$config['aff']['payout_methods']))
                unset($res[$k]);
    }
    return $res;
}

function aff_config(){
    global $config;
    config_set_notebook_comment('Affiliates', 'affiliate program configuration');
    add_config_field('aff.payout_methods', 'Accepted Payout methods',
    'multi_select', "affiliate can choose a method for payout comissions.<br />
    If nothing will be selected, comissions will not be included to automated<br />
    payout report.
    ",
    "Affiliates",
    '', '', '',
    array(
        'store_type' => 1,
        'options' => aff_get_payout_methods()
    ));
    add_config_field('aff.aff_commission', 'Affiliate commission for first payment',
    'integer', "affiliate comissions for first payment, ex.: 1.5 or 2.5%",
    "Affiliates",
    '', '', '');
    add_config_field('aff.aff_commission_rec', 'Affiliate commission for the following payments',
    'integer', "affiliate comissions for the following payments, ex.: 1.5 or 2.5%",
    "Affiliates",
    '', '', '');
    add_config_field('aff.aff_commission2', '2 Tier - Affiliate commission for the first payment',
    'integer', 'affiliate comissions for referrer of the affiliate, can be set to<br />
                 percentage of commission received by immediate affiliate only, ex.: 1.5 or 15%<br />
                 in first case 2 tier affiliate will get USD 1.5 for each sale,<br />
                 in second case 2 tier affiliate will receive 15% of all related<br />
                 affiliate commissions',
        "Affiliates",
        '', '', '');
    add_config_field('aff.aff_commission_rec2', '2 Tier - Affiliate commission for the following payments',
    'integer', 'affiliate comissions for referrer of the affiliate, can be set to<br />
                 percentage of commission received by immediate affiliate only, ex.: 1.5 or 15%<br />
                 in first case 2 tier affiliate will get USD 1.5 for each sale,<br />
                 in second case 2 tier affiliate will receive 15% of all related<br />
                 affiliate commissions',
        "Affiliates",
        '', '', '');
    add_config_field('aff.cookie_lifetime', 'Affiliate cookie lifetime',
    'integer', "how long (in days) store cookies about referrer.<br />
    So if customer will return to the site later, comission will be<br />
    paid to referring affiliate.
    ",
    "Affiliates",
    '', '', '',
    array('default'=>365));
    add_config_field('aff.only_first', 'Pay only first commission',
    'checkbox', "affiliate will get commision only for first purchase.<br />
    In case of recurring payments, only one (first) commission will<br />
    be generated.
    ",
    "Affiliates",
    '', '', '',
    array(
        'store_type' => 1
    ));

    add_config_field('aff.do_not_pay_for_free_subscriptions', 'Do not give commision for free subscriptions',
    'select', "sale commission will not be credited to affiliate account<br />
    if user subscribed to free subscription. Of course, it only affects<br />
    'fixed' affiliate commissions.
    ",
    "Affiliates",
    '', '', '',
    array(
        'store_type' => 1,
        'options' => array('' => 'Give Commission even for free subscriptions',
                            1 => 'Do not give commissions for free subscriptions')
    ));

    add_config_field('aff.signup_type', 'Affiliates Signup Type',
    'select', "affiliate will get commision only for first purchase.<br />
    In case of recurring payments, only one (first) commission will<br />
    be generated.
    ",
    "Affiliates",
    '', '', '',
    array(
        'store_type' => 1,
        'options' => array('' => 'Default - user have to click link to become affiliate',
        1 => 'All new members automatically become affiliates',
        2 => 'Only admin can enable user as an affiliate')
    ));

    add_config_field('aff.mail_sale_admin', 'E-Mail commission to admin',
    'checkbox', "when new sale commission credited to affiliate account<br />
    send an e-mail message to admin
    ",
    "Affiliates",
    '', 'email_checkbox_get', '',
    array(
        'store_type' => 1,
    ));
    add_config_field('aff.mail_sale_user', 'E-Mail commission to customer',
    'checkbox', "when new sale commission credited to affiliate account<br />
    send an e-mail message to affiliate
    ",
    "Affiliates",
    '', 'email_checkbox_get', '',
    array(
        'store_type' => 1,
    ));
    add_config_field('aff.mail_signup_user', 'Send Signup E-Mail to Affiliate',
    'checkbox', "send email when affiliate will signup
    ",
    "Affiliates",
    '', 'email_checkbox_get', '',
    array(
        'store_type' => 1,
    ));

}

function aff_set_cookie($aff_id){
    global $config;
    $aff_id = intval($aff_id);
    if (!$aff_id) return;
    $d = $_SERVER['HTTP_HOST'];
    $k = 'amember_aff_id';
    $v = $aff_id;
    $tm = time() + $config['aff']['cookie_lifetime'] * 3600 * 24;
    if (preg_match('/([^\.]+)\.(org|com|net|biz|info)$/', $d, $regs))
        setcookie($k,$v,$tm,"/",".{$regs[1]}.{$regs[2]}");
    else
        setcookie($k,$v,$tm,"/");
}

function aff_decrypt($s){ // easy encryption to hide texts in URL
    $from = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $to   = 'nopqrstuvwxyzabcdefghijklmNOPQRSTUVWXYZABCDEFGHIJKLM';
    return strtr($s, $from, $to);
}

function aff_encrypt($s){ // easy encryption to hide texts in URL
    return aff_decrypt($s);
}

function aff_make_url($url, $link_id, $aff_id){
    global $config;
    return "$config[root_url]/go.php?r=$aff_id&i=$link_id";
//    return "$config[root_url]/go.php?r=$aff_id&l=".urlencode(aff_encrypt($url));
}

function aff_get_commission($product_id, $is_first, $aff_id=0, $paid='', $tier=1){
    global $config, $db;
    $pr = $db->get_product($product_id);
    $fa = ($tier == 1) ? '' : $tier;
    $cf = ($pr['aff_commission'.$fa] != '') ?
        $pr['aff_commission'.$fa] : $config['aff']['aff_commission'.$fa];
    $cr = ($pr['aff_commission_rec'.$fa] != '') ?
        $pr['aff_commission_rec'.$fa] : $config['aff']['aff_commission_rec'.$fa];
    $cexp = $is_first ? $cf : $cr;
    if (preg_match('/^([\d\.]+)\s*\%$/', $cexp, $regs)){ // %
        return round($regs[1] * 0.01 * $paid, 2);
    } else { // absolute
        return $cexp;
    }
    return 0;
}

function add_affiliate_commission($payment_id, $receipt_id='', $amount=''){
    global $db, $config;
    if ($GLOBALS['amember_ignore_aff_commission'] > 0)
        return;
    $oldp = $db->get_payment($payment_id);
    /// affiliate thing
    if (!$oldp['member_id']) return;
    $u = $db->get_user($oldp['member_id']);
    $aff_id = $u['aff_id'];
    if ($aff_id){
        if ($u['member_id'] == $aff_id) return; // don't credit affiliate himself
        $pl = $db->get_user_payments($oldp['member_id'], 1);
        $is_first = 1;
        if (count($pl))
            foreach ($pl as $v){
                $orig_id = $v['data'][0]['ORIG_ID'];
                if ($v['payment_id'] == $payment_id)
                    continue;
                if ($orig_id && ($oldp['payment_id'] == $orig_id))
                    continue;
                if ($orig_id && ($oldp['data'][0]['ORIG_ID'] == $orig_id))
                    continue;
                $is_first = 0;
                break;
            }
        if ($config['aff']['only_first'] && !$is_first){
            return;
        }
        if ($amount == '')
            $amount = $oldp['amount'];
        if ($amount<=0.0 && $config['aff']['do_not_pay_for_free_subscriptions'])
            return;
        $commission = aff_get_commission($oldp['product_id'],
            $is_first, $aff_id, $amount, 1);
        if ($commission){
            $db->aff_add_commission($aff_id, $commission,
                $payment_id, $receipt_id,
                $oldp['product_id'], $is_first, false, 1);
            // calculate 2-tier commission
            $aff = $db->get_user($aff_id);
            $aff_id2 = $aff['aff_id']; // find second-tier affiliate
			if(!$aff_id2) return;            	
            $commission2 = aff_get_commission($oldp['product_id'],
                $is_first, $aff_id, $commission, 2);
			if(!$commission2) return;
            $db->aff_add_commission($aff_id2, $commission2,
                $payment_id, $receipt_id,
                $oldp['product_id'], $is_first, false, 2);
        }
    }
}

function aff_get_payout($dat2, $payout_method){
    global $db, $config;
    $payout_method = $db->escape($payout_method);
    if ($payout_method == 'ALL')
        $payout_where = " 1 ";
    else
        $payout_where = " m.aff_payout_type = '$payout_method' ";
    $prefix = $db->config['prefix'];
    $q = $db->query($s = "SELECT
        m.*,
        SUM(c.amount) as credit_amount
        FROM {$prefix}members m
            LEFT JOIN {$prefix}aff_commission c
            ON (m.member_id = c.aff_id AND c.record_type='credit' AND ifnull(c.payout_id,'')='' AND c.date <= '$dat2')
        WHERE $payout_where
        GROUP BY m.member_id
        HAVING credit_amount > 0
    ");
    $rows = array();
    while ($r = mysql_fetch_assoc($q)){
        if ($r['data'])
            $r['data'] = $db->decode_data($r['data']);
        $rows[$r['member_id']] = $r;
    }
###
    $q = $db->query($s = "SELECT
        m.*,
        SUM(c1.amount) as debit_amount,
        SUM(IF(record_type='debit', 1, 0)) as debit_count
        FROM {$prefix}members m
            LEFT JOIN {$prefix}aff_commission c1
            ON (m.member_id = c1.aff_id AND c1.record_type='debit' AND ifnull(c1.payout_id, '') = '' AND c1.date <= '$dat2')
        WHERE $payout_where
        GROUP BY m.member_id
        HAVING debit_amount > 0
    ");
    while ($r = mysql_fetch_assoc($q)){
        $i = $r['member_id'];
        if ($rows[$i]['member_id'])
            $rows[$i]['debit_amount'] = $r['debit_amount'] != 0 ? -$r['debit_amount']." (".$r['debit_count'].")" : '';
        else {
            $r['credit_amount'] = 0.0;
            $rows[$i] = $r;
        }
    }
    foreach ($rows as $k=>$r){
        
        $r['account_id'] = "";
        switch ($r['aff_payout_type']) {
            case 'stormpay':
                if ($payout_method == 'ALL')
                    $r['account_id'] .= 'StormPay: ';
                $r['account_id'] .= $r['data']['aff_stormpay_email'];
                break;
            case 'ikobo':
                if ($payout_method == 'ALL')
                    $r['account_id'] .= 'iKobo: ';
                $r['account_id'] .= $r['data']['aff_ikobo_email'];
                break;
            case 'moneybookers':
                if ($payout_method == 'ALL')
                    $r['account_id'] .= 'MoneyBookers: ';
                $r['account_id'] .= $r['data']['aff_moneybookers_email'];
                break;
            case 'egold':
                if ($payout_method == 'ALL')
                    $r['account_id'] .= 'E-Gold: ';
                $r['account_id'] .= $r['data']['aff_egold_id'];
                break;
            case 'paypal':
                if ($payout_method == 'ALL')
                    $r['account_id'] .= 'PayPal: ';
                $r['account_id'] .= $r['data']['aff_paypal_email'];
                break;
            case 'safepay':
                if ($payout_method == 'ALL')
                    $r['account_id'] .= 'SafePaySolutions: ';
                $r['account_id'] .= $r['data']['aff_safepay_email'];
                break;
            default:
                $r['account_id'] = '&nbsp;';
                break;
        }
        $rows[$k] = $r;

        $rows[$k]['to_pay'] = $s = sprintf('%.2f', $r['credit_amount'] + $r['debit_amount']);
        if (!$s)
            $rows[$k]['to_pay'] = '';

    }
    return $rows;
}

function aff_pay_commissions($dat2, $rows, $payout_id){
    global $db;
    $payout_id = $db->escape($payout_id);
    foreach ($rows as $k=>$r){
        $db->query("UPDATE {$db->config[prefix]}aff_commission
        SET payout_id='$payout_id'
        WHERE aff_id='$r[member_id]' AND date <= '$dat2' AND payout_id IS NULL
        ");
    }
}

function aff_pay_commission_paypal($dat1, $dat2, $payout_method, $rows){

    header('Cache-Control: maxage=3600');
    header('Pragma: public');

    header("Content-type: application/csv");
    $tm = time();
    header("Content-Disposition: attachment; filename=paypal-commission-$tm.txt");
    foreach ($rows as $r){
        $r['to_pay'] = sprintf('%.2f', $r['to_pay']);
        print "{$r[data][aff_paypal_email]}\t$r[to_pay]\tUSD\t$r[member_id]\tAffiliate commission for $dat1 - $dat2\r\n";
    }
}
function aff_pay_commission_check($dat1, $dat2, $payout_method, $rows){

    header('Cache-Control: maxage=3600');
    header('Pragma: public');

    header("Content-type: application/csv");
    $tm = time();
    header("Content-Disposition: attachment; filename=check-commission-$tm.csv");
    print "First Name;Last Name;Street Address;City;State;ZIP;Country;Check Amount\n";
    foreach ($rows as $r){
        $r['to_pay'] = sprintf('%.2f', $r['to_pay']);
        print "$r[name_f];$r[name_l];$r[street];$r[city];$r[state];$r[zip];$r[country];$r[to_pay]\n";
    }
}
function aff_pay_commission_stormpay($dat1, $dat2, $payout_method, $rows){

    header('Cache-Control: maxage=3600');
    header('Pragma: public');

    header("Content-type: application/csv");
    $tm = time();
    header("Content-Disposition: attachment; filename=stormpay-commission-$tm.csv");
    print "StormPay Account ID; Amount\n";
    foreach ($rows as $r){
        $r['to_pay'] = sprintf('%.2f', $r['to_pay']);
        print "{$r[data][aff_stormpay_email]};$r[to_pay]\n";
    }
}
function aff_pay_commission_ikobo($dat1, $dat2, $payout_method, $rows){

    header('Cache-Control: maxage=3600');
    header('Pragma: public');

    header("Content-type: application/csv");
    $tm = time();
    header("Content-Disposition: attachment; filename=ikobo-commission-$tm.csv");
    print "iKobo Account ID; Amount\n";
    foreach ($rows as $r){
        $r['to_pay'] = sprintf('%.2f', $r['to_pay']);
        print "{$r[data][aff_ikobo_email]};$r[to_pay]\n";
    }
}
function aff_pay_commission_moneybookers($dat1, $dat2, $payout_method, $rows){

    header('Cache-Control: maxage=3600');
    header('Pragma: public');

    header("Content-type: application/csv");
    $tm = time();
    header("Content-Disposition: attachment; filename=moneybookers-commission-$tm.csv");
    print "MoneyBookers Account ID; Amount\n";
    foreach ($rows as $r){
        $r['to_pay'] = sprintf('%.2f', $r['to_pay']);
        print "{$r[data][aff_moneybookers_email]};$r[to_pay]\n";
    }
}
function aff_pay_commission_egold($dat1, $dat2, $payout_method, $rows){
    global $db;

    header('Cache-Control: maxage=3600');
    header('Pragma: public');

    header("Content-type: application/csv");
    $tm = time();
    header("Content-Disposition: attachment; filename=egold-commission-$tm.csv");
    print "E-Gold Account ID; Amount\n";
    foreach ($rows as $r){
        $r['to_pay'] = sprintf('%.2f', $r['to_pay']);
        print "{$r[data][aff_egold_id]};$r[to_pay]\n";
    }
}

function aff_pay_commission_safepay($dat1, $dat2, $payout_method, $rows){

    header('Cache-Control: maxage=3600');
    header('Pragma: public');

    header("Content-type: application/csv");
    $tm = time();
    header("Content-Disposition: attachment; filename=safepay-commission-$tm.txt");
    foreach ($rows as $r){
        $r['to_pay'] = sprintf('%.2f', $r['to_pay']);
        print "\"{$r[data][aff_safepay_email]}\";\"$r[to_pay]\";\"Affiliate commission for $dat1 - $dat2\"\r\n";
    }
}



function aff_get_member_links($u){
    global $config;
    if (!$u['is_affiliate'] && ($config['aff']['signup_type'] == '')){
        return array($config['root_url']."/aff.php?action=enable_aff"
            => _AFF_ADVERTISE_SITE);
    } elseif ($u['is_affiliate']) {
        return array($config['root_url']."/aff_member.php"
            => _AFF_MEMBER_AREA);
    }
}

function aff_make_affiliate($member_id){
    global $db, $_AFF_MAKE_AFFILIATE,$config;
    if ($_AFF_MAKE_AFFILIATE) return;
    if ($member_id <= 0) return;
    $u = $db->get_user($member_id);
    $u['is_affiliate'] = 1;
    $_AFF_MAKE_AFFILIATE = 1;
    $db->update_user($member_id, $u);
    if($config['aff']['mail_signup_user']) check_aff_signup_email_sent($member_id);

    unset($_AFF_MAKE_AFFILIATE);
}

global $config;
if ($config['use_affiliates']){
/**/
    add_product_field('aff_commission', 'Affiliate commission for first payment',
        'text', 'affiliate comissions for first payment, ex.: 1.5 or 2.5%');
    add_product_field('aff_commission_rec', 'Affiliate commission for the following payments',
        'text', 'affiliate comissions for following payments, ex.: 0 or 5%');
    add_product_field('aff_commission2', '2 Tier - Affiliate commission for first payment',
        'text', 'affiliate comissions for referrer of the affiliate');
    add_product_field('aff_commission_rec2', '2 Tier - Affiliate commission for the following payments',
        'text', 'affiliate comissions for referrer of the affiliate');
    add_member_field(
        'aff_signup_email_sent', 'Affiliate Signup Email Sent',
        'hidden', '',
        '',
        array('hidden_anywhere' => 1));

    /**/
    
    if (in_array('paypal', (array)$config['aff']['payout_methods'])){
        add_member_field('aff_paypal_email', _AFF_MEMBER_F1_1, 'text', '', '',
            array('hidden_anywhere' => 1));
    }
    if (in_array('stormpay', (array)$config['aff']['payout_methods'])){
        add_member_field('aff_stormpay_email', _AFF_MEMBER_F2_1, 'text', '', '',
            array('hidden_anywhere' => 1));
    }
    if (in_array('ikobo', (array)$config['aff']['payout_methods'])){
        add_member_field('aff_ikobo_email', _AFF_MEMBER_F3_1, 'text', '', '',
            array('hidden_anywhere' => 1));
    }
    if (in_array('moneybookers', (array)$config['aff']['payout_methods'])){
        add_member_field('aff_moneybookers_email', _AFF_MEMBER_F4_1, 'text', '', '',
            array('hidden_anywhere' => 1));
    }
    if (in_array('egold', (array)$config['aff']['payout_methods'])){
        add_member_field('aff_egold_id', _AFF_MEMBER_F5_1, 'text', '', '',
            array('hidden_anywhere' => 1));
    }
    if (in_array('check', (array)$config['aff']['payout_methods'])){
        add_member_field('aff_check_payable_to', _AFF_MEMBER_F6_1, 'text', '', '',
            array('hidden_anywhere' => 1));
    }
    if (in_array('safepay', (array)$config['aff']['payout_methods'])){
        add_member_field('aff_safepay_email', _AFF_MEMBER_F7_1, 'text', '', '',
            array('hidden_anywhere' => 1));
    }

    setup_plugin_hook('finish_waiting_payment', 'add_affiliate_commission');
    setup_plugin_hook('get_member_links', 'aff_get_member_links');
    if ($config['aff']['signup_type'] == 1){
        setup_plugin_hook('update_users', 'aff_make_affiliate');
    }
}

function check_aff_signup_email_sent($member_id){
    global $db,$config;
    $user = $db->get_user($member_id);
    if(!$user['data']['aff_signup_email_sent']){
        mail_signup_affiliate($member_id);
        $user['data']['aff_signup_email_sent']++;
        $db->update_user($member_id, $user);
    }
}

?>