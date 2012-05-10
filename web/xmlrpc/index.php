<?php
//list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
require_once('../config.inc.php');

if ($config['use_xmlrpc'] != '1') {
    die("Error #8001 - XML-RPC is not enabled at aMember CP->Setup->Advanced");
    exit(); // check in config is XML-RPC Library enable
}
if ($config['xmlrpc_login'] == '' && $config['xmlrpc_password'] == '') {
    die("Error #8002 - XML-RPC username or password is not set at aMember CP->Setup->Advanced");
    exit(); // check in config is XML-RPC Library enable
}

if (!isset($_SERVER['PHP_AUTH_USER'])) { // HTTP Authentication
    header('WWW-Authenticate: Basic realm="aMember XML-RPC"');
    header('HTTP/1.0 401 Unauthorized');
    print "Error #8003 - XML-RPC username is not entered";
    exit();
}

// checking name and password
if( ($_SERVER['PHP_AUTH_USER'] != $config['xmlrpc_login'])  
 || ($_SERVER['PHP_AUTH_PW']  != $config['xmlrpc_password'])){
    header('WWW-Authenticate: Basic realm="aMember XML-RPC"');
    header('HTTP/1.0 401 Unauthorized');
    print "Error #8004 - Incorrect XML-RPC username or password entered";
    exit();
}

require_once('./ixr.inc.php');

/*  
 **  Fix for PHP 5.2.2 bug that breaks xmlrpc
 **  See http://bugs.php.net/bug.php?id=41293
 */
if (@phpversion()=="5.2.2") $GLOBALS['HTTP_RAW_POST_DATA'] = @file_get_contents("php://input");

function xmlrpc_ping($args){
    return $args[0];
}

function _xmlrpc_query($args){
    global $db,$config;
    $s = $args;
    if (is_array($s)) $s = $s[0];
    $s = str_replace("#__", $config['db']['mysql']['db'] . '.' . $config['db']['mysql']['prefix'], $s);
    if ($s == ''){
        return new IXR_Error(100, "Empty sql passed to db.query function");
    } else {
        ob_start();
        $q = $db->query($s, 1);
        ob_end_clean();
        if (mysql_errno()){
            return new IXR_Error(101, "SQL Error: " . mysql_error());
        }
        return $q;
    }
}
function xmlrpc_query_all($args){
    $q = _xmlrpc_query($args);
    if (is_a($q, 'IXR_Error'))
        return $q;
    $ret = array();
    while ($a = mysql_fetch_assoc($q))
        $ret[] = $a;
    return $ret;
}
function xmlrpc_query($args){
    return _xmlrpc_query($args);
}
function xmlrpc_query_one($args){
    $q = _xmlrpc_query($args);
    if (is_a($q, 'IXR_Error'))
        return $q;
    $a = mysql_fetch_row($q);
    return $a[0];
}
function xmlrpc_query_row($args){
    $q = _xmlrpc_query($args);
    if (is_a($q, 'IXR_Error'))
        return $q;
    return (array)mysql_fetch_row($q);
}
function xmlrpc_query_first($args){
    $q = _xmlrpc_query($args);
    if (is_a($q, 'IXR_Error'))
        return $q;
    return (array)mysql_fetch_assoc($q);
}
function xmlrpc_add_pending_user($args){
    global $db;
    $u = $args[0];
    return $db->add_pending_user($u);
}
function xmlrpc_add_payment($args){
    global $db;
    $p = $args[0];

    $member_id      = $p['member_id'];
    $product_id     = $p['product_id'];
    $paysys_id      = $p['paysys_id'];
    $begin_date     = $p['begin_date'];
    $expire_date    = $p['expire_date'];
    $price          = $p['amount'];
    $receipt_id     = $p['receipt_id'];
    
    $vars = array();
    $err = '';
    $payment_id = $db->add_waiting_payment($member_id, $product_id, $paysys_id, $price, $begin_date, $expire_date, $vars);
    if ($payment_id <= 0) $err = "Error add waiting payment";
    if (!$err)
        $err = $db->finish_waiting_payment($payment_id, $paysys_id, $receipt_id, $price, $vars);
    if (!$err)
        return $payment_id;
    else
        return $err;
}

function xmlrpc_calculate_expiration($args){
    // todo - handle existing member subscription
    list($product_id, $begin_date) = $args;
    $pr = get_product($product_id);
    if ($begin_date == '') 
        $begin_date = date('Y-m-d');
    return $pr->get_expire($begin_date);
}
function xmlrpc_get_user($args){
    global $db;
    $member_id = $args[0];
    return $db->get_user($member_id);
}
function xmlrpc_update_user($args){
    global $db;
    list($member_id, $u) = $args;
    $db->update_user($member_id, $u);
}
function xmlrpc_delete_user($args){
    global $db;
    list($member_id) = $args;
    return $db->delete_user($member_id);
}
function xmlrpc_get_user_payments($args){
    global $db;
    list($member_id, $only_completed) = $args;
    return $db->get_user_payments($member_id, $only_completed);
}
function xmlrpc_get_products_list($args){
    global $db;
    return $db->get_products_list();
}

function xmlrpc_check_uniq_login($args){
    global $db, $config;
    $u = $args[0];
    if ($u['check_type'] == '2'){
        // new check type, not implemetned into mysql.inc.php yet
        // 2 - uniq user - return member_id if user exists, -1 if no and 0 if email/password failed
        
        $login = $db->escape($u['login']);
        $email = $db->escape($u['email']);
        $pass  = $db->escape($u['pass']);
        $q = $db->query($s = "SELECT login,
            (email='$email'), member_id, pass='$pass'
            FROM {$db->config['prefix']}members
            WHERE login='$login'
        ");
        $db->log_error ($s);
        list($login_x, $same_email,$member_id, $same_pass) = mysql_fetch_row($q);
        if ( $member_id ) {
            if ($config['generate_pass']) {
                if (!$same_email) return 0; //check email
            } else {
                if (!$same_pass || !$same_email) return 0; //check email&pass
            }
        } else $member_id = -1;
        return $member_id;
    } else {
        return $db->check_uniq_login($u['login'], $u['email'], $u['pass'], $u['check_type']);
    }
}

function xmlrpc_check_remote_access($args){
    global $db;
    $a = $args[0];
    return $db->check_remote_access($a['login'], $a['password'], $a['product_id'], $a['ip'], $a['url'], $a['referer']);
}

function xmlrpc_check_login($args){
    global $db;
    $a = $args[0];
    $member_id = '';
    $res = $db->check_login($a['login'], $a['pass'], $member_id, $a['accept_md5']);
    if ($res)
        return $member_id;
    else
        return 0;
}

$server = & new IXR_Server(array(
    'ping'      => 'xmlrpc_ping',
    'query'     => 'xmlrpc_query',
    'query_all' => 'xmlrpc_query_all',
    'query_one' => 'xmlrpc_query_one',
    'query_row' => 'xmlrpc_query_row',
    'query_first' => 'xmlrpc_query_first',
    'add_pending_user' => 'xmlrpc_add_pending_user',
    'get_user' => 'xmlrpc_get_user',
    'update_user' => 'xmlrpc_update_user',
    'delete_user' => 'xmlrpc_delete_user',
    'add_payment' => 'xmlrpc_add_payment',
    'get_user_payments' => 'xmlrpc_get_user_payments',
    'get_products_list' => 'xmlrpc_get_products_list',
    'calculate_expiration'  => 'xmlrpc_calculate_expiration',
    'check_uniq_login'      => 'xmlrpc_check_uniq_login',
    'check_remote_access'   => 'xmlrpc_check_remote_access',
    'check_login'           => 'xmlrpc_check_login',
));

?>