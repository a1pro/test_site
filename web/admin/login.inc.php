<?php
$badWords = array('script', 'onabort', 'onactivate',
    'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy',
    'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste',
    'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce',
    'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect',
    'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete',
    'ondblclick', 'ondeactivate', 'ondrag', 'ondragdrop', 'ondragend', 'ondragenter',
    'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate',
    'onfilterupdate', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown',
    'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown',
    'onmouseenter', 'onmouseleave', 'onmousemove', 'onmoveout', 'onmouseover', 'onmouseup',
    'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange',
    'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowexit',
    'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart',
    'onstart', 'onstop', 'onsubmit', 'onunload');
foreach ($_GET as $k => $v)
    if (@preg_match('/\b'.join('|', $badWords).'\b/', $v))
       die('Bad word detected in GET parameter, access deined');

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Login
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4833 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


function admin_auth(){
    global $db, $config, $vars;

    $l = $_SESSION['_admin_login'];
    $p = $_SESSION['_admin_pass'];
    if ($_POST['do_login']) {
        $l = $_POST['login'];
        $p = $_POST['passwd'];
    }
    if (strlen($l) && strlen($p)){
        $b = & new BruteforceProtector(BRUTEFORCE_PROTECT_ADMIN, $db, 
            $config['bruteforce_count'], $config['bruteforce_delay']);
        $b->setIP($_SERVER['REMOTE_ADDR']);
        $left = null; // how long secs to wait if login is not allowed
        if (!$b->loginAllowed($left)){
            $min = ceil($left / 60); 
            return "Please wait $min minutes before next login attempt";
        }        
            
        if (!($admin_rec = $db->check_admin_password($l, $p))){
            $b->reportFailedLogin();
            return "Incorrect login/password. Try again";
        } else {
            $_SESSION['_admin_login'] = $l;
            $_SESSION['_admin_pass']  = $p;
            $_SESSION['amember_admin'] = $admin_rec;
            return '';
        }
    } 
    return 'Please Login';
}    

function admin_login_form($err){
    global $t;
    global $do_login;
    if ($err)
        if ($err){
        $t->assign('error', $err);
        } else {
        $t->assign('error', 'Please login');
        }
    $t->display('admin/login.html');
    exit();
}

function admin_bruteforce_count(){
    global $db, $config;
    $bf_time = $config['_bf_time'];
    $bf_value = $config['_bf_value'];
    $tm = time();
    $v  = $bf_value;
    $dt1 = $dt = $tm - $bf_time;
    if ($dt1 == 0) $dt1 = 0.1;
    $v -= $dt;
    if ($v < 0) $v = 0;
    $v += (($v + 4)/ $dt1);
    if ($v > 480) $v = 480;
    $db->config_set('_bf_time', $tm, 0);
    $db->config_set('_bf_value', $v , 0);
}

function admin_bruteforce_denied(){
    global $config;
    $bf_time = $config['_bf_time'];
    $bf_value = $config['_bf_value'];
    $v  = $bf_value;
    $dt = time() - $bf_time;
    $v -= $dt;
    if ($v > 20)
        return "Please repeat login later (wait 5 minutes before next login attempt)";
}

function admin_has_permissions($action){
    if ($_SESSION['amember_admin']['super_user'] > 0) 
        return 1;
    if ($action == 'super_user')
        return 0;
    if (in_array($action, @array_keys($_SESSION['amember_admin']['perms'])))
        return 1;
    return 0;        
}
function admin_check_permissions($action){
    if (!admin_has_permissions($action))
        fatal_error("Sorry, you have no enough permissions 
            to perform this operation ('$action')", 0);
}

function admin_check_session(){
    global $config;
    /// check user-agent
    $ua = md5($_SERVER['HTTP_USER_AGENT']);
    if (!isset($_SESSION['_amember_ua'])){
        $_SESSION['_amember_ua'] = $ua;
    } elseif ($_SESSION['_amember_ua'] != $ua){
        if (isset($_COOKIE[session_name()])) 
            setcookie(session_name(), '', time()-42000, '/');
        session_destroy();
        admin_html_redirect($config['root_url'].'/admin/', 
        "Browser Agent Changed - Session destroyed",
        "Browser Agent Changed - Session destroyed",
                $target_top = true);
        exit();
    }
    /// check for admin session expiration
    $now = time();
    if (isset($_SESSION['_amember_sess_expires']) && isset($_SESSION['_admin_pass'])){
        if ($_SESSION['_amember_sess_expires'] < $now){
            if (isset($_COOKIE[session_name()])) 
                setcookie(session_name(), '', time()-42000, '/');
            session_destroy();
            admin_html_redirect($config['root_url'].'/admin/', 
                "Admin session expired",
                "Admin session expired, please login again",
                $target_top = true
                );
            exit();
        }
    }
    $_SESSION['_amember_sess_expires'] = $now + 3600;
}
///////////////////////////////////////////////////////////////////////////////

$t = new_smarty();
$vars = get_input_vars(); 

admin_check_session(); // check if session expired and User Agent

if($err = admin_auth()) {                // authentication failed
  admin_login_form($err);               // display login form
  exit();
}

if ($_SESSION['amember_admin']['last_session'] != session_id()){
    $db->admin_update_login_info($_SESSION['amember_admin']['admin_id']);
}    
if ($t)
    $t->assign('SID', session_name().'='.session_id());
unset($vars);
?>