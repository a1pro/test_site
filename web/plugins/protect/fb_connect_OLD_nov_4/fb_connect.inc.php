<?php
/**
 *  Facebook Connect v1.3
 *  Copyright 2010 (c) R Woodgate
 *  All Rights Reserved
 *
 * ============================================================================
 *	Revision History:
 *	----------------
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
	
	$root_url = $config['root_url'];
	$ret = array('#2'=>'<fb:login-button size="small" perms="email" autologoutlink="true"></fb:login-button>');
		
	// Include Like button?
	if ($likebutton)
		$ret = array_merge(array('#1'=>'<fb:like layout="button_count" href="'.$root_url.'"></fb:like>'), $ret);

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
		$message = " just signed up to convert Youtube videos to MP3 on http://www.getaudiofromvideo.com";
		//$message = " just connected to {$config['site_title']} via Facebook";
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
		header("Location: ".$config['root_url']."/signup.php");
		exit;
	}
	
	// Check login to Facebook
	$fbuserid = fb_connect_get_fbuser();
	if (!$fbuserid) {
		header("Location: ".$config['root_url']."/signup.php");
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
		header("Location: ".$config['root_url']."/signup.php");
		exit;
	}
	
	// Ok, now we can create the account
	$vars = array();
	$vars['login'] = generate_login($vars);
    $vars['pass'] = $vars['pass0'] = $vars['pass1'] = generate_password($vars);
	$vars['name_f'] = $_SESSION['fbme']['first_name'];
	$vars['name_l'] = $_SESSION['fbme']['last_name'];
	$vars['email'] = $_SESSION['fbme']['email'];
	
	if ($GLOBALS['_LANG_SELECTED'] != get_default_lang())
		$vars['selected_lang'] = $GLOBALS['_LANG_SELECTED'];

	$member_id = $db->add_pending_user($vars);
	$db->query("UPDATE {$db->config['prefix']}members SET fbuserid = '$fbuserid' WHERE member_id = '$member_id' LIMIT 1");
	$db->log_error("fb_connect: Created aMember account for Facebook user ($fbuserid) - ".print_r($vars,1));
		
	$is_affiliate = '0'; //only member newsletters
	if ($db->get_signup_threads_c($is_affiliate))
			$db->subscribe_member ($member_id, $is_affiliate);

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
    /// It's a most important function - when user subscribed to 
    /// new product (and his subscription status changed to ACTIVE
    /// for this product), this function will be called
    /// In fact, you should add user to database here or update
    /// his record if it is already exists (it is POSSIBLE that
    /// record exists)
	
	$product = $db->get_product($product_id);
	//$message = " just signed up for '{$product[title]}' at http://www.getaudiofromvideo.com";
	//$message = " just signed up for '{$product[title]}' at {$config['site_title']}";
	$message="just signed up to convert Youtube videos to MP3 on http://www.getaudiofromvideo.com";
	fb_connect_post_message($message);
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