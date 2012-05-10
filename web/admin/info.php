<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Info / PHP
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2097 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

require "../config.inc.php";
$t = new_smarty();
require "login.inc.php";


$vars = get_input_vars();
extract($vars, EXTR_OVERWRITE);

check_demo();

$t->assign('config', $config);
$t->assign('plugins', $plugins);
$t->assign('plugins_config', $plugins_config);
$t->assign('root_url', $config['root_url']);
$t->display("admin/info.html");

?>
