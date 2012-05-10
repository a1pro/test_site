<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");


function protection_check_htpasswd(){
    if (!in_array(php_sapi_name(), array('apache2filter', 'apache2handler', 'apache', 'cgi', 'cgi-fcgi')))
        return 'This method works under Apache webserver only';
}
function protection_check_mod_rewrite(){
    global $config;
    if (is_lite())
        return "This method of protection is not available in aMember Lite";
    if (!function_exists('virtual'))
        return "PHP must be compiled-in as Apache module to use this method";
    $ret = get_url($url = "$config[root_url]/plugins/protect/new_rewrite/tests/mod_rewrite/1.php",'',0,1);
    if (!strlen($ret))
        return "#Unable to fetch result of test from $url.";
    elseif ($ret != '2-second_file')
        return "RewriteRule doesn't work in .htaccess files on this server";
}
function protection_check_new_rewrite(){
    global $config, $plugins;
    if (is_lite())
        return "This method of protection is not available in aMember Lite";
    if (!in_array('new_rewrite', $plugins['protect']))
        return "new_rewrite protection module must be enabled at aMember CP -> Setup -> Plugins";
    if (!is_dir("$config[root_dir]/data/new_rewrite/"))
        return "please create folder $config[root_dir]/data/new_rewrite/";
    if (!is_writeable("$config[root_dir]/data/new_rewrite/"))
        return "please make folder $config[root_dir]/data/new_rewrite/ writeable for PHP scripts";
    $ret = get_url($url = "$config[root_url]/plugins/protect/new_rewrite/tests/mod_rewrite/1.php",'',0,1);
    if (!strlen($ret))
        return "#Unable to fetch result of test from $url.";
    elseif ($ret != '2-second_file')
        return "RewriteRule doesn't work in .htaccess files on this server";
}

function protection_check_auto_prepend_file(){
    global $config;
    $ret = get_url($url = "$config[root_url]/plugins/protect/new_rewrite/tests/auto_prepend_file/1.htm",'',0,1);
    if (!strlen($ret))
        return "#Unable to fetch result of test from $url.";
    elseif ($ret != '2-second_file1-first_file')
        return "php_value auto_prepend_file directive doesn't work in .htaccess files on this server";
    if (is_lite())
        return "This method of protection is not available in aMember Lite";
}


function get_protection_methods(){
    $methods = array(
    'htpasswd' => array(
        'name' => 'htpasswd',
        'title' => 'htpasswd',
        'disabled' => protection_check_htpasswd(),
        'description' => 'Usual htpasswd protection. Popup login box will be
        displayed when member enters into protected area, and it is impossible to
        avoid this box with this method of protection. Any type of content can be protected.
        Special configuration required to track customers access and use account sharing protection
        '
    ),
    'new_rewrite' => array(
        'name' => 'new_rewrite',
        'title' => 'new_rewrite',
        'disabled' => protection_check_new_rewrite(),
        'description' => 
        'Most advanced method of protection - user will see your custom HTML login form.
        This method can protect ANY type of content, and will NOT cause problems with
        complex PHP scripts residing in protected area.<br />
        Access sharing prevention works automatically with this plugin.'
    ),
    'mod_rewrite' => array(
        'name' => 'mod_rewrite',
        'title' => 'php_include+mod_rewrite',
        'disabled' => protection_check_mod_rewrite(),
        'description' => 
        'An advanced method of protection - user will see your custom HTML login form.
        This method can protect any type of content, but may cause problems with
        complex PHP scripts residing in protected area. Remember, you do not need to
        specially protect third-party scripts if you have enabled aMember integration
        plugin for it. For example, if you are using vBulletin-integration plugin, you do not
        need to protect vBulletin using this tool - just use vBulletin permission management 
        controls.<br />
        Access sharing prevention works automatically with this plugin.'
    ),
    'auto_prepend_file' => array(
        'name' => 'auto_prepend_file',
        'title' => 'php_include+auto_prepend_file',
        'disabled' => protection_check_auto_prepend_file(),
        'description' => 
        'This method can protect only PHP and HTML files, not images. It may cause problems with
        SSI files (files having .shtml extenstion). This method does not protect
        images or downloadable content in the protected folder - only PHP and HTML files.<br />
        Access sharing prevention works automatically with this plugin.'
    ),
    'php_include' => array(
        'name' => 'php_include',
        'title' => 'php_include',
        'disabled' => 0,
        'description' => 
        'This method can protect only PHP files and applications, nothing else.
        This method does not protect  images, HTML files or downloadable content 
        in the protected folder - it protects PHP files only.<br />
        Access sharing prevention works automatically with this plugin.<br />
        <i>You have to manually install this protection (edit your PHP files)</i>'
    )
    );
    return $methods;
}


function validate_htaccess($vars){
    if (!is_dir($vars['path'])){
        return "Path '$vars[path]' is not a folder";
    }
    $f = "$vars[path]/.htaccess";
    if (!file_exists($f) && !is_writable($vars['path'])){
        return "Folder $vars[path] is not a writable for the PHP script. Please <br />
        chmod this folder using webhosting control panel file manager or using your<br />
        favorite FTP client to 777 (write, read and execute for all)<br />
        After protection, please don't forget to chmod it back to 755.
        ";
    }
    if (file_exists($f) && 
        !preg_match('/^\s*#+ AMEMBER START #+.+AMEMBER FINISH #+\s*$/s',
            join('',file($f)))
    ){
        return "
        File $s exists and contains non-aMember code unmodifiable by aMember. 
        If you don't need this file, delete it and aMember will automatically create 
        a new aMember compatible .htaccess file to replace it.
        ";
    }

    if (file_exists($f) && !is_writeable($f)){
        return "File $f is not a writable for the PHP script. Please <br />
        chmod this file using webhosting control panel file manager or using your<br />
        favorite FTP client to 666 (write and read for all)<br />
        After protection, please don't forget to chmod it back to 644.
        ";
    }
}

function write_files($vars, $files){
    foreach ($files as $fname => $content){
        $f = "$vars[path]/$fname";
        $fh = fopen($f, 'w');
        if (!$fh)
            return "Cannot open file $f for write";
        fwrite($fh, $content);        
        if (!fclose($fh)) 
            return "Cannot write file $f";
    }
}

function protect_htpasswd($vars, &$files){
    global $config;

    if ($err = validate_htaccess($vars))
        return $err;

    if ($vars['product_id_all']){
        $require = "valid-user";
    } else {
        $require = ' group ';
        foreach ($vars['product_id'] as $i) $require .= " PRODUCT_$i";
    }
    $files['.htaccess'] = <<<CUT
########### AMEMBER START #####################
AuthType Basic
AuthName "Members Only"
AuthUserFile {$config[root_dir]}/data/.htpasswd
AuthGroupFile {$config[root_dir]}/data/.htgroup
Require $require
########### AMEMBER FINISH ####################
CUT;

    if ($err = write_files($vars, $files))
        return $err;
}



function protect_new_rewrite($vars, &$files){
    global $config;
    if ($err = validate_htaccess($vars))
        return $err;
    $line = "";
    if ($vars['product_id_all']){
        $line = "
## allow access for any active subscription
RewriteCond %{HTTP_COOKIE} amember_nr=([a-zA-Z0-9]+)
RewriteCond $config[root_dir]/data/new_rewrite/%1 -f 
RewriteRule ^(.*)\$ - [L]
";        
        $products = 'any';
    } else {
        foreach ($vars['product_id'] as $pid){
            $line .= "
## allow access for product #$pid        
RewriteCond %{HTTP_COOKIE} amember_nr=([a-zA-Z0-9]+)
RewriteCond $config[root_dir]/data/new_rewrite/%1-$pid -f 
RewriteRule ^(.*)\$ - [L]
";        
        }
        $products = join(',', $vars['product_id']);
    }        
        
    $files['.htaccess'] = <<<CUT
########### AMEMBER START #####################
Options +FollowSymLinks
RewriteEngine On
$line
## if user is not authorized, redirect to login page
# BrowserMatch "MSIE" force-no-vary
RewriteCond %{QUERY_STRING} (.+)
RewriteRule ^(.*)$ $config[root_url]/plugins/protect/new_rewrite/login.php?v=-$products&url=%{REQUEST_URI}?%{QUERY_STRING} [L,R]
RewriteRule ^(.*)$ $config[root_url]/plugins/protect/new_rewrite/login.php?v=-$products&url=%{REQUEST_URI} [L,R]
########### AMEMBER FINISH ####################
CUT;

    if ($err = write_files($vars, $files))
        return $err;
}

function protect_mod_rewrite($vars, &$files){
    global $config;
    if ($err = validate_htaccess($vars))
        return $err;
    $ru = parse_url($config['root_url']);
    if ($vars['product_id_all'])
        $line = "";
    else
        $line = '[E=PRODUCT_ID:' . join(';', $vars['product_id']) . ']';
    $files['.htaccess'] = <<<CUT
########### AMEMBER START #####################
Options +FollowSymLinks
RewriteEngine On
RewriteRule ^(.*)$ {$ru[path]}/plugins/protect/php_include/rewrite.php $line
########### AMEMBER FINISH ####################
CUT;

    if ($err = write_files($vars, $files))
        return $err;
}

function protect_auto_prepend_file($vars, &$files){
    global $config;
    if ($err = validate_htaccess($vars))
        return $err;
    if ($vars['product_id_all'])
        $line = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24";
    else
        $line = join(',', $vars['product_id']);
    $files['.htaccess'] = <<<CUT
########### AMEMBER START #####################
AddType application/x-httpd-php .html
AddType application/x-httpd-php .htm 
php_value auto_prepend_file $vars[path]/amember_protect.inc.php
########### AMEMBER FINISH ####################
CUT;
    

    $files['amember_protect.inc.php'] = <<<CUT
<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

\$_product_id = array($line); 
require_once '{$config[root_dir]}/plugins/protect/php_include/check.inc.php';
?>
CUT;
    if ($err = write_files($vars, $files))
        return $err;
}
function protect_php_include($vars, &$files){
    global $config;
    if ($vars['product_id_all'])
        $line = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24";
    else
        $line = join(',', $vars['product_id']);
    $s = <<<CUT
CUT;
    print <<<CUT
    <html><head>
    This method of protection is simple - your must edit your PHP files and
    include to TOP of each PHP file (or just into one which is included by 
    all other files) these lines of code:    
    <table bgcolor=gray><tr><td bgcolor=gray><pre>&lt;?php
\$_product_id = array($line); 
require_once '{$config[root_dir]}/plugins/protect/php_include/check.inc.php';
?&gt;</pre></td></tr></table>

    <br /><br />Click <a href='protect.php'>here</a> to continue...
CUT;
    $GLOBALS['protection_is_instruction']=1;
}

?>