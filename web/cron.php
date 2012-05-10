<?php 
/** 
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Cron run file
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4619 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/
set_time_limit(0);
ignore_user_abort(true);
require 'config.inc.php';

function is_running_from_commandline(){
    return ($_SERVER['REMOTE_ADDR'] == '') && ($_SERVER['REQUEST_METHOD'] == '');
}
function has_different_permissions($file){
    if (!file_exists($file))
        return false;
    return fileowner($file) != getmyuid();
}
    
function is_running_without_suexec(){
    global $config;
    return has_different_permissions("$config[root_dir]/data") ||
           has_different_permissions("$config[root_dir]/data/.htpasswd") ||
           has_different_permissions("$config[root_dir]/data/new_rewrite");
}

function get_lock_cron(){
	global $db;
	register_shutdown_function('release_lock_cron');
	return $db->query_one("SELECT GET_LOCK('cron_lock', 30)");
}
function release_lock_cron(){
	global $db;
	return $db->query("DO RELEASE_LOCK('cron_lock')");
}

/// if running from command-line, try to open aMember cron via HTTP
if (is_running_from_commandline() && is_running_without_suexec()){
    $db->log_error("Cron is running from command line, running [$config[root_url]/cron.php?ok] via curl...");
    $ret = get_url("$config[root_url]/cron.php?ok");
    if ($ret == "OK")
        $db->log_error("Cron job finished successfully with the following output: [$ret]");
    else
        $db->log_error("Cron job finished with the following error message: [$ret]");        
    exit();
}

$vars = get_input_vars();

if (!$config['use_cron']) 
    fatal_error(_CRON_ERROR);

if (isset($_GET['ok']))
    $db->log_error("cron.php started");
get_lock_cron();
check_cron();
release_lock_cron();
if (isset($_GET['ok']))
    $db->log_error("cron.php finished");
if (isset($_GET['ok']))
    print "OK";


