<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");


add_report('income', 'Income Report');




function income_display_params_dialog($vars, $err=''){
    global $t,$db;
    $t->assign('title', 'Report Parameters');
    $t->assign('report_title', 'Income Report');    
    $t->assign('discretion_options', array(
        'week'  => 'Weekly',
        'month' => 'Monthly',
        'day'   => 'Daily'
    ));
    $prs = array();
    foreach($db->get_products_list() as $pr)
	$prs[$pr['product_id']]=$pr['title'];
    $t->assign('products_options', $prs);

    set_date_from_smarty('beg_date', $vars);
    set_date_from_smarty('end_date', $vars);
    if ($vars['beg_date'] == '0000-00-00') $vars['beg_date'] = date('Y-01-01');
    if ($vars['end_date'] == '0000-00-00') $vars['end_date'] = date('Y-m-d');

    foreach ($vars as $k=>$v)
        $t->assign($k,$v);

    ///////////////////////////////////////
    $t->display('admin/header.inc.html');
    $otd = $t->template_dir ;
    $t->template_dir = dirname(__FILE__);
    $t->display('income.inc.html');
    $t->template_dir = $otd;
    $t->display('admin/footer.inc.html');
}


function income_check_params(&$vars){
    set_date_from_smarty('beg_date', $vars);
    set_date_from_smarty('end_date', $vars);
    return array();
}

function income_display_report($vars){
    global $t,$db,$config;
    // get income
    $beg_tm = $vars['beg_date'] . ' 00:00:00';
    $end_tm = $vars['end_date'] . ' 00:00:00';
    $res = array();
    $where_p = " AND product_id IN (0,".implode(',',$vars['products']).") ";
	
    switch ($vars['discretion']){
    case 'week': 
        $what  = $group = 'YEARWEEK(tm_added)';
        break;
    case 'month':
        $what   = $group = "DATE_FORMAT(tm_added, '%Y%m')";
        break;
    case 'day';
        $what  = 'FROM_DAYS(TO_DAYS(tm_added))';
        $group = 'TO_DAYS(tm_added)';
    }
    $q = $db->query($s = "SELECT $what as date,
        count(payment_id) as added_count, sum(amount) as added_amount
        FROM {$db->config[prefix]}payments p
        WHERE tm_added BETWEEN '$beg_tm' AND '$end_tm' $where_p 
        GROUP BY $group
        ");
    while ($x = mysql_fetch_assoc($q)){
        switch ($vars['discretion']){
        case 'week': 
            $year = substr($x['date'], 0, 4);
            $weeknum = substr($x['date'], 4, 2);
            if ($weeknum == 53){
                $weeknum = 0;
                $year++;
            }
            $w = date('w', strtotime("$year-01-01")); if($w==0) $w = 7;
            $weekstartday = $weeknum * 7 - $w;
            $d = date('Y-m-d', strtotime($year.'-01-01') + $weekstartday*3600*24);
            break;
        case 'month':
            $d1 = substr($x['date'], 0, 4);
            $d2 = substr($x['date'], 4, 2);
            $d = "$d1-$d2-01";
            break;
        case 'day';
            $d = $x['date'];
        }
        $res[$d] = $x;
    }
    $what = str_replace('tm_added','tm_completed', $what);
    $group = str_replace('tm_added', 'tm_completed', $group);
    $q = $db->query($s = "SELECT $what as date,
        count(payment_id) as completed_count, sum(amount) as completed_amount
        FROM {$db->config[prefix]}payments p
        WHERE tm_completed BETWEEN '$beg_tm' AND '$end_tm' $where_p 
        AND completed>0
        GROUP BY $group
        ");
    $max_total = 0;
    while ($x = mysql_fetch_assoc($q)){
        switch ($vars['discretion']){
        case 'week': 
            $year = substr($x['date'], 0, 4);
            $weeknum = substr($x['date'], 4, 2);
            if ($weeknum == 53){
                $weeknum = 0;
                $year++;
            }
            $w = date('w', strtotime("$year-01-01")); if($w==0) $w = 7;
            $weekstartday = $weeknum * 7 - $w;
            $d = date('Y-m-d', strtotime($year.'-01-01') + $weekstartday*3600*24);
            break;
        case 'month':
            $d1 = substr($x['date'], 0, 4);
            $d2 = substr($x['date'], 4, 2);
            $d = "$d1-$d2-01";
            break;
        case 'day';
            $d = $x['date'];
        }
        $res[$d] = array_merge($x, (array)$res[$d]);
        $total_completed += $x['completed_amount'];
        if ($x['completed_amount'] > $max_total) 
            $max_total = $x['completed_amount'];
    }

//////
/**/
    $members = array();
    $q = $db->query($s = "SELECT payment_id, member_id, $what as date
        FROM {$db->config[prefix]}payments p
        WHERE tm_completed BETWEEN '$beg_tm' AND '$end_tm' $where_p 
        AND completed > 0
	AND amount > 0
        ");
    $xx = array();
    while ($xxx = mysql_fetch_assoc($q)){
	$member_id = $xxx['member_id'];
	$x_date = $xxx['date'];
	if (!$members[$member_id]){
	    $members[$member_id]++;
	    $xx[$x_date]++;
	}
    }
    $xy = array();
    foreach ($xx as $date=>$paid_count){
	$xy[] = array(
	    'date' => $date,
	    'paid_count' => $paid_count
	    );
    }
//    print_r ($xy);
    
//    while ($x = mysql_fetch_assoc($q)){
    while (list(,$x) = each($xy)){
//    print_r ($x);
        switch ($vars['discretion']){
        case 'week': 
            $year = substr($x['date'], 0, 4);
            $weeknum = substr($x['date'], 4, 2);
            if ($weeknum == 53){
                $weeknum = 0;
                $year++;
            }
            $w = date('w', strtotime("$year-01-01")); if($w==0) $w = 7;
            $weekstartday = $weeknum * 7 - $w;
            $d = date('Y-m-d', strtotime($year.'-01-01') + $weekstartday*3600*24);
            break;
        case 'month':
            $d1 = substr($x['date'], 0, 4);
            $d2 = substr($x['date'], 4, 2);
            $d = "$d1-$d2-01";
            break;
        case 'day';
            $d = $x['date'];
        }
        $res[$d] = array_merge($x, (array)$res[$d]);
    }
/**/
//////


    $res1 = array();
    $keys = array_keys($res);
    $min = @min($keys); $max = @max($keys);
    list($min, $e) = round_period($min, $min, $vars['discretion']);
    list($m, $max) = round_period($max, $max, $vars['discretion']);
    for ($k=$min;$k<=$max;list($k,$e)=next_period($k, $vars['discretion'])){
        switch ($vars['discretion']){
            case 'week':
                $dp = strftime($config['date_format'], strtotime($k)) . ' - ' .
                    strftime($config['date_format'], strtotime($e));
                break;
            case 'month':
                $dp = date("m/Y", strtotime($k));                
                break;
            case 'day';
                $dp = strftime("%a&nbsp;" . $config['date_format'], strtotime($k));
                break;
        }
        $d = $k;
//        $res1[$d]['date'] = $d;
        $totals[0] = 'TOTAL';
        $totals[1] += $res[$d]['paid_count'];
        $totals[2] += $res[$d]['added_count'];
        $totals[3] += $res[$d]['completed_count'];
        $totals[4] += $res[$d]['added_amount'];
        $totals[5] += $res[$d]['completed_amount'];
        $totals[6] = '';
        $res1[$d][0] = $dp;
        $res1[$d][1] = intval($res[$d]['paid_count']);
        $res1[$d][2] = intval($res[$d]['added_count']);
        $res1[$d][3] = intval($res[$d]['completed_count']);
        $res1[$d][4] = number_format($res[$d]['added_amount'],2,'.',',');
        $res1[$d][5] = number_format($res[$d]['completed_amount'], 2,'.',',');
        if ($max_total){
            $x = round(100*$res[$d]['completed_amount']/$max_total);
            $p = round(100*$res[$d]['completed_amount']/$total_completed);
            if ($x)
            $res1[$d][6] = "
            <table align=left width=$x cellpadding=0 cellspacing=0 style='font-size: 5pt;' height=6><tr><td bgcolor=red style='background-color: red;'></td></tr></table>
            &nbsp;($p%)
            ";
            else
            $res1[$d][6] = '';
        }
    };
    ksort($res1);

$totalacv=  $totals[5] ;
    $totals[4] = number_format($totals[4], 2,'.',',');
    $totals[5] = number_format($totals[5], 2,'.',',');

    ///// DISPLAY RESULT 

    $t->assign('header', array(
        0 => 'Date',
	1 => 'Paid Count',
        2 => 'Added Count',
        3 => 'Completed Count',
        4 => 'Added Amount',
        5 => 'Completed Amount',
       6 => 'Percent Graph'
    ));
$acv= ($totals[1]>0)?$totalacv / $totals[1]:0;
$acv= round($acv,3);
    $t->assign('acv', $acv);
    $t->assign('title', 'Income Report');
    $t->assign('report', $res1);
    $t->assign('totals', $totals);
    $t->display('admin/header.inc.html');
    $otd = $t->template_dir ;
    $t->template_dir = dirname(__FILE__);
    $t->display('income_result.inc.html');
    $t->template_dir = $otd;
    $t->display('admin/footer.inc.html');
    print "<br /><br /><font size=1>Creation date: ".strftime($config['time_format'])."</font>";
}

?>