<?php
/**
 *  Facebook Connect v1.4
 *  Copyright 2010 (c) R Woodgate
 *  All Rights Reserved
 *
 * ============================================================================
 *	Revision History:
 *	----------------
 *  2010-11-26  v1.4	R Woodgate  New config options and updated Facebook API
 *  2010-07-23  v1.3	R Woodgate  Automatic signup and new config options
 *  2010-06-28  v1.2	R Woodgate  Various optimisations
 *	2010-06-12	v1.1	R Woodgate	Bugfix in SQL Field function
 *	2010-05-28	v1.0	R Woodgate	Plugin Created
 * ============================================================================
 **/


if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

require_once($config['root_dir']."/plugins/protect/fb_connect/facebook.php");

function check_setup_fb_connect(){
    global $plugin_config, $config, $db;
	$this_config = $plugin_config['protect']['fb_connect'];

	if (!$this_config['sqlupdated'])
		fb_connect_sql_field();
	add_member_field(
            'fbuserid',
            "Facebook UserID",
            'text', 
            "",
            '',
			array('sql' => 1, 'sql_type' => 'INT', 'display_profile' => '0', 'display_signup' => '0', 'default' => 0)
    );
	
	// Check login to Facebook
	$fbuserid = fb_connect_get_fbuser();
	if ($fbuserid) {	
		// Add email permissions button?
		if ($this_config['fbemail'] && !$_SESSION['fbme']['email'])
			add_member_field(
				'fbperm_email',
				"Sync With Facebook Email",
				'readonly', 
				"Automatically updates your profile email address<br/>
				whenever you change your Facebook email address<br/>
				(NB: Will not work with proxied Facebook email<br/>
				 addresses, as these are too long)",
				'',
				array('display_profile' => '1', 'display_signup' => '0', 'default' => "<a href='https://graph.facebook.com/oauth/authorize?client_id={$this_config['appid']}&redirect_uri={$config['root_url']}/profile.php&scope=email'>Grant Permission</a>")
			);
	
		// Add publish permissions button
		if ($this_config['publish_stream'])
			add_member_field(
				'fbperm_publish',
				"Allow Facebook Wall Updates",
				'readonly', 
				"Allow us to automatically put a notice on your Wall<br/>
				whenever you order a new product from us",
				'',
				array('display_profile' => '1', 'display_signup' => '0', 'default' => "<a href='https://graph.facebook.com/oauth/authorize?client_id={$this_config['appid']}&redirect_uri={$config['root_url']}/profile.php&scope=publish_stream'>Grant Permission</a>")
			);		
	}

   return '';
}

if (!check_setup_fb_connect()){
	setup_plugin_hook('check_logged_in', 'fb_connect_check_logged_in');
	setup_plugin_hook('after_login', 'fb_connect_after_login');
	setup_plugin_hook('after_logout', 'fb_connect_after_logout');
	setup_plugin_hook('fill_in_signup_form', 'fb_connect_fill_in_signup_form');
	setup_plugin_hook('get_member_links', 'fb_connect_get_member_links');
	//  setup_plugin_hook('get_left_member_links', 'fb_connect_get_member_links');
	setup_plugin_hook('subscription_added', 'fb_connect_added');
	//	setup_plugin_hook('subscription_updated', 'fb_connect_updated');
	//	setup_plugin_hook('subscription_deleted', 'fb_connect_deleted');
	//	setup_plugin_hook('subscription_removed', 'fb_connect_removed');
	//	setup_plugin_hook('subscription_rebuild', 'fb_connect_rebuild');
	//	setup_plugin_hook('finish_waiting_payment', 'fb_connect_payment_completed');
	if (function_exists('amember_filter_output')) {
	    setup_plugin_hook('filter_output', 'fb_connect_filter_output');
	}
}

////
// Version 3.2.3+ allows us to add template items automatically
function fb_connect_filter_output(&$source, $resource_name, $smarty) {
	global $config, $db, $plugin_config, $vars;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
	
	/// Header Elements
	if ($resource_name == 'layout.html') {
		
		// Add FB Namespace
		$source = preg_replace('|<html ([^>]*)>|i', '<html $1 xmlns:fb="http://www.facebook.com/2008/fbml">', $source, 1);
		
		// Add FB Javascript
		$fbappid	 = trim($this_config['appid']);
		$fb_login_url = $config['root_url']."/login.php";
		$fb_logout_url = $config['root_url']."/logout.php";
		
		$output = <<<EOF
<div id="fb-root"></div>
<script type="text/javascript">
window.fbAsyncInit = function() {
FB.init({appId: '$fbappid', status: true, cookie: true, xfbml: true});

FB.Event.subscribe('auth.sessionChange', function(response) {
	if (response.session) {
	  // A user has logged in, and a new cookie has been saved
	  document.location.href = "$fb_login_url";
	} else {
	  // The user has logged out, and the cookie has been cleared
	  document.location.href = "$fb_logout_url";
	}
});
};
(function() {
var e = document.createElement('script');
e.type = 'text/javascript';
e.src = document.location.protocol +
  '//connect.facebook.net/en_GB/all.js';
e.async = true;
document.getElementById('fb-root').appendChild(e);
}());
</script>
EOF;

		$source = preg_replace('|(<body[^>]*>)|i', "$1\n<!--fb_connect: http://www.amemfblogin.com header-->\n$output\n<!--fb_connect: /header-->\n", $source, 1);
	}
	
	/// Signup Form Elements (if not restricted by price group or manually integrated)
	if ($resource_name == 'signup.html' && (in_array($vars['price_group'], split(',',$this_config['price_group'])) || $vars['fb']) && $this_config['signupform'] ) {
	
		// Table top
		if (!strpos($source, "<!--openid: table top-->") )
			$source = preg_replace('|(<form[^>]*>)|i', "<!--fb_connect: table top-->\n<table width=\"100%\" border=\"0\">\n<tr><td width='50%' style='text-align:center;'>\n<!--fb_connect: /table top-->\n$1", $source, 1);
		
		// Table middle
		$blurb = ($this_config['signupblurb'])?$this_config['signupblurb']:'<h2 style="text-align:center;">Use your existing account...</h2>';
		if (!strpos($source, "<!--openid: table middle-->") )
			$output = "</form>\n<!--fb_connect: table middle--></td>\n<td width='50%' style='text-align:center;'>\n<!--fb_connect: /table middle-->\n<!--fb_connect: signup heading-->\n$blurb\n<!--fb_connect: /signup heading-->\n<!--fb_connect: signup output-->\n";
		else
			$output = "</form>\n";
		
		// Signup output
		$fbperms = array();
		if ($this_config['newaccount'] || ($this_config['fbemail'] && $this_config['forceperms']) )
			$fbperms[] = 'email';
		if ($this_config['publish_stream'] && $this_config['forceperms'])
			$fbperms[] = 'publish_stream';
		$fbperms = implode(',',$fbperms);
		$fbuser = fb_connect_get_fbuser();
		list($l, $p) = fb_connect_check_logged_in();
		$button_txt = ($this_config['signupbtntxt'])?$this_config['signupbtntxt']:'Connect with Facebook';
		
		if (strlen($l) && strlen($p)) {
			$output .= "<script type=\"text/javascript\">document.location.href = \"{$config['root_url']}/login.php\";</script>";

		} else if ($fbuser) {
			$msg = ($vars['fb'] == 'email')?"but we couldn't create your account automatically as there was a problem with your email address (missing, too long or already registered)":"and we've pre-filled the signup form with your Facebook details";
			$output .=  "<p style='width:400px;margin:1em auto;text-align:left;'><img src='https://graph.facebook.com/{$_SESSION['fbme']['id']}/picture' align='left' style='padding-right:1em;'> <strong>{$_SESSION['fbme']['name']}</strong>, you've successfully logged in to Facebook $msg. <span style='color:red'>Please complete the rest of the form to register your Facebook linked account.</span></p>";
			$output .=  "<p style='width:50%;margin:1em auto;text-align:center'><fb:login-button size=\"small\" autologoutlink=\"true\"></fb:login-button></p>";
			
		} else {
			$output .=  "<p style='margin:1em 0;text-align:center;'><fb:login-button size=\"medium\" perms=\"$fbperms\" onlogin='document.location.href=\"{$config['root_url']}/plugins/protect/fb_connect/signup.php\";'>$button_txt</fb:login-button></p>";
		}
		
		// Table bottom
		if (!strpos($source, "<!--openid: /signup output-->") )
			$output .= "\n<!--fb_connect: /signup output-->\n<!--fb_connect: table bottom-->\n</td>\n</tr>\n</table>\n<!--fb_connect: /table bottom-->\n";
		$source = str_replace("</form>",$output,$source);
	}
	
	/// Login Page Elements
	if ($resource_name == 'login.html' && $this_config['loginform']) {
		
		$fbperms = array();
		if ($this_config['newaccount'] || ($this_config['fbemail'] && $this_config['forceperms']) )
			$fbperms[] = 'email';
		if ($this_config['publish_stream'] && $this_config['forceperms'])
			$fbperms[] = 'publish_stream';
		$fbperms = implode(',',$fbperms);
		$fbuser = fb_connect_get_fbuser();
		$button_txt = ($this_config['loginbtntxt'])?$this_config['loginbtntxt']:'Connect with Facebook';
		if ($fbuser) {
			$output = "<p style='width:400px;margin:1em auto;text-align:left;'><img src='https://graph.facebook.com/{$_SESSION['fbme']['id']}/picture' align='left' style='padding-right:1em;'> <strong>{$_SESSION['fbme']['name']}</strong>, you've successfully logged in to Facebook. <span style='color:red'>Now please login to your account on our site to complete the link.</span> You will only have to do this once. If you don't have an account with us, please <a href='{$config['root_url']}/plugins/protect/fb_connect/signup.php'>signup here</a>.</p>";
			
			if (!$this_config['forceperms'] && $this_config['loginoffer']) {
				if ( ($this_config['fbemail'] && !$this_config['newaccount']) || $this_config['publish_stream'] )
					$output .= "<div style='width:50%;margin:1em auto 0;text-align:left;'><p><strong>Before you link your account...</strong> Grant optional permissions to enhance your experience:</p><ul>";
				
				if ($this_config['fbemail'] && !$this_config['newaccount'])
					$output .= "<li><a href='https://graph.facebook.com/oauth/authorize?client_id=$fbappid&redirect_uri={$config['root_url']}/login.php&scope=email'>Sync With Facebook Email</a>. <small>Automatically update your email address whenever it changes in Facebook</small></li>";	
				
				if ($this_config['publish_stream'])
					$output .= "<li><a href='https://graph.facebook.com/oauth/authorize?client_id=$fbappid&redirect_uri={$config['root_url']}/login.php&scope=publish_stream'>Allow Facebook Wall Updates</a>. <small>Put a notice on your Wall whenever you order a new product from us</small></li>";
				
				if ( ($this_config['fbemail'] && !$this_config['newaccount']) || $this_config['publish_stream'] )
					$output .= "</ul></div>";
			}
			$output .= "<p style='text-align:center'><fb:login-button size=\"small\" autologoutlink=\"true\"></fb:login-button></p>";
			
		} else {
			$output = "<p style='margin:1em 0;text-align:center;'><fb:login-button size=\"medium\" perms=\"$fbperms\">$button_txt</fb:login-button></p>";
		}
		
		$source = preg_replace('|(<form name="login"(.*)</form>)|Uis', "$1\n<!--fb_connect: login output-->$output<!--fb_connect: /login output-->", $source, 1);
	}
}

////
// Checks for active Facebook user
function fb_connect_get_fbuser() {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
	
	$facebook = new Facebook(array(
	'appId'  => "{$this_config['appid']}",
	'secret' => "{$this_config['appsecret']}",
	'cookie' => true, // enable optional cookie support
	));
	
	$session = $facebook->getSession();
    if ($session) {
		$fbuser = $facebook->getUser();
		// Grab Facebook user data if needed
		if (!$_SESSION['fbme'] || $_SESSION['fbme']['id'] != $fbuser) {
			try {
				$_SESSION['fbme'] = $facebook->api('/me');
			} catch (FacebookApiException $e) {
				if ($testmode == 1) $db->log_error("fb_connect: Error getting Facebook User data for fbuser ($fbuser), error = $e");
			}
		}
		return $fbuser;
	}
	return false;
}
		
////
// Returns Facebook redirected login url
function fb_connect_get_login_url($next='') {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
	
	$facebook = new Facebook(array(
	'appId'  => "{$this_config['appid']}",
	'secret' => "{$this_config['appsecret']}",
	'cookie' => true, // enable optional cookie support
	));
	
	$url = ($next)?$next:"{$config['root_url']}/login.php";
	$fb_login_url = $facebook->getLoginUrl(array('next'=>"$url"));
	return $fb_login_url;
}

////
// Returns Facebook redirected logout url
function fb_connect_get_logout_url($next='') {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
	
	$facebook = new Facebook(array(
	'appId'  => "{$this_config['appid']}",
	'secret' => "{$this_config['appsecret']}",
	'cookie' => true, // enable optional cookie support
	));
	
	$url = ($next)?$next:"{$config['root_url']}/logout.php";
	$fb_logout_url = $facebook->getLogoutUrl(array('next'=>"$url"));
	return $fb_logout_url;
}

////
// Post message to member's Facebook wall
function fb_connect_post_message($message) {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
	$publish = $this_config['publish_stream'];
	
	if (!$publish) return;
	if (!$message) return;
	
	$facebook = new Facebook(array(
	'appId'  => "{$this_config['appid']}",
	'secret' => "{$this_config['appsecret']}",
	'cookie' => true, // enable optional cookie support
	));
	
	// Check they are logged into Facebook
	$session = $facebook->getSession();
    if ($session) {
		$fbuser = $facebook->getUser();
		try {
			$statusupdate = $facebook->api('/me/feed', 'post', array('message'=> $message, 'cb' => ''));
			if ($testmode) $db->log_error("fb_connect: Updated wall for fbuser ($fbuser), message = $message");
		} catch (FacebookApiException $e) {
			if ($testmode) $db->log_error("fb_connect: Error updating wall for fbuser ($fbuser) - $e");
		}
	}
}
		
////
// Add logout to member links
function fb_connect_get_member_links($member) {
    global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
	$likebutton = $this_config['likebutton'];
	$likeurl = ($this_config['likeurl'])?$this_config['likeurl']:substr($config['root_url'], 0, strpos($config['root_url'],'/',8)+1 );
	$likestyle = ($this_config['likestyle'])?'':'layout="button_count"';
	// Check login to Facebook
	$fbuserid = fb_connect_get_fbuser();
	$fbperms = array();
	$fbperms[] = 'email';
	$fbperms[] = 'publish_stream';
	$fbperms[] = 'offline_access';
	$fbperms = implode(',',$fbperms);
	
	if (!$fbuserid){
	$ret = array('#1'=>'<fb:login-button size="medium" perms="'.$fbperms.'">Signup with Facebook</fb:login-button>',$ret);
	return $ret;
	}
	
	$ret = array('#2'=>'<fb:login-button size="small" perms="email" autologoutlink="true"></fb:login-button>');
		
	// Include Like button?
	if ($likebutton)
		$ret = array_merge(array('#1'=>'<fb:like '.$likestyle.' href="'.$likeurl.'"></fb:like>'), $ret);

	return $ret;
}

////
// Checks that member has a linked Facebook account and is already logged in to Facebook
// If so, allows immediate login to the linked amember account.
function fb_connect_check_logged_in() {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
	
	// Check login to Facebook
	$fbuserid = fb_connect_get_fbuser();
	if (!$fbuserid) return array('','');
	
	// Check for linked aMember account
	$fbuserid = $db->escape($fbuserid);
	list ($l,$p) = $db->query_row("SELECT login, pass FROM {$db->config['prefix']}members WHERE fbuserid = '$fbuserid' LIMIT 1");
	if ($l) {
		if ($testmode == 1) $db->log_error("fb_connect: access granted via Facebook login for member: $l, fbuser: $fbuserid");
		return array($l,$p);
	}
    return array('','');
}

////
// Checks that logged in member has linked a Facebook account to their amember account
// If not, and member is currently signed into Facebook, create a link.
function fb_connect_after_login() {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
	
	// Check login to Facebook
	$fbuserid = fb_connect_get_fbuser();
	if (!$fbuserid) return;
	
	// Update user email?
	fb_connect_update_email();
	
	// Check for existing link
	$fbuserid = $db->escape($fbuserid);
	$l = $db->query_one("SELECT login FROM {$db->config['prefix']}members WHERE fbuserid = '$fbuserid' LIMIT 1");
	if ($l) return; // Ok, Already linked...
	
	// Create link from Facebook to this amember account
	if ($_SESSION['_amember_user']['login']) { // Should always be set, but just in case...
		$l = $db->escape($_SESSION['_amember_user']['login']);
		$db->query("UPDATE {$db->config['prefix']}members SET fbuserid = '$fbuserid' WHERE login = '$l' LIMIT 1");
		if ($testmode == 1) $db->log_error("fb_connect: Linked Facebook account ($fbuserid) for member: $l");
		
		// Update Facebook status, if appropiate
		$message = " just connected to {$config['site_title']} via Facebook";
		fb_connect_post_message($message);
	}
}

////
// Clears Facebook session on logout from aMember
function fb_connect_after_logout() {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
	$forcelogout = $this_config['forcelogout'];
	if (!$forcelogout) return;
	
	// Check login to Facebook
	$fbuserid = fb_connect_get_fbuser();
	if (!$fbuserid) return;
	
	// Send them to facebook logout
	$url = fb_connect_get_logout_url($config['root_url']);
	header ("Location: $url");
}

////
// Updates account with Facebook data (name, email)
function fb_connect_update_email() {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
	
	// Check email option enabled
	$fbemail = $this_config['fbemail'];
	if (!$fbemail) return;
	
	// Check login to Facebook
	$fbuserid = fb_connect_get_fbuser();
	if (!$fbuserid) return;
	
	$fbuserid = $db->escape($fbuserid);
	$member_id = $db->query_one("SELECT member_id FROM {$db->config['prefix']}members WHERE fbuserid = '$fbuserid' LIMIT 1");
	if (!$member_id) return;

	// Update if email has changed and email fits amember limit of 64 characters (FB proxy emails are usually longer)
	$u = $db->get_user($member_id);
	if ($_SESSION['fbme']['email'] && $u['email'] != $_SESSION['fbme']['email'] && strlen($_SESSION['fbme']['email']) <= 64) {
		
		$u['email'] = $_SESSION['fbme']['email'];
		$u['name_f'] = $_SESSION['fbme']['first_name'];
		$u['name_l'] = $_SESSION['fbme']['last_name'];
			
		$u = $db->escape($u);
		$db->update_user($member_id, $u);
		if ($testmode == 1) $db->log_error("fb_connect: Synced name and email for member_id = $member_id, fbuser = $fbuserid");
	}
}

////
// Pre-fill signup form with Facebook information, if available
function fb_connect_fill_in_signup_form(&$vars) {
	global $config, $db, $plugin_config, $member_additional_fields;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
	
	// Check login to Facebook
	$fbuserid = fb_connect_get_fbuser();
	if (!$fbuserid) return;
	
	// Prefill Signup form vars
	$vars['name_f'] = $_SESSION['fbme']['first_name'];
	$vars['name_l'] = $_SESSION['fbme']['last_name'];
	$vars['email'] = $_SESSION['fbme']['email'];
	if ($testmode == 1) $db->log_error("fb_connect: Prefilled Signup form for Facebook account ($fbuserid) - ".print_r($vars,1));
}

////
// Creates new amember account and adds Facebook product subscription
function fb_connect_create_account() {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
	
	// Check create account is allowed
	if (!$this_config['newaccount']) {
		header("Location: ".$config['root_url']."/signup.php?fb=manual");
		exit;
	}
	
	// Check login to Facebook
	$fbuserid = fb_connect_get_fbuser();
	if (!$fbuserid) {
		header("Location: ".$config['root_url']."/signup.php?fb=cancel");
		exit;
	}
	
	// Check there is not already a linked account
	list($l, $p) = fb_connect_check_logged_in();
	if (strlen($l) && strlen($p)) {
		header("Location: ".$config['root_url']."/login.php");
		exit;
	}
	
	// Check facebook email is available, that it is not proxied (too long), and not already exists in aMember
	if (!$_SESSION['fbme']['email'] || strlen($_SESSION['fbme']['email']) > 64 || ($config['unique_email'] && $db->users_find_by_string($_SESSION['fbme']['email'], 'email', 1)) ) {
		if ($testmode == 1) $db->log_error("fb_connect: Create account aborted: Has a proxied email, no email address or email already exists ({$_SESSION['fbme']['email']})");
		header("Location: ".$config['root_url']."/signup.php?fb=email");
		exit;
	}
	
	// Ok, now we can create the account
	$vars = array();
	$vars['name_f'] = $_SESSION['fbme']['first_name'];
	$vars['name_l'] = $_SESSION['fbme']['last_name'];
	$vars['email'] = $_SESSION['fbme']['email'];
	$vars['login'] = generate_login($vars);
    $vars['pass'] = $vars['pass0'] = $vars['pass1'] = generate_password($vars);
	
	if ($GLOBALS['_LANG_SELECTED'] != get_default_lang())
		$vars['selected_lang'] = $GLOBALS['_LANG_SELECTED'];

	$member_id = $db->add_pending_user($vars);
	$db->query("UPDATE {$db->config['prefix']}members SET fbuserid = '$fbuserid' WHERE member_id = '$member_id' LIMIT 1");
	$db->log_error("fb_connect: Created aMember account for Facebook user ($fbuserid) - ".print_r($vars,1));
		
	$is_affiliate = '0'; //only member newsletters
	if ($db->get_signup_threads_c($is_affiliate))
			$db->subscribe_member ($member_id, $is_affiliate);
			
	if ($config['auto_login_after_signup']){
		$_SESSION['_amember_login']     = $vars['login'];
		$_SESSION['_amember_pass']      = $vars['pass'];
	}

	// Now add Facebook product subscripton, if set
	if ($this_config['newaccountproduct']) {
		
		$fb_product = &get_product($this_config['newaccountproduct']);
		$fb_payment = array(
		'member_id' => $member_id,
		'product_id' => $fb_product->config['product_id'],
		'completed' => 0,
		'paysys_id' => 'free',
		'begin_date' => $begin_date = date('Y-m-d'),
		'expire_date' => $fb_product->get_expire($begin_date)
		);
		$db->add_payment($fb_payment);
		if ($testmode == 1) $db->log_error("fb_connect: Added subscription (product #{$this_config['newaccountproduct']} ) for Facebook user ($fbuserid), login = {$vars['login']}");

		// Now go to thanks page...
		$payment_id = $GLOBALS['_amember_added_payment_id'];
		$vcode = md5($payment_id . $begin_date . $member_id);
		header("Location: ".$config['root_url']."/plugins/protect/fb_connect/thanks.php?payment_id=$payment_id&vcode=$vcode");
		exit();
	
	}
	
	// Account only - go to member page...
	header("Location: ".$config['root_url']."/member.php");
	exit();
}

////
// Creates an 'fbuserid' SQL field in the amember member table (if not already there)
function fb_connect_sql_field(){
    global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
    
	$mt = $db->config['prefix'] . 'members';
    $q = $db->query("SELECT * FROM $mt LIMIT 1");
    $i = 0;
    while ($i<mysql_num_fields($q)){
    	if ($meta = mysql_fetch_field($q, $i)){
			if (strcasecmp($meta->name, 'fbuserid') == 0) {
				$db->log_error("Facebook Connect: fbuserid sql field already exists.");
				return;
			}
		}
		$i++;
    }
	
    // actually add field
    mysql_query($s = "ALTER TABLE $mt ADD fbuserid BIGINT UNSIGNED NOT NULL");
	if (mysql_errno())
		$db->log_error("Facebook Connect: Couldn't add field - mysql error: " . mysql_error());
	else {
		@mysql_query($s = "ALTER TABLE $mt DROP INDEX fbuserid");
		@mysql_query($s = "ALTER TABLE $mt ADD INDEX (fbuserid)");
		$db->config_set('protect.fb_connect.sqlupdated',1,0);
	}
	return;
}

function fb_connect_rebuild(&$members){
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['fb_connect'];
    /// some actions when admin click aMember CP -> Rebuild Db
    /// it should compare all records in your third-party
    /// database with aMember supplied-list ($members)
    /// Or you may just skip this hook
}
    
function fb_connect_added($member_id, $product_id, $member) {
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['fb_connect'];
	$testmode = $this_config['testmode'];
    /// It's a most important function - when user subscribed to 
    /// new product (and his subscription status changed to ACTIVE
    /// for this product), this function will be called
    /// In fact, you should add user to database here or update
    /// his record if it is already exists (it is POSSIBLE that
    /// record exists)
	
	$product = $db->get_product($product_id);
	if ( !in_array($product_id, (array)$this_config['no_wall_update']) ) {	
		$message = " just signed up for '{$product[title]}' at {$config['site_title']}";
		fb_connect_post_message($message);
	
	} else if ($testmode) $db->log_error("fb_connect: Skipping Wall update for product $product_id ({$product[title]})");
	
	
}

function fb_connect_updated($member_id, $oldmember, $newmember) {
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['fb_connect'];
    /// this function will be called when member updates
    /// his profile. If user profile is exists in your 
    /// database, you should update his profile with 
    /// data from $newmember variable. You should use
    /// $oldmember variable to get old user profile - 
    /// it will allow you to find original user record.
    /// Don't forget - login can be changed too! (by admin)
}

function fb_connect_deleted($member_id, $product_id, $member) {
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['fb_connect'];
    /// This function will be called when user subscriptions
    /// status for $product_id become NOT-ACTIVE. It may happen
    /// if user payment expired, marked as "not-paid" or deleted
    /// by admin
    /// Be careful here - user may have active subscriptions for 
    /// another products and he may be should still in your 
    /// database - check $member['data']['status'] variable
}

function fb_connect_removed($member_id, $member) {
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['fb_connect'];
    /// This function will be called when member profile 
    /// deleted from aMember. Your plugin should delete 
    /// user profile from database (if your application allows it!), 
    /// or it should just disable member access if your application
    /// if application doesn't allow profiles deletion
}

?>
