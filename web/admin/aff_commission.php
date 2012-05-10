<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Affiliate commission
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3171 $)
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


if ($_GET['do'] == 'aff_menu') {
    $t->display('admin/aff_menu.html'); 
    exit();
}

admin_check_permissions('affiliates');

$vars = get_input_vars();

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
    $m -= 2;
    if ($m <= 0){
        $y--;
        $m = 12 - $m;
    }
    return "{$y}_{$m}";
}

/*******************************************/
$t->assign('year_month_options', get_ym_options());
$t->assign('default_month', get_default_ym());
$t->assign('payout_methods', aff_get_payout_methods() + array('' => 'Not selected', 'ALL' => 'All payouts (for reporting)'));

if ($vars['action'] == 'aff_payout_export'){
    $rand = $vars['payout_sess_id'];    
    $rows = array();
    foreach ($_SESSION['_amember_aff_commission'][$rand]['rows'] 
            as $member_id => $to_pay){
        $r = $db->get_user($member_id);
        $r['to_pay'] = $to_pay;
        $rows[] = $r;
    }
    $dat1 = $_SESSION['_amember_aff_commission'][$rand]['dat1'];
    $dat2 = $_SESSION['_amember_aff_commission'][$rand]['dat2'];
    $payout_method = $_SESSION['_amember_aff_commission'][$rand]['payout_method'];
    $func_name = 'aff_pay_commission_'.$payout_method;
    $func_name($dat1, $dat2, $payout_method, $rows);
    exit();
}

if ($ym=$vars['year_month']){
    list($y, $m) = split('_', $ym);
    $m = sprintf('%02d', $m);
    $dat1 = "{$y}-{$m}-01";
    $dat2 = date('Y-m-t', strtotime($dat1));
    $t->assign('dat1', $dat1);
    $t->assign('dat2', $dat2);
    $rows = aff_get_payout($dat2, $vars['payout_method']);
    if ($vars['pay']){
        $to_pay = array_keys($vars['pay']);
        foreach ($rows as $k=>$r){
            if (!in_array($r['member_id'], $to_pay))
                unset($rows[$k]);
        }
        
        if ($vars['payout_method'] && 
          function_exists('aff_pay_commission_'.$vars['payout_method'])){
            srand(time());
            $rand = rand(1000,9999);
            
            foreach ($rows as $r){
                $_SESSION['_amember_aff_commission'][$rand]['rows'][$r['member_id']] = 
                    $r['to_pay'];
            }
            $_SESSION['_amember_aff_commission'][$rand]['dat1']= $dat1;
            $_SESSION['_amember_aff_commission'][$rand]['dat2']= $dat2;
            $_SESSION['_amember_aff_commission'][$rand]['payout_method']= $vars['payout_method'];
            
            $t->assign('payout_sess_id', $rand);
            $t->assign('payout_export', 1);
        }
        
        if ($vars['payout_method'] == 'ALL') {
            
            $rands = array();
            $links = array();
            foreach ($rows as $r){
                $payout_method = $r['aff_payout_type'];
                if (!array_key_exists($payout_method, $rands) && function_exists('aff_pay_commission_'.$payout_method)){
                    srand(time());
                    $rand = rand(1000,9999) * $r['member_id'];
                    $rand = substr ($rand, 0, 4);
                    
                    $rands[$payout_method] = $rand;
                    $links[] = "<a href=\"aff_commission.php?action=aff_payout_export&payout_sess_id=".$rand."\">Export Payout records for \"".$payout_methods[$payout_method]."\"</a><br />";
                }
            }
            foreach ($rows as $r){
                $payout_method = $r['aff_payout_type'];
                if (function_exists('aff_pay_commission_'.$payout_method)){
                    $rand = $rands[$payout_method];
                    $_SESSION['_amember_aff_commission'][$rand]['rows'][$r['member_id']] = 
                        $r['to_pay'];
                    $_SESSION['_amember_aff_commission'][$rand]['dat1']= $dat1;
                    $_SESSION['_amember_aff_commission'][$rand]['dat2']= $dat2;
                    $_SESSION['_amember_aff_commission'][$rand]['payout_method']= $payout_method;
                }
            }
        }
        
        aff_pay_commissions($dat2, $rows, date('Y-m-d'));
        admin_log("Paid afffiliate commission $dat2=>$td");
        $t->assign('rows', $rows);
        $t->assign('links', $links);
        $t->assign('payout_method', $vars['payout_method']);
        $t->display('admin/aff_commission_paid.html');
    } else {
        $t->assign('rows', $rows);
        $t->display("admin/aff_commission_form.html");
    }
} else 
    $t->display("admin/aff_commission_form.html");

