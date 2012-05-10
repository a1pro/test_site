<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Protect folders
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2926 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

include "../config.inc.php";
$t = new_smarty();
require "login.inc.php";
require "protection_methods.inc.php";

check_demo();
admin_check_permissions('protect_folders');

function get_folders(){
    global $db;
    $q = $db->query("SELECT * FROM {$db->config[prefix]}folders");
    $res = array();
    while ($a = mysql_fetch_assoc($q)){
        $a['files_content'] = $db->decode_data($a['files_content']);
        $res[ $a['folder_id'] ] = $a;
    }
    return $res;
}

function display_folders_list(){
    global $t;
    $t->assign('folders', get_folders());
    $t->display("admin/protect.html");
}

function display_add_form($vars, $err=''){
    global $config, $db, $t;

    if ($err) $err = (array)$err;

    if ($vars['path']){
        /// check folder exists
        if (!is_dir($vars['path'])){
            $err[] = "Path entered is incorrect - it is not exists or is not a folder;
            Press BACK browser button and choose another, valid folder";
        }
        /// check uniq
        $path = $db->escape($vars['path']);
        $q = $db->query("SELECT folder_id 
            FROM {$db->config[prefix]}folders
            WHERE path='$path'");
        list($c) = mysql_fetch_row($q);
        if ($c) {
            $err[] = "This path has been already protected, you cannot add it 
            again. Try to <a href='protect.php?action=edit&folder_id=$c'>Edit</a>
            it instead.
            ";
            
            $vars['path'] = ''; // show first page of Add form (path selection)
        }
    }



    $t->assign('error', $err);
    $t->assign('protection_methods', get_protection_methods());
    foreach ($db->get_products_list() as $p)
        $pl[$p['product_id']] = $p['product_id'] . ' - ' . $p['title'];
    $t->assign('products', $pl);

    $t->assign('path', $vars['path']);
    if ($vars['path']) 
        if ($vars['url'])
            $t->assign('url', $vars['url']);
        else
            $t->assign('url', $x=determine_url_from_path($vars['path']));
    $t->assign('method', $vars['method']);
    $t->assign('product_id', $vars['product_id']);
    $t->assign('product_id_all', $vars['product_id_all']);

    $t->display("admin/protect_form.html");
}

function display_edit_form($vars, $err=''){
    global $config, $db, $t;

    if ($err) $err = (array)$err;

    /// check folder exists
    if (!is_dir($vars['path'])){
        $err[] = "Path entered is incorrect - it is not exists or is not a folder;
        Press BACK browser button and choose another, valid folder";
    }
    /// check uniq
    $path = $db->escape($vars['path']);
    $q = $db->query("SELECT folder_id 
        FROM {$db->config[prefix]}folders
        WHERE path='$path'");
    list($c) = mysql_fetch_row($q);
    if (!$c) {
        $err[] = "This path is not already protected, you cannot edit it.
        ";
    }

    $t->assign('error', $err);
    $t->assign('protection_methods', get_protection_methods());
    if ($vars['path'])
        $t->assign('url', determine_url_from_path($vars['path']));
    foreach ($db->get_products_list() as $p)
        $pl[$p['product_id']] = $p['product_id'] . ' - ' . $p['title'];
    $t->assign('products', $pl);

    $t->assign('path', $vars['path']);
    $t->assign('url', $vars['url']);
    $t->assign('method', $vars['method']);
    $t->assign('product_id', $vars['product_id']);
    $t->assign('product_id_all', $vars['product_id_all']);

    $t->display("admin/protect_form.html");
}

function validate_add_form($vars){
    if (!$vars['url'])
        $err[] = "Please enter protected area URL";
    if (!$vars['method'])
        $err[] = "Please choose a protection method";
    if (!$vars['product_id'] && !$vars['product_id_all'] )
        $err[] = "Please choose product(s) to require";
    return (array)$err;
}

function validate_edit_form($vars){
    if (!$vars['url'])
        $err[] = "Please enter protected area URL";
    if (!$vars['method'])
        $err[] = "Please choose a protection method";
    if (!$vars['product_id'] && !$vars['product_id_all'] )
        $err[] = "Please choose product(s) to require";
    return (array)$err;
}




function determine_url_from_path($dir){
    global $config;
    $dir_delim_re = ( substr(php_uname(), 0, 7) == "Windows")  ? '{/|\\\}' : '{/}';
    $dir_delim = ( substr(php_uname(), 0, 7) == "Windows")  ? "\\" : "/";

    $dirs = preg_split($dir_delim_re, $dir, -1,  PREG_SPLIT_NO_EMPTY);

    $rdirs = array_reverse(preg_split($dir_delim_re, $config['root_dir'], -1, PREG_SPLIT_NO_EMPTY));

    $uu = parse_url($config['root_url']);
    $rurls = array_reverse(preg_split('{/}', $uu['path'], -1, PREG_SPLIT_NO_EMPTY));

    $c = 0;
    foreach ($rurls as $i) { // go down by url, delete elements from path
        if ($rdirs[0] == $i) 
            array_shift($rdirs);
        else
            break;
        $c++;
    }
    if ($c != count($rurls)) 
        return '';
    
    // $rdirs now contains path to server root folder
    $rdirs = array_reverse($rdirs);

    $c = 0;
    foreach ($rdirs as $i){
        if ($dirs[0] == $i)
            array_shift($dirs);
        else
            break;
        $c++;
    }
    if ($c != count($rdirs)) 
        return '';

    // dirs now contains list of url path elements to protected folder
    $res = $uu['scheme'] . '://' . $uu['host'];
    if ($uu['port'])
        $res .= ":$uu[port]";
    $res .= "/" ;
    if ($dirs)
        $res .= join('/', $dirs) . '/';
    return $res;
}

function clean_path($dir){
    $dir_delim_re = ( substr(php_uname(), 0, 7) == "Windows")  ? '{/|\\\}' : '{/}';
    $dir_delim = ( substr(php_uname(), 0, 7) == "Windows")  ? "\\" : "/";

    $dirs = preg_split($dir_delim_re, $dir );

    $d = array('');
    $skip = 0;
    foreach (array_reverse($dirs) as $s){
      if ($s == '') 
          continue;
      if ($s == '..') {
          $skip++;
          continue;
      }
      if ($s == '.') 
            continue;
      if ($skip) {
          $skip--;
          continue;
      } else {
          $d[] = $s;
      }
    }
    $d[] = '';
    $res = join($dir_delim, array_reverse($d));
    return $res;
}

function check_security($dir){
    return 1;
}

function format_permissions($p){
    $res = "";
    $res .= ($p & 256) ? 'r' : '-';
    $res .= ($p & 128) ? 'w' : '-';
    $res .= ($p & 64) ?  'x' : '-';
    $res .= ' ';                  
    $res .= ($p & 32) ?  'r' : '-';
    $res .= ($p & 16) ?  'w' : '-';
    $res .= ($p & 8) ?   'x' : '-';
    $res .= ' ';                  
    $res .= ($p & 4) ?   'r' : '-';
    $res .= ($p & 2) ?   'w' : '-';
    $res .= ($p & 1) ?   'x' : '-';
    return $res;
}

function format_file_date($tm){
    global $config;
    return strftime($config['date_format'], $tm);
}


function browse_dir($vars){
    global $config;

    $dir_delim_re = ( substr(php_uname(), 0, 7) == "Windows")  ? '{/|\\\}' : '{/}';
    $dir_delim = ( substr(php_uname(), 0, 7) == "Windows")  ? "\\" : "/";
    $dir = $vars['dir']; ##current
    $init_dir = $vars['init_dir']; ## 0/1 (set if it open from old config)
    if ($dir == '') 
        $dir = $config['root_dir'];
    $dir = &clean_path($dir);
    if (!check_security($dir))
        fatal_error("You are not allowed to view $dir", 1) ;
    $dirs = preg_split($dir_delim_re, $dir );
    $dir_link = $dir_delim;
    $p = $dir_delim;
    foreach ($dirs as $s){
        if ($s == '') continue;
        $p .= $s . $dir_delim;
        if (check_security($p)){
        $dir_link .= "<a href=\"protect.php?action=browse_dir&dir=$p\"><b>$s</b></a>$dir_delim";
        } else {
        $dir_link .= "<b>$s</b>$dir_delim";
        }
    }

    if ($dh = opendir($dir)) {
    } else {
        die("Cannot open directory: $dir");
    }
    print <<<CUT
    <html><head><title>Select Directory</title>
        <style> 
            body,td,th,input { 
                font-family: 'Helvetica', sans-serif; 
                font-size: 0.8em; }
            td { background-color: #F0F0F0;}
        </style>
        <script>
            function clicked(rd){
                window.opener.browse_dir_clicked(rd.value);
                window.close();
            }
        </script>
    <body bgcolor=white>    
    <center>
    <b>Contents of directory $dir_link</b>
    <table align=center bgcolor=#E0E0E0 cellpadding=3>
    <tr>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>Directory</th>
        <th>Mode</th>
        <th>Created</th>
    </tr>
CUT;
if (check_security($x="$dir..") && is_dir($x) && ($dir != $dir_delim)){
print <<<CUT
    <tr>
        <td>&nbsp;</td>
        <td align=center><b>..</b></td>
        <td colspan=3><a href="protect.php?action=browse_dir&dir=$dir..">.. <b>Previous Directory</b></td>
    </tr>   
    <form>
CUT;
}
    while ($fn = readdir($dh)){ 
        $file = "$dir$fn";
        if (!is_dir($file)) 
            continue;
        $file .= "$dir_delim";
        $stat = stat($file);
        if (preg_match('/^\.|\.\.$/', $fn))
            continue;
        $mode  = format_permissions($stat[3]);    
        $cdate = format_file_date($stat[10]);
    print <<<CUT
    <tr>
        <td><input type=radio name=dir value="$file" onclick='clicked(this)'></td>
        <td align=center><b>D</b></td>
        <td><a href="protect.php?action=browse_dir&dir=$file"><b>$fn</b></a></td>
        <td nowrap>$mode</td>
        <td nowrap>$cdate</td>
    </tr>
CUT;
    }
    closedir($dh);
    print <<<CUT
    </form>
    </table>
    </center>
    </body></html>
CUT;
}


function protect_folder($vars){
    $func_name = "protect_{$vars[method]}";
    $files = array();
    $err = $func_name($vars, $files);
    if ($err){
        display_add_form($vars, $err);
        return;
    }
    // save folder info now
    global $config, $db;
    $path = $db->escape($vars['path']);
    $url = $db->escape($vars['url']);
    $method = $db->escape($vars['method']);
    $product_ids = ($vars['product_id_all']) ? 'ALL' : join(',', $vars['product_id']);
    $files = $db->escape(serialize($files));
    $db->query("INSERT INTO {$db->config[prefix]}folders
    (path, url, method, product_ids, files_content)
    VALUES
    ('$path', '$url', '$method', '$product_ids', '$files')
    ");
    if ($GLOBALS['protection_is_instruction']){
        
    } else {
	    admin_log("Folder protected ($path) - $method", "folders", mysql_insert_id());
        admin_html_redirect("protect.php?added=ok", "Folder protected", "Folder has been protected successfully");    
    }
    exit();
}

function protect_change_folder($vars){
    global $db;
    $err = array();
    /// first delete protection from the folder
    $fl = get_folders();
    $folder = $fl[$vars['folder_id']];
    if (!$folder) die("Folder not found: $vars[folder_id]");

    if (is_dir($folder['path'])){
        $errs = 0;
        foreach ($folder['files_content'] as $fname => $content){
            $f = "$folder[path]/$fname";
            if (!is_file($f)) continue;
            $res = unlink($f);
            if (!$res){
                $errs++;
                $err[] = "File $f couldn't be removed - please remove it manually";
            }
        }
    } else {
        print "Folder $folder[path] seems to be removed...skipping protection removing step<br />";
    }
    /////// now protect folder
    if ($errs){
        display_edit_form($vars, $err);
        return;
    }
    $err = array();
    $func_name = "protect_{$vars[method]}";
    $files = array();
    $err = $func_name($vars, $files);
    if ($err){
        display_edit_form($vars, (array)$err);
        return;
    }

    // save folder info now
    global $config, $db;
    $path = $db->escape($vars['path']);
    $url = $db->escape($vars['url']);
    $method = $db->escape($vars['method']);
    $product_ids = ($vars['product_id_all']) ? 'ALL' : join(',', $vars['product_id']);
    $files = $db->escape(serialize($files));
    $db->query("UPDATE {$db->config[prefix]}folders
    SET path = '$path', url = '$url', method = '$method',
        product_ids = '$product_ids', files_content = '$files'
    WHERE folder_id=$vars[folder_id]
    ");
    if ($GLOBALS['protection_is_instruction']){
        
    } else {
	    admin_log("Folder protection changed ($path) - $method", "folders", $vars['folder_id']);
        admin_html_redirect("protect.php?added=ok", "Folder protected", "Folder has been protected successfully");    
    }
    exit();
}

function unprotect_folder($vars){
    $fl = get_folders();
    $folder = $fl[$vars['folder_id']];
    if (!$folder) die("Folder not found: $vars[folder_id]");

    if (is_dir($folder['path'])){
        $errs = 0;
        foreach ($folder['files_content'] as $fname => $content){
            $f = "$folder[path]/$fname";
            if (!is_file($f)) continue;
            $res = unlink($f);
            if (!$res){
                $errs++;
                $err[] = "File $f couldn't be removed - please remove it manually";
            }
        }
    } else {
        print "Folder $folder[path] seems to be removed...skipping protection removing step<br />";
    }
    // save folder info now
    global $config, $db;
    $files = $db->escape(serialize($files));
    $db->query("DELETE FROM {$db->config[prefix]}folders
    WHERE folder_id={$vars[folder_id]}
    ");
    admin_log("Folder protection removed ($path) - $method", "folders", $vars['folder_id']);
    if ($errs){
        print "<font color=red><b>";
        print "Protection has removed, but some errors happened - please follow our recommenendations to fix:<br />";
        foreach ($err as $e)
            print "<li>$e";
        print "<br /><br />after fixing all problems listed above, click <a href='protect.php'>here</a>";
    } else {
        admin_html_redirect("protect.php?added=ok", "Folder un-protected", "Protection has been removed from the folder");    
    }
    exit();
}

////////////////////////////////////////////////////////////////////////////
//
//                      M A I N
//
////////////////////////////////////////////////////////////////////////////
$vars = get_input_vars();
switch (@$vars['action']){
    case 'browse': case '':
        display_folders_list();
        break;
    case 'add':
        if ($vars['save']){
            if ($err = validate_add_form($vars)){
                display_add_form($vars, $err);
            } else {
                protect_folder($vars);
            }
        } else {
            display_add_form($vars);
        }
        break;
    case 'edit':
        if ($vars['save']){
            if ($err = validate_edit_form($vars)){
                display_edit_form($vars, $err);
            } else {
                protect_change_folder($vars);
            }
        } else {
            $fl = get_folders();
            $v = $fl[$vars['folder_id']];
            if (($ids = $v['product_ids']) == 'ALL'){
                $v['product_id'] = array();
                $v['product_id_all'] = 1;
            } else {
                $v['product_id'] = split(',', $ids);
                $v['product_id_all'] = 0;
            }
            display_edit_form($v);
        }
        break;
    case 'delete':
        unprotect_folder($vars);
        break;
    case 'browse_dir':
        browse_dir($vars);
        break;
    default:
        fatal_error("Unknown action: '$vars[action]' for protect.php");
}


?>
