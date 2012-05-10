<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin users
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5458 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

include "../config.inc.php";
$t = new_smarty();
include "login.inc.php";

$vars = get_input_vars();
extract($vars, EXTR_OVERWRITE);

$count = 20;

function get_az(){
    $az = array();
    for ($c=ord('A');$c<=ord('Z');$c++){
        $az[chr($c)] = 0;
    }
    return $az;
}

function display_not_approved_user_list(){
    global $db, $t;
    $ul = & $db->users_find_by_string("", "additional:is_approved");
    global $all_count, $count, $start;
    $all_count = count($ul);

    $ul = @array_slice($ul, $start, $count);
    $t->assign('ul', $ul);

    $t->assign('title', 'Not-Approved Users');
    $t->assign('users_total', $all_count);
    $t->assign('az', array());
    $t->display('admin/users_approve.html');
}

function approve(){
    global $db, $t, $vars;
    foreach ((array)$vars['act'] as $member_id=>$v){
        switch ($v){
            case 'approve': 
                $u = $db->get_user($member_id);
                $u['data']['is_approved'] = 1;
                $db->update_user($member_id, $u);
        check_for_signup_mail("", $member_id);
            break;
            case 'remove' :
                $db->delete_user($member_id);
            break;
            default:;
        }
    }
    header("Location: users.php?action=not_approved");
    exit();
}

function get_status_options(){
    return array(
        '' => 'All members',
        '00' => 'Pending',
        '1' => 'Active', 
        '2' => 'Expired',
        '3' => 'Affiliates'
    );
}

function display_user_list(){
    global $letter;
    global $active;
    global $db, $t, $vars;
    global $all_count, $count, $start;
    $letter = substr($letter, 0, 1);
    $all_count = $db->get_users_list_c($letter."%", $vars['status']);
    $ul = & $db->get_users_list($letter."%", $vars['status'], $start, $count);
    $t->assign('ul', $ul);
    $t->assign('az', get_az());
    $t->assign('users_total', $db->get_users_list_c('', $vars['status']));
    $t->assign('status_options', get_status_options());
    $t->display('admin/users.html');
}

function display_access_log(){
    global $member_id;
    global $start, $count;
    global $all_count;
    global $db, $t;

    if (!$count) $count = 20;
    $list = $db->get_access_log($member_id, $start, $count);
    $all_count = $db->get_access_log_c($member_id);

    $t->assign("u", $db->get_user($member_id));
    $t->assign('list', $list);
    $t->assign('count', $count);
    $t->display("admin/user_access_log.html");
}
function display_aff_clicks(){
    global $member_id;
    global $start, $count;
    global $all_count;
    global $db, $t;

    admin_check_permissions('affiliates');

    if (!$count) $count = 20;
    $list = $db->get_aff_clicks($member_id, $start, $count);
    $all_count = $db->get_aff_clicks_c($member_id);

    $t->assign('u', $db->get_user($member_id));
    $t->assign('list', $list);
    $t->assign('count', $count);
    $t->display("admin/user_aff_clicks.html");
}


function get_ym_options(){
    $res = array();
    $y = date('Y');
    $m = date('m');
    for ($i=0;$i<36;$i++){
        $res[ $y."_".$m ] = date('F 01-t, Y', strtotime("{$y}-{$m}-01"));
        $m--;
        if ($m <= 0) { $m = 12; $y--; }
    }
    return $res;
}
function get_default_ym(){
    $y = date('Y');
    $m = date('m');
    $m -= 0;
    if ($m <= 0){
        $y--;
        $m = 12 - $m;
    }
    return "{$y}_{$m}";
}
function display_aff_sales(){
    global $member_id, $vars;
    global $db, $t;
    $t->assign('year_month_options', get_ym_options());
    $t->assign('default_month', get_default_ym());
    
    admin_check_permissions('affiliates');

    if ($vars['year_month'] == '')
        $vars['year_month'] = get_default_ym();

    list($y, $m) = split('_', $vars['year_month']);
    $m = sprintf('%02d', $m);
    $dat1 = "{$y}-{$m}-01"; 
    $dat2 = date('Y-m-t', strtotime($dat1));
    $dattm1 = date('Ymd000000', strtotime($dat1));
    $dattm2 = date('Ymd235959', strtotime($dat2));
    $totaldays = date('t', strtotime($dat1));        
    
    $days = array();
    $total = array();
    for ($i=1;$i<=$totaldays;$i++)
        $days[$i] = array('dat' => sprintf("$y-$m-%02d", $i));
    // get clicks for the month
    $q = $db->query("SELECT DAYOFMONTH(ac.time), COUNT(log_id), COUNT(DISTINCT(remote_addr))
        FROM {$db->config[prefix]}aff_clicks ac
        WHERE aff_id=$member_id AND ac.time BETWEEN $dattm1 AND $dattm2
        GROUP BY DAYOFMONTH(ac.time)
    ");
    while (list($d, $r, $u) = mysql_fetch_row($q)){
        $days[$d]['raw'] = $r;
        $days[$d]['uniq'] = $u;
    }
    
    // get total clicks for the month
    $q = $db->query("SELECT COUNT(log_id), COUNT(DISTINCT(remote_addr))
        FROM {$db->config[prefix]}aff_clicks ac
        WHERE aff_id=$member_id AND ac.time BETWEEN $dattm1 AND $dattm2
    ");
    while (list($r, $u) = mysql_fetch_row($q)){
        $total['raw'] = $r;
        $total['uniq'] = $u;
    }
    
    // get comissions for the month
    $q = $db->query("SELECT DAYOFMONTH(ac.date), COUNT(commission_id),
        SUM(IF(record_type='debit', amount, 0)), 
        SUM(IF(record_type='credit', amount, 0)),
        SUM(IF(record_type='debit', 1, 0))
        FROM {$db->config[prefix]}aff_commission ac
        WHERE aff_id=$member_id AND ac.date BETWEEN '$dat1' AND '$dat2'
        GROUP BY DAYOFMONTH(ac.date)
    ");
    while (list($d, $cnt, $deb, $cre, $deb_count) = mysql_fetch_row($q)){
        $days[$d]['trans'] = $cnt;
        $days[$d]['debit'] = $deb != 0 ? -$deb." ($deb_count)" : '';
        $days[$d]['credit'] = $cre;
        $dat = "{$y}-{$m}-{$d}"; 
        if ($deb || $cre){
        	$rr = $db->query_all("SELECT c.amount as c_amount, c.payment_id, p.member_id, 
        			pr.title as pr_title,
        			m.login, m.name_f, m.name_l, p.amount as p_amount, c.tier as tier, c.record_type
        		FROM {$db->config[prefix]}aff_commission c 
        		LEFT JOIN {$db->config[prefix]}payments p USING (payment_id)
        		LEFT JOIN {$db->config[prefix]}members m ON p.member_id = m.member_id
        		LEFT JOIN {$db->config[prefix]}products pr ON p.product_id = pr.product_id 
        		WHERE c.date = '$dat' AND c.aff_id=$member_id 
        	");
        	$days[$d]['detail'] = $rr;
        }
        
        if ($deb || $cre)
            $days[$d]['total'] = $cre - $deb;
        $total['trans'] += $cnt;
        $total['debit'] += $deb;
        $total['credit'] += $cre;
        $total['total'] += $days[$d]['total'];
    }
    $total['debit'] = $total['debit'] != 0 ? -$total['debit'] : '';
    $t->assign('days', $days);
    $t->assign('total', $total);
    
    /// top 20 referrers
    $q = $db->query("SELECT referrer, COUNT(log_id), COUNT(DISTINCT(remote_addr))
        FROM {$db->config[prefix]}aff_clicks ac
        WHERE aff_id=$member_id AND referrer > '' AND ac.time BETWEEN $dattm1 AND $dattm2
        GROUP BY referrer
        #ORDER BY COUNT(log_id) DESC, COUNT(DISTINCT(remote_addr)) DESC
        #LIMIT 0,20
    ");
    $refs = array();
    while (list($ref, $raw, $uniq) = mysql_fetch_row($q)){
        $refs[] = array(
            'raw'  => $raw,
            'uniq' => $uniq,
            'ref'  => $ref
        );
    }
    $t->assign('u', $db->get_user($member_id));
    $t->assign('refs', $refs);
    $t->display("admin/user_aff_stats.html");
}


function display_search_form(){
    global $db, $t;

    $t->assign('az', get_az());

    $products = & $db->get_products_list();
    $pp = array();
    foreach ($products as $p) $pp[ $p['product_id'] ] = $p['title'] ;
    $t->assign('products', $pp);

    $user_fields = array();
    foreach ($GLOBALS['member_additional_fields'] as $f){
        if ($f['name'] == 'is_locked') continue;
        if ($f['name'] == 'i_agree') continue;
        if ($f['name'] == 'is_approved') continue;
        if ($f['type'] == 'hidden') continue;
        $user_fields[$f['name']] = $f['title'];
    }
    $t->assign('member_fields', $user_fields);

    $t->display('admin/user_search.html');
}

function search_by_string(){
    global $q, $q_where;
    global $db, $t;
    $ul = & $db->users_find_by_string($q, $q_where);
    if (count($ul) == 0){
        $t->assign('error', 'Nothing was found by your request. Try to redefine search terms');
        display_search_form();
        return;
    }
    global $all_count, $count, $start;
    $all_count = count($ul);

    $ul = @array_slice($ul, $start, $count);
    $t->assign('ul', $ul);

    $t->display('admin/user_search_res.html');
}

function search_by_product(){
    global $product_id, $include_expired;
    global $db, $t;
    $ul = & $db->users_find_by_product($product_id, $include_expired);
    if (count($ul) == 0){
        $t->assign('error', 'Nothing was found by your request. Try to redefine search terms');
        display_search_form();
        return;
    }
    global $all_count, $count, $start;
    $all_count = count($ul);

    $ul = @array_slice($ul, $start, $count);
    $t->assign('ul', $ul);

    $t->display('admin/user_search_res.html');
}

function search_by_date(){
    global $search_type;
    global $db, $t;
    set_date_from_smarty('date', $_GET);
    $ul = & $db->users_find_by_date($_GET['date'], $search_type);
    if (count($ul) == 0){
        $t->assign('error', 'Nothing was found by your request. Try to redefine search terms');
        display_search_form();
        return;
    }
    global $all_count, $count, $start;
    $all_count = count($ul);

    $ul = @array_slice($ul, $start, $count);
    $t->assign('ul', $ul);

    $t->display('admin/user_search_res.html');
}

function search_locked(){
    global $db, $t;

    $ul = & $db->users_find_by_string('1', 'additional:is_locked');
    if (count($ul) == 0){
        $t->assign('error', 'Nothing was found by your request. Try to redefine search terms');
        display_search_form();
        return;
    }
    global $all_count, $count, $start;
    $all_count = count($ul);

    $ul = @array_slice($ul, $start, $count);
    $t->assign('ul', $ul);

    $t->display('admin/user_search_res.html');
}

function display_add_form($vars=''){
    global $db, $t;
    $t->assign('add', 1);
    settype($vars, 'array');

    global $member_additional_fields;
    $u = $vars;
    $t->assign('u', $u);
    $t->assign('threads_list', $db->get_newsletter_threads());
    $t->assign('member_additional_fields', $member_additional_fields);
    $t->assign('additional_fields_html', get_additional_fields_html($u, 'admin', 1));
    $t->assign('aff_payout_types', aff_get_payout_methods());
    $t->display('admin/user_form.html');
}

function display_edit_form($vars=array()){
    global $member_id;
    global $db, $t;
    $u = & $db->get_user(intval($member_id));
    global $member_additional_fields;
    if ($u['aff_id']){
        $a = $db->get_user($u['aff_id']);
        $t->assign('aff', $a);
    }
    foreach ((array)$vars['data'] as $k=>$v)
        $u['data'][$k] = $v;
    foreach ($vars as $k=>$v){
        if ($k != 'data')
            $u[$k] = $v;
    }
    
    $threads = $db->get_member_threads($member_id);
    $threads = array_keys($threads);
    
    $t->assign('u', $u);
    $t->assign('threads', $threads);
    $t->assign('threads_list', $db->get_newsletter_threads());
    $t->assign('member_additional_fields', $member_additional_fields);
    $t->assign('additional_fields_html', get_additional_fields_html($u, 'admin', 1));
    $t->assign('aff_payout_types', aff_get_payout_methods());
    $t->display('admin/user_form.html');
}

function get_visible_cc_number($cc){
    $cc = preg_replace('/\D+/', '', $cc);
    return '**** **** **** '.substr($cc, -4);
}

function update_cc_info($member_id, $vars){
    global $db;
    $change = array();
    if ( strlen($vars['cc_number']) && $vars['cc_expire_Month'] && $vars['cc_expire_Year'] ) {
        $vars['cc_number'] = preg_replace('/\D+/', '', $vars['cc_number']);
        $change['cc-hidden'] = amember_crypt($vars['cc_number']);
        $change['cc']        = get_visible_cc_number($vars['cc_number']);
        
    }
    if ( $vars['cc_expire_Month'] && $vars['cc_expire_Year'] ) {
        $change['cc-expire'] = sprintf('%02d%02d',
        $vars['cc_expire_Month'],
        substr($vars['cc_expire_Year'], 2, 2));
    }

    ////////////////////////////////////////////////////////////////////
    $m = $db->get_user($member_id);
    foreach ($change as $k=>$v)
        $m['data'][$k] = $v;

    $db->update_user($member_id, $m);        
}

function add_save(){
    global $db, $t;
    global $member_additional_fields;

    $errors = array();
    $vars = get_input_vars();

    if (strlen($vars['generate_login']))
        $vars['login'] = generate_login($vars);
    if (strlen($vars['generate_pass']))
        $vars['pass'] = generate_password($vars);

    $vars['pass0'] = $vars['pass'];
    if (!strlen($vars['login'])){
        $errors[] = "'Login' is a required field";
    } elseif ($db->check_uniq_login($vars['login'], $vars['email'], $vars['pass0'], 1)>=0) {
        $errors[] = "User '$vars[login] already exists' - please choose another username";
    }
    if (!strlen($vars['pass0'])){
        $errors[] = "'Password' is a required field";
    }
    foreach ($member_additional_fields as $f){
        $fname = $f['name'];
        if ($f['validate_func'])
            foreach ((array)$f['validate_func'] as $func){
                if (!function_exists($func))
                    fatal_error("Validation function '$func' for field: '$fname' not defined. Internal error", 0);
                if ($err = $func($vars[$fname], $f['title'], $f))
                    $errors[] ="$err";
            }
    }
    foreach ($member_additional_fields as $f){
        $fname = $f['name'];
        if (isset($vars[$fname]))
            $vars['data'][$fname] = $vars[$fname];
    }
    if ($errors){
        $t->assign('errors', $errors);
        return display_add_form($vars);
    }
    foreach ($member_additional_fields as $f){
        $fname = $f['name'];
        $vars[$fname] = $vars['data'][$fname];
        unset($vars['data'][$fname]);
    }

    $member_id = $db->add_pending_user( $vars);
    
    if ($config['use_affiliates'])
        $is_affiliate = $vars['is_affiliate'];
    else
        $is_affiliate = '0';
    $db->subscribe_member ($member_id, $is_affiliate);
    
    $db->add_member_threads($member_id, $vars['threads']);

    update_cc_info($member_id, $vars);
    admin_log("Member added ($vars[login])", 'members', $member_id);
    $t->assign('member_id', $member_id);
    $t->assign('msg', "User added. Click on 'User Payments' link in top to subscribe him.");
    $t->assign('link', "users.php?action=payments&member_id=$member_id");
    $t->display("admin/user_saved.html");
}

function delete_user(){
    global $member_id;
    global $db, $t, $vars;
    if (!$vars['confirm']){
        $user = $db->get_user($member_id);
        display_confirm("Delete user: $user[login]", "
        <center>Do you really want to delete user: $user[login]?
        It will be impossible to restore customer records.
        </center>
        ");
        return;
    } elseif ($vars['confirm'] != 'Yes'){
        display_edit_form();
        return;
    }
    $user = $db->get_user($member_id);
    $db->delete_user($member_id);
    $db->delete_member_threads($member_id);
    admin_log("Member record removed ($user[login])", 'members', $member_id);
    $t->assign('msg', "User deleted");
    $t->assign('link', 'users.php');
    $t->assign('hide_notebook', 1);
    $t->display("admin/user_saved.html");
}


function edit_save(){
    global $db, $t;
    global $member_additional_fields, $config;

    $vars = get_input_vars();
    $oldm = $db->get_user($vars['member_id']);
    
    $vars['email_verified']     = $oldm['email_verified'];
    $vars['security_code']      = $oldm['security_code'];
    $vars['securitycode_expire'] = $oldm['securitycode_expire'];

    $errors = array();

    if (strlen($vars['generate_login']))
        $vars['login'] = generate_login($vars);
    if (strlen($vars['generate_pass']))
        $vars['pass'] = generate_password($vars);
    if (!strlen($vars['login'])){
        $errors[] = "'Login' is a required field";
    }
    
    if (($vars['pass'] == '') && $config['hide_password_cp'])
        $vars['pass'] = $oldm['pass'];
        
    if (!strlen($vars['pass'])){
        $errors[] = "'Password' is a required field";
    }
    foreach ($member_additional_fields as $f){
        $fname = $f['name'];
        if ($f['validate_func'])
            foreach ((array)$f['validate_func'] as $func){
                if (!function_exists($func))
                    fatal_error("Validation function '$func' for field: '$fname' not defined. Internal error");
                if ($err = $func($vars[$fname], $f['title'], $f))
                    $errors[] = $err;
            }
    }
    foreach ($member_additional_fields as $f){
        if ($f['sql']) continue;
        $fname = $f['name'];
        if (isset($vars[ $fname ]))
            $vars['data'][ $fname ] = $vars[ $fname ];
        unset($vars[ $fname ]);
    }
    if ($errors){
        $t->assign('errors', $errors);
        return display_edit_form($vars);
    }

    // no subscriptions for updated user
    //$db->subscribe_member ($vars['member_id'], $vars['is_affiliate']);
    //
    $err = $db->update_user($vars['member_id'], $vars);
    
    $db->delete_member_threads($vars['member_id']);
    $db->add_member_threads($vars['member_id'], $vars['threads']);
    
    if ($err) {
        fatal_error("Cannot update user info: $err");
    }
    update_cc_info($vars['member_id'], $vars);
    admin_log("Member record changed ($oldm[login])", 'members', $vars['member_id']);

    if ($config['manually_approve']){
        if (($oldm['data']['is_approved'] != $vars['data']['is_approved'])
             && $vars['data']['is_approved'])
            check_for_signup_mail(0, $vars['member_id']);
    }

    $t->assign('link', "users.php?member_id=$vars[member_id]&action=edit");
    $t->display("admin/user_saved.html");
}

function edit_payment(){
    global $member_id, $payment_id, $vars;
    global $db, $t;
    $t->assign("u", $db->get_user($member_id));
    $p = $db->get_payment(intval($payment_id));
    $t->assign('p', $p);
    $products = & $db->get_products_list();
    $pp = array();
    foreach ($products as $p) $pp[ $p['product_id'] ] = $p['title'] ;
    $t->assign('products', $pp);

    $paysystems = get_paysystems_list();
    $pp = array();
    foreach ($paysystems as $p) $pp[ $p['paysys_id'] ] = $p['title'] ;
    $t->assign('paysystems', $pp);
    
    /// 
    $payment_id = intval($payment_id);
    if ($vars['void'] > 0){ // void affilaite commission
        $commission_id = intval($vars['commission_id']);
        $orig_c = $db->query_first("SELECT * 
            FROM {$db->config[prefix]}aff_commission
            WHERE commission_id=$commission_id");
        $orig_c['receipt_id'] = $db->escape($orig_c['receipt_id']);
        $c = $db->query_all($s = "SELECT commission_id
            FROM {$db->config[prefix]}aff_commission
            WHERE payment_id='$orig_c[payment_id]' AND
                  receipt_id='$orig_c[receipt_id]' AND
                  tier = '$orig_c[tier]' AND
                  record_type='debit'
            ");
        if (!$c){            
            $db->query("INSERT INTO {$db->config[prefix]}aff_commission
            SET
                aff_id='$orig_c[aff_id]',
                date=NOW(),
                amount='$orig_c[amount]',
                record_type='debit',
                payment_id='$orig_c[payment_id]',
                receipt_id='$orig_c[receipt_id]',
                product_id='$orig_c[product_id]',
                tier='$orig_c[tier]',
                is_first='$orig_c[is_first]'
            ");
        }
    } elseif ($vars['void'] < 0){
        $db->query("DELETE 
            FROM {$db->config[prefix]}aff_commission
            WHERE commission_id = '$vars[void_id]'
        ");
    }
    
    $commissions = $db->query_all("SELECT ac.aff_id as caff_id, ac.*, m.*, 
        acv.date AS void_date, acv.commission_id as void_id
        FROM {$db->config[prefix]}aff_commission ac
            LEFT JOIN {$db->config[prefix]}members m ON ac.aff_id = m.member_id
            LEFT JOIN {$db->config[prefix]}aff_commission acv ON   
                (ac.payment_id = acv.payment_id AND ac.receipt_id = acv.receipt_id AND acv.record_type='debit' AND ac.tier = acv.tier)
        WHERE ac.payment_id = $payment_id AND ac.record_type ='credit'");
    $t->assign('commissions', (array)$commissions);        

    global $payment_additional_fields;
    $t->assign('payment_additional_fields', $payment_additional_fields);
    $t->display('admin/user_payment.html');
}

function del_payment(){
    global $member_id, $payment_id;
    global $db, $t;

    $oldp = $db->get_payment($payment_id);
    $err = $db->delete_payment($payment_id);
    if ($err) {
        fatal_error("Cannot delete payment: $err");
    }
    admin_log("Payment/subscription record removed", 'members', $oldp['member_id']);
    $t->assign('msg', 'Payment deleted');
    $t->assign('link', "users.php?action=payments&member_id=$oldp[member_id]");
    $t->display('admin/user_saved.html');
}

function display_payments_form(){
    global $member_id;
    global $db, $t,$config;

    if ($_GET['cancel_recurring'] > 0){
        $p = $db->get_payment($_GET['cancel_recurring']);
        if (!$p) die('Cannot find payment to cancel recurring. internal error');
        $p['data']['CANCELLED'] = 1;
        $p['data']['CANCELLED_AT'] = strftime($config['time_format'], time());
        $db->update_payment($p['payment_id'], $p);
        admin_log("Subscription cancelled", 'payments', $p['payment_id']);
    }
    if ($_GET['restart_recurring'] > 0){
        $p = $db->get_payment($_GET['restart_recurring']);
        if (!$p) die('Cannot find payment to restart recurring. internal error');
        $p['data']['CANCELLED'] = 0;
        $p['data']['CANCELLED_AT'] = "";
        $db->update_payment($p['payment_id'], $p);
        admin_log("Subscription restarted", 'payments', $p['payment_id']);
    }

    $products = & $db->get_products_list();
    $pp = array();
    foreach ($products as $p) $pp[ $p['product_id'] ] = $p['title'] ;
    $t->assign('products', $pp);
    $t->assign('member_id', $member_id);
    $member=$db->get_user($member_id);
    $payments = & $db->get_user_payments(intval($member_id));
    foreach ($payments as $k=>$p){
        $payments[$k]['items_count'] = count($p['data'][0]['BASKET_PRODUCTS']);
        /** Following is a dirty hack to show cancel link in admin cp */
        if ($payments[$k]['expire_date'] >= date('Y-m-d')){
            $paysys = get_paysystem($p['paysys_id']);
            $product = $db->get_product($p['product_id']);
            if ($paysys['recurring']
                && ($pay_plugin = &instantiate_plugin('payment', $p['paysys_id']))
                && $product['is_recurring']
                && method_exists($pay_plugin, 'get_cancel_link')){
                $l = $pay_plugin->get_cancel_link($p['payment_id']);
                if (preg_match('|cc.php\?action=cancel_recurring|', $l, $regs)){
                    $u = $_SERVER['PHP_SELF'] . "?member_id=$member_id&action=payments&" .
                    'cancel_recurring=' . $p['payment_id'];
                    $payments[$k]['cancel_url'] = $u;
                }
				if($member['data']['cc'] && $config['enable_resubscribe']){
                    $r = $_SERVER['PHP_SELF'] . "?member_id=$member_id&action=payments&" .
                    'restart_recurring=' . $p['payment_id'];
                    $payments[$k]['restart_url'] = $r;
				}
            }
        }
    }
    $t->assign('payments', $payments);


    $paysystems = get_paysystems_list();
    $pp = array();
    foreach ($paysystems as $p) $pp[ $p['paysys_id'] ] = $p['title'] ;
    $t->assign('paysystems', $pp);

    global $payment_additional_fields;
    $t->assign("u", $db->get_user($member_id));
    $t->assign('payment_additional_fields', $payment_additional_fields);
    $t->display('admin/user_payments.html');
}

function display_confirm($title, $message){
    global $t, $vars;
    $t->assign('title', $title);
    $t->assign('message', $message);
    $t->assign('vars', $vars);
    $t->display('admin/confirm.html');
    exit();
}


function payment_save(){
    $vars = $GLOBALS['vars'];
    global $db, $t;

    set_date_from_smarty('begin_date',  $vars);
    set_date_from_smarty('expire_date',  $vars);

    global $payment_additional_fields;
    foreach ($payment_additional_fields as $f){
        $fname = $f['name'];
        if ($f['validate_func'])
            foreach ((array)$f['validate_func'] as $func){
                if (!function_exists($func))
                    fatal_error("Validation function '$func' for field: '$fname' not defined. Internal error", 0);
                if ($err = $func($vars, $fname))
                    fatal_error("Cannot update payment: $err", 0);
            }
        if (isset($vars[$fname]))
            $vars['data'][$fname] = $vars[$fname];
        unset($vars[$fname]);
    }

    $oldp = $db->get_payment($vars['payment_id']);

    if ($vars['completed'] != $oldp['completed'] &&
        (count((array)$oldp['data'][0]['BASKET_PRODUCTS'])>1)){
        if (!$vars['confirm']){
        display_confirm('This change will affect multiple payments', "This payment
        record is a 'parent' invoice for multiple subscriptions.
        If you want to change status (paid/not-paid) for all
        subscriptions in the batch, click YES, if you want to
        change status only for one product, click NO.
        ");
        } elseif ($vars['confirm'] == 'Yes') { // confirmed
            if ($vars['completed']){
                $vars['completed'] = 0;
                $err = $db->update_payment($vars['payment_id'], $vars);
                $vars['completed'] = 1;
                if ($err) {
                    fatal_error("Cannot update payment info: $err");
                }
                $db->finish_waiting_payment($vars['payment_id'],
                    $oldp['paysys_id'], $vars['receipt_id'], '', $x=array());
                $oldp = $db->get_payment($vars['payment_id']);
                $vars['amount'] = $oldp['amount'];
                $t->assign('link', "users.php?action=payments&member_id=$oldp[member_id]");
                $t->display("admin/user_saved.html");
                return; ///////////////////////////!!!!!!!!!!!!!
            } else {
                foreach ($db->get_user_payments($oldp['member_id'], 1) as $p){
                    if ($p['data'][0]['ORIG_ID'] == $oldp['payment_id']){
                        $db->delete_payment($p['payment_id']);
                    }
                }
                $vars['amount'] = array_sum($oldp['data'][0]['BASKET_PRICES']);
            }
        }
    }

    $err = $db->update_payment($vars['payment_id'], $vars);
    admin_log("Payment/subscription record changed", 'members', $oldp['member_id']);
    if ($err) {
        fatal_error("Cannot update payment info: $err");
    }
    $t->assign('link', "users.php?action=payments&member_id=$oldp[member_id]");
    $t->display("admin/user_saved.html");
}

function payment_add(){
    $vars = $GLOBALS['vars'];
    global $db, $t;
    global $payment_additional_fields,$config;
    foreach ($payment_additional_fields as $f){
        $fname = $f['name'];
        if ($f['validate_func'])
            foreach ((array)$f['validate_func'] as $func){
                if (!function_exists($func))
                    fatal_error("Validation function '$func' for field: '$fname' not defined. Internal error", 0);
                if ($err = $func($vars, $fname))
                    fatal_error("Cannot update payment: $err", 0);
            }
        $vars['data'][$fname] = $vars[$fname];
        unset($vars[$fname]);
    }

    set_date_from_smarty('begin_date',  $vars);
    set_date_from_smarty('expire_date',  $vars);
    if($config['use_tax']&&!$vars['incl_tax']){
        $vars['amount'] = $vars['amount'] + $vars['tax_amount'];
    }
    $err = $db->add_payment($vars);
    if ($err) {
        fatal_error("Cannot add payment: $err");
    }
    admin_log("Payment/subscription record added", 'members', $vars['member_id']);
    $t->assign('link', "users.php?member_id=$vars[member_id]&action=payments");
    $t->assign('member_id', $vars['member_id']);
    $t->display("admin/user_saved.html");
}

function display_actions(){
    global $t, $config, $db, $member_id;
    $vars = get_input_vars();
    $t->assign('admin_email_from', $config['admin_email_from'] ? $config['admin_email_from'] : $config['admin_email']);
    $t->assign('admin_email_name', $config['admin_email_name'] ? $config['admin_email_name'] : $config['site_title']);
    $t->assign('u', $db->get_user($vars['member_id']));
    $t->assign('member_id', $member_id);
    $t->display("admin/user_actions.html");
}

function move_user(){
    global $t, $db, $vars;
    $new_members = $db->users_find_by_string($vars['new_login'], 'login', 1);
    $new_member_id = $new_members[0]['member_id'];
    if (!$new_member_id)
        fatal_error("Cannot find user for move to: username '$vars[new_login]'");
    //// find payments for old user
    $op = $db->get_user_payments($vars['member_id'], 1);
    $np = $db->get_user_payments($new_member_id, 1);
    /// find last dates for each product in new account
    $dates = array();
    foreach ($np as $p){
        if ($dates[ $p['product_id'] ] < $p['expire_date'])
            $dates[ $p['product_id'] ] = $p['expire_date'];
    }
    /// move not-existing subscriptions without changes
    foreach ($op as $k => $p){
        $p['member_id'] = $new_member_id;
        $db->update_payment($p['payment_id'], $p);
        unset($op[$k]);
    }
    $db->delete_user($vars['member_id']);
    /*
    /// convert existing subscriptions into number of days
    /// which not used
    foreach ($op as $p){
        $b = sql_to_timestamp($p['begin_date']);
        $e = sql_to_timestamp($p['expire_date']);
        $t = sql_to_timestamp(date('Y-m-d'));
        $days_total = $e - max($b, $t);

    }
    */
    admin_log("User record moved (from '{$new_members[0][login]}' to '$vars[new_login]')", 'members', $vars['member_id']);
    admin_log("User record moved (from '{$new_members[0][login]}' to '$vars[new_login]')", 'members', $new_member_id);
    $t->assign('link', "users.php?member_id=$new_member_id&action=edit");
    $t->assign('member_id', $new_member_id);
    $t->display("admin/user_saved.html");
}

function member_send_signup_email(){
    global $t, $db, $vars;
    mail_signup_user($vars['member_id']);
    $t->assign('title', 'E-Mail sent');
    $t->assign('msg', 'Signup E-Mail has been sent');
    $t->assign('link', "users.php?member_id=$vars[member_id]&action=actions");
    $t->assign('member_id', $member_id);
    $t->display("admin/user_saved.html");
}

function member_send_verification_email(){
    global $t, $db, $config, $vars;
    $payments = $db->get_user_payments($vars['member_id']);

    //Get first payment
    end($payments);
    $payment  = current($payments);
    
    $payment_id = $payment['payment_id'];
    $code       = $payment['data']['email_confirm']['code'];
    $u          = $db->get_user( $vars['member_id'] );
    
    if ( $payment['completed'] ) {
        $t->assign('title', 'Payment is already completed');
        $t->assign('msg', 'Payment is already completed');
    } elseif (!$payment_id || !$code) {
        $t->assign('title', 'Can not send Verification E-Mail');
        $t->assign('msg', 'Can not send Verification E-Mail');
    } else {
        mail_verification_email($u, $config['root_url'] . "/signup.php?cs=" . $payment_id . "-" . $code);
        
        $t->assign('title', 'E-Mail sent');
        $t->assign('msg', 'Verification E-Mail has been sent');
    }
    
    $t->assign('link', "users.php?member_id=$vars[member_id]&action=actions");
    $t->assign('member_id', $member_id);
    $t->display("admin/user_saved.html");
}

function email_to_user_from_admin(){
    global $db, $config, $t, $_AMEMBER_TEMPLATE;
    check_demo();
    $vars = get_input_vars();
    $u = $db->get_user($vars['member_id']);
    $tmp = & new_smarty();
    $tmp->assign('user', $u);

    $_AMEMBER_TEMPLATE['text'] = $vars['text'];
    $vars['text'] = $tmp->fetch('memory:text');
    
    $_AMEMBER_TEMPLATE['text'] = $vars['subject'];
    $vars['subject'] = $tmp->fetch('memory:text');
    
    mail_customer($u['email'], $vars['text'], $vars['subject'], 0, '', 0, $u['name_f'] . ' ' . $u['name_l']);

    $t->assign('member_id', $vars['member_id']);
    $t->assign('msg', "EMail Sent to customer");
    $t->assign('link', "users.php?action=actions&member_id=$vars[member_id]");
    $t->display("admin/user_saved.html");
    
}

function get_commissions_by_user($aff_id, $member_id){
    global $db;
    $q = $db->query($s = "select sum(if(record_type='credit', 1, -1)) as commissions_count , sum(IF(record_type='credit', c.amount, -c.amount)) as commissions_summa
                        from {$db->config['prefix']}aff_commission c left join {$db->config['prefix']}payments p on c.payment_id = p.payment_id
                        where c.aff_id = '$aff_id' and p.member_id = '$member_id'");
    return mysql_fetch_assoc($q);

}
function display_referred_users(){
    global $db,$t,$member_id;
    global $all_count, $count, $start;
    $t->assign("u", $db->get_user($member_id));

    $all_count = $db->get_users_list_c("%", -1, $member_id);
    $ul = & $db->get_users_list("%", -1, $start, $count, $member_id);
    foreach($ul as $k=>$v){
        $r = get_commissions_by_user($member_id, $v['member_id']);
        $ul[$k]['commissions_count'] = $r['commissions_count'];
        $ul[$k]['commissions_summa'] = $r['commissions_summa'];
    }
    $t->assign('ul', $ul);
    $t->display("admin/user_referred_users.html");
}


function login_as_user(){
    global $vars,$db,$config;
    if(!$vars['member_id']) fatal_error("user ID is not set!");
    $user = $db->get_user(intval($vars['member_id']));
    $_SESSION['_amember_login'] = $user['login'];
    $_SESSION['_amember_pass']  =    $user['pass'];
    html_redirect($config['root_url']."/member.php", "Redirect to member's area");
}
////////////////////////////////////////////////////////////////////////////
//
//                      M A I N
//
////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
if ($vars['member_id']) $t->assign('member_id', $vars['member_id']);
admin_check_permissions('browse_users');
switch (@$vars['action']){
    case 'access_log':
        display_access_log();
        break;
    case 'aff_clicks':
        display_aff_clicks();
        break;
    case 'aff_sales':
        display_aff_sales();
        break;
    case 'add_form':
        admin_check_permissions('add_users');
        display_add_form();
        break;
    case 'add_save':
        admin_check_permissions('add_users');
        add_save();
        break;
    case 'search_form':
        display_search_form();
        break;
    case 'search_by_string':
        search_by_string();
        break;
    case 'search_by_product':
        search_by_product();
        break;
    case 'search_by_date':
        search_by_date();
        break;
    case 'search_locked':
        search_locked();
        break;
    case 'edit':
        display_edit_form();
        break;
    case 'delete':
        admin_check_permissions('delete_users');
        delete_user();
        break;
    case 'edit_save':
        admin_check_permissions('edit_users');
        edit_save();
        break;
    case 'payments':
        display_payments_form();
        break;
    case 'edit_payment':
        admin_check_permissions('manage_payments');
        edit_payment();
        break;
    case 'del_payment':
        admin_check_permissions('manage_payments');
        del_payment();
        break;
    case 'payment_save':
        admin_check_permissions('manage_payments');
        payment_save();
        break;
    case 'payment_add':
        admin_check_permissions('manage_payments');
        payment_add();
        break;
    case 'actions':
        display_actions();
        break;
    case 'move':
        admin_check_permissions('edit_users');
        move_user();
        break;
    case 'email':
        email_to_user_from_admin();
        break;
    case 'send_signup_email':
        check_demo();
        member_send_signup_email();
        break;
    case 'send_verification_email':
        check_demo();
        member_send_verification_email();
        break;
    case 'not_approved':
        display_not_approved_user_list();
        break;
    case 'approve':
        approve();
        break;
    case 'browse': case '':
        display_user_list();
        break;
    case 'referred_users' :
        display_referred_users();
        break;
    case 'login_as_user'    :
        login_as_user();
        break;
    default:
        fatal_error("Unknown action: '$action' for users.php");
}
?>
