<?php
/*
 *
 *
 *     Author: Alex Scott
 *      Email: alex@cgi-central.net
 *        Web: http://www.cgi-central.net
 *    Details: Rebill Log
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
admin_check_permissions('super_user');

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

function construct_where($q, $q_where){
	global $db;
	if ($q == '') return '';
	$q = $db->escape($q);
	switch ($q_where){
		case 'date':
			$d1 = date('Ymd000000', strtotime($q));
			$d2 = date('Ymd235959', strtotime($q));
			return "AND rl.added_tm BETWEEN $d1 AND $d2";
		case 'payment_date':
			$q = date('Y-m-d', strtotime($q));
			return "AND rl.payment_date = '$q'";
		case 'login':
			return "AND u.login = '$q'";
		case 'name_l':
			return "AND u.name_l LIKE '%$q%'";
		case 'payment_id':
			return "AND rl.payment_id = '$q' OR rl.rebill_payment_id = '$q'";
	}
}

function do_logs(){
	global $db, $all_count, $count, $start;
	$t = new_smarty();
	$vars = get_input_vars();

	/*******************************************/
	$q_where_options = array(
    'date' => 'Date of transaction (format yyyy-mm-dd)',
    'dat' => 'Date of rebilling (format yyyy-mm-dd)',
	'login' => 'Username',
    'name_l' => 'Last Name',
    'payment_id' => 'Payment#',
	);

	$start     = intval($vars['start']);
	$count     = 40;
	$where     = construct_where($vars['q'], $vars['q_where']);


	$all_count      = $db->query_one(
			 "SELECT COUNT(*)
             FROM {$db->config[prefix]}rebill_log rl
             LEFT JOIN {$db->config[prefix]}payments p ON rl.payment_id = p.payment_id
             LEFT JOIN {$db->config[prefix]}members u ON p.member_id = u.member_id
             WHERE 1 $where");

	$list      = $db->query_all(
			 "SELECT rl.*, 
			 u.member_id, u.login, u.name_f, u.name_l,
			 p.product_id, pr.title
             FROM {$db->config[prefix]}rebill_log rl
             LEFT JOIN {$db->config[prefix]}payments p ON rl.payment_id = p.payment_id
             LEFT JOIN {$db->config[prefix]}members u ON p.member_id = u.member_id
             LEFT JOIN {$db->config[prefix]}products pr ON p.product_id = pr.product_id 
             WHERE 1 $where
             ORDER BY rebill_log_id DESC
             LIMIT $start, $count");

	$status_strings = array(
	CC_RESULT_INTERNAL_ERROR => "<font color=red>Internal error</font>",
	CC_RESULT_SUCCESS => "<b>OK</b>",
	CC_RESULT_DECLINE_TEMP => "<font color=red>Declined</font>",
	CC_RESULT_DECLINE_PERM => "<font color=red>Declined perm</font>",
	CC_RESULT_IGNORE => "<font color=red>Unknown</font>",
	);

	foreach ($list as $k => $r)
	$list[$k]['status_string'] = $status_strings[$r['status']];

	$t->assign('list', $list);
	$t->assign('count', $count);
	$t->assign('q_where_options', $q_where_options);
	$t->display('admin/rebill_log.html');
}

function do_stats(){
	global $db;

	$vars = get_input_vars();
	$t = new_smarty();
	$t->assign('year_month_options', get_ym_options());
	$t->assign('default_month', get_default_ym());
	if ($vars['year_month'] == '')
	$vars['year_month'] = get_default_ym();

	list($y, $m) = split('_', $vars['year_month']);
	$m = sprintf('%02d', $m);
	$dat1 = "{$y}-{$m}-01";
	$dat2 = date('Y-m-t', strtotime($dat1));
	$dattm1 = date('Ymd000000', strtotime($dat1));
	$dattm2 = date('Ymd235959', strtotime($dat2));
	$totaldays = date('t', strtotime($dat1));
	$days = array();
	$total = array();
	for ($i=1;$i<=$totaldays;$i++)
	$days[sprintf("$y-$m-%02d", $i)] = array();
	// get clicks for the month
	$q = $db->query("SELECT
		DAYOFMONTH(rl.payment_date), IFNULL(status, 'null'), COUNT(rebill_log_id) as cnt, SUM(amount) as amount
        FROM {$db->config[prefix]}rebill_log rl
        WHERE rl.payment_date BETWEEN $dattm1 AND $dattm2
        GROUP BY DAYOFMONTH(rl.payment_date), IFNULL(status, 'null')
    ");
	while (list($d, $status, $cnt, $amount) = mysql_fetch_row($q)){
		$dat = sprintf('%04d-%02d-%02d', $y,$m,$d);
		if ($status == 'null')
		$s = 'null';
		elseif ($status == 0)
		$s = 0;
		else
		$s = 1;
		$days[$dat][$s]['cnt'] += $cnt;
		$days[$dat][$s]['amount'] += $amount;
		$days[$dat]['total']['cnt'] += $cnt;
		$days[$dat]['total']['amount'] += $amount;
	}
	$t->assign('list', $days);
	$t->display('admin/rebill_stats.html');
}

function do_rebill(){
	global $config, $db;

	$t = new_smarty();
	$vars = get_input_vars();
	if ($vars['dat'] == '') die('[dat] cannot be empty');
	$dat = $vars['dat'];
	if (time() - strtotime($dat) > 3600 * 24 * 30)
	die("Rebill cannot be called for periods longer than 30 days from nows");
	if (time() - strtotime($dat) < 0)
	die("Rebill cannot be called for future dates - please wait the date ");
	$t->display('admin/header.inc.html');
	$hdat = strftime($config['date_format'], strtotime($dat));
	if (!$vars['paysys_id']){
		print "
	<h2>Manual CC Rebill $hdat</h2>
	<br><br><p>Are you sure you want to run rebill process for date $hdat ?
	<a href='rebill_log.php?do=rebill_stats'>Click here to cancel and back to rebill reports</a>
	</p>";
		print "<p><b>Make sure to do not close browser windows and do not start any new rebill processes until it is finished, else it may result to double billing of customers</p></b>";

		$options = "";
		foreach (cc_core_get_plugins(true) as $p)
		$options .= "<option value='$p'>$p</option>\n";
		$dat = htmlentities($vars['dat']);
		print "<form method='post' action='rebill_log.php'>
	<select name='paysys_id'>
	<option value=''>*** Select a Payment System to continue ***</option>
	$options</select> <br />
	<label><input type='checkbox' name='repeat_declined' value='1' />
	Re-process payments that were marked as declined
	</label><br />
	<input type='submit' value='Continue'>
	<input type='hidden' name='dat' value='$dat'>
	<input type='hidden' name='do' value='rebill'>
	</form>
	";
	} else { // do rebill
		print "
		<h2>Manual CC Rebill $hdat - $vars[paysys_id]</h2>";
		print "<p><b>Please do not stop/exit your browser, do not run other payment processes until this process is finished!</b></p>";
		for ($i=0;$i<100;$i++) print "          \n"; // to flush browser/apache buffer for sure
		print " Rebilling Process started at ".strftime($config['time_format'])."....<br />\n" ;
		ob_end_flush();
		$dat = date('Y-m-d', strtotime($vars['dat']));
		$was = $db->query_one("SELECT COUNT(*) FROM {$db->config[prefix]}rebill_log");
		cc_core_rebill($vars['paysys_id'], $dat, $from_cron=false, intval($vars['repeat_declined']));
		$now = $db->query_one("SELECT COUNT(*) FROM {$db->config[prefix]}rebill_log");
		$added = $now - $was;
		print " Rebilling Process finished at ".strftime($config['time_format']).".<br />
		 <b>$added</b> transactions processed. <br />\n" ;
		print "<br /><a href='rebill_log.php?do=rebill_stats'>Go back to Rebilling Stats</a>";
	}
	$t->display('admin/footer.inc.html');
}

if ($_REQUEST['do'] == 'rebill_stats') do_stats();
elseif ($_REQUEST['do'] == 'rebill') do_rebill();
else do_logs();