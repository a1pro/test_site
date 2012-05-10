<?php              
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: SQL monitor
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3211 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

require "../config.inc.php";

if (!defined('AM_DEBUG')) die("Disabled by default, you have to edit admin/sql.php to enable SQL Monitor");

$t = new_smarty();
require "login.inc.php";
admin_check_permissions('super_user');
check_demo();


define('AM_DEBUG', 1);

$vars = get_input_vars();
$sql = $vars['sql'];
$h_sql = htmlentities($sql);

$t->display('admin/header.inc.html');


print <<<CUT
<center>
<h2>SQL Monitor</h2>
<hr>
<form method=post>
<textarea name="sql" cols="80" rows="5">$h_sql</textarea>
<br />
<input type=submit value="Go" style='width: 200px;'>
</form>
CUT;

if ($sql != ''){
    $d = & amDb();
    $res = $d->select($sql);

if (is_array($res)){
    if ($res == array()) {
        print "Query returned 0 rows.";
    } else {
        $cols = array_keys($res[0]);
        print "<table width=90% class=hedit><tr>";
        foreach ($cols as $k) print "<th>".htmlentities($k)."</th>";
        print "</tr><tr>";
        foreach ($res as $r){
            foreach ($r as $v) print "<td>".htmlentities($v)."</td>";
            print "</tr><tr>";
        }
        $c = count($res);
        print "</tr></table>
        <br><br>
        $c rows retreived.
        ";
    }
} else {
    print "Query executed, returned [$res]";
}
}

print '</center>';
$t->display('admin/footer.inc.html');
