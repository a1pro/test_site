<?php 
/*
*   Send lost password
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Send lost password page
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 4944 $)
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

$security_code = $vars['s'];

$member_code = split (":", $security_code);
$security_code = $member_code[1];
$member_code = intval($member_code[0]);

if ($config['safe_send_pass'] == '1' && isset ($security_code) && $security_code != '') {
    
    //if use safety password reminder
    
    $unix_timestamp = time();
    $q = $db->query("SELECT member_id, login, IF( ((UNIX_TIMESTAMP(securitycode_expire) - $unix_timestamp) > 0), '1', '0') AS valid_code
        FROM {$db->config[prefix]}members
        WHERE security_code='".$db->escape($security_code)."' AND member_id='".$member_code."'
        ");
   list($member_id, $login, $valid_code) = mysql_fetch_row($q);
   
   if ($member_id == '' || $login == '') {
    
    //if wrong security code

        $t->assign('message', _TPL_CHANGEPASSWORD_FAILED_USRNOTFOUND);
        $t->assign('login_page', $config['root_url'] . "/member.php");
        $t->display('changepass_failed.html');
        exit();

   }
   
   if ($valid_code == '0') {
    
    // security_code expired. clear security_code and exit

        $q = $db->query("UPDATE {$db->config[prefix]}members
                    SET security_code='', securitycode_expire=''
                    WHERE member_id='$member_id'
                    ");

        $t->assign('message', _TPL_CHANGEPASSWORD_FAILED_EXPIRED);
        $t->assign('login_page', $config['root_url'] . "/member.php");
        $t->display('changepass_failed.html');
         exit;
    
   } else {

        if (isset($_POST['do_change']) && $_POST['do_change'] == '1') {
            
            // save new password, clear security_code, register sessions and redirect to member.php
            
            $pass0 = $vars['pass0'];
            $pass1 = $vars['pass1'];
            
            if ($pass0 == $pass1 && strlen($pass0) >= $config['pass_min_length']) {
                
                //if user exists and passwords is right
                
                 $q = $db->query("UPDATE {$db->config[prefix]}members
                                SET security_code='', securitycode_expire=''
                                WHERE member_id='$member_id'
                                ");
            
                 $member = $db->get_user($member_id);
                 $member['pass'] = $pass0;
                 $member['security_code'] = '';
                 $member['securitycode_expire'] = '';
                 $q = $db->update_user($member_id, $member);
                 
                  
                 $_SESSION['_amember_login']  = $login;
                 $_SESSION['_amember_pass']   = $pass0;
                 $redirect = $config['root_url'] . "/member.php";
                 html_redirect("$redirect", 0, 'Redirect', _LOGIN_REDIRECT);
                 exit;
             
            } else {
    
                $t->assign('message', _TPL_CHANGEPASSWORD_FAILED_WRONGPASS);
                $t->assign('login_page', $config['root_url'] . "/sendpass.php?s=" . $member_code.":".$security_code);
                $t->display('changepass_failed.html');
                exit();
            
            }
            
        } else {
            
            //show change password form
            $t->assign('login', $login);
            $t->assign('code', $member_code.":".$security_code);
            $t->display('changepass.html');
            exit;
            
        }
    } //end $valid_code != '0'
    
} //end $config['safe_send_pass'] == '1' && isset ($security_code)


if (!strlen($login)) {
    $t->assign('title', _SENDPWD_FAILED_TITLE);
    $t->assign('text', 
        _SENDPWD_FAILED_TEXT . ".<br />\n" .
        sprintf(_SENDPWD_FAILED_TEXT_1, htmlentities($login)) 
        );
    $t->display('sendpass_result.html');
    exit();
};

$ul = $db->users_find_by_string($login, 'login', 1);

if (!count($ul)){
    $l = $db->escape($login);
    $q = $db->query("SELECT m.member_id, p.expire_date
        FROM {$db->config[prefix]}members m LEFT JOIN
            {$db->config[prefix]}payments p USING (member_id)
        WHERE m.email='$l'
        ORDER BY p.completed DESC, p.expire_date DESC
        LIMIT 1
        ");
    list($member_id, $expire) = mysql_fetch_row($q);
    
    $ul = $member_id ? array($db->get_user($member_id)) : array();
}
if ( count($ul) ){
    $u = $ul[0];
    $member_id = $u['member_id'];
    
    if ($config['safe_send_pass'] != '1') {
         
         //simple password remind method
        $t->assign('login',  $u['login']);
        $t->assign('pass',   $u['pass']);
        $t->assign('email',  $u['email']);
        $t->assign('name_f', $u['name_f']);
        $t->assign('name_l', $u['name_l']);
        $t->assign('user', $u);
        
        $et = & new aMemberEmailTemplate();
        $et->name = "send_pass";
        mail_template_user($t, $et, $u);
        
     } else {
        
        //send a security code
        $acceptedChars = 'azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN0123456789';
        $max = strlen($acceptedChars) - 1;
        $security_code = "";
        for($i=0; $i < 16; $i++) $security_code .= $acceptedChars{mt_rand(0, $max)};
        $security_code = $security_code . time();
        $security_code = md5($security_code);
        $security_code = substr($security_code, 0, 16);
        
        $hours = 48;
        $securitycode_expire = date("Y-m-d H:i:s", time() + $hours * 60 * 60);

       $t->assign('login',  $u['login']);
       $t->assign('email',  $u['email']);
       $t->assign('name_f', $u['name_f']);
       $t->assign('name_l', $u['name_l']);
       $t->assign('user', $u);
       $t->assign('code',   $member_id.":".$security_code);
       $t->assign('hours',  $hours);
       
       $et = & new aMemberEmailTemplate();
       $et->name = "send_security_code";
       mail_template_user($t, $et, $u);

       $q = $db->query("UPDATE {$db->config[prefix]}members
            SET security_code='".$db->escape($security_code)."', securitycode_expire='$securitycode_expire'
            WHERE member_id='$member_id'
       ");
      
     }
    
    $t->assign('title', _SENDPWD_OK_TITLE);
    $t->assign('text', _SENDPWD_OK_TEXT . ".<br />\n" . _SENDPWD_OK_TEXT_1 );
    $t->display('sendpass_result.html');
} else {
    $t->assign('title', _SENDPWD_FAILED_TITLE);
    $t->assign('text', 
        _SENDPWD_FAILED_TEXT . ".<br />\n" .
        sprintf(_SENDPWD_FAILED_TEXT_1, htmlentities($login)) 
        );
    $t->display('sendpass_result.html');
}

?>
