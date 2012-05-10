<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin index
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5169 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/
if (@$_REQUEST['page'] == ''){
    define('SILENT_AMEMBER_ERROR_HANDLER', true);// disable error reporting in frameset
}    

include "../config.inc.php";
$t = new_smarty();
include "login.inc.php";

function get_income_report(){
    global $t,$vars,$db,$config;

    $report_days = get_first($GLOBALS['config']['payment_report_num_days'], 7);

    // get income
    $beg_tm = date('Y-m-d 00:00:00', time()-3600*$report_days*24);
    $end_tm = date('Y-m-d 23:59:59', time());
    $res = array();
    $q = $db->query("SELECT FROM_DAYS(TO_DAYS(tm_added)) as date,
        count(payment_id) as added_count, sum(amount) as added_amount
        FROM {$db->config[prefix]}payments p
        WHERE tm_added BETWEEN '$beg_tm' AND '$end_tm'
        GROUP BY date
        ");
    while ($x = mysql_fetch_assoc($q))
        $res[$x['date']] = $x;
    $q = $db->query("SELECT FROM_DAYS(TO_DAYS(tm_completed)) as date,
        count(payment_id) as completed_count, sum(amount) as completed_amount
        FROM {$db->config[prefix]}payments p
        WHERE tm_completed BETWEEN '$beg_tm' AND '$end_tm'
        AND completed>0
        GROUP BY date
        ");
    $max_total = 0;
    while ($x = mysql_fetch_assoc($q)){
        $res[$x['date']] = array_merge((array)$x, (array)$res[$x['date']]);
        $total_completed += $x['completed_amount'];
        if ($x['completed_amount'] > $max_total) 
            $max_total = $x['completed_amount'];
    }
    $res1 = array();
	list($ty,$tm,$td)=split('-', date('Y-m-d'));
	$rtime = mktime(12,0,0,$tm,$td,$ty);
    for ($i=0;$i<$report_days;$i++){
        $dp = strftime("%a&nbsp;" . $config['date_format'], $rtime-$i*3600*24);
        $d = date('Y-m-d', $rtime-$i*3600*24);
        $res1[$d]['date'] = $d;
        $res1[$d]['date_print'] = $dp;
        $res1[$d]['added_count'] = intval($res[$d]['added_count']);
        $res1[$d]['completed_count'] = intval($res[$d]['completed_count']);
        $res1[$d]['added_amount'] = number_format($res[$d]['added_amount'],2,'.',',');
        $res1[$d]['completed_amount'] = number_format($res[$d]['completed_amount'], 2,'.',',');
        if ($max_total){
            $res1[$d]['percent_v'] = round(100*$res[$d]['completed_amount']/$max_total);
            $res1[$d]['percent'] = round(100*$res[$d]['completed_amount']/$total_completed);
        }
    };
    ksort($res1);
    return $res1;
}

function get_users_report(){
    global $t,$vars,$db,$config;
    $res = array(0 => array(), 1=>array(), 2=>array());
    $q = $db->query("SELECT status,COUNT(*) 
    FROM {$db->config[prefix]}members
        GROUP BY status");
    while (list($s,$c) = mysql_fetch_row($q)){
        $res[$s] = array('count'=>$c);
        $total += $c;
    }
    for ($i=0;$i<=2;$i++) 
        $res[$i]['count'] = intval($res[$i]['count']);
    $now_date = date('Y-m-d');
    $active_paid = $db->query_one("SELECT COUNT(DISTINCT member_id) AS active
            FROM {$db->config[prefix]}payments
            WHERE completed > 0 AND expire_date >= '$now_date' AND amount > 0");
    $active_free  = $res[1]['count'] - $active_paid;
    $res[0]['title'] = 'Pending';
    $res[1]['title'] = '<b>Active (free/paid)</b>';
    $res[1]['count'] = '<b>'.$res[1]['count']." ($active_free/$active_paid)</b>";
    $res[2]['title'] = 'Expired';
    $res[3]['title'] = '<b><a href="users.php?letter=A">Total</a></b>';
    $res[3]['count'] = '<b><a href="users.php?letter=A">'.$total.'</a></b>';
    
    return $res;
}
function get_error_log_count(){
    global $db;
    $date = date('Ymd');
    $q = $db->query($s="SELECT COUNT(*) 
    FROM {$db->config[prefix]}error_log
    WHERE time BETWEEN {$date}000000 AND {$date}235959");
    list($c) = mysql_fetch_row($q);
    return $c;
}
function get_access_log_count(){
    global $db;
    $date = date('Ymd');
    $q = $db->query("SELECT COUNT(log_id) 
    FROM {$db->config[prefix]}access_log
    WHERE time BETWEEN {$date}000000 AND {$date}235959");
    list($c) = mysql_fetch_row($q);
    return $c;
}

function get_warnings(){
    global $db, $config, $member_additional_fields, $plugins,$plugin_error;
    $warn = array();

    
    if (($config['db_version'] < $config['require_db_version']) &&
        $config['require_db_version']){
        $warn[] = "Please upgrade your SQL database, upload latest file amember/amember.sql 
        then run <a href='$config[root_url]/admin/upgrade_db.php' target=_blank>upgrade script</a><br />
        Your database has version [$config[db_version]], your aMember requires [$config[require_db_version]]";
    }
    
    if (defined("INCREMENTAL_CONTENT_PLUGIN") && !function_exists('get_incremental_plugin_files')) {
        $warn[] = 'You have outdated version of Incremental Content plugin installed.
        Please download latest plugin version from your account
        <a href="http://www.amember.com/amember/member.php">account</a>
        and reupload all files from the plugin package into your
        /amember/plugins/protect/incremental_content/ folder
        (replace existing files). If you need help with this contact us in
        <a href="http://www.amember.com/support/">helpdesk</a>.';
    }
    
    ///check for configuration problems
    foreach ($member_additional_fields as $f)
        if ($f['name'] == 'cc') $has_cc_fields++;
    if ((function_exists('cc_core_init') || $has_cc_fields) && !$config['use_cron']){
        $warn[] = "Enable and configure external cron (<a href=\"setup.php?notebook=Advanced\" target=_blank>aMember CP -> Setup -> Advanced</a>) if you are using credit card payment plugins";
        if (!amConfig('agreed_cc_warning') && $_GET['agreed_cc_warning'] == ''){
            $t = & new_smarty();
            $t->display('admin/cc_warning.html');
            exit();
        }
    }

    $q = $db->query("SELECT UNIX_TIMESTAMP(MAX(time)) FROM {$db->config[prefix]}cron_run");
    list($t) = mysql_fetch_row($q);
    $diff = time() - $t;
    $tt = $t ? strftime('at '.$config['time_format'], $t) : "NEVER (oops! no records that it has been running at all!)";
    if ($diff > 24 * 3600) 
        $warn[] = "Cron job has been running last time $tt, it is more than 24 hours before.<br />
        Most possible external cron job has been set incorrectly. It may cause very serious problems with the script";
    ////
    if (!count($db->get_products_list()))
        $warn[] = "You have not added any products, your signup forms will not work until you <a href='products.php'>add at least one product</a>";
    //
    if ($has_cc_fields || function_exists('cc_core_init')){
        if (!extension_loaded("curl") && !$config['curl'])
            $warn[] = "You must <a href='setup.php'>enter cURL path into settings</a>, because your host doesn't have built-in cURL functions.";
    }
    // check for license expiration
    if (!function_exists('is_trial') || !is_trial()){
        global $_amember_license;
        $tm1 = strtotime($_amember_license['expire']);
        $tm2 = time();
        $df = round(($tm1 - $tm2) / (3600 * 24));
        if (($df >= 0) && ($df <= 25)){
            define('AMEMBER_LICENSE_EXPIRES_SOON', $df);
            $t = strftime($config['date_format'], $tm1);
            $warn[] = "Your aMember license key will expire within $df days ($t).
            Please login into <a href='https://www.amember.com/amember/member.php' target=_blank>members area</a>,
            get your lifetime license (it is FREE) and paste it to 
            \"aMember CP -> Setup -> License\"";
        }
    }

    //check protect plugins setup
    foreach ($plugins['protect'] as $plugin_name){
        $func = "check_setup_" . $plugin_name;
        if (function_exists($func)){
            $res = $func();
            if ($res) $warn[] = $res;
        }
		//check if $plugin_error[$plugin_name] not same as result of "check_setup_" . $plugin_name
		if(trim($plugin_error[$plugin_name]) && trim($res)!=trim($plugin_error[$plugin_name]))
			$warn[] = ucfirst($plugin_name)." plugin error: ".$plugin_error[$plugin_name];
    }
    
    return $warn;
}

function display_first_page(){
    global $t,$vars,$db,$config;

    $t->assign('warnings', get_warnings());
    $t->assign('income', get_income_report());
    $t->assign('users', get_users_report());
    $t->assign('errors', get_error_log_count());
    $t->assign('access', get_access_log_count());
    $t->assign('pi', get_pi());
    $t->display("admin/blank.html");
}

function get_pi(){
  global $db;
  return join(',',get_loaded_extensions()) . ';mysql='.$db->query_one("SELECT VERSION()");
}

function check_for_fixfiles_uploaded(){
    $files = array('fixadmin.php', 'fixroot.php', 'fixlicense.php');
    foreach ($files as $f){
        if (file_exists(dirname(__FILE__).'/../'.$f)){
            fatal_error("File [ $f ] is uploaded to your aMember folder, and
            it is serious security risk for your installation. Please
            remove file [ $f ] from your aMember folder using your
            favorite FTP client, then click <a href='window.location.reload()'>Refresh</a>
            to continue.",0,1);
            exit();
        }
    }
}

$vars = get_input_vars();
extract($vars, EXTR_OVERWRITE);

if ($_POST['do'] == 'ajax'){
    if ($_POST['root_url'])
        $db->config_set('root_url', $_POST['root_url'], 0);
    if ($_POST['root_surl'])
        $db->config_set('root_surl', $_POST['root_surl'], 0);
    if ($_POST['disable_url_checking'])
        $db->config_set('disable_url_checking', $_POST['disable_url_checking'], 0);
    exit();
}

$t->assign('config', $config);
$page = $vars['page'];
switch ($page){
 case 'menu':   
    require_once('menu.inc.php');
    $menu = & initMainMenu();
    $t->assign('menu_html', $menu->render());
    $t->display("admin/menu.html");  
    break;
 case 'blank':  
    if ($_GET['agreed_cc_warning'] != ''){
        $db->config_set('agreed_cc_warning', 
            $config['agreed_cc_warning'] = intval($_GET['agreed_cc_warning']), 
            0);
    }
    display_first_page();
    break;
 default:       
    check_for_fixfiles_uploaded();
    $t->display("admin/index.html");
}
?>
