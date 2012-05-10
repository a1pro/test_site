<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin error log
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1963 $)
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

$vars = get_input_vars();
extract($vars, EXTR_OVERWRITE);


settype($start, 'integer');
$count = 20;
if (!strlen($vars['q'])){
    $list = $db->get_error_log($start, $count);
    $all_count = $db->get_error_log_c();
} else {
    $vars['q'] = $db->escape($vars['q']);
    $q = $db->query("SELECT * 
        FROM {$db->config[prefix]}error_log  
        WHERE 
            url LIKE '%{$vars['q']}%' 
        OR remote_addr LIKE '%{$vars['q']}%' 
        OR referrer LIKE '%{$vars['q']}%' 
        OR error LIKE '%{$vars['q']}%' 
        ORDER BY time DESC, log_id DESC
        LIMIT $start, $count
    ");
    $list = array();
    while ($x = mysql_fetch_assoc($q)){
        $list[] = $x;
    }
    $q = $db->query("SELECT COUNT(*) 
        FROM {$db->config[prefix]}error_log  
        WHERE 
            url LIKE '%{$vars['q']}%' 
        OR remote_addr LIKE '%{$vars['q']}%' 
        OR referrer LIKE '%{$vars['q']}%' 
        OR error LIKE '%{$vars['q']}%' 
    ");
    list($all_count) = mysql_fetch_row($q);
}
$t->assign('list', $list);
$t->assign('count', $count);
$t->display("admin/error_log.html");

?>
