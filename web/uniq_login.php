<?php 
/*
*  Small window. Checks for unique login (called from signup.inc.php)
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Unique login check page
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1678 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/
include('./config.inc.php');
$t = & new_smarty();

$vars = get_input_vars();
$login = $vars['login'];
$email = $vars['email'];
$pass  = $vars['word'];

$res = $db->check_uniq_login($login, $email, $pass, 1);
$t->assign('login', $login);
if (strlen($login)==0){
    $t->assign('error', true);
    $t->assign('title', _UNIQ_LOGIN_EMPTY_TITLE);
    $t->assign('text', _UNIQ_LOGIN_EMPTY_TEXT);
} elseif (!$res){
    $t->assign('error', true);
    $t->assign('title', _UNIQ_LOGIN_EXSTS_TITLE);
    $t->assign('text', 
    sprintf(_UNIQ_LOGIN_EXSTS_TEXT, htmlentities($login)) . ".<br />" .
    _UNIQ_LOGIN_EXSTS_TEXT_1 . "<br />" .
    sprintf(_UNIQ_LOGIN_EXSTS_TEXT_2, "<a href='member.php' target='blank'>", "</a>") . "<br />" 
    );
} else {
    $t->assign('title', _UNIQ_LOGIN_FREE_TITLE);
    $t->assign('text', 
        sprintf(_UNIQ_LOGIN_FREE_TEXT, htmlentities($login)) . "<br />".
        _UNIQ_LOGIN_FREE_TEXT_1 
    );
}

$t->display("uniq_login_popup.html");
?>
