<?php
/*
*  Dummy index page
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: User's failed payment page
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2098 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/
$rd = dirname(__FILE__);

### check if config.inc.php was propertly copied (for setup.php)
if (@$_GET['a'] == 'cce'){
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', FALSE);
    header('Pragma: no-cache');     
    ini_set('display_errors', 1);
    error_reporting(E_ALL ^ E_NOTICE);
    if (!file_exists($rd.'/config.inc.php')){
        print "File amember/config.inc.php does not exist. Please <a href='javascript: history.back(-1)'>go back</a> and create config file as described.";
        exit();
    }        
    include($rd.'/config.inc.php');
    if (count($config) < 10) {
        print "File amember/config.inc.php is exist, but something went wrong. Database configuration was empty or cannot be read. Please remove amember/config.inc.php <a href='setup.php'>and repeat installation</a>.";
        exit();
    }
    //all ok - redirect
    $url = "$config[root_url]/setup.php?step=5";
    @header("Location: $url");
    html_redirect($url, 0, 'Installation successfull', 'Installation finished successfully');
    exit();
} 

#### regular config check
if (!file_exists($rd.'/config.inc.php')){
    print "aMember is not configured yet. Go to <a href=setup.php>configuration page</a>";
    exit();
}
include($rd.'/config.inc.php');

###############################################################################
##
##                             M  A  I  N
##
###############################################################################
$t = & new_smarty();
$error = '';
$vars = & get_input_vars();

$t->display("index.html");
?>