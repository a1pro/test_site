<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Members handling functions
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5466 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/
global $member_fields;
$member_fields = array(
    array(
        'name'         => 'member_id',
        'title'        => 'Member #',
        'type'         => 'hidden'
    ),
    array(
        'name'         => 'login',
        'title'        => 'Login',
        'type'         => 'text'
    ),
    array(
        'name'         => 'pass',
        'title'        => 'Password',
        'type'         => 'text'
    ),
    array(
        'name'         => 'email',
        'title'        => 'EMail',
        'type'         => 'text'
    ),
    array(
        'name'         => 'name_f',
        'title'        => 'First Name',
        'type'         => 'text'
    ),
    array(
        'name'         => 'name_l',
        'title'        => 'Last Name',
        'type'         => 'text'
    ),
    array(
        'name'         => 'street',
        'title'        => 'Street',
        'type'         => 'text'
    ),
    array(
        'name'         => 'city',
        'title'        => 'City',
        'type'         => 'text'
    ),
    array(
        'name'         => 'state',
        'title'        => 'State',
        'type'         => 'text'
    ),
    array(
        'name'         => 'zip',
        'title'        => 'ZIP',
        'type'         => 'text'
    ),
    array(
        'name'         => 'country',
        'title'        => 'Country',
        'type'         => 'text'
    ),
    array(
        'name'         => 'is_male',
        'title'        => 'Gender (female:0/male:1)',
        'type'         => 'radio'
    ),
    array(
        'name'         => 'aff_id',
        'title'        => 'Affiliate #',
        'type'         => 'readonly'
    )
);

global $member_additional_fields;
$member_additional_fields = array(
);

function add_member_field($name, $title,
                            $type, $description='', $validate_func='',
                            $additional_fields=NULL){
    settype($additional_fields, 'array');
    global $member_additional_fields;
    foreach ($member_additional_fields as $k=>$v){
        if ($v['name'] == $name) {
            if ($v['validate_func'] &&
                ($v['validate_func'] != $validate_func)){
                $member_additional_fields[$k]['validate_func'] =
                        (array)$v['validate_func'];
                $member_additional_fields[$k]['validate_func'][] =
                    $validate_func;
            }
            return;
        }
    }
    $member_additional_fields[] = array_merge(
        $additional_fields,
        array(
            'name'          => $name,
            'title'         => $title,
            'type'          => $type,
            'description'   => $description,
            'validate_func' => $validate_func
        )
    );
}

function mail_expire_members(){
    // send mail to members having
    // expiration_date = today() + $config['expire_mail_days']
    global $config, $db;
    $t = & new_smarty();

    $pl = array();
    $et = & new aMemberEmailTemplate();
    $et->name = "mail_expire";
    $global_days = $et->find_days();
    foreach ($db->get_products_list() as $k=>$pr){
        if (($pr['dont_mail_expire'] != 2) && !$config['mail_expire'])
            continue;
        if ($pr['dont_mail_expire'] == 1)
            continue;
        $et = & new aMemberEmailTemplate();
        $et->name = "mail_expire";
        $et->product_id = $pr['product_id'];
        $days = $et->find_days();
        if (($pr['dont_mail_expire'] == 2) && !$config['mail_expire'] && !$days)
            continue;
        $pr['mail_expire_days'] = $days ? $days : $global_days;
        $pr['mail_expire_days_global'] = $days ? false : true;
        if (!$pr['mail_expire_days']) continue;
        $pl[$k] = $pr;
    }

    // iterate on products
    $msent = array();
    foreach ($pl as $pr){
        //iterate on expiration days
        foreach ($pr['mail_expire_days'] as $days){
            $dat = date('Y-m-d', time() + 3600 * 24 * $days);
            $plist = $db->get_expired_payments($dat, $dat, null, ($pr['dont_mail_expire'] ? true : false) , $pr['product_id']);
            //print_rr($plist, "$dat,$pr[product_id]");
            foreach ($plist as $p){ // go through expired payments and send mail
                if ($msent[$p['member_id'] ]) continue; //dont send second mail !
                $paysys = get_paysystem($p['paysys_id']);
                if ($paysys['recurring'] && $pr['is_recurring']) continue; // don't send if auto-recurring
                $u = $db->get_user($p['member_id']);
                $product = get_product($p['product_id']);
                ///////////////////////////////////
                $t->assign('login',  $u['login']);
                $t->assign('pass',   $u['pass']);
                $t->assign('name_f', $u['name_f']);
                $t->assign('name_l', $u['name_l']);
                ///////////////////////////////////
                $t->assign('user',   $u);
                $t->assign('payment',$p);
                $t->assign('product',$product->config);
                ///////////////////////////////////
                $et = & new aMemberEmailTemplate();
                $et->name = "mail_expire";
                $et->day = $days;
                if (!$pr['mail_expire_days_global'])
                    $et->product_id = $pr['product_id'];
                //print_rr($et, $u['email']);
                mail_template_user($t, $et, $u, false);
                $msent[$p['member_id'] ]++;
            }

        }
    }
}


function mail_not_completed_members(){
    // send mail to all members having no active subscriptions
    // and pending payment completed before 'max_not_completed_days' days
    global $config, $db;

    $pl = array();
    $et = & new aMemberEmailTemplate();
    $et->name = "mail_not_completed";
    $global_days = $et->find_days();
    foreach ($db->get_products_list() as $k=>$pr){
        if (!$config['mail_not_completed'])
            continue;
        $et = & new aMemberEmailTemplate();
        $et->name = "mail_not_completed";
        $et->product_id = $pr['product_id'];
        $days = $et->find_days();
        if (!$config['mail_not_completed'] && !$days)
            continue;
        $pr['mail_not_completed_days'] = $days ? $days : $global_days;
        $pr['mail_not_completed_days_global'] = $days ? false : true;
        if (!$pr['mail_not_completed_days']) continue;
        $pl[$k] = $pr;
    }

    $t = & new_smarty();
    $msent = array();
    foreach ($pl as $pr){
        //iterate on not_completed days
        foreach ($pr['mail_not_completed_days'] as $days){
            $dat = date('Y-m-d', time() - 3600 * 24 * $days);
	    $plist = $db->get_payments($dat, $dat, -1,0,-1,'',$pr['product_id']);
            foreach ($plist as $p){ // go through expired payments and send mail
                if ($msent[$p['member_id'] ]) continue; //dont send second mail !
                // check for customers with the same email and last name
                $u = $db->get_user($p['member_id']);
                if ($u['unsubscribed']) continue;

                $email = $db->escape($u['email']);
                $name_l = $db->escape($u['name_l']);
                $name_f = $db->escape($u['name_f']);
                $q = $db->query($s = "SELECT SUM(p.completed)
                    FROM {$db->config[prefix]}members m LEFT JOIN
                        {$db->config[prefix]}payments p USING (member_id)
                    WHERE (m.name_l = '$name_l' AND m.name_f = '$name_f') OR m.email = '$email'
                        OR m.member_id = {$p[member_id]}
                    ");
                list($c_count) = mysql_fetch_row($q);
                if ($c_count) continue;

                $product = get_product($p['product_id']);
                ///////////////////////////////////
                $t->assign('login',  $u['login']);
                $t->assign('pass',   $u['pass']);
                $t->assign('name_f', $u['name_f']);
                $t->assign('name_l', $u['name_l']);
                ///////////////////////////////////
                $t->assign('user',   $u);
                $t->assign('payment',$p);
                $t->assign('product',$product->config);
                ///////////////////////////////////
                $et = & new aMemberEmailTemplate();
                $et->name = "mail_not_completed";
                $et->day = $days;
                if (!$pr['mail_not_completed_days_global'])
                    $et->product_id = $pr['product_id'];
                mail_template_user($t, $et, $u, true);
                $msent[$p['member_id'] ]++;
            }
        }
    }
}

function check_expire_members(){
    // check that internal hooks for subscription_delete
    // was called for expired members (for the last week)
    global $config, $db, $t;
    $dat1 = date('Y-m-d', time() - 3600 * 24 * 7);
    $dat2 = date('Y-m-d');
    $plist = $db->get_expired_payments($dat1, $dat2);
    $msent = array();
    foreach ($plist as $p){ // go through expired payments and send mail
        if ($msent[$p['member_id'] ]) continue; //dont send second mail !
        $db->check_subscriptions($p['member_id']);
        $msent[$p['member_id'] ]++;
    }
}

////////////////// ACCESS CHECKER CODE /////////////////////////////////
add_member_field(
    'is_locked', 'Locked',
    'select', 'auto-locking by IP',
    '',
    array('options' => array(
        '' => 'No',
        1  => 'Yes',
        -1 => "Disable auto-lock for this user"
    ))
);
global $config;
if ($config['manually_approve']){
    add_member_field(
        'is_approved', 'Approved',
        'select', 'is member manually approved?',
        '',
        array('options' => array(
            '' => 'No',
            1  => 'Yes',
        ))
    );
}
add_member_field(
    'i_agree', 'Agreed With User Agreement',
    'hidden', '',
    '',
    array('hidden_anywhere' => 1));
add_member_field(
    'signup_email_sent', 'Signup Email Sent',
    'hidden', '',
    '',
    array('hidden_anywhere' => 1));
add_member_field(
    'approval_email_sent', 'Approval Email Sent',
    'hidden', '',
    '',
    array('hidden_anywhere' => 1));
add_member_field(
    'selected_lang', 'Selected Language',
    'hidden', '',
    '',
    array('hidden_anywhere' => 1));

if($config['verify_email_profile']){

    add_member_field(
        'email_new', 'New Email address(not verified)',
        'hidden', '',
        '',
        array('hidden_anywhere' => 1));

    add_member_field(
        'email_confirm_code', 'Email verification confirmation code',
        'hidden', '',
        '',
        array('hidden_anywhere' => 1));
    add_member_field(
        'email_confirm_code_exp', 'Email verification confirmation code expiration time',
        'hidden', '',
        '',
        array('hidden_anywhere' => 1));
    
}

// running from cron
function clear_access_log(){
    global $db, $config;
    if (!$config['clear_access_log']) return;
    $dat = date('Y-m-d', time() - $config['clear_access_log_days'] * 3600 * 24);
    $db->clear_access_log($dat);
}
// running from cron
function delete_old_newsletters(){
    global $db, $config;
    $db->delete_old_newsletters();
}
//

function member_lock_by_ip($member_id){
    global $db, $config;
    $m = $db->get_user($member_id);
    if ($m['data']['is_locked'] < 0) return; // auto-lock disabled
    if ($config['max_ip_actions'] != 1){ // email admin
        $t = & new_smarty();
        $t->assign("user", $m);
        $et = & new aMemberEmailTemplate();
        $et->name = "max_ip_actions";
        mail_template_admin($t, $et);
    }
    if ($config['max_ip_actions'] != 2){ // disable customer
        $m['data']['is_locked'] = 1;
        $db->update_user($member_id, $m);
    }
}

function member_check_ip_ban(){ //return 1 if should be banned
    global $config;
    foreach (preg_split('/[\r\n]+/', $config['ban']['ip']) as $i)
        if (compare_with_pattern($i, $_SERVER['REMOTE_ADDR']))
            return 1;
}

function member_check_email_ban($email){ //return 1 if should be banned
    global $config;
    foreach (preg_split('/[\r\n]+/', $config['ban']['email']) as $i)
        if (compare_with_pattern($i, $email))
            return 1;
}

function member_check_login_ban($login){ //return 1 if should be banned
    global $config;
    foreach (preg_split('/[\r\n]+/', $config['ban']['login']) as $i)
        if (compare_with_pattern($i, $login))
            return 1;
}

function member_check_ban($vars){
    global $config, $db;
    $err = array();
    if (member_check_ip_ban())
        if ($config['ban']['ip_action'] == 'die'){
            $db->log_error("Attempt to signup from denied IP: $_SERVER[REMOTE_ADDR]");
            header("Status: 500 Internal Error");
            header("Status: 500 Internal Error");
            exit();
        } else {
            $db->log_error("Attempt to signup from denied IP: $_SERVER[REMOTE_ADDR]");
            $err[] = "Signup from this IP address denied";
        }
    if ($vars['email'] && member_check_email_ban($vars['email']))
        if ($config['ban']['email_action'] == 'die'){
            $db->log_error("Attempt to signup from denied E-Mail: $vars[email]");
            header("HTTP/1.0 500 Internal Error");
            header("Status: 500 Internal Error");
            exit();
        } else {
            $db->log_error("Attempt to signup from denied E-Mail: $vars[email]");
            $err[] = "Signup from this E-Mail address is not allowed";
        }
    if ($vars['login'] && member_check_login_ban($vars['login']))
        if ($config['ban']['login_action'] == 'die'){
            header("HTTP/1.0 500 Internal Error");
            header("Status: 500 Internal Error");
            exit();
        } else {
            $err[] = "Username is already taken. Please choose another username";
        }

    return $err;
}

function member_send_autoresponders(){
    global $db, $config, $t;

    $pl = $db->get_products_list();
    // setup product responders
    foreach ($pl as $k => $p){
        $et = & new aMemberEmailTemplate();
        $et->name = "mail_autoresponder";
        $et->product_id = $p['product_id'];
        $pl[$k]['autoresponder'] = $et->find_days();
    }
    // set global responder
    if ($config['mail_autoresponder']){
        $et = & new aMemberEmailTemplate();
        $et->name = "mail_autoresponder";
        $days = $et->find_days();
        if ($days){
            $pl[] = array(
                'product_id' => -1,
                'autoresponder' => $days,
                'autoresponder_renew' => $config['autoresponder_renew'],
            );
        }
    }

    foreach ($pl as $pr){
        $t = &new_smarty();
        if (!$pr['autoresponder']) continue;
//        if (!preg_match_all('/^\s*(\d+)\s*\-\s*(.+?)\s*$/m', $pr['autoresponder'], $regs))
            //continue;
        if ($pr['product_id'] > 0)
            $product_where = "AND p.product_id=$pr[product_id]";
        else
            $product_where = "";
        foreach ($pr['autoresponder'] as $days){
            $dat = date('Y-m-d', time()-$days*3600*24);
            $today = date('Y-m-d');
            if ($pr['autoresponder_renew'])
                $q = $db->query($s = "SELECT m.*
                FROM {$db->config['prefix']}payments p
                LEFT JOIN {$db->config['prefix']}members m USING (member_id)
                WHERE p.begin_date='$dat' AND p.completed>0
                    $product_where
                GROUP BY m.member_id ");
            else
                $q = $db->query($s = "SELECT m.*
                FROM {$db->config['prefix']}members m
                LEFT JOIN {$db->config['prefix']}payments p USING (member_id)
                WHERE p.completed > 0
                AND p.begin_date <= '$today' $product_where
                GROUP BY m.member_id
                HAVING
                SUM(to_days(if(p.expire_date>'$today', '$today', p.expire_date)) - to_days(p.begin_date)) = $days
                AND
                MAX(p.expire_date) >= '$today'
                ");
            $et = & new aMemberEmailTemplate();
            $et->name = "mail_autoresponder";
            $et->product_id = ($pr['product_id'] > 0) ? $pr['product_id'] : null;
            $et->day = $days;
            while ($u = mysql_fetch_assoc($q)){
                $u['data'] = $db->decode_data($u['data']);
                $t->assign('user', $u);
                $t->assign('product', $pr);
                $t->assign('login',  $u['login']);
                $t->assign('pass',   $u['pass']);
                $t->assign('name_f', $u['name_f']);
                $t->assign('name_l', $u['name_l']);
                if ($u['unsubscribed']) continue;
                mail_template_user($t, $et, $u, true);
            }
        }
    }
}

function keep_only_zeroes($i){
    return $i == '0';
}

function member_send_zero_autoresponder($payment_id, $member_id=0){
    global $db, $config;

    $p = $db->get_payment($payment_id);
    $member_id = $p['member_id'];
    $pr = $db->get_product($p['product_id']);

    $t = new_smarty();


    $et = & new aMemberEmailTemplate();
    $et->name = "mail_autoresponder";
    $et->product_id = $pr['product_id'];
    $pl[0] = $pr;
    $pl[0]['autoresponder'] = array_filter($et->find_days(), 'keep_only_zeroes');

    // set global responder
    if ($config['mail_autoresponder']){
        $et = & new aMemberEmailTemplate();
        $et->name = "mail_autoresponder";
        $days = $et->find_days();
        if ($days){
            $pl[] = array(
                'product_id' => -1,
                'autoresponder' => array_filter($days, 'keep_only_zeroes'),
                'autoresponder_renew' => $config['autoresponder_renew'],
            );
        }
    }

    foreach ($pl as $pr){
        $t = &new_smarty();
        if (!$pr['autoresponder']) continue;
//        if (!preg_match_all('/^\s*(\d+)\s*\-\s*(.+?)\s*$/m', $pr['autoresponder'], $regs))
            //continue;
        if ($pr['product_id'] > 0)
            $product_where = "AND p.product_id=$pr[product_id]";
        else
            $product_where = "";
        foreach ($pr['autoresponder'] as $days){
            $dat = date('Y-m-d', time()-$days*3600*24);
            $today = date('Y-m-d');
            if ($pr['autoresponder_renew'])
                $q = $db->query($s = "SELECT m.*
                FROM {$db->config['prefix']}payments p
                LEFT JOIN {$db->config['prefix']}members m USING (member_id)
                WHERE m.member_id = '$member_id' and p.begin_date='$dat' AND p.completed>0
                    $product_where
                GROUP BY m.member_id ");
            else
                $q = $db->query($s = "SELECT m.*
                FROM {$db->config['prefix']}members m
                LEFT JOIN {$db->config['prefix']}payments p USING (member_id)
                WHERE m.member_id = '$member_id' and p.completed > 0
                AND p.begin_date <= '$today' $product_where
                GROUP BY m.member_id
                HAVING
                SUM(to_days(if(p.expire_date>'$today', '$today', p.expire_date)) - to_days(p.begin_date)) = $days
                AND
                MAX(p.expire_date) >= '$today'
                ");
            $et = & new aMemberEmailTemplate();
            $et->name = "mail_autoresponder";
            $et->product_id = ($pr['product_id'] > 0) ? $pr['product_id'] : null;
            $et->day = $days;
            while ($u = mysql_fetch_assoc($q)){
                $u['data'] = $db->decode_data($u['data']);
                $t->assign('user', $u);
                $t->assign('product', $pr);
                $t->assign('login',  $u['login']);
                $t->assign('pass',   $u['pass']);
                $t->assign('name_f', $u['name_f']);
                $t->assign('name_l', $u['name_l']);
                if ($u['unsubscribed']) continue;
                mail_template_user($t, $et, $u, true);
            }
        }
    }
}

function is_field_can_be_changed($field_name, $is_signup) {
    $fields_to_change = (array)$GLOBALS['config']['profile_fields'];
    return ($is_signup || in_array($field_name, $fields_to_change));
}

function vsf_address($vars, $is_signup = true){
    $err = array();

    if (is_field_can_be_changed('street', $is_signup) && $vars['street'] == '')
        $err[] = _SIGNUP_ENTER_STREET;
    if (is_field_can_be_changed('city', $is_signup) && $vars['city'] == '')
        $err[] = _SIGNUP_ENTER_CITY;
    if (is_field_can_be_changed('state', $is_signup) && ($vars['country'] == 'US' || $vars['country'] == 'CA') && ($vars['state'] == ''))
        $err[] = _SIGNUP_ENTER_STATE;
    if (is_field_can_be_changed('zip', $is_signup) && $vars['zip'] == '')
        $err[] = _SIGNUP_ENTER_ZIP;
    if (is_field_can_be_changed('country', $is_signup) && $vars['country'] == '')
        $err[] = _SIGNUP_ENTER_COUNTRY;
    return $err;
}

function get_additional_field_html($f, $val){
    global $t, $config;
    $t->assign('f', $f);
    $t->assign('value', "");
    $t->assign('value', $val);
    return $t->fetch('add_field.inc.html');
}

function get_additional_field_html_readonly($f, $val){
    global $t, $config;
    $t->assign('f', $f);
    if ($f['type'] == 'select'){
        $val = @$f['options'][$val];
    } elseif ($f['type'] == 'radio'){
        $val = $f['options'][$val];
    } elseif ($f['type'] == 'multi_select'){
        $s = array();
        foreach ((array)$val as $v)
            $s[] = $f['options'][$v];
        $val = join(',', $s);
    } elseif ($f['type'] == 'checkbox'){
        $s = array();
        foreach ((array)$val as $v)
            $s[] = $f['options'][$v];
        $val = join(',', $s);
    }
    $t->assign('value', $val);
    return $t->fetch('add_field_ro.inc.html');
}

/*
 * @param mixed (null||array) $price_group
 * @param array $f additional filed decription
 * @return boolean
 *
 */
function is_additional_fields_avalable($price_group, $f){
    if (is_null($price_group) || !$f['price_group']) {
        return true;
    } else {
        return array_intersect($price_group, $f['price_group']);
    }
}

function get_additional_fields_html($vars, $scope, $display_all=0, $price_group=null){
    global $db, $config, $member_additional_fields;
    $ret = "";
    if (!isset($vars['data'])){
        // hack for signup form
        $tmp['data'] = $vars;
        $vars = $tmp;
        //$vars['data'] = $vars; // avoid *RECURSION* message in PHP 5
    }
    foreach ($member_additional_fields as $f){
        $val = $f['sql'] ?
            (isset($vars[$f['name']])?$vars[$f['name']]: $f['default']) :
            (isset($vars['data'][$f['name']])?$vars['data'][$f['name']]:$f['default']);
        //------    
        if (isset($f[require_value])) {
            $val=(array)$val;
            foreach ($f[require_value] as $rv) {
                $val[] = $rv['sql'] ?
            (isset($vars[$rv['name']])?$vars[$rv['name']]: $rv['default']) :
            (isset($vars['data'][$rv['name']])?$vars['data'][$rv['name']]:$rv['default']);
            }
        }
        //------
        if ($f['type'] == 'hidden') continue;
        if (!is_additional_fields_avalable($price_group, $f)) continue;
        
        if (($f['display_'.$scope] == 1) || ($display_all == 1)){
            $ret .= get_additional_field_html($f, $val);
        } elseif (($f['display_'.$scope] == -1) || ($display_all == -1)){
            $ret .= get_additional_field_html_readonly($f, $val);
        }
    }
    return $ret;
}

function member_check_additional_fields(&$vars, $scope='signup', $price_group=null){
    global $member_additional_fields;
    $error = array();

    // Get price group from request for signup form
    if ($scope=='signup' && isset($vars['price_group'])) {
        $price_group = explode(',', $vars['price_group']);
    }
    
    foreach ($member_additional_fields as $f){
        if (!is_additional_fields_avalable($price_group, $f)) continue;
        if (!$f['display_'.$scope]) continue;
        
        $v = $vars[$fn = $f['name']];
        foreach ((array)$f['validate_func'] as $func){
            if (!strlen($func)) continue;
            if ($err = $func($v, $f['title'], $f)){
                $error[] = $err;
            }
        }
    }
    return $error;
}

function vf_integer($val, $field_title, $f){
    if (!preg_match('/^\d+$/', $val))
        return sprintf(_MEMBER_VF_INTEGER, $field_title);
}
function vf_number($val, $field_title, $f){
    if (!is_numeric($val))
        return sprintf(_MEMBER_VF_NUMERIC, $field_title);
}
function vf_require($val, $field_title, $f){
    if (!strlen($val))
        return sprintf(_MEMBER_VF_REQUIRE, $field_title);
}
function vf_email($val, $field_title, $f){
    if (!check_email($val))
        return sprintf(_MEMBER_VF_EMAIL, $field_title);
}
function vf_regex($val, $field_title, $f){
    if (!preg_match($f['re'], $val))
        return $f['re_msg'];
}


function move_guest_subscriptions ($member_id){
    global $db, $config;
    settype($member_id, 'integer');
    if ($member_id <= 0)
        return;

    $u = $db->get_user($member_id);
    if (count($u) < 1)
        return;

    if (!$u['unsubscribed']){

        $g = $db->get_guest_by_email($u['email']);
        if (count($g) > 0 && $g['guest_id'] > 0){

            $guest_id = $g['guest_id'];
            $threads = $db->get_guest_threads($guest_id);
            $threads = array_keys($threads);
            if (count($threads) > 0){
                $db->add_member_threads($member_id, $threads);
            }

        }

    }
}

function remove_newsletter_guest ($payment_id) {
    global $db;
    $payment = $db->get_payment($payment_id); // $payment is now array

    if (count($payment) < 1)
        return;

    $member = $db->get_user($payment['member_id']);

    if (count($member) < 1)
        return;

    move_guest_subscriptions ($payment['member_id']);

    $g = $db->get_guest_by_email($member['email']);
    if (count($g) > 0 && $g['guest_id'] > 0) {
        $guest_id = $g['guest_id'];

        $threads = $db->get_guest_threads($guest_id);
        $threads = array_keys($threads);
        if (count($threads) > 0) {
            $db->delete_guest_threads($guest_id);
        }

        $db->delete_guest($guest_id);
    }

}

/**
 * Function returns effective tax value for customer
 * or if member_id is null, it checks only for global tax
 * enabled and returns value
 * You have to check if $product['use_tax'] is enabled before you add tax!
 * @param int Member ID
 * @return float Returns tax value as number in percents, like 17.5, or 20 
 */
function get_member_tax($member_id=null){
    global $config, $db;
    if (!$config['use_tax']) return;
    if ($config['tax_type'] == 1){ // global tax
        return floatval($config['tax_value']);
    } elseif ($config['tax_type'] == 2) { //regional tax
		if (!$member_id) return ;
		$member = $db->get_user($member_id);
		if (!$member) return ;
	    foreach ((array)$config['regional_taxes'] as $t){
	        if($t['zip']&&($t['country'] == $member['country'])){
                    // First get all values; 
                    $zips = split(";", $t['zip']);
                    foreach($zips as $v){
                        $v = trim($v);
                        if(preg_match("/(\d+)\-(\d+)/", $v, $regs) && ($member['zip'] >= $regs[1] && $member['zip']<=$regs[2])){
                            return floatval( $t['tax_value'] );
                        }
                        if($member['zip'] == $v)
                            return floatval( $t['tax_value'] );

                    }
                    
                }
                if ($t['state'] && ($t['state'] == $member['state']) && ($t['country'] == $member['country']))
	            return floatval( $t['tax_value'] );
	        if (!$t['state'] && $t['country'] && ($t['country'] == $member['country']))
	            return floatval( $t['tax_value'] );
	    }
    }
}