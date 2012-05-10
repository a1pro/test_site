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

ini_set('session.use_trans_sid', 0);
ini_set('url_rewriter.tags', '');
include "../config.inc.php";
$t = new_smarty();
include "login.inc.php";

check_lite();

function mysql_escape_value($v){
    return "'" . mysql_escape_string($v) . "'";
}

function do_backup(){
    global $config, $plugin_config, $db;
    
    $memory_limited = 0;
    if (@ini_get('memory_limit')){
        @ini_set('memory_limit', '128M');
        if (@ini_get('memory_limit') != '128M')
            $memory_limited = 1;
    }
    if (function_exists('gzencode') && !$memory_limited)
        $cmp = "gz";
    else
        $cmp = "";

    $dat = date('Y_m_d');
    if ($cmp == 'gz')
        header("Content-Type: application/x-gzip");
    else
        header("Content-Type: text/sql");
                
    header("Content-Disposition: attachment; filename=amember-$dat.sql" .
           ($cmp ? ".$cmp" : "" ) );
    if ($cmp) ob_start();
    print "### aMember Pro $config[version] database backup\n";
    print "### Created: " . date('Y-m-d H:i:s') . "\n";

    $db_prefix = $plugin_config['db']['mysql']['prefix'];
    foreach ($config['tables'] as $table){  
        print "\n\nDROP TABLE IF EXISTS $db_prefix$table;\n";

        $q = $db->query("SHOW CREATE TABLE $db_prefix$table");
        list($t1, $create_sql) = mysql_fetch_row($q);
        print "$create_sql;\n";

        if (in_array($table, $config['tables_skip_backup'])) 
            continue;

        $q = $db->query("SELECT * FROM $db_prefix$table");
        while ($a = mysql_fetch_assoc($q)){
            $fields = join(',', array_keys($a));
            $values = join(',', array_map('mysql_escape_value', array_values($a)));
            print "INSERT INTO $db_prefix$table ($fields) VALUES ($values);\n";
        }
    }

    if ($cmp == 'gz') {
        $out = gzencode(ob_get_contents());
        ob_end_clean();
        echo $out;
    }
}

function check_backup(){
    global $config, $plugin_config, $db;
    $db_prefix = $plugin_config['db']['mysql']['prefix'];
    foreach ($config['tables'] as $table){  
        $q = $db->query("SHOW CREATE TABLE $db_prefix$table");
        list($t1, $create_sql) = mysql_fetch_row($q);
    }
}


##################### main ####################################
$vars = get_input_vars();
admin_check_permissions('backup_restore');
if ($vars['do_backup']) {
    check_demo();
    do_backup();
    admin_log('Downloaded backup');
    exit();
}
check_backup();
$t = &new_smarty();
$t->display('admin/backup.html');

?>