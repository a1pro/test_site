<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Email test
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3905 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

require_once "../config.inc.php";
$t = new_smarty();
require_once "login.inc.php";
require_once ROOT_DIR . "/includes/phpmailer/class.phpmailer.php";
check_demo();

$vars = get_input_vars();

function email_test($vars=''){
    global $db, $t, $error, $config;
    settype($vars, 'array');
    $action         = $vars['a'];
    $email          = $vars['e'];
    $method         = $vars['m'];
    $smtp_host      = $vars['h'];
    $smtp_user      = $vars['u'];
    $smtp_pass      = $vars['pass'];
    $smtp_port      = $vars['port'];
    $smtp_security  = $vars['s'];
    $sendmail_path  = $vars['p'];
    
    if (!check_email($email))
        $error[] = 'Invalid email';
    if ($method != 'mail' && $method != 'smtp' && $method != 'sendmail')
        $error[] = 'Invalid method';

    if ($method == 'smtp' && $smtp_host == '')
        $error[] = 'Invalid SMTP host';
    if ($method == 'sendmail' && $sendmail_path == '')
        $error[] = 'Invalid sendmail path';
    
    if ($error) {
        $t->assign ('error', $error);
        $t->assign ('a', 'info');
        $t->assign ('text_message', '');
        $t->display('admin/email_test.html');
        exit;
    }

    $name = '';
    if (preg_match('/"*(.*?)"*\s*\<(.+?)\>\s*$/', $email, $regs)){
        $email_only = $regs[2];
        if ($name == '') $name = $regs[1];
    } else
        $email_only = $email;

    $mail = & new PHPMailer();    
    $mail->From     = $config['admin_email_from'] ? $config['admin_email_from'] : $config['admin_email'];
    $mail->FromName = $config['admin_email_name'] ? $config['admin_email_name'] : $config['site_title'];
    if ($method == 'smtp'){
        if (preg_match('/^(.+?):(.+?)@(.+)$/', $smtp_host, $regs)){
            $mail->Username = $regs[1];
            $mail->Password = $regs[2];
            $mail->SMTPAuth = true;
            $mail->Host = $regs[3];
        } else {
            $mail->Host = $smtp_host;
            if($smtp_user && $smtp_pass){
                $mail->Username = $smtp_user;
                $mail->Password = $smtp_pass;
                $mail->SMTPAuth = true;
            }
        }
	if($smtp_port) $mail->Port = $smtp_port;
	$mail->SMTPSecure = $smtp_security;
        $mail->Mailer   = "smtp";
    }
    
    if ($method == 'sendmail'){
        $mail->Mailer   = "sendmail";
        $mail->Sendmail = $sendmail_path;
    }
    
    $mail->isHTML(true);
    $mail->Body = 'This is a test email. If you read this message the method you selected are valid.';
    $mail->Subject = "Email method verification ($method)";
    $mail->AddAddress($email_only, $name);
    
    if (!$mail->Send()) {
        $error[] = 'There was an error sending the email message';
        $error[] = $mail->ErrorInfo;
        $t->assign ('error', $error);
        $t->assign ('a', 'info');
        $t->assign ('text_message', '');
        $t->display('admin/email_test.html');
        exit;
    }
    
    
    $t->assign('error', $error);
    $t->assign ('a', '');
    $t->assign ('text_message', 'Verification email message has been just emailed to you. Did you receive this message?');
    $t->display('admin/email_test.html');
}

function display_info($vars=''){
    global $db, $t;
    settype($vars, 'array');
    
    $t->assign ('a', 'info');
    $t->assign ('text_message', 'Instruction of email settings.');
    $t->display('admin/email_test.html');
}

////////////////////////////////////////////////////////////////////////////
//
//                      M A I N
//
////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
$error = array();

//admin_check_permissions('browse_users');
switch (@$vars['a']){
    case 'info':
        display_info($vars);
        break;
    default:
        email_test($vars);
}
?>
