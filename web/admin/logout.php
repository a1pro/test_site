<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin logout
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2926 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/
include('../config.inc.php');

admin_log("Logged-out");

unset($_SESSION['_admin_login']);
unset($_SESSION['_admin_pass']);
unset($_SESSION['amember_admin']);
session_write_close();
admin_html_redirect($config['root_url']."/admin/", 'Logout', 'Logged out', $target_top=true);
