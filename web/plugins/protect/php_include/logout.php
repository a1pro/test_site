<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: The installation file
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1907 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

if (!defined('INCLUDED_AMEMBER_CONFIG')){
    include("../../../config.inc.php");
}


setcookie('_amember_ru', '', time() - 3600 * 26, '/');
setcookie('_amember_rp', '', time() - 3600 * 26, '/');
$to_delete = array();

plugin_after_logout($_SESSION['_amember_user']);

foreach ($_SESSION as $k=>$v){
   if (!preg_match('/^_amember/', $k) || $k == '_amember_login_attempt_id') continue;
   $to_delete[] = $k;
}

foreach ($to_delete as $k){
   if (ini_get('register_globals'))
       session_unregister($k);
   else
       unset($_SESSION[$k]);
   unset($$k);
}


session_write_close();
//var_dump($_SESSION);die;
if (!$config['protect']['php_include']['redirect'])
    @($config['protect']['php_include']['redirect'] = 
            'http://' . $_SERVER['HTTP_HOST'] . '/');

$redirect = $config['protect']['php_include']['redirect'];
/*if(substr_count($redirect, 'getaudiofromvideo.com/search/')>0){
    }else{
        $redirect = str_replace('getaudiofromvideo.com', 'getaudiofromvideo.com/search/', $redirect);
    }*/
html_redirect($redirect, 1, 'Logout', 'Logged out');
?>
