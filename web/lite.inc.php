<?php


global $config, $_amember_config;
define('AMEMBER_ONLY_DB_CONFIG', 1);
$old_config = @$config;
require_once(dirname(__FILE__).'/config.inc.php');
$_amember_config = $config;
$config = $old_config;

function amember_lite_connect(){
    global $_amember_config;
    $conn = mysql_connect(
        $_amember_config['db']['mysql']['host'], 
        $_amember_config['db']['mysql']['user'], 
        $_amember_config['db']['mysql']['pass']);
    if (!$conn) 
        die("Cannot connect to aMember SQL: " . mysql_error());
    if (!mysql_select_db($_amember_config['db']['mysql']['db'], $conn))
        die("Cannot select aMember Db: " . mysql_error());
    return $conn;
}

function amember_query($sql, $conn){
    $q = mysql_query($sql, $conn);
    if (!$q) {
        die("aMember query error: ".mysql_error($conn)."<br />in query: <pre>$sql</pre>");
    }
    return $q;
}

function amember_get_user($login){
    global $_amember_config;
    $prefix = $_amember_config['db']['mysql']['prefix'];
    $conn = amember_lite_connect();
    $login = mysql_escape_string($login);
    $q = amember_query("SELECT * FROM {$prefix}members WHERE login='$login'", $conn);
    $m = mysql_fetch_assoc($q);
    $m['data'] = unserialize($m['data']);
    if (!$m['member_id']) $m = array();
    return $m;
}

function amember_check_user($login, $pass, &$real_pass){
    // password can be : plain, md5 or crypt
    $m = amember_get_user($login);
    if (!$m) return 0;
    if (($m['pass'] == $pass) ||
        (md5($m['pass']) == $pass) ||
        (crypt($m['pass'], $pass) == $pass)
    ) {
        $real_pass = $m['pass'];
        return 1;
    } else 
        return 0;
}

function amember_get_subscriptions($login, $completed=0){
    // returns list of member subscriptions
    // completed: 0 - any, -1 - not paid, 1 - paid
    $m = amember_get_user($login);
    if (!$m) return array();

    global $_amember_config;
    $prefix = $_amember_config['db']['mysql']['prefix'];
    $conn = amember_lite_connect();
    
    
    $where_add = "";
    if ($completed > 0)
        $where_add = " AND completed > 0 ";
    elseif ($completed < 0)
        $where_add = " AND completed = 0 ";
    
    $q = amember_query("SELECT p.*,  pr.title as product_title
        FROM {$prefix}payments p
              LEFT JOIN  {$prefix}products pr USING (product_id) 
        WHERE member_id = $m[member_id]
        $where_add
        ORDER BY tm_added", $conn);
    $res = array();
    while ($a = mysql_fetch_assoc($q)){
        $a['data'] = unserialize($a['data']);
        $res[] = $a;
    }
    return $res;
}

function amember_get_products($login){
    // returns list of member active products
    $m = amember_get_user($login);
    if (!$m) return array();

    global $_amember_config;
    $prefix = $_amember_config['db']['mysql']['prefix'];
    $conn = amember_lite_connect();
    
    $q = amember_query("SELECT DISTINCT product_id
        FROM {$prefix}payments
        WHERE member_id = $m[member_id]
        AND begin_date <= NOW() AND expire_date >= NOW()
        AND completed > 0
        ORDER BY tm_added", $conn);
    $res = array();
    while (list($a) = mysql_fetch_row($q))
        $res[] = $a;
    if (!$res) return array();
    ///////////////////////////////
    $ids = join(',', $res);
    $res = array();
    $q = amember_query("SELECT *
        FROM {$prefix}products
        WHERE product_id IN ($ids)", $conn);
    while ($a = mysql_fetch_assoc($q)){
        foreach (unserialize($a['data']) as $k=>$v)
            $a[$k] = $v;
        $res[$a['product_id']] = $a;
    }
    return $res;
}

function amember_login_user($login, $pass){
    if (amember_check_user($login, $pass, $real_pass)){
        session_start();
        global $_amember_login, $_amember_pass;
        if (ini_get('register_globals')){
            session_register('_amember_login');
            session_register('_amember_pass');
        }
        $_amember_login = $_SESSION['_amember_login'] = $login;
        $_amember_pass  = $_SESSION['_amember_pass'] = $real_pass;
        return 1;
    } else 
        return 0;
}

?>