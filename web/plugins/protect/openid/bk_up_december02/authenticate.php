<?php 
/**
 *  OpenID v1.1
 *  Copyright 2010 (c) R Woodgate
 *  All Rights Reserved
 *
 **/
require_once("../../../config.inc.php");
require_once("openid.php");
global $db, $config, $plugin_config;
$this_config = $plugin_config['protect']['openid'];
$testmode = $this_config['testmode'];
$ax_required = array_merge(array_filter((array)$this_config['ax_required']),array('contact/email'));
$ax_optional = array_merge(array_filter((array)$this_config['ax_optional']),array('namePerson','namePerson/first','namePerson/last'));
$ax_optional = array_diff($ax_optional,$ax_required);
$vars = get_input_vars();
try {
    if(!isset($vars['openid_mode'])) {
        if(isset($vars['openid_identifier'])) {
			$openid = new LightOpenID;
			$openid->required = $ax_required;
			$openid->optional = $ax_optional;
            $openid->identity = $vars['openid_identifier'];
			if ($testmode ==1) $db->log_error("openid: ax_required = ".print_r($ax_required,1).", ax_optional = ".print_r($ax_optional,1));
			//$openid->returnUrl = $config['root_url']."/plugins/protect/openid/authenticate.php";
            header('Location: ' . $openid->authUrl());
			exit;
        }

	} elseif($vars['openid_mode'] == 'cancel') {
        if ($testmode == 1) $db->log_error("openid: User has canceled authentication via {$vars['openid_identifier']}!");
		$_SESSION['openid']['error'] = "Your login attempt has been cancelled...";
		
	} else {
        $openid = new LightOpenID;
        if ($openid->validate() ) {
			$_SESSION['openid']['identity'] = $openid->identity;
			$_SESSION['openid']['data'] = $openid->getAttributes();
			if ($testmode == 1) $db->log_error("openid: User validated - ".print_r($_SESSION['openid'],1));
		}
    }
} catch(ErrorException $e) {
	$db->log_error("openid: Error authenticating user - ".$e->getMessage());
	$_SESSION['openid']['error'] = $e->getMessage();
}
$go = ($vars['do'] == 'new')?'/plugins/protect/openid/signup.php':'/login.php';
header("Location: ".$config['root_url'].$go);
exit;
?>
