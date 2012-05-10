<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Info /
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
include "login.inc.php";

admin_check_permissions('backup_restore');
check_lite();

function mysql_escape_value($v){
    return "'" . mysql_escape_string($v) . "'";
}

function do_restore(){
    global $config, $plugin_config, $db;
    $file = $_FILES['file']['tmp_name'];

    $f = file($file);
    
    $first_line = trim($f[0]);
    $second_line = trim($f[1]);

    global $t;
    $t->assign('backup_header', "$first_line<br />$second_line");


    $f = join('', $f);
    if (!strlen($f)) fatal_error("Uploaded file is empty!");
    if (!preg_match('/^### aMember Pro .+? database backup/', $first_line)) 
        fatal_error("Uploaded file is not valid aMember Pro backup");
    foreach (preg_split('/;\n/', $f) as $sql)
        if (strlen($sql))
            $db->query($sql);
    admin_log("Restored from $first_line");
}

##################### main ####################################
$vars = get_input_vars();
$t = &new_smarty();
if ($_FILES['file']['size']) {
    check_demo();
    do_restore();
    $t->display('admin/restore_ok.html');
} else
    $t->display('admin/restore.html');

?>
