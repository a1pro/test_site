<?php
///home/getaudio/public_html/members/plugins/protect/php_include/reload_if_cookie.inc.php
include_once('/home/getaudio/public_html/members/config.inc.php');
include_once('/home/getaudio/public_html/members/plugins/protect/php_include/reload_if_cookie.inc.php');
$_BASEPATH_DIR = "/home/getaudio/public_html/";
define('AUDIOPATH',$_BASEPATH_DIR.'/audio/');
define('VIDEOPATH', $_BASEPATH_DIR.'/video/');
define('YOUTUBEDL', $_BASEPATH_DIR."/");
$_BASEPATH_WEB = "/";
$t = & new_smarty();
//print_r($_SERVER);
if(isset($_SERVER['HTTPS'])){
$base_url = "https://www.getaudiofromvideo.com/";
$cdn_url = "https://gafv-primesitemedia.netdna-ssl.com/";
}else{
$base_url = "http://www.getaudiofromvideo.com/";
$cdn_url = "http://gafv.primesitemedia.netdna-cdn.com/";
}
$_SESSION['base_url'] =$base_url;
$_SESSION['cdn_url'] =$cdn_url;

	if(isset($_SESSION['_amember_user'])){
		$amember_user = $_SESSION['_amember_user'];
		$amember_subscription = $_SESSION['_amember_subscriptions'];
		$t->assign('amember_user', $amember_user);
	}
	
    if(isset($amember_user['member_id']) && $amember_user['member_id']>0){
        $is_user_logged = true;
		$is_logged =1;
    }else{
        $is_user_logged = false;
		$is_logged =0;
    }
	
    if(isset($amember_subscription) && count($amember_subscription) != 0){
        $has_active_subscriptions = true;
		$has_subscriptions =1;
    }else{
        $has_active_subscriptions = false;
		$has_subscriptions =0;
    }

$t->assign('base_url', $base_url);
$t->assign('cdn_url', $cdn_url);
$t->assign('is_user_logged', $is_logged);
$t->assign('has_active_subscriptions', $has_subscriptions);
$t->display('member_header.html');

?>
