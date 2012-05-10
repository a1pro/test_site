<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Access log
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3749 $)
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

admin_check_permissions('affiliates');

function get_ym_options(){
    $res = array();
    $y = date('Y');
    $m = date('m');
    for ($i=0;$i<36;$i++){
        $res[ $y."_".$m ] = date('F 01-t, Y', strtotime("{$y}-{$m}-01"));
        $m--;
        if ($m <= 0) { $m = 12; $y--; }
    }
    return $res;
}
function get_default_ym(){
    $y = date('Y');
    $m = date('m');
    $m -= 0;
    if ($m <= 0){
        $y--;
        $m = 12 - $m;
    }
    return "{$y}_{$m}";
}
function display_aff_sales(){
    global $vars;
    global $db, $t;
    $t->assign('year_month_options', get_ym_options());
    $t->assign('default_month', get_default_ym());
    
    if ($vars['year_month'] == '')
        $vars['year_month'] = get_default_ym();

    list($y, $m) = split('_', $vars['year_month']);
    $y = intval($y); $m = intval($m);
    $m = sprintf('%02d', $m);
    $dat1 = "{$y}-{$m}-01"; 
    $dat2 = date('Y-m-t', strtotime($dat1));
    $dattm1 = date('Ymd000000', strtotime($dat1));
    $dattm2 = date('Ymd235959', strtotime($dat2));
    $totaldays = date('t', strtotime($dat1));        
    
    $days = array();
    $total = array();
    for ($i=1;$i<=$totaldays;$i++)
        $days[$i] = array('dat' => sprintf("$y-$m-%02d", $i));
    // get clicks for the month
    $q = $db->query("SELECT DAYOFMONTH(ac.time), COUNT(log_id), COUNT(DISTINCT(remote_addr))
        FROM {$db->config[prefix]}aff_clicks ac
        WHERE ac.time BETWEEN $dattm1 AND $dattm2
        GROUP BY DAYOFMONTH(ac.time)
    ");
    while (list($d, $r, $u) = mysql_fetch_row($q)){
        $days[$d]['raw'] = $r;
        $days[$d]['uniq'] = $u;
    }
    
    // get total clicks for the month
    $q = $db->query("SELECT COUNT(log_id), COUNT(DISTINCT(remote_addr))
        FROM {$db->config[prefix]}aff_clicks ac
        WHERE ac.time BETWEEN $dattm1 AND $dattm2
    ");
    while (list($r, $u) = mysql_fetch_row($q)){
        $total['raw'] = $r;
        $total['uniq'] = $u;
    }
    
    // get comissions for the month
    $q = $db->query("SELECT DAYOFMONTH(ac.date), COUNT(commission_id),
        SUM(IF(record_type='debit', amount, 0)), 
        SUM(IF(record_type='credit', amount, 0))
        FROM {$db->config[prefix]}aff_commission ac
        WHERE ac.date BETWEEN '$dat1' AND '$dat2'
        GROUP BY DAYOFMONTH(ac.date)
    ");
    while (list($d, $cnt, $deb, $cre) = mysql_fetch_row($q)){
        $days[$d]['trans'] = $cnt;
        $days[$d]['debit'] = $deb != 0 ? -$deb : '';
        //$days[$d]['debit'] = $deb;
        $days[$d]['credit'] = $cre;
        if ($deb || $cre)
            $days[$d]['total'] = $cre - $deb;
        $total['trans'] += $cnt;
        $total['debit'] += $deb;
        $total['credit'] += $cre;
        $total['total'] += $days[$d]['total'];
    }
    $total['debit'] = $total['debit'] != 0 ? -$total['debit'] : '';
    $t->assign('days', $days);
    $t->assign('total', $total);
    
    /// top 20 referrers
    $q = $db->query("SELECT referrer, COUNT(log_id) as clog_id, COUNT(DISTINCT(remote_addr)) as cremote_addr
        FROM {$db->config[prefix]}aff_clicks ac
        WHERE referrer > '' AND ac.time BETWEEN $dattm1 AND $dattm2
        GROUP BY referrer
        ORDER BY clog_id DESC, cremote_addr DESC
        LIMIT 0,20
    ");
    $refs = array();
    while (list($ref, $raw, $uniq) = mysql_fetch_row($q)){
        $refs[] = array(
            'raw'  => $raw,
            'uniq' => $uniq,
            'ref'  => $ref
        );
    }
    $t->assign('refs', $refs);
    $t->display("admin/aff_stats.html");
}



$vars = get_input_vars();
extract($vars, EXTR_OVERWRITE);

/*******************************************/
switch ($action){
case 'aff_clicks':
    settype($start, 'integer'); 
    settype($member_id, 'integer');  // $member_id == 0 if not set
    $count = 20;
    $list = $db->get_aff_clicks($member_id, $start, $count);
    $all_count = $db->get_aff_clicks_c($member_id);
    $list_all = $db->get_aff_clicks_distinct();

    $t->assign('list_all', $list_all);
    $t->assign('list', $list);
    $t->assign('count', $count);
    $t->display("admin/aff_clicks.html");
    break;
default:
    display_aff_sales();
}

?>
