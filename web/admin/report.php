<?php 
/*
build reports:
    - report income                    : daily/weekly/monthly for period
    - report income by product         : daily/weekly/monthly for period
*/


/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin index
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3431 $)
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


check_lite();
admin_check_permissions('report');

$_reports = array();


function add_report($name, $title){
    global $_reports;
    $_reports[] = array('name' => $name, 'title' => $title);
}

function load_reports(){
    global $config;
    $d = opendir($pdir = $config['root_dir'].'/admin/reports');
    if (!$d)
        die("Cannot open {$config[root_dir]}/admin/reports/");
    while ($f = readdir($d)){
        if (!preg_match('/^[^\._].+php$/', $f)) continue;
        require_once("$pdir/$f");
    }
    closedir($d);
}

function display_report_select(){
    global $t, $_reports;
    $rr = array();
    foreach ($_reports as $r) $rr[$r['name']] = $r['title'];
    $t->assign('reports', $rr);
    $t->display('admin/report_select.html');
}

function display_report_params(){
    global $vars;
    $func = $vars['report'] . '_display_params_dialog';
    $func($vars);
}

function display_report(){
    global $vars;
    $func = $vars['report'] . '_check_params';
    $errors = $func($vars);
    if ($errors) {
       $func = $vars['report'] . '_display_params_dialog';
       $func($vars, $errors);
       exit();
    }
    $func = $vars['report'] . '_display_report';
    $func($vars);
}

function round_period($d1, $d2, $discretion){
    switch ($discretion){
        case 'week':
            $d1 = strtotime($d1); $d2 = strtotime($d2);
            $dd1 = date('w', $d1); $dd2 = date('w', $d2);
            $d1 = $d1 - ($dd1 - 0) * 3600*24;
            $d2 = $d2 + (6 - $dd2) * 3600*24;
            return array(date('Y-m-d', $d1), date('Y-m-d', $d2));
            break;
        case 'month':
            $d1 = strtotime($d1); $d2 = strtotime($d2);
            return array(date('Y-m-01', $d1), date('Y-m-t', $d2));
            break;
        case 'day':
        default: //nothing
    }
    return array($d1, $d2);
}

function next_period($d1, $discretion){
    //next first and last day of period
	$d1_real = $d1;
    $d1 = strtotime($d1);
    switch ($discretion){
        case 'week':
            $d1 = $d2 = $d1 + 7*3600*24  + 3600*12; 
            $dd1 = date('w', $d1);
            $dd2 = date('w', $d2);
            $d1 = $d1 - ($dd1 - 0) * 3600*24;
            $d2 = $d2 + (6 - $dd2) * 3600*24;
            return array(date('Y-m-d', $d1), date('Y-m-d', $d2));
            break;
        case 'month':
            list($y,$m) = split('-', date('Y-m', $d1));
            $d1 = $d2 = mktime(0,0,0,$m+1,1,$y);
            return array(date('Y-m-01', $d1), date('Y-m-t', $d2));
            break;
        case 'day':
			list($y,$m,$d) = split('-', $d1_real);
			$d1 = $d2 = date('Y-m-d',mktime(12,0,0,$m,$d+1,$y));
        default: //nothing
    }
    return array($d1, $d2);
}

load_reports();
$vars = get_input_vars();
if (!$vars['report']) {
    display_report_select();
} elseif (!$vars['build']){
    display_report_params();
} else {
    display_report();
}
?>
