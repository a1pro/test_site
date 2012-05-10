<?php 
//print('<pre>Request on Login Page:');print_r($_REQUEST);die();

/*
*   Members page, used to login. If user have only 
*  one active subscription, redirect them to url
*  elsewhere, redirect to member.php page
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Member display page
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3266 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

if (@$_GET['_test_'] != '' ||
  in_array(@$_GET['_test_'], array('file', 'root_url', 'root_surl'))){
    header("Content-type: text/javascript; charset=UTF-8");
    echo $_GET['_test_'];
    exit();
}

include('./config.inc.php');

//$config['root_url'] = "http://getaudiofromvideo.com/search/members";
$t = & new_smarty();
$_product_id = array('ONLY_LOGIN');
if (isset($_REQUEST['amember_redirect_url']))
    $_SESSION['amember_redirect_url'] = $_REQUEST['amember_redirect_url'];
function rcmp_begin_date($a, $b){
    return strcmp($b['begin_date'], $a['begin_date']);
}
include($config['plugins_dir']['protect'] . '/php_include/check.inc.php');
$payments = & $db->get_user_payments(intval($_SESSION['_amember_id']), 1);
usort($payments, 'rcmp_begin_date');
$now = date('Y-m-d');
$urls = array();
foreach ($payments as $k=>$v){
    if (($v['expire_date'] >= $now) && ($v['begin_date'] <= $now)) {
        $p = get_product($v['product_id']);
        $url = $p->config['url'];
        if (strlen($url)){
            $urls[] = $url;
        }
    }
}
//$_SESSION['amember_user'] = $_SESSION['_amember_user'];
//$_SESSION['amember_subscriptions'] = $_SESSION['_amember_subscriptions'];

if ($_SESSION['amember_redirect_url']) {
        $redirect = $_SESSION['amember_redirect_url'];
        unset($_SESSION['amember_redirect_url']);

} elseif (count(array_unique($urls)) == 1){
        $redirect = add_password_to_url($urls[0]);
} else {
    $redirect = $config['root_url'] . "/member.php";
}

/*if(!substr_count($redirect, 'getaudiofromvideo.com/search/')){
    $redirect = str_replace('getaudiofromvideo.com', 'getaudiofromvideo.com/search/', $redirect);
    $redirect = str_replace('search//', 'search/', $redirect);

}*/
html_redirect("$redirect", 0, 'Redirect', _LOGIN_REDIRECT);
?>
