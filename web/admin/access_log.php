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

                                                                                 
require "../config.inc.php";
$t = new_smarty();
require "login.inc.php";


$vars = get_input_vars();
extract($vars, EXTR_OVERWRITE);

/*******************************************/
$order_options = array(
     'a.time DESC' => 'Time DESC'    ,
     'a.time'      => 'Time ASC'     ,
     'm.login'     => 'Username ASC' ,
     'm.login DESC' => 'Username DESC'
);

$order = array();
if ($vars['order1'] && $order_options[$vars['order1']])
    $order[] = $vars['order1'];
if ($vars['order2'] && $order_options[$vars['order2']])
    $order[] = $vars['order2'];
$order = join(',', $order);
if (!$order) $order = 'a.time DESC';

/*******************************************/
settype($start, 'integer'); 
$count     = 20;
if ($vars['get_csv']) $count = 999999;
$list      = $db->get_access_log(0, $start, $count, $order);
$all_count = $db->get_access_log_c();

if ($vars['get_csv']) {
    header("Content-type: application/csv");
    $dat = date('YmdHis');
    header("Content-Disposition: attachment; filename=amember-$dat.csv");
    foreach ($list as $l) {
		list($year,$month,$day,$hour,$minute,$second) = split('[- :]',$l['time']);
		$ltime = mktime($hour,$minute,$second,$month,$day,$year);
        print strftime($config['time_format'],$ltime) . ';';
        print "$l[login];$l[url];$l[remote_addr];$l[referrer]\n";
    }
} else {
    $t->assign('order_options', $order_options);
    $t->assign('list', $list);
    $t->assign('count', $count);
    $t->display("admin/access_log.html");
};
?>
