<?php 

function version_check($vercheck)
{
    $minver = str_replace(".","", $vercheck);
    $curver = str_replace(".","", phpversion());
    if($curver >= $minver){
        return true;
    } else {
        die("PHP version ".$vercheck." or greater is required to run aMember. Your PHP-Version is : ".phpversion().
        "<br>Please upgrade or ask your hosting to upgrade. PHP before $vercheck was buggy and vunerable.");
    }
}
version_check('4.1.0');  // if version not ok the script stopped and displays an errormsg

if (!function_exists('mysql_connect')){
    die("PHP on your webhosting has been compiled without MySQL support.<br>
        so aMember cannot be installed to your hosting, please contact your<br>
        hosting support to fix this problem.");
}


error_reporting(E_ALL ^ E_NOTICE);
ini_set("magic_quotes_runtime", 0);
// set_magic_quotes_runtime(0);
ini_set('display_errors', 1);
/***************************************************************************
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: The installation file
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5392 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 

  Web-based setup. Steps:
   0 - check for installed config.php, check if it writeable
     - main config.inc.php data form
   1 - check config.inc.php data
     - display mysql connection form
   2 - check mysql connection
     - check for tables installed
     - if installed, skip to step 4
     - if not installed ask to install it
   3 - install mysql db
   4 - plugins configuration (except MySQL)
   5 - save all config files

*/

/**
* Retrieve input vars, trim spaces and return as array
* @return array array of input vars (_POST or _GET)
*
*/
function get_input_vars(){
    $REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

    $vars = $REQUEST_METHOD == 'POST' ? $_POST : $_GET;
    foreach ($vars as $k=>$v){
        if (is_array($v)) continue;
        if (get_magic_quotes_gpc()) $v = stripslashes($v);
        $vars[$k] = trim($v);
    }
    return $vars;
}

function make_password($length=16){
    $vowels = 'aeiouy';
    $consonants = 'bdghjlmnpqrstvwxz';
    $password = '';
    
    $alt = time() % 2;
    srand(time());

    for ($i = 0; $i < $length; $i++) {
        if ($alt == 1) {
            $password .= $consonants[(rand() % 17)];
            $alt = 0;
        } else {
            $password .= $vowels[(rand() % 6)];
            $alt = 1;
        }
    }
    return $password;
}



function display_header($title){
    global $header_displayed;
    if ($header_displayed) return;
    $header_displayed++;
    print <<<EOF
<html>
<head>  
    <title>$title</title>
    <style>
        body, th, td {
            font-family: Verdana, sans-serif;
            font-size: 0.8em;
        }
        input, textarea {ldelim}
            font-family: Verdana, sans-serif;
            font-size: 1em;
        }
        .err {
            color: red;
            font-weight: bold;
        }
        .vedit {
            background-color: #F0F0F0;
        }
        .vedit td {
            padding: 10px;
            padding-left:  15px;
            background-color: #E0E0E0;
        }
        .vedit th {
            padding: 10px;
            padding-right: 15px;
            text-align: right;
            background-color: #C0B9C0;
            font-weight: normal;
        }
    </style>
</head>
<body bgcolor=#E0E0E0><center>
<h2 style='color: #707070'>$title</h2>
<table width=60% cellpadding=0><tr><td height=1 bgcolor=black></td></tr></table>
EOF;
}

function display_footer(){
    global $footer_displayed;
    if ($footer_displayed) return;
    $footer_displayed++;
    print <<<EOF
<table width=60% cellpadding=0><tr><td height=1 bgcolor=black></td></tr></table>
<small>&copy; <a href=http://cgi-central.net>CGI-Central, Inc.</a>, 2002-2006</small>
</center></body></html>
EOF;
}

function display_fatal($error){
    display_header();
    print "<FONT COLOR=red>Fatal Error: $error<B></B></FONT>";
    display_footer();
    exit();
}

function display_errors($errs){
    display_header("aMember Installation");
    print "<table border=0><tr><td><p class=err>";
    foreach ((array)$errs as $e)
        print "<LI><font color=red><b>$e</b></font>\n";
    print "</p></td></tr></table>";
}
function exit_errors($errs){
    if (!is_array($errs))
        $errs = array($errs);
    display_errors($errs);
print <<<EOF
        Please <a href="javascript: history.back(-1)">return</a> and fix these errors.
    <br /><br />
EOF;
    display_footer();
    exit();
}

function check_for_existance(){
    global $root_dir;
    $errors = array();
 
    $cf = "$root_dir/config.inc.php";
    if (file_exists($cf) && filesize($cf)){
        $errors[] = "File 'config.inc.php' in amember folder is already exists and non-empty. Please remove it or delete all content if you want to do full reconfiguration";
    } 
    if ($errors) {
        display_errors($errors);
        print <<<EOF
    After you fix these problems, click <a href="javascript: window.location.reload()">here</a> to refresh page.
    <br><br>
EOF;
        display_footer();
        exit();
    }
}


function check_for_writeable(){
    global $root_dir;
    $errors = array();
 
    $cf = "$root_dir/config.inc.php";

    if (!is_writeable($d = "$root_dir/data/")){
        $errors[] = "Directory '$d' is not writeable. Please <a href='http://manual.amember.com/How_To_Chmod' target=_blank>fix it</a>";
    }
    if (!is_writeable($d = "$root_dir/data/new_rewrite/")){
        $errors[] = "Directory '$d' is not writeable. Please <a href='http://manual.amember.com/How_To_Chmod' target=_blank>fix it</a>";
    }
    if (!is_writeable($d = "$root_dir/templates_c")){
        $errors[] = "Directory '$d' is not writeable. Please <a href='http://manual.amember.com/How_To_Chmod' target=_blank>fix it</a>";
    }
    if ($errors) {
        display_errors($errors);
        print <<<EOF
    After you fix these problems, click <a href="javascript: window.location.reload()">here</a> to refresh page.
    <br><br>
EOF;
        display_footer();
        exit();
    }
}

function display_main_config(){
    global $root_dir;
    $HTTP_HOST    = $_SERVER['HTTP_HOST'];
    $SERVER_ADMIN = $_SERVER['SERVER_ADMIN'];
    $REQUEST_URI  = $_SERVER['REQUEST_URI'];

    $root_url    = "http://$HTTP_HOST" . str_replace('/setup.php', '', $REQUEST_URI);
    $admin_email = $SERVER_ADMIN;
    $admin_login = 'admin';
    $admin_pass  = '';

    print <<<EOF
    <form method=post>
    <h3>Enter configuration parameters</h3>

    <small>You may modify these values later via the aMember Control Panel
    </small>

    <table class=vedit>
    <tr>
        <th><b>Root URL of script</b><br><small><b>Do not</b> place a trailing slash ( <b>/</b> ) at the end!
        <br>Please note that url must match your license.
        </small></th>
        <td><input type=text name=@ROOT_URL@ value="$root_url" size=30></td>
    </tr>
    <tr>
        <th><b>Secure (HTTPS) Root URL of script</b><br><small>
        Please keep default (not-secure) value if you are unsure.<br>
        No trailing slash ( <b>/</b> ) please!
        <br>Please note that url must match your license.
        </small></th>
        <td><input type=text name=@ROOT_SURL@ value="$root_url" size=30></td>
    </tr>
    <tr>
        <th><b>Admin Email</b><br><small>The address that alerts and other email should be sent to</small></th>
        <td><input type=text name=@ADMIN_EMAIL@ value='$admin_email' size=30></td>
    </tr>
    <tr>
        <th><b>Admin Login</b><br><small>Username for login to the Admin interface</small></th>
        <td><input type=text name=@ADMIN_LOGIN@ value='$admin_login' size=30></td>
    </tr>
    <tr>
        <th><b>Admin Password</b><br><small>Password for login to the Admin interface</small></th>
        <td><input type=text name=@ADMIN_PASS@ value='$admin_pass' size=30></td>
    </tr>
EOF;
if ('@TRIAL@' == '@'.'TRIAL@')
print <<<EOF
    <tr>
        <th colspan=2><div align=center><b>License</b><br><small>Enter the <b>Full</b> license key, including dashed lines ( == ) above and below!</small></center></th>
    </tr><tr>
        <td colspan=2><div align=center><textarea style='font-size: 10px; font-family: Helvetica, sans-serif;' type=text name='@LICENSE@' cols=120 rows=6></textarea></div></td>
    </tr>
EOF;
print <<<EOF
    </table>
    <br>
    <input type=submit value="&nbsp;&nbsp;&nbsp;&nbsp;Next&gt;&gt;&nbsp;&nbsp;&nbsp;">
    <input type=hidden name="@PAYMENT_PLUGINS@[]" value="free">
    <input type=hidden name="@PROTECT_PLUGINS@[]" value="php_include">
EOF;
if ('@LITE@' == ('@' . 'LITE@')) print <<<EOF
    <input type=hidden name="@PROTECT_PLUGINS@[]" value="new_rewrite">
EOF;
print <<<EOF
    <input type=hidden name="@PROTECT_PLUGINS@[]" value="htpasswd">
    <input type=hidden name=step value=1>
    </form>
EOF;
}

function check_main_config(){
    $vars = get_input_vars();

    $errors = array();
    if (!strlen($vars['@ROOT_URL@']))    $errors[] = "Please enter root url of script";
    if (!strlen($vars['@ROOT_SURL@']))   $errors[] = "Please enter secure root url of script (or keep DEFAULT VALUE - set it equal to Not-secure root URL - it will work anyway)";
    if (!strlen($vars['@ADMIN_EMAIL@'])) $errors[] = "Please enter admin email";
    if (!strlen($vars['@ADMIN_LOGIN@'])) $errors[] = "Please enter admin login";
    if (!strlen($vars['@ADMIN_PASS@']))  $errors[] = "Please enter admin password";

    if ('@TRIAL@' == '@'.'TRIAL@'){
    if (!strlen($vars['@LICENSE@'])) $errors[] = "Please enter license code";
    if (!preg_match('/===== .+?===== EN(F|D) OF LICENSE =====/s', $vars['@LICENSE@'])) $errors[] = "Please enter full license code (it should start and end with ======)";
    if (preg_match('/===== .+? \((.+?), (.+?), valid thru (.+?)\) =====/', $vars['@LICENSE@'], $regs)){
        $d = preg_quote($regs[1]);
        $sd = preg_quote($regs[2]);
        $exp = $regs[3];
        $u1 = parse_url($url=$vars['@ROOT_URL@']);
        $u2 = parse_url($surl=$vars['@ROOT_SURL@']);
        if (!preg_match($x = "/($d|$sd)\$/", $u1['host']))
            $errors[] = "Root URL '$url' doesn't match license domain";
        if (!preg_match("/($d|$sd)\$/", $u2['host']))
            $errors[] = "Secure Root URL '$surl' doesn't match license domain";
    }
    }
    if ($errors) {
        display_errors($errors);
        print <<<EOF
        Please <a href="javascript: history.back(-1)">return</a> and fix these errors.
    <br><br>
EOF;
        display_footer();
        exit();
    }
}

function get_hidden_vars(){
    $res = '';
    foreach ($_POST as $k=>$v){
      if ($k[0] == '@')
        if (is_array($v)) // array
            foreach ($v as $kk=>$vv)
             $res .= sprintf('<input type=hidden name="%s[]" value="%s">'."\n",
                htmlspecialchars($k), htmlspecialchars($vv));
        else
            $res .= sprintf('<input type=hidden name="%s" value="%s">'."\n",
                htmlspecialchars($k), htmlspecialchars($v));
    }
    return $res;
}

function display_mysql_form(){
    $hidden = get_hidden_vars();

    print <<<EOF
    <form method=post>
    <h3>Enter MySQL configuration parameters</h3>
    
    <small>You may edit <b>config.inc.php</b> if you need to make changes after Setup</small>

    <table class=vedit>
    <tr>
        <th><b>MySQL Host</b><br><small>Very often 'localhost'</small></th>
        <td><input type=text name='@DB_MYSQL_HOST@' value='localhost' size=30></td>
    </tr>
    <tr>
        <th><b>MySQL Database </b><br><small>Note: Setup does not create the database for you.<br>
        Use the default database created by your host or<br>
        create a new database, for example 'amember'</small></th>
        <td><input type=text name='@DB_MYSQL_DB@' value='$admin_email' size=30></td>
    </tr>
    <tr>
        <th><b>MySQL Username</b><br><small>MySQL Username</small></th>
        <td><input type=text name='@DB_MYSQL_USER@' value='$admin_login' size=30></td>
    </tr>
    <tr>
        <th><b>MySQL Password</b><br><small>MySQL Password</small></th>
        <td><input type=text name='@DB_MYSQL_PASS@' value='$admin_pass' size=30></td>
    </tr>
    <tr></tr>
    <tr>
        <th><b>MySQL Tables Prefix</b><br><small>If unsure, use the default value '<i>amember_</i>'<br></small></th>
        <td><input type=text name='@DB_MYSQL_PREFIX@' value='amember_' size=30></td>
    </tr>
    </table>
    <br>
    <input type=submit value="&nbsp;&nbsp;&nbsp;&nbsp;Next&gt;&gt;&nbsp;&nbsp;&nbsp;">
    <input type=hidden name=step value=2>
    $hidden
    </form>
EOF;
}

function check_mysql_form(&$db_installed){
    $db_installed = 0;
    $vars = $_POST;
    $errors = array();
    if (!strlen($vars['@DB_MYSQL_DB@']))   $errors[] = "Please enter mysql database name";
    if (!strlen($vars['@DB_MYSQL_USER@'])) $errors[] = "Please enter mysql username";
#    if (!strlen($vars['@DB_MYSQL_PASS@'])) $errors[] = "Please enter mysql password";
    if (!strlen($vars['@DB_MYSQL_HOST@'])) $errors[] = "Please enter mysql hostname";
    if ($errors) {
        display_errors($errors);
        print <<<EOF
        Please <a href="javascript: history.back(-1)">return</a> and fix these errors.
    <br><br>
EOF;
        display_footer();
        exit();
    }
    /// really connect
    $conn = @mysql_connect(
        $vars['@DB_MYSQL_HOST@'], 
        $vars['@DB_MYSQL_USER@'],
        $vars['@DB_MYSQL_PASS@']
    );
    if (!$conn)
        $errors = "Cannot connect to mysql (".mysql_error().")";
    if ($errors) {
        display_errors($errors);
        print <<<EOF
        Please <a href="javascript: history.back(-1)">return</a> and fix these errors.
    <br><br>
EOF;
        display_footer();
        exit();
    }
    $dbc = @mysql_select_db($vars['@DB_MYSQL_DB@']);
    if (!$dbc)
        $errors = "Cannot select database '" . $vars["@DB_MYSQL_DB@"] . "' (" . mysql_error() . ")";
    if ($errors) {
        display_errors($errors);
        print <<<EOF
        Please <a href="javascript: history.back(-1)">return</a> and fix these errors.
    <br><br>
EOF;
        display_footer();
        exit();
    }   
    /*
    $q = mysql_query("SELECT COUNT(*) FROM ".$vars['@DB_MYSQL_PREFIX@']."error_log", $conn);
    if (mysql_errno()) return;
    $q = mysql_query("SELECT COUNT(*) FROM ".$vars['@DB_MYSQL_PREFIX@']."members", $conn);
    if (mysql_errno()) return;
    $q = mysql_query("SELECT COUNT(*) FROM ".$vars['@DB_MYSQL_PREFIX@']."payments", $conn);
    if (mysql_errno()) return;
    $q = mysql_query("SELECT COUNT(*) FROM ".$vars['@DB_MYSQL_PREFIX@']."access_log", $conn);
    if (mysql_errno()) return;
    $q = mysql_query("SELECT COUNT(*) FROM ".$vars['@DB_MYSQL_PREFIX@']."cron_run", $conn);
    if (mysql_errno()) return;
    $q = mysql_query("SELECT COUNT(*) FROM ".$vars['@DB_MYSQL_PREFIX@']."products", $conn);
    if (mysql_errno()) return;
    */ // always install db
    $db_installed = 0;
}

function display_db_install_query(){
    $hidden = get_hidden_vars();

    print <<<EOF
    <form method=post>
    <h3>Continue installation?</h3>
    <div style="width: 50%; text-align: left;">aMember Setup Wizard is now ready to finish
    installation and create database tables. If database tables are 
    already created, aMember will intelligently modify its structure
    to match latest aMember version. Your existing configuration and 
    database records will not be removed.
    
    </div>
    <br>
    <input type=submit value="&nbsp;&nbsp;&nbsp;&nbsp;Next&gt;&gt;&nbsp;&nbsp;&nbsp;">
    <input type=hidden name=step value=3>
    $hidden
    </form>
EOF;
}


function create_mysql_tables(){
    $vars = $_POST;
    $conn = @mysql_connect(
        $vars['@DB_MYSQL_HOST@'], 
        $vars['@DB_MYSQL_USER@'],
        $vars['@DB_MYSQL_PASS@']
    );
    if (!$conn)
        exit_errors("Cannot connect to mysql (".mysql_error().")");
    $dbc = @mysql_select_db($vars['@DB_MYSQL_DB@']);
    if (!$dbc)
    	exit_errors("Cannot select database '" . $vars["@DB_MYSQL_DB@"] . "' (" . mysql_error() . ")");

    if (!is_readable("amember.sql"))
    	exit_errors("File [amember.sql] not found, make sure you've uploaded all files");
    $file = join('', file("amember.sql"));    
    $file = str_replace('@DB_MYSQL_PREFIX@', $vars['@DB_MYSQL_PREFIX@'], $file);
    $file = preg_replace('/^\s+\#(.*)$/m', '', $file);
    preg_match_all('/(CREATE TABLE\s+(.+?)\s+.+?|.+?);\s*$/ms', $file, $out);
    $vars['@CURL_PATH@'] = guess_curl();
    $prefix = $vars['@DB_MYSQL_PREFIX@'];

    $email_templates_created = email_templates_created($prefix);
    foreach ($out[0] as $sql){
        foreach ($vars as $k=>$v){
            if ($k == '@ADMIN_PASS@') {
                srand(time());
                $v = crypt($v);
            }
            if ($k == '@PAYMENT_PLUGINS@')
                $v = serialize($v);
            if ($k == '@PROTECT_PLUGINS@')
                $v = serialize($v);
            $k = mysql_escape_string($k);
            $v = mysql_escape_string($v);
            $sql = str_replace($k, $v, $sql);  
        }
        if (preg_match('/CREATE TABLE\s+(\w+)/', $sql, $regs)) {
            $tname = $regs[1];
            if (mysql_query("SELECT * FROM $tname LIMIT 1") && !mysql_errno()){
                continue; // SKIP TABLE CREATION
            }
        } elseif (preg_match("/INSERT INTO {$prefix}email_templates/", $sql) && $email_templates_created) {
            continue;
        } elseif (preg_match('/MODIFY\s+(\w+)\s+(.+);/', $sql, $regs)){
            $tname = $regs[1];
            $mreq  = $regs[2];
            if (preg_match('/FIELD\s+(\w+)\s+(.+)/', $mreq, $regs)){
                $field = $regs[1];
                $q = mysql_query("SHOW FIELDS FROM $tname");
                $sql = '';
                while (list($f,$t,$null,$index,$add) = mysql_fetch_row($q)){
                    if ($f == $field) {
                        $sql = "ALTER TABLE $tname CHANGE $field $field $regs[2];";
                        break;
                    }
                }
                if (!$sql) 
                    $sql = "ALTER TABLE $tname ADD $field $regs[2];";
            } elseif (preg_match('/DROP_FIELD\s+(\w+)/', $sql, $regs)){
                $field = $regs[1];
                $q = mysql_query("SHOW FIELDS FROM $tname");
                $sql = '';
                while (list($f,$t,$null,$index,$add) = mysql_fetch_row($q)){
                    if ($f == $field) {
                        $sql = "ALTER TABLE $tname DROP $field";
                        break;
                    }
                }
            } elseif (preg_match('/(UNIQUE INDEX|INDEX)\s+(\w+)\s+.+/', 
            $mreq, $regs)){
                $index = $regs[2];
                $q = mysql_query("SHOW INDEX FROM $tname");
                while (list($t,$t,$index1) = mysql_fetch_row($q)){
                    if ($index1 != $index) continue;
                    mysql_query("ALTER TABLE $tname DROP INDEX $index");
                }
                $sql = "ALTER TABLE $tname ADD $regs[0]";
            } else { // unknown modify request
                print "unknown modify request";
                continue;
            }
        }
        $sql = preg_replace('/;\s*$/s', '', $sql);
        mysql_query($sql);
        if ($err = mysql_error()) 
	        exit_errors($err . "<br>SQL: <pre>$sql</pre>");
    }
	// insert countries
	foreach (array('countries', 'states') as $ff){
        $fn = "sql-$ff.sql";
        $q = mysql_query($s = "SELECT COUNT(*) FROM {$prefix}$ff");
        if ($e = mysql_error())
        	print "SQL Error: $e<br />\n";
        $c = mysql_fetch_row($q);
        $c = $c[0];
        if ($c) continue;
	    if (!is_readable($fn))
    		exit_errors("File [$fn] not found, make sure you've uploaded all files");
    	$sql = join('', file("$fn"));    
    	$sql = str_replace('@DB_MYSQL_PREFIX@', $vars['@DB_MYSQL_PREFIX@'], $sql);
        mysql_query($sql);
        if ($err = mysql_error()) 
	        exit_errors($err . " in [$fn]");
	}    
}

function guess_curl(){ // try to guess location of "curl" executable
    if (extension_loaded("curl")) return; // don't try
    $try = array(   
        '/usr/bin/curl',
        '/usr/local/bin/curl',
        '/usr/local/curl/bin/curl',
        '/win/curl.exe',
    );
    foreach ($try as $p){
        if (!@file_exists($p)) continue;
        $ret = @`$p -m 3 -k https://www.paypal.com/`;
        if (($ret != '') && preg_match('|<html|', $ret))
            return $p;
    }
    // nothing found
}


function get_config_files(){
    // return 2-elements array with content of 
    // config.inc.php config-plugins.inc.php
    global $root_dir;
    $vars = $_POST;

    foreach (array('@DB_PLUGINS@', '@PAYMENT_PLUGINS@', '@PROTECT_PLUGINS@')
    as $varname)
        foreach ((array)$vars[$varname] as $k=>$v)
            $vars[$varname][$k] = "'" . $v . "'";        

    $vars['@DB_PLUGINS@']      = join(',', (array)$vars['@DB_PLUGINS@']);
    $vars['@PAYMENT_PLUGINS@'] = join(',', (array)$vars['@PAYMENT_PLUGINS@']);
    $vars['@PROTECT_PLUGINS@'] = join(',', (array)$vars['@PROTECT_PLUGINS@']);

    srand(time());
    $vars['@ADMIN_PASS@'] = crypt($vars['@ADMIN_PASS@']);

    $f = file($fn = "$root_dir/config-dist.inc.php");
    if (!$f)
        display_errors(array("Cannot open $fn . Please upload this file"));
    $f1 = array();
    foreach ($f as $k1=>$v1) {
        foreach ($vars as $k=>$v)
            $v1 = str_replace($k, $v, $v1);
        $f1[] = $v1;
    }

    $f2 = array();
    return array(join('', $f1), join('', $f2));
}

function email_templates_created($prefix){
    $q = mysql_query($s = "SELECT COUNT(email_template_id)
        FROM {$prefix}email_templates");
    if (mysql_errno()) 
        return false;        
    $r = mysql_fetch_row($q);
    return $r[0] > 0;
}

function display_send_files_form($error_filename){
    display_header("Could not save config file");
    $hidden = get_hidden_vars();
    global $root_dir;
    $files = get_config_files();
    $conf = htmlentities($files[0]);
    print <<<CUT
    <p><b>Installation script is unable to save file <i>$error_filename</i></b>. <br>
    For complete setup you may download new config files to your computer and upload
    it back to your server.</p>

    <p>File <i>config.inc.php</i>. Upload it to your FTP:
    <br><i>$root_dir/config.inc.php</i><br>
    <form name=f1 method=post>
    <input type=submit value="Download config.inc.php">
    <input type=hidden name=step value=9>
    <input type=hidden  name=file value=0>
    $hidden
    </form>
    </p>

    <p><font color=red><b>Internet Expolorer sometimes rename <br>
    files when save it. For example, it may rename <i>config.inc.php</i><br>
    to <i>config[1].inc.php</i>. Don't forget to  fix it before uploading!
    </b></font></p>
<script language="JavaScript">
function copyc(){
    holdtext = document.getElementById('conf');
    Copied = holdtext.createTextRange();
    Copied.execCommand("Copy");
}    
</script>    
    
    
    Or, alternatively, you may copy&paste this text to amember/config.inc.php 
    file.<br/>
    <textarea rows="10" cols="80" readonly name="conf" id="conf"
    >$conf</textarea>
    <br>
    <a href="javascript:copyc()">Copy to clipboard</a>
    <br><br><br>
    
    <b>When the file is copied or created, 
    <a href="index.php?a=cce">click this link to continue</a>.

    <br><br><br>
    <br><br><br>
CUT;
    display_footer();
}

function send_config_file(){
    global $_POST;
    $file = $_POST['file'];
    $files = get_config_files();
    $filename = 'config.inc.php';
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Type: application/php");
    print $files[$file];
    exit();
}

function commit_changes(){
    global $root_dir;
    $vars = $_POST;

    create_mysql_tables();

    list($f1, $f2) = get_config_files();
    /// 
    $fp = @fopen($fn = "$root_dir/config.inc.php", 'wb');
    if (!$fp){
        display_send_files_form($fn);
        return;
    }
    fwrite($fp, $f1);
    fclose($fp);

    $HTTP_HOST   = $_SERVER['HTTP_HOST'];
    $REQUEST_URI = $_SERVER['REQUEST_URI'];
    header(sprintf("Location: http://%s%s?step=5", $HTTP_HOST, $REQUEST_URI));
    exit();
}

function display_thanks(){
    print "
    <p>Thank you for choosing aMember Pro. You can find
    the aMember Pro User's Guide <a href='http://manual.amember.com/'>here</a>.<br>
    Feel free to <a href='https://www.amember.com/support/'>contact CGI-Central</a> any time 
    if you have any issues with the script.
    <br><br>
    <b>Please review the following:</b><br>
    (You may want to bookmark this page)<br><br>
        <table width=40%><tr><td>
        <li><a href=admin/index.php target=_blank>Admin page (aMember Control Panel)</a>
        <li><a href=signup.php target=_blank>Signup page</a>
        <li><a href=member.php target=_blank>Registered Member page</a>
        <li><a href=login.php target=_blank>Login page (redirect to protected area)</a>
        </td></tr></table>
    </p>
    <br><br>
    
    <b>Before aMember is ready to use you will also need to do the following:</b><br><br>
      <table width=40%><tr><td>
      <li>Go to the <a href=admin/ target=_blank>Admin page</a> and Login. From the 
Main Menu on the left select <b>Utilities>Setup/Configuration</b> These settings may be used 
      to tune your installation. Enable any additional payment plugins that you need, then visit 
the plugins configuration section and enter your settings.<br><br>
      <li>Go to the <a href=admin/ target=_blank>Admin page</a> and
      add your products or subscription types.<br><br>
      You may prefer to refer to them as 'Products' or 'Subscription Types' depending upon the type 
      of business you are in. For example, you might choose to refer to a newsletter as a 
      'Subscription,' while you might call computer software or hardware a 'Product.' It's up to 
      you what you choose to call these aMember database records.<br><br>

      Remember, a 'Product' or 'Subscription Type' is just a different way to refer to the same thing, 
      which is an aMember database record.<br><br>

      You may specify the Subscription Type (free or paid signup, etc.) as you enter each product.<br><br>
      <b>It is important to set up at least one product!</b><br><br><br>
      <li>Determine whether or not your payment system(s) require any special configuration. If 
      so then you can refer to the 
      <a href='http://manual.amember.com/Payment_plugin_configuration' target=_blank>Installation Manual</a> 
      for more information, or contact CGI-Central for script customization services.<br><br>
      <li>Setup your protection for protected areas. 
      You can use .htaccess or PHP included files. To use the Cookie-Based login capability all 
      of your protected files must be PHP! 
      <br><br>
      <li>Check your installation by testing your<br>
      <a href=signup.php target=_blank>Signup Page</a>.<br><br>
      <li>Once you have everything set up and working correctly then you may wish to customize 
      some of the HTML templates. You should at least consider customizing the following templates:<br>
      
      <br>header.html
      <br>footer.html
      <br>thanks.html
      <br>sendpass.txt
      <br>signup_mail.txt
      <br><br>
      You can read more about templates customization <a href=\"http://manual.amember.com/Templates_Customization\">here</a>.
      <b>Tip:</b> 90% of aMember's look and feel can be customized by editing the CSS stylesheet 
      in the file <b>amember/templates/css/site.css</b>.
      </td></tr></table>

    <br>
    Feel free to contact <a href='https://www.amember.com/support/' target=_blank>CGI-Central Support</a> if you need any customization of the script.
    <br><br>

    You can also find a lot of useful info in the <a href='http://www.amember.com/forum/?from=setup' target=_blank>aMember Forum</a>.
    <br><br>
    ";        
}

//////////////////////// main //////////////////////////////////////////////
$root_dir = dirname(__FILE__);

$step = intval($_POST['step']);
if (!$step)
    $step = intval($_GET['step']);
if ($step != 5) check_for_existance();

while (1){ switch ($step){
    case 0: case '0': 
        display_header("aMember Setup: Step ".($step+1)." of 4");
        check_for_writeable();
        display_main_config();
        break;
    case 1: case '1':
        display_header("aMember Setup: Step ".($step+1)." of 4");
        check_main_config();
        display_mysql_form();
        break;
    case 2: case '2':
        display_header("aMember Setup: Step ".($step+1)." of 4");
        check_mysql_form($db_installed);
        if ($db_installed) { $step = 3; display_plugins_form(); break;}
        display_db_install_query();
        break;
    case 3: case '3':
        commit_changes();
        break;
    case 5: case '5':
        display_header("aMember Setup: Step ".($step-1)." of 4");
        display_thanks();
        break;
    case 9: case '9':
        send_config_file();
        break;

} break; }

display_footer();
