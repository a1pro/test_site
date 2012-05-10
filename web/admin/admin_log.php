<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Access log
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2121 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

                                                                                 
require "../config.inc.php";
$t = new_smarty();
require "login.inc.php";
admin_check_permissions('super_user');


$vars = get_input_vars();

function construct_where($q, $q_where){
    global $db;
    if ($q == '') return '';
    $q = $db->escape($q);
    switch ($q_where){
        case 'message':
            return "AND message LIKE '%$q%'";
        case 'admin_login':
            return "AND IFNULL(a.login, al.admin_login) = '$q'";
        case 'ip':
            return "AND ip LIKE '%$q%'";
        case 'ip_exact':
            return "AND ip = '$q'";
        case 'subject':
            $a = @split(':', $q, 2);            
            if (count($a) == 1){
                return "AND tablename = '$q' OR record_id = '$q'";
            } else {
                return "AND tablename = '$a[0]' AND record_id = '$a[1]'";
            }
    }
}

/*******************************************/
$q_where_options = array(
    'message' => 'Message',
    'admin_login' => 'Admin Login',
    'ip' => 'IP Address (broad match)',
    'ip_exact' => 'IP Address (exact match)',
    'subject' => 'Subject (table:record_id)',
);

$start     = intval($vars['start']);
$count     = 40;
$where     = construct_where($vars['q'], $vars['q_where']);


$all_count      = $db->query_one(
			 "SELECT COUNT(*)
             FROM {$db->config[prefix]}admin_log al
             LEFT JOIN {$db->config[prefix]}admins a USING (admin_id)
             WHERE 1 $where");

$list      = $db->query_all(
			 "SELECT al.*, IFNULL(a.login, al.admin_login) as admin_login
             FROM {$db->config[prefix]}admin_log al
             LEFT JOIN {$db->config[prefix]}admins a USING (admin_id)
             WHERE 1 $where
             ORDER BY dattm DESC
             LIMIT $start, $count");

foreach ($list as $k => $r){
    switch ($r['tablename']){
        case 'members' : 
            $list[$k]['record_link'] = "users.php?member_id=$r[record_id]&action=edit";
            break;            
        case 'admins' : 
            $list[$k]['record_link'] = "admins.php?admin_id=$r[record_id]&action=edit";
            break;            
        case 'coupons' : 
            $list[$k]['record_link'] = "coupons.php?batch_id=$r[record_id]&action=view_batch";
            break;            
        case 'products' : 
            $list[$k]['record_link'] = "products.php?product_id=$r[record_id]&action=edit";
            break;            
        case 'payments' : 
            $list[$k]['record_link'] = "users.php?payment_id=$r[record_id]&action=edit_payment";
            break;            
    }
}             
             
$t->assign('list', $list);
$t->assign('count', $count);
$t->assign('q_where_options', $q_where_options);
$t->display("admin/admin_log.html");
