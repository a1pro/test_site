<?php 

/*
*  Welcome email resend pag
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Signup Page
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3914 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/
include('./config.inc.php');

###############################################################################
##
##                             M  A  I  N 
##
###############################################################################
$t = & new_smarty();
$error = '';

$vars = & get_input_vars();

$member_id = intval($vars['member_id']);
$u = $db->get_user($member_id);
$v = $u['member_id'].$u['login'].$u['email'];

if ($config['verify_email'] && $member_id && (md5($v) == $vars['v'])){
    
    if ($u['email_verified'] >= 0){
        fatal_error(_RESEND_ERROR, 1);
    } elseif ($u['email_verified'] < -3){
        fatal_error(sprintf(_RESEND_ERROR2,"<a href='signup.php'>","</a>"),1,1);
    }        
    $u['email_verified']--;
    $db->update_user($u['member_id'], $u);
    
    foreach ($db->get_user_payments($member_id, 0) as $p)
	{
        $payment_id = $p['payment_id'];
		$code=$p['data']['email_confirm']['code'];
	}
    $md5 = md5($u['login'].$u['pass'].$member_id.$payment_id);
    $member_id_exists = 0;
    mail_verification_email($u, $config['root_url'] . "/signup.php?cs=" . $payment_id . "-" . $code);
    $t->assign('user', $u);
    $t->display("email_verify.html");
    exit();
} else {
    fatal_error(_RESEND_ERROR3, 1);
}


?>