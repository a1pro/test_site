<?php

add_report('tax', 'Tax report');




function tax_display_params_dialog($vars, $err=''){
    global $t;
    $t->assign('title', 'Report Parameters');
    $t->assign('report_title', 'Tax report');    
    $t->assign('discretion_options', array(
        'week'  => 'Weekly',
        'month' => 'Monthly',
        'day'   => 'Daily'
    ));
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


function tax_check_params(&$vars){
    set_date_from_smarty('beg_date', $vars);
    set_date_from_smarty('end_date', $vars);
    return array();
}

function tax_display_report($vars){
    global $t,$db,$config;

    // get income
    $beg_tm = $vars['beg_date'] . ' 00:00:00';
    $end_tm = $vars['end_date'] . ' 23:59:59';
    $res = array();
    switch ($vars['discretion']){
    case 'week': 
        $what  = $group = 'YEARWEEK(tm_completed)';
        break;
    case 'month':
        $what   = $group = "DATE_FORMAT(tm_completed, '%Y%m')";
        break;
    case 'day';
        $what  = 'FROM_DAYS(TO_DAYS(tm_completed))';
        $group = 'TO_DAYS(tm_completed)';
    }
    $q = $db->query($s = "SELECT $what as date,
        count(payment_id) as completed_count, sum(tax_amount) as completed_amount, sum(amount) as completed_total
        FROM {$db->config[prefix]}payments p
        WHERE tm_completed BETWEEN '$beg_tm' AND '$end_tm'
        AND completed>0 and tax_amount > 0
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
            $w = date('w', strtotime("$year-01-01"));
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
    $res1 = array();
    $keys = array_keys($res);
    if (count($keys) > 0) {
        $min = @min($keys);
        $max = @max($keys);
    } else {
        $min = 0;
        $max = 0;
    }
    
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
        $totals[1] += $res[$d]['completed_total'];
        $totals[2] += $res[$d]['completed_amount'];
        $res1[$d][0] = $dp;
        $res1[$d][1] = number_format($res[$d]['completed_total'],2,'.',',');
        $res1[$d][2] = number_format($res[$d]['completed_amount'], 2,'.',',');
    };
    ksort($res1);

    $totals[1] = number_format($totals[1], 2,'.',',');
    $totals[2] = number_format($totals[2], 2,'.',',');

    ///// DISPLAY RESULT 

    $t->assign('header', array(
        0 => 'Date',
        1 => 'Total Amount',
        2 => 'Tax Amount'
    ));
    $t->assign('title', 'Tax Report');
    $t->assign('report', $res1);
    $t->assign('totals', $totals);
    $t->display('admin/header.inc.html');
    $otd = $t->template_dir ;
    $t->template_dir = dirname(__FILE__);
    $t->display('income_result.inc.html');
    $t->template_dir = $otd;
    $t->display('admin/footer.inc.html');
    print "<br><br><font size=1>Creation date: ".strftime($config['time_format'])."</font>";
}

?>