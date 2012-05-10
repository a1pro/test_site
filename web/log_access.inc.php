<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Log user access if it logged in via .htpasswd
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2050 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

require_once dirname(__FILE__) . '/config.inc.php';

//find out member_id
if (strlen($_SESSION['_amember_user']['login'])){ // found user session
    $member_id = intval($_SESSION['_amember_user']['member_id']);
} else {
    if (!strlen($login))
        $login = $_SERVER['PHP_AUTH_USER'];
    if (!strlen($login))
        $login = $_SERVER['REMOTE_USER'];
    $ul = $db->users_find_by_string($login, 'login', 1);
    if (!count($ul)) {
        $db->log_error("Unknown user was logged in: '$login'. Look like protection isn't setup correctly");
        exit();
    }
    $member_id = $ul[0]['member_id'];
}
// log access
$db->log_access($member_id);

if (!$_SESSION['ip_checked']){ //skip if already checked
    if ($db->check_multiple_ip($member_id, $config['max_ip_count'], 
                    $config['max_ip_period'], $_SERVER['REMOTE_ADDR'])){ //limit exceeded
        member_lock_by_ip($member_id);
    }                    
    $_SESSION['ip_checked'] = 1;
}
session_write_close();

?>
