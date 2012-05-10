<?php 
/**
*  aMember Pro Config 
* 
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Plugins config
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4924 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*
*/
error_reporting(E_ALL & ~E_NOTICE); // Disable Notices

if (!defined('INCLUDED_AMEMBER_CONFIG')){
    define('INCLUDED_AMEMBER_CONFIG', 1);
    
    ini_set('session.bug_compat_warn', 0);
    ini_set('session.bug_compat_42', 1);
    
    @set_magic_quotes_runtime(0);
    global $config, $plugins, $plugin_config;
    
    $pc = array();
    $pc['db']   = '@DB_MYSQL_DB@';
    $pc['user'] = '@DB_MYSQL_USER@';
    $pc['pass'] = '@DB_MYSQL_PASS@';
    $pc['host'] = '@DB_MYSQL_HOST@';
    $pc['prefix'] = '@DB_MYSQL_PREFIX@';
    $pc['charset'] = ''; // replace with 'utf8' for example

    $config = array();
    $config['db']['mysql'] = $pc;
    $config['use_mysql_connect'] = 1;
    $config['root_dir']    = dirname(__FILE__); 

    if (!strlen(dirname(__FILE__))) {
        die("Script cannot detect path to the script automatically.<br />
        Please edit file <i>amember/config.inc.php</i> and replace line<br />
        <b>\$config['root_dir']    = dirname(__FILE__);</b><br />
        to
        <b>\$config['root_dir']    = '/home/user/public_html/amember';</b><br />
        Of course, <i>/home/user/public_html/amember<i> must be replaced to the <br />
        actual UNIX filesystem path (not URL!) to aMember folder.<br />
        ");
    }
    if (!defined('AMEMBER_ONLY_DB_CONFIG')){
        require_once("$config[root_dir]/rconfig.inc.php");
    }
}