<?php

/* //allow access for google-bot
if (preg_match('/^googlebot/i', $_SERVER['HTTP_USER_AGENT']))
	return;
*/
/*
*
*
*	 Author: Alex Scott
*	  Email: alex@cgi-central.net
*		Web: http://www.cgi-central.net
*	Details: The installation file
*	FileName $RCSfile$
*	Release: 3.2.3PRO ($Revision: 5278 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*																		  
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

////////////////// SAVE NAMESPACE BEFORE LITTER IT ///////////////////////////
$_list_vars = array_keys($GLOBALS);
//////////////////////////////////////////////////////////////////////////////
if (!is_array($_product_id) && !is_array($_link_id)) die("Product ID is not defined in called script! Died.");

function __start_amember_session()
{
	session_start();
}

if (!count(@$_SESSION))
	__start_amember_session();

if (@$_SESSION['_amember_login'] && @$_SESSION['_amember_pass'] && array_intersect((array)$_SESSION['_amember_product_ids'],(array)$_product_id))
{
	return;
}
if(defined("INCREMENTAL_CONTENT_PLUGIN"))
if (@$_SESSION['_amember_login'] && @$_SESSION['_amember_pass'] && array_intersect((array)$_SESSION['_amember_link_ids'],(array)$_link_id))
{
	return;
}

$root = dirname(__FILE__) . '/../../..'; ## set it to root_dir if wrong
if (!defined('INCLUDED_AMEMBER_CONFIG'))
	include("$root/config.inc.php");

function _amember_check_access()
{
	global $_product_id, $_link_id, $db, $config;
	
	$this_config = $config['plugins']['protect']['php_include'];

	$_SESSION['_amember_user']		  = array();
	$_SESSION['_amember_product_ids']   = array();
	if(defined("INCREMENTAL_CONTENT_PLUGIN"))
	$_SESSION['_amember_link_ids']   = array();
	$_SESSION['_amember_products']	  = array();
	$_SESSION['_amember_links']	  = array();
	$_SESSION['_amember_subscriptions'] = array();

	$l = $_POST['amember_login'];
	$p = $_POST['amember_pass'];
	if (!strlen($l)) {
		$l = $_GET['amember_login'];
		$p = $_GET['amember_pass'];
	}
	if (!strlen($l)) {
		$l = $_SESSION['_amember_login'];
		$p = $_SESSION['_amember_pass'];
	}
	if (!strlen($l)) {
		$l = $_COOKIE['_amember_ru'];
		$p = $_COOKIE['_amember_rp'];
	}
	
	$b = & new BruteforceProtector(BRUTEFORCE_PROTECT_USER, $db, 
		$config['bruteforce_count'], $config['bruteforce_delay']);
	$b->setIP($_SERVER['REMOTE_ADDR']);
	$left = null; // how long secs to wait if login is not allowed
	if (!$b->loginAllowed($left)){
		if ($_SESSION['_amember_login']) unset($_SESSION['_amember_login']);
		if ($_SESSION['_amember_pass']) unset($_SESSION['_amember_pass']);
		$min = ceil($left / 60); 
		return sprintf(_LOGIN_WAIT_BEFORE_NEXT_ATTEMPT, $min);
	}
	
	// check for vBulletin login
	if (!strlen($l)){
		list($l, $p) = plugin_check_logged_in();
		$skip_bruteforce_check=1;
	} 
	
	if (in_array($_POST['login_attempt_id'], 
		(array)$_SESSION['_amember_login_attempt_id'])){
			return _LOGIN_SESSION_EXPIRED;
	}

	if (strlen($l) && strlen($p))
	{
		if (!$db->check_login($l, $p, $_SESSION['_amember_id'], $accept_md5=1))
		{
			if (!$skip_bruteforce_check) $b->reportFailedLogin();
			if ($_SESSION['_amember_login']) unset($_SESSION['_amember_login']);
			if ($_SESSION['_amember_pass']) unset($_SESSION['_amember_pass']);
			return _LOGIN_INCORRECT;
		}
		if (($_product_id[0] != 'ONLY_LOGIN') && !$db->check_access($l, $_product_id) && !link_check_access($l, $_link_id))
		{
			$_SESSION['_amember_login'] = $l;
			$_SESSION['_amember_pass']  = $p;
			return sprintf(_LOGIN_ACCESS_NOT_ALLOWED, "<a href=\"{$config['root_url']}/member.php\">", "</a>");
		} else {
			$_SESSION['_amember_login'] = $l;
			$_SESSION['_amember_pass']  = $p;

			/// check for ip violance
			/// lock user if it needed
//			if (!$_SESSION['ip_checked']){ //skip if already checked
				if ($db->check_multiple_ip($_SESSION['_amember_id'], $config['max_ip_count'], 
								$config['max_ip_period'], $_SERVER['REMOTE_ADDR'])){ //limit exceeded
					member_lock_by_ip($_SESSION['_amember_id']);
				}					
				$_SESSION['ip_checked'] = 1;
  //		  }

			// assign user info to session var '_amember_id 
			// and to same template var 
			$_SESSION['_amember_user']  = $db->get_user($_SESSION['_amember_id']);
			$_SESSION['_amember_login'] = $_SESSION['_amember_user']['login']; // login is case insensitive, will use original login from DB instead of $_POST['login']
			if ($_SESSION['_amember_user']['data']['is_locked']>0)
				return _LOGIN_ACCOUNT_DISABLED;

			if ($config['manually_approve'] && !$_SESSION['_amember_user']['data']['is_approved']>0)
				return _LOGIN_MANUAL_VERIFICATION_PENDING;

			/* // it is no more needed, was developed for htpasswd
			if (!strcasecmp($l, $_SESSION['_amember_user']['login']) && 
				 strcmp($l, $ln=$_SESSION['_amember_user']['login']))
				return sprintf(_LOGIN_USERNAME_WRONG_CASE, $l, $ln);
			*/
			// find out active subscriptions for this user
      $pl = (Array)$db->get_user_payments($_SESSION['_amember_id'], 1);
      $today = date('Y-m-d');
			foreach ($pl as $pp){
                            if (($pp['begin_date'] <= $today) && ($pp['expire_date'] >= $today))
                            {
                                $_SESSION['_amember_product_ids'][] = $pp['product_id'];
                                $_SESSION['_amember_subscriptions'][] = $pp;
                            }
			}
			$_SESSION['_amember_product_ids'] = array_unique($_SESSION['_amember_product_ids']);
			if(defined("INCREMENTAL_CONTENT_PLUGIN"))
			$_SESSION['_amember_links'] = user_get_links($_SESSION['_amember_id']);
			if(defined("INCREMENTAL_CONTENT_PLUGIN"))
			if ($_SESSION['_amember_links'])
			{
				foreach ($_SESSION['_amember_links'] as $link_id => $link)
				{
					$_SESSION['_amember_link_ids'][] = $link_id;
				}
			}
			foreach ($_SESSION['_amember_product_ids'] as $product_id){
				$pr = $db->get_product($product_id);;
				$urls = array();
				foreach ( preg_split('/[\r\n]+/', trim($pr['add_urls'])) as $u){
					if (!strlen($u)) continue;
					list($k, $v) = preg_split('/\|/', $u);
					if (!$v) $v = $pr['title'];
					$urls[$k] = $v;
				} 
				$pr['add_urls'] = $urls;
				$_SESSION['_amember_products'][] = $pr;
			}

			if ($_POST['login_attempt_id'])
				$_SESSION['_amember_login_attempt_id'][] = 
					$_POST['login_attempt_id'];
			$db->log_access($_SESSION['_amember_id']);
			php_include_remember_login($_SESSION['_amember_user']);
			plugin_after_login($_SESSION['_amember_user']);
			return '';
		}
	} 
	return _LOGIN_PLEASE_LOGIN;
}	

function link_check_access($login, $link_ids)
{
	global $db;
	
	if (!array_filter((array)$link_ids)) return false;
	if ($link_ids[0] == 'ONLY_LOGIN') return true;
	//$link_id = $link_ids[0];
        $link_id = join(',', $link_ids);
        $query = $db->query("SELECT * FROM {$db->config[prefix]}products_links WHERE link_id IN ($link_id)");
	if (!mysql_num_rows($query)) return false;

	while ($row=mysql_fetch_assoc($query)) {
	   $product_ids[] = $row['link_product_id'];
	   $link[]=$row;
	}
	$login = $db->escape($login);
	
	foreach ($product_ids as $k=>$product_id) {
            // first payment
            $begin_date = $db->query_one("SELECT MIN(p.begin_date)
                    FROM {$db->config['prefix']}payments p
                            LEFT JOIN {$db->config['prefix']}members m USING (member_id)
                    WHERE m.login = '$login'
                            AND p.begin_date <= NOW()
                            AND p.completed > 0
                            AND p.product_id IN ($product_id)");

            //last payment
            $expire_date = $db->query_one("SELECT MAX(p.expire_date)
                    FROM {$db->config['prefix']}payments p
                            LEFT JOIN {$db->config['prefix']}members m USING (member_id)
                    WHERE m.login = '$login'
                            AND p.begin_date <= NOW()
                            AND p.completed > 0
                            AND p.product_id IN ($product_id)");

            // check for active payment if needed
            if (!$GLOBALS['plugin_config']['protect']['incremental_content']['allow_use_after_expire'] &&
                    !($db->query_one("SELECT p.begin_date
                    FROM {$db->config['prefix']}payments p
                            LEFT JOIN {$db->config['prefix']}members m USING (member_id)
                    WHERE m.login = '$login'
                            AND p.begin_date <= NOW()
                            AND p.expire_date >= NOW()
                            AND p.completed > 0
                            AND p.product_id IN ($product_id)"))){
                    continue;
            }

            if ($begin_date)
            {
                    $expr = min(strtotime($expire_date), time()); // works for enabled option
                          //$GLOBALS['plugin_config']['protect']['incremental_content']['allow_use_after_expire']
                          // otherwise $expr == $now
                    $now = time();
                    $product = $db->get_product($link[$k]['link_product_id']);
                    $begin_time = strtotime($begin_date);
                    $link_start_delay_sec = link_get_seconds($link[$k]['link_start_delay'], $begin_time);
                    $link_duration_sec = link_get_seconds($link[$k]['link_duration'], $link_start_delay_sec);
                    if ($link_start_delay_sec <= $expr && $link_duration_sec >= $now)
                    {
                            return true;
                    } else {
                            continue;
                    }
            } else {
                    continue;
            }
	}
	return false;
}
	
function user_get_links($member_id)
{
    global $db;
    $links = array();
    if(!defined("INCREMENTAL_CONTENT_PLUGIN")) return $links;

    $pl = (Array)$db->get_user_payments($member_id, 1);
    $products_begin_date = array();
    $today = date('Y-m-d');
    foreach ($pl as $pp){
        if ($pp['begin_date'] <= $today &&
             ($GLOBALS['plugin_config']['protect']['incremental_content']['allow_use_after_expire'] ||
              $pp['expire_date'] >= $today)
            ) {

            $begin_date = link_get_first_payment($pp);

            $expire_date = $db->query_one("SELECT MAX(p.expire_date)
                    FROM {$db->config['prefix']}payments p
                    LEFT JOIN {$db->config['prefix']}members m USING (member_id)
                    WHERE m.member_id = '$member_id'
                    AND p.begin_date <= NOW()
                    AND p.completed > 0
                    AND p.product_id = {$pp['product_id']}");

            if (isset($products_begin_date[$pp['product_id']])) {
                if ($products_begin_date[$pp['product_id']]['begin_date'] > $begin_date) {
                    $products_begin_date[$pp['product_id']]['begin_date'] = $begin_date;
                }
                if ($products_begin_date[$pp['product_id']]['expire_date'] < $expire_date) {
                    $products_begin_date[$pp['product_id']]['expire_date'] = $expire_date;
                }
            } else {
                $products_begin_date[$pp['product_id']]['begin_date'] = $begin_date;
                $products_begin_date[$pp['product_id']]['expire_date'] = $expire_date;
            }
        }
    }

    $now = time();

    if ($products_begin_date)
    {
        $links_query = $db->query("SELECT * FROM {$db->config[prefix]}products_links WHERE link_product_id IN (".implode(",",array_keys($products_begin_date)).")");
        while ($link = mysql_fetch_assoc($links_query))
        {
            $begin_time = strtotime($products_begin_date[$link['link_product_id']]['begin_date']);
            $link_start_delay_sec = link_get_seconds($link['link_start_delay'], $begin_time);
            $link_duration_sec = link_get_seconds($link['link_duration'], $link_start_delay_sec);

            //works for enabled option
            //$GLOBALS['plugin_config']['protect']['incremental_content']['allow_use_after_expire']
            //otherwise $expr == $now
            $expr = min(
                strtotime($products_begin_date[$link['link_product_id']]['expire_date']),
                time()
            );
            if ($link_start_delay_sec <= $expr && $link_duration_sec >= $now)
            {
            if (function_exists('get_incremental_plugin_files')) {
                $link['files'] = get_incremental_plugin_files($link['link_id']);
            }
                    $links[$link['link_id']] = $link;
            }
        }
    }
    return $links;
}

function link_get_seconds($duration, $start_time)
{
	if ($duration == 'lifetime')
	{
		return time() + (3600*24*365);
		
	} elseif (preg_match('/^\s*(\d{4})-(\d{2})-(\d{2})\s*$/', $duration, $regs)) {
	
		return mktime(0,0,0,$regs[2],$regs[3],$regs[1]);
		
	} elseif (preg_match('/^\s*(\d+)\s*([y|Y|m|M|w|W|d|D]{0,1})\s*$/', $duration, $regs)) {
		$period = $regs[1];
		$period_unit = $regs[2];
		list($day,$month,$year) = explode("-",date("d-m-Y",$start_time));
		$begin_time = $start_time;
		$period_unit = strtoupper($period_unit);
		
		switch ($period_unit)
		{
			case 'Y':
				return $begin_time + ($period*3600*24*365);
				break;
			case 'M':
				return mktime(0,0,0,$month+$period,$day,$year);
				break;
			case 'W':
				return $begin_time + ($period*3600*24*7);
				break;
			case 'D':
				return $begin_time + ($period*3600*24);
				break;
			default:
				return 0;
		}
	} else {
		return 0;
	}
}

function link_get_first_payment($payment)
{
	global $db;
	
	$original_begin_date = $payment['begin_date'];
	
	if ($payment_id = $payment['data'][0]['RENEWAL_ORIG'])
	{
		do {
			$begin_date = $payment['begin_date'];
			$x = preg_split('/ /', $payment['data'][0]['RENEWAL_ORIG']);
			$payment_id = $x[@count($x)-1];
			$payment = $db->get_payment($payment_id);
		} while ($payment_id <> $payment['data'][0]['RENEWAL_ORIG']);
	} else {
		$begin_date = $payment['begin_date'];
	}
	
	if ($original_begin_date == $begin_date) // small buggy check
	{
		$begin_date = $db->query_one($s = "SELECT MIN(begin_date)
						FROM {$db->config['prefix']}payments
						WHERE member_id = '$payment[member_id]'
						AND begin_date <= NOW()
						AND completed > 0
						AND product_id = '$payment[product_id]'");
	}
	
	return $begin_date;
}


function php_include_remember_login($user){
	global $plugin_config;
	$this_config = $plugin_config['protect']['php_include'];
	if (!$this_config['remember_login']) return;
	$need = 0;
	if ($this_config['remember_auto']) 
		$need++;
	else {
		$vars = get_input_vars();
		if ($vars['remember_login']) $need++;
	}
	if (!$need) return;
	setcookie('_amember_ru', $user['login'], time() + $this_config['remember_period'] * 3600 * 24, '/');
	setcookie('_amember_rp', md5($user['pass']),  time() + $this_config['remember_period'] * 3600 * 24, '/');
}

function _amember_run()
{
	global $affiliates_signup;
	
	$self = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];

	if ($_amember_error = _amember_check_access())
	{
		/// serialize request variables
		$_amember_prev_request = $_SESSION['_amember_prev_request'];
//		if (!$_SESSION['_amember_prev_request']){
			$_SESSION['_amember_prev_request'] = $_amember_prev_request = array( 
				'GET'  => $_GET,
				'POST' => $_POST,
				'METHOD' => $_SERVER['REQUEST_METHOD']
			);
		/// display login form
		$t = & new_smarty();
		global $plugin_config;
		
		if (($_amember_error == _LOGIN_PLEASE_LOGIN ) && ($_POST['login_attempt_id']==''))
			$t->assign('error', null);
		else
			$t->assign('error', $_amember_error);
		$t->assign('form_action', $self);
		
		$t->assign('this_config', $plugin_config['protect']['php_include']);
		if (isset($affiliates_signup) && $affiliates_signup == 1)
				$t->assign('affiliates_signup', '1');
    $t->display('login.html');
		exit();
	} else { // auth successfull
		// extract serialized request vars
		global $_amember_prev_request;
		if ($o = $_SESSION['_amember_prev_request']){
			$_GET = $o['GET'];
			$_POST = $o['POST'];
			unset($_SESSION['_amember_prev_request']);
			/// we don't set global variables here by security reasons
		}
	}
}

function _amember_match_url()
{
	global $config;
	foreach (array($config['root_url'], $config['root_surl']) as $url)
	{
		$u = parse_url($url);
		$port = $_SERVER['SERVER_PORT'];
		$host = $_SERVER['HTTP_HOST'];
		$uri = $_SERVER['REQUEST_URI'];
		if (($u['scheme'] == 'http') && ($port == 443))
			continue;
		if (($u['scheme'] == 'https') && ($port == 80))
			continue;
		$s = preg_quote($u['path']);
		if (preg_match("|^$s|", $uri))
			return 1;
	}
}

_amember_run();

/* 
if (!_amember_match_url()){
	header("Location: $_SERVER[REQUEST_URI]");
	exit();
}
*/

////////////////// CLEANUP NAMESPACE
foreach (array_keys($GLOBALS) as $k)
{
	if ($k == '_list_vars')			 continue;
	if ($k == 'HTTP_SESSION_VARS')	  continue;
	if ($k == 'HTTP_GET_VARS')		  continue;
	if ($k == 'HTTP_POST_VARS')		 continue;
	if ($k == 'HTTP_SERVER_VARS')	   continue;
	if ($k == 'HTTP_REFERRER')		  continue;
	if ($k == '_SESSION')			   continue;
	if (session_is_registered($k))	  continue;
	if (!in_array($k, $_list_vars)){
		unset($GLOBALS[$k]);
	}
}
unset($k);
unset($_list_vars);


?>