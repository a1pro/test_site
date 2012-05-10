<?php
die("Disabled for safety reasons. Please consult with support@cgi-central.net before use.");

include "../config.inc.php";
$t = new_smarty();
require "login.inc.php";
require "protection_methods.inc.php";
admin_check_permissions('super_user');

function get_count_of_rebills($date1, $date2){
    global $db;
    $q = $db->query("SELECT COUNT(*), SUM(completed > 0)
        FROM {$db->config[prefix]}payments
        WHERE begin_date BETWEEN '$date1 00:00:00' AND '$date2 23:59:59'
        AND data LIKE '%RENEWAL_ORIG:%'");
    return mysql_fetch_row($q);
}

function display_form(){
    $t = new_smarty();
    $t->display('admin/header.inc.html');
    if (!$_POST[date1]) $_POST[date1] = date('Y-m-d');
    if (!$_POST[date2]) $_POST[date2] = date('Y-m-d');
    
    foreach (get_paysystems_list() as $ps){
    	$sel = ($ps['paysys_id'] == $_POST[paysys_id]) ? 'selected' : '';
    	$options .= "<option value=".$ps['paysys_id']." $sel>".$ps['title'] . '</option>';
    }
    print <<<CUT
    <h1>Run CC Rebills</h1>
    <form method=post>
    Start Date (format yyyy-mm-dd): <input type=text name=date1 size=14 value="$_POST[date1]"/> <br />
    End Date   (format yyyy-mm-dd):   <input type=text name=date2 size=14 value="$_POST[date2]" /> <br />
    Payment System : <select name=paysys_id><option value=''>Choose payment system</option>
    $options
    </select><br />
    <input type=submit value='Run Rebills'>
    <i>Dangerous! Use it at your own risk</i>
    </form>
CUT;
    $t->display('admin/footer.inc.html');
}

function display_confirmation($vars){
	global $config;
	$t = new_smarty();
	$t->display('admin/header.inc.html');
	$vars['date1'] = date('Y-m-d', strtotime($vars['date1']));
	$vars['date2'] = date('Y-m-d', strtotime($vars['date2']));
	$d1 = strftime($config['date_format'], strtotime($vars['date1']));
	$d2 = strftime($config['date_format'], strtotime($vars['date2']));
	print <<<CUT
	<h1>Confirm Rebill Run</h1>
	<font size=3>
	Are you sure you want to run rebill for dates
	<b>from [$d1] to [$d2]</b> and payment processor <b>$vars[paysys_id]</b>? Please review carefully!
	</font>
	<form method=post>
	<input type=submit name=confirm value='Confirm' />
	<input type=button name=dont_confirm value='No, back to form' onclick='window.location="rebill.php"' />
	<input type=hidden name=date value='$vars[date1]' />
	<input type=hidden name=date1 value='$vars[date1]' />
	<input type=hidden name=date2 value='$vars[date2]' />
	<input type=hidden name=paysys_id value='$vars[paysys_id]' />
	</form>
CUT;
	$t->display('admin/footer.inc.html');
}

function do_rebill($vars){
	global $config;
	
	$d = strftime($config['date_format'], strtotime($vars['date']));
	$d1 = strftime($config['date_format'], strtotime($vars['date1']));
	$d2 = strftime($config['date_format'], strtotime($vars['date2']));
	$ds = date('Y-m-d', strtotime($vars['date']));
	
	$t = new_smarty();
	$t->display('admin/header.inc.html');
	print "<h1>Running Rebill for [$d], please wait patiently...</h1>";
	print "<p>Rebilling dates from [$d1] to [$d2].</p>";
	ob_end_flush();

		
	cc_core_rebill($vars['paysys_id'], $vars['date'], $from_cron=false);
	
	/// next
	$vars['date'] = date('Y-m-d', strtotime($vars['date']) + 3600*24);
	if ($vars['date'] > $vars['date2']){
		print "<font size=3>Rebilling Finished! <a href='rebill.php'>Back to the form</a></font>";
		$t->display('admin/footer.inc.html');
		return;
	}
	$d = strftime($config['date_format'], strtotime($vars['date']));
	print <<<CUT
	<form method=post>
	<input type=submit name=confirm value="Process next date[$d]" />
	<input type=hidden name=date value='$vars[date]' />
	<input type=hidden name=date1 value='$vars[date1]' />
	<input type=hidden name=date2 value='$vars[date2]' />
	<input type=hidden name=paysys_id value='$vars[paysys_id]' />
	</form>
CUT;
	$t->display('admin/footer.inc.html');
}

$vars = get_input_vars();
if ($vars['date1'] && $vars['date2'] && $vars['paysys_id']){
	if ($vars['confirm'])
		do_rebill($vars);
	else
		display_confirmation($vars);
} else 
	display_form();
