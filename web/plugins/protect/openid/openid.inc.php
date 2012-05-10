<?php
/**
 *  OpenID v1.1
 *  Copyright 2010 (c) R Woodgate
 *  All Rights Reserved
 *
 * ============================================================================
 *	Revision History:
 *	----------------
 *	2010-10-28	v1.1	R Woodgate	Bug fixes (images)
 *	2010-07-28	v1.0	R Woodgate	Plugin Created
 * ============================================================================
 **/


if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

require_once($config['root_dir']."/plugins/protect/openid/openid.php");

function check_setup_openid(){
    global $plugin_config, $config, $db;
	$this_config = $plugin_config['protect']['openid'];

	if (!$this_config['sqlupdated'])
		openid_sql_field();
	add_member_field(
            'openid',
            "OpenID UserID",
            'text', 
            "",
            '',
			array('sql' => 1, 'sql_type' => 'TEXT', 'display_profile' => '0', 'display_signup' => '0', 'default' => 0)
    );
	
   return '';
}

if (!check_setup_openid()){
	setup_plugin_hook('check_logged_in', 'openid_check_logged_in');
	setup_plugin_hook('after_login', 'openid_after_login');
	setup_plugin_hook('after_logout', 'openid_after_logout');
	setup_plugin_hook('fill_in_signup_form', 'openid_fill_in_signup_form');
	//setup_plugin_hook('subscription_added', 'openid_added');
	// setup_plugin_hook('subscription_updated', 'openid_updated');
	// setup_plugin_hook('subscription_deleted', 'openid_deleted');
	// setup_plugin_hook('subscription_removed', 'openid_removed');
	// setup_plugin_hook('subscription_rebuild', 'openid_rebuild');
	// setup_plugin_hook('finish_waiting_payment', 'openid_payment_completed');
}
	
////
// Checks that member has a linked OpenID account and is already logged in to OpenID
// If so, allows immediate login to the linked amember account.
function openid_check_logged_in() {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['openid'];
	$testmode = $this_config['testmode'];
	
	// Check login to OpenID
	if (!$_SESSION['openid']['identity']) return array('','');
	
	// Check for linked aMember account
	$openid = $db->escape($_SESSION['openid']['identity']);
	list ($l,$p) = $db->query_row("SELECT login, pass FROM {$db->config['prefix']}members WHERE openid = '$openid' LIMIT 1");
	if ($l) {
		if ($testmode == 1) $db->log_error("openid: access granted via OpenID login for member: $l, openid: $openid");
		return array($l,$p);
	}
    return array('','');
}

////
// Checks that logged in member has linked a OpenID account to their amember account
// If not, and member is currently signed into OpenID, create a link.
function openid_after_login() {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['openid'];
	$testmode = $this_config['testmode'];
	
	// Check login to OpenID
	if (!$_SESSION['openid']['identity']) return;
	
	// Update user profile?
	openid_update_profile();
	
	// Check for existing link
	$openid = $db->escape($_SESSION['openid']['identity']);
	$l = $db->query_one("SELECT login FROM {$db->config['prefix']}members WHERE openid = '$openid' LIMIT 1");
	if ($l) return; // Ok, Already linked...
	
	// Create link from OpenID to this amember account
	if ($_SESSION['_amember_user']['login']) { // Should always be set, but just in case...
		$l = $db->escape($_SESSION['_amember_user']['login']);
		$db->query("UPDATE {$db->config['prefix']}members SET openid = '$openid' WHERE login = '$l' LIMIT 1");
		if ($testmode == 1) $db->log_error("openid: Linked OpenID account ($openid) for member: $l");
	}
}

////
// Clears OpenID session on logout from aMember
function openid_after_logout() {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['openid'];
	$testmode = $this_config['testmode'];
	
	unset($_SESSION['openid']);
}

////
// Updates account with OpenID data
function openid_update_profile() {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['openid'];
	$testmode = $this_config['testmode'];
	
	// Check login to OpenID
	if (!$_SESSION['openid']['identity']) return;
	
	// Check we have not already updated profile this session
	if ($_SESSION['openid']['profile_updated']) return;
	
	$openid = $db->escape($_SESSION['openid']['identity']);
	$member_id = $db->query_one("SELECT member_id FROM {$db->config['prefix']}members WHERE openid = '$openid' LIMIT 1");
	if (!$member_id) return;

	$u = $db->get_user($member_id);
	
	if ($_SESSION['openid']['data']['contact/email'] && strlen($_SESSION['openid']['data']['contact/email']) <= 64)
		$u['email'] = $_SESSION['openid']['data']['contact/email'];
	
	if ($_SESSION['openid']['data']['namePerson/first'] && $_SESSION['openid']['data']['namePerson/last'] ) {	
		$u['name_f'] = $_SESSION['openid']['data']['namePerson/first'];
		$u['name_l'] = $_SESSION['openid']['data']['namePerson/last'];
	} else if ($_SESSION['openid']['data']['namePerson'])
		list ($u['name_f'], $u['name_l']) = explode(" ",$_SESSION['openid']['data']['namePerson']); 
	
	// Grab any updates to required/optional profile items
	$ax_array = array_merge((array)$this_config['ax_optional'], (array)$this_config['ax_required']);
	foreach ($ax_array as $ax) {
		if ($_SESSION['openid']['data'][$ax]) {
			$u[openid_ax2field($ax)] = $_SESSION['openid']['data'][$ax];
			if ($ax == 'person/gender') 
				$u['is_male'] = ($_SESSION['openid']['data'][$ax] == "M")?1:0;
		}
	}
	
	$u = $db->escape($u);
	$db->update_user($member_id, $u);
	if ($testmode == 1) $db->log_error("openid: Updated profile for member_id = $member_id, openid = $openid - ".print_r($u,1));
	$_SESSION['openid']['profile_updated'] = true;
}

////
// Pre-fill signup form with OpenID information, if available
function openid_fill_in_signup_form(&$vars) {
	global $config, $db, $plugin_config, $member_additional_fields;
	$this_config = $plugin_config['protect']['openid'];
	$testmode = $this_config['testmode'];
	
	// Check login to OpenID
	if (!$_SESSION['openid']['identity']) return;
	
	// Prefill Signup form vars
	$vars['email'] = $_SESSION['openid']['data']['contact/email'];
	
	if ($_SESSION['openid']['data']['namePerson/first'] && $_SESSION['openid']['data']['namePerson/last'] ) {	
		$vars['name_f'] = $_SESSION['openid']['data']['namePerson/first'];
		$vars['name_l'] = $_SESSION['openid']['data']['namePerson/last'];
	} else list ($vars['name_f'], $vars['name_l']) = explode(" ",$_SESSION['openid']['data']['namePerson']); 
	
	// Grab any updates to required/optional profile items
	$ax_array = array_merge((array)$this_config['ax_optional'], (array)$this_config['ax_required']);
	foreach ($ax_array as $ax) {
		if ($_SESSION['openid']['data'][$ax])
			$vars[openid_ax2field($ax)] = $_SESSION['openid']['data'][$ax];
	}
	
	if ($testmode == 1) $db->log_error("openid: Prefilled Signup form for OpenID account ($openid) - ".print_r($vars,1));
}

////
// Creates new amember account and adds OpenID product subscription
function openid_create_account() {
	global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['openid'];
	$testmode = $this_config['testmode'];
	
	// Check create account is allowed
	if (!$this_config['newaccount']) {
		header("Location: ".$config['root_url']."/signup.php");
		exit;
	}
	
	// Check login to OpenID
	if (!$_SESSION['openid']['identity']) {
		header("Location: ".$config['root_url']."/signup.php");
		exit;
	}
	
	// Check there is not already a linked account
	list($l, $p) = openid_check_logged_in();
	if (strlen($l) && strlen($p)) {
		header("Location: ".$config['root_url']."/login.php");
		exit;
	}
	
	// Check OpenID email is available, that it is not too long, and not already exists in aMember
	$email = $_SESSION['openid']['data']['contact/email'];
	if (!$email || strlen($email) > 64 || ($config['unique_email'] && $db->users_find_by_string($email, 'email', 1)) ) {
		if ($testmode == 1) $db->log_error("openid: Create account aborted: Email address too long, not provided, or already exists ($email)");
		header("Location: ".$config['root_url']."/signup.php");
		exit;
	}
	
	// Check all required information has been supplied
	$ax_required = array_filter((array)$this_config['ax_required']);
	$ax_missing = array();
	foreach ($ax_required as $ax) {
		if (!$_SESSION['openid']['data'][$ax])
			$ax_missing[] = $ax;
	}
	if (count($ax_missing) > 0) {
		$ax_missing = implode(", ",$ax_missing);
		if ($testmode == 1) $db->log_error("openid: Auto-create account aborted: The following required items were missing ($ax_missing)");
		header("Location: ".$config['root_url']."/signup.php");
		exit;
	}
	
	// Ok, now we can create the account
	$vars = array();
	if ($_SESSION['openid']['data']['namePerson/first'] && $_SESSION['openid']['data']['namePerson/last'] ) {	
		$vars['name_f'] = $_SESSION['openid']['data']['namePerson/first'];
		$vars['name_l'] = $_SESSION['openid']['data']['namePerson/last'];
	} else if ($_SESSION['openid']['data']['namePerson'])
		list ($vars['name_f'], $vars['name_l']) = explode(" ",$_SESSION['openid']['data']['namePerson']);
	
	foreach ($ax_required as $ax) {
		$vars[openid_ax2field($ax)] = $_SESSION['openid']['data'][$ax];
		if ($ax == 'person/gender') 
			$vars['is_male'] = ($_SESSION['openid']['data'][$ax] == "M")?1:0;
	}
	$vars['pass'] = $vars['pass0'] = $vars['pass1'] = generate_password($vars);
	$vars['email'] = $email;
	$vars['login'] = generate_login($vars);
	
	if ($GLOBALS['_LANG_SELECTED'] != get_default_lang())
		$vars['selected_lang'] = $GLOBALS['_LANG_SELECTED'];

	$member_id = $db->add_pending_user($vars);
	$openid = $db->escape($_SESSION['openid']['identity']);
	$db->query("UPDATE {$db->config['prefix']}members SET openid = '$openid' WHERE member_id = '$member_id' LIMIT 1");
	$db->log_error("openid: Created aMember account for OpenID user ($openid) - ".print_r($vars,1));
		
	$is_affiliate = '0'; //only member newsletters
	if ($db->get_signup_threads_c($is_affiliate))
			$db->subscribe_member ($member_id, $is_affiliate);

	// Now add OpenID product subscripton, if set
	if ($this_config['newaccountproduct']) {
		
		$openid_product = &get_product($this_config['newaccountproduct']);
		$openid_payment = array(
		'member_id' => $member_id,
		'product_id' => $openid_product->config['product_id'],
		'completed' => 0,
		'paysys_id' => 'free',
		'begin_date' => $begin_date = date('Y-m-d'),
		'expire_date' => $openid_product->get_expire($begin_date)
		);
		$db->add_payment($openid_payment);
		if ($testmode == 1) $db->log_error("openid: Added subscription (product #{$this_config['newaccountproduct']} ) for OpenID user ($openid), login = {$vars['login']}");

		// Now go to thanks page...
		$payment_id = $GLOBALS['_amember_added_payment_id'];
		$vcode = md5($payment_id . $begin_date . $member_id);
		header("Location: ".$config['root_url']."/plugins/protect/openid/thanks.php?payment_id=$payment_id&vcode=$vcode");
		exit();
	
	}
	
	// Account only - go to member page...
	header("Location: ".$config['root_url']."/member.php");
	exit();
}

////
// Returns AX to field mappings
function openid_ax2field($q=''){

	$ax_array = array(
		'namePerson/friendly'     => 'nickname',
		'birthDate'               => 'birthday',
		'person/gender'           => 'gender',
		'contact/postalCode/home' => 'zip',
		'contact/country/home'    => 'country',
		'pref/language'           => 'language',
		'pref/timezone'           => 'timezone'
		);
	if ($q)
		return $ax_array[$q];
	else
		return $ax_array;
}

////
// Creates an 'openid' SQL field in the amember member table (if not already there)
function openid_sql_field(){
    global $config, $db, $plugin_config;
	$this_config = $plugin_config['protect']['openid'];
	$testmode = $this_config['testmode'];
    
	$mt = $db->config['prefix'] . 'members';
    $q = $db->query("SELECT * FROM $mt LIMIT 1");
    $i = 0;
    while ($i<mysql_num_fields($q)){
    	if ($meta = mysql_fetch_field($q, $i)){
			if (strcasecmp($meta->name, 'openid') == 0) {
				$db->log_error("OpenID: openid sql field already exists.");
				return;
			}
		}
		$i++;
    }
	
    // actually add field
    mysql_query($s = "ALTER TABLE $mt ADD openid VARCHAR(255) NOT NULL");
	if (mysql_errno())
		$db->log_error("OpenID: Couldn't add field - mysql error: " . mysql_error());
	else {
		@mysql_query($s = "ALTER TABLE $mt DROP INDEX openid");
		@mysql_query($s = "ALTER TABLE $mt ADD INDEX (openid)");
		$db->config_set('protect.openid.sqlupdated',1,0);
	}
	return;
}

function openid_rebuild(&$members){
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['openid'];
    /// some actions when admin click aMember CP -> Rebuild Db
    /// it should compare all records in your third-party
    /// database with aMember supplied-list ($members)
    /// Or you may just skip this hook
}
    
function openid_added($member_id, $product_id, $member) {
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['openid'];
    /// It's a most important function - when user subscribed to 
    /// new product (and his subscription status changed to ACTIVE
    /// for this product), this function will be called
    /// In fact, you should add user to database here or update
    /// his record if it is already exists (it is POSSIBLE that
    /// record exists)
}

function openid_updated($member_id, $oldmember, $newmember) {
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['openid'];
    /// this function will be called when member updates
    /// his profile. If user profile is exists in your 
    /// database, you should update his profile with 
    /// data from $newmember variable. You should use
    /// $oldmember variable to get old user profile - 
    /// it will allow you to find original user record.
    /// Don't forget - login can be changed too! (by admin)
}

function openid_deleted($member_id, $product_id, $member) {
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['openid'];
    /// This function will be called when user subscriptions
    /// status for $product_id become NOT-ACTIVE. It may happen
    /// if user payment expired, marked as "not-paid" or deleted
    /// by admin
    /// Be careful here - user may have active subscriptions for 
    /// another products and he may be should still in your 
    /// database - check $member['data']['status'] variable
}

function openid_removed($member_id, $member) {
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['openid'];
    /// This function will be called when member profile 
    /// deleted from aMember. Your plugin should delete 
    /// user profile from database (if your application allows it!), 
    /// or it should just disable member access if your application
    /// if application doesn't allow profiles deletion
}

?>