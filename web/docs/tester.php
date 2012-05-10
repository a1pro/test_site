<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 1);
/**
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Run-time enviroment tester
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3419 $)
*                                                                                 
*/


function ic_system_info()
{
  $thread_safe = false;
  $debug_build = false;
  $cgi_cli = false;
  $php_ini_path = '';

  ob_start();
  phpinfo(INFO_GENERAL);
  $php_info = ob_get_contents();
  ob_end_clean();

  foreach (split("\n",$php_info) as $line) {
    if (eregi('command',$line)) {
      continue;
    }

    if (eregi('thread safety.*(enabled|yes)',$line)) {
      $thread_safe = true;
    }

    if (eregi('debug.*(enabled|yes)',$line)) {
      $debug_build = true;
    }

    if (eregi("configuration file.*(</B></td><TD ALIGN=\"left\">| => |v\">)([^ <]*)(.*</td.*)?",$line,$match)) {
      $php_ini_path = $match[2];

      //
      // If we can't access the php.ini file then we probably lost on the match
      //
      if (!@file_exists($php_ini_path)) {
    $php_ini_path = '';
      }
    }

    $cgi_cli = ((strpos(php_sapi_name(),'cgi') !== false) ||
        (strpos(php_sapi_name(),'cli') !== false));
  }

  return array('THREAD_SAFE' => $thread_safe,
           'DEBUG_BUILD' => $debug_build,
           'PHP_INI'     => $php_ini_path,
           'CGI_CLI'     => $cgi_cli);
}

function ioncube_will_work(&$what_to_do){
    $sys_info = ic_system_info();
    if ($sys_info['THREAD_SAFE'] && !$sys_info['CGI_CLI']) 
        return "(reason: multi-thread PHP build)";
    if ($sys_info['DEBUG_BUILD']) 
        return "(reason: debug PHP build)";

    if (ini_get('safe_mode')) {
        $what_to_do = "Ask your provider to enable safe_mode in php.ini file";
        return "(safe_mode enabled in php.ini)";
    } elseif (!ini_get('enable_dl')) {
        $what_to_do = "Ask your provider to enable safe_mode in php.ini file";
        return "(enable_dl disabled in php.ini)";
    } elseif (!is_dir($pp=realpath(ini_get('extension_dir')))) {
        $what_to_do = "Ask your provider to create folder: $pp";
        return "(extension_dir setting in php.ini is incorrect - points to not-existing folder)";
    } elseif (preg_match('/\bdl\b/', ini_get('disable_functions'))){
        $what_to_do = "Ask your provider to allow 'dl' PHP function execution";
        return "(dl function disabled in php.ini - disable_functions parameters set)";
    }

    $_u = php_uname();
    $_os = substr($_u,0,strpos($_u,' '));
    $_os_key = strtolower(substr($_u,0,3));

    $_php_version = phpversion();
    $_php_family = substr($_php_version,0,3);

    $_loader_sfix = (($_os_key == 'win') ? '.dll' : '.so');

    $_ln_old="ioncube_loader.$_loader_sfix";
    if (($os_key == 'lin') || ($os_key == 'fre')){
        $what_to_do = "Please get loader for $_php_family for $_os (file $_ln_old) 
        <a href='http://www.ioncube.com/loaders/' target=_top>here</a> 
        and upload it to /amember/ioncube/ folder";
    }
}
function ioncube_is_installed(){
    echo "Testing whether your system has ionCube Loader in php.ini...";
    $res = extension_loaded('ionCube Loader');
    print $res ? "OK" : "Failed";
    print "<br />";
    return $res;
}
function zend_is_installed(){
    echo "Testing whether your system has Zend Optimizer in php.ini...";
    $res = extension_loaded('Zend Optimizer');
    print $res ? "OK" : "Failed";
    print "<br />";
    return $res;
}
function test_ioncube_loading(){
    echo "Testing whether your system can load ionCube Loader dynamically...";
    $err = ioncube_will_work($what_to_do);
    print ($err=='' && phpversion() < '5.2') ? "OK" : "<font color=red>Failed $err</font>";
    print "<br />";
    return ($err=='' && phpversion() < '5.2');
}

if (phpversion() < "4.0.6"){
    print "aMember requires PHP version 4.0.6 or later. We recommend you to upgrade
    to <a href='http://www.php.net/downloads.php' target=_blank>latest version</a>";
    exit();
} elseif (ioncube_is_installed()) {
    $enc = "ionCube Loader installed (v.".@ioncube_loader_version().")";
} elseif (zend_is_installed()){
    $enc = "Zend Optimizer installed";
} elseif (test_ioncube_loading()){
    $enc = "ionCube dynamic loading possible";
} else {
}

print "<b>Testing results:</b><br />";
if ($enc){
    print "No additional configuration required and aMember will work on your 
    hosting using the following loading method: <b>$enc</b><br /><br />";
} else {
    print "Unfortunately, no available loaders found in your system and
    some additional configuration required. Please 
    contact your hosting support and ask them to do <b>ONE</b> from the 
    following:<ul>
<li> Disable PHP safe_mode;
<br />OR
<li> Install free <a href='http://www.zend.com/store/products/zend-optimizer.php' target=_blank>Zend Optimizer</a>
<br />OR
<li> Install free <a href='http://www.ioncube.com/loaders/' target=_blank>ionCube Loader</a>
</ul>
    ";
}

if (!function_exists('mysql_connect')){
    print "<br /><font color=red><b>Additionally, PHP has no MySQL support 
    compiled-in</b></font>, please ask your hosting provider to add it.<br /><br />";
}


$version = phpversion() . " (" . php_sapi_name() . ")";
$safe_mode = ini_get('safe_mode') ? 'Enabled' : 'Disabled';
$enable_dl = ini_get('enable_dl') ? 'Enabled' : 'Disabled';
$extension_dir = ini_get('extension_dir');

$ext = array_diff(get_loaded_extensions(), array('standard', 'overload', 'pcre',
    'session', 'com', 'tokenizer', 'apache'));
$extensions = join(', ', $ext);


if (file_exists("/usr/bin/curl"))
    $curl_bin = "/usr/bin/curl";
elseif (file_exists("/usr/local/bin/curl"))
    $curl_bin = "/usr/local/bin/curl";
elseif (in_array('curl', $ext))
    $curl_bin = "Not found, but curl PHP extension is available";
else 
    $curl_bin = "Not found, and curl PHP extension is not available";

$uname = php_uname();

print <<<CUT
    Additional information:
    <table align=left cellpadding=3 border=1 style='border-collapse: collapse'>
    <tr>
        <th>PHP version</th>
        <td>$version</td>
    </tr>
    <tr>
        <th>OS</th>
        <td>$uname</td>
    </tr>
    <tr>
        <th>safe_mode</th>
        <td>$safe_mode</td>
    </tr>
    <tr>
        <th>enable_dl</th>
        <td>$enable_dl</td>
    </tr>
    <tr>
        <th>extension_dir</th>
        <td>$extension_dir</td>
    </tr>
    <tr>
        <th>PHP Extensions</th>
        <td>$extensions</td>
    </tr>
    <tr>
        <th>CURL binary</th>
        <td>$curl_bin</td>
    </tr>
    </table>
CUT;

?>