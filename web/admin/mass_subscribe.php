<?php 
@set_time_limit(600);
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Products
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5146 $)
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

$vars = get_input_vars();
extract($vars, EXTR_OVERWRITE);

function display_form(){
    global $db,$t;
    $pl = $db->get_products_list();
    //
    $prl = array();
    foreach ($pl as $p)
        $prl[$p['product_id']] = 'Subscribe to "' . $p['title'] . '"';
    $t->assign('products', $prl);
    //
    $sfo = array(
        'ACT-ANY' => 'ALL ACTIVE MEMBERS',
        'ALL-ANY' => 'ALL MEMBERS (both active and expired)',
        'PENDING-ANY' => 'ALL PENDING MEMBERS',
    );

    foreach ($pl as $p)
        $sfo['ACT-'.$p['product_id']] = "Active subscribers of \"{$p[title]}\"";
    foreach ($pl as $p)
        $sfo['ALL-'.$p['product_id']] = "All subscribers of \"{$p[title]}\" (both active and expired)";
    $t->assign('select_from_options', $sfo);
    /// 
    $ado = array(
        'SAME' => 'Assign the same dates as for original subscription',
        'FIXED' => 'Use fixed dates (specified below)',
        'LIFETIME' => 'Make subscription lifetime (from today to 31/12/2037)'
    );
    $t->assign('assign_date_options', $ado);
    ///

    $t->display("admin/mass_subscribe.html");
}

function check_form(){
    global $vars, $t;
    $err = array();
    if ((in_array('PENDING-ANY', $vars['select_from'])) &&
        ($vars['assign_date'] == 'SAME'))
        $err[] = "If you choose pending members, date cannot be SAME, choose FIXED or LIFETIME";

    if (!$vars['select_from'])
        $err[] = "Please choose selection criteria";
    if (!$vars['assign_date'])
        $err[] = "Please choose date assignment policy";
    else {
       if (($vars['assign_date'] == 'FIXED') && !$vars['period_begin'])
           $err[] = "Please enter fixed subscription start date";
       if (($vars['assign_date'] == 'FIXED') && !$vars['period_end'])
           $err[] = "Please enter fixed subscription expiration date";
    }
    if (!$vars['product_id'])
        $err[] = "Please enter product id for mass subscription";
    if ($err){
        $t->assign('error', $err);
        return;
    }
    return 1;
}

function select_members(){
    global $vars, $db;
    // select members list for given conditions
    // returns array(member_id => array(begin_date => xx, expire_date=>xx), ...)
    $res = array();
    foreach ($vars['select_from'] as $cond){
//        print "$cond...<br />";
        list($how, $what) = split('-', $cond);
//        print "$how=$what<br />";
        if ($how == 'PENDING'){
            $q = $db->query("SELECT member_id, '2000-01-01', '2099-12-31'
                FROM {$db->config[prefix]}members 
                WHERE status = 0
            ");
        } else {
            if ($how == 'ACT') // only active
                $where_add1 = "AND p.begin_date <=NOW() AND p.expire_date>=NOW()";
            else 
                $where_add1 = "";
            if ($what == 'ANY')
                $where_add2 = "";
            else
                $where_add2 = "AND p.product_id = $what";
            $q = $db->query($s = "SELECT p.member_id, p.begin_date, p.expire_date
            FROM {$db->config[prefix]}payments p 
            WHERE 1 $where_add1 $where_add2 AND p.completed=1
            ");
        }
        while (list($member_id, $b, $e) = mysql_fetch_row($q))
            if ($res[$member_id]['expire_date'] < $e){
                $res[$member_id]['begin_date'] = $b;
                $res[$member_id]['expire_date'] = $e;
            }
    }
    return $res;
}



function mass_subscribe_confirm(){
    global $vars, $db, $t, $config;

    if (!check_form()){
        display_form();
        return;
    }


    $pl = $db->get_products_list();
    //
    $prl = array();
    foreach ($pl as $p)
        $prl[$p['product_id']] = 'Subscribe to "' . $p['title'] . '"';
    $t->assign('product', $prl[$vars['product_id']]);
    //
    $sfo = array(
        'ACT-ANY' => 'ALL ACTIVE MEMBERS',
        'ALL-ANY' => 'ALL MEMBERS (both active and expired)',
        'PENDING-ANY' => 'ALL PENDING MEMBERS',
    );

    foreach ($pl as $p)
        $sfo['ACT-'.$p['product_id']] = "Active subscribers of \"{$p[title]}\"";
    foreach ($pl as $p)
        $sfo['ALL-'.$p['product_id']] = "All subscribers of \"{$p[title]}\" (both active and expired)";
    $sfoR = array();
    foreach ($vars['select_from'] as $k)
        $sfoR[] = $sfo[$k];
    $sfoR = join('<br /><b>AND</b><br />', $sfoR);
    $t->assign('select_from', $sfoR);
    /// 
    $ado = array(
        'SAME' => 'Assign the same dates as for original subscription',
        'FIXED' => 'Use fixed dates (specified below)',
        'LIFETIME' => 'Make subscription lifetime (from today to 31/12/2037)'
    );
    $t->assign('assign_date_options', $ado);
    $adoR = $ado[$vars['assign_date']];
    if ($vars['assign_date'] == 'FIXED')
        $adoR .= "<br />".strftime($config['date_format'], strtotime($vars['period_begin'])) 
              .  " - " .strftime($config['date_format'], strtotime($vars['period_end']));
    $t->assign('assign_date', $adoR);

    ///
    $t->assign('receipt_id', $vars['receipt_id']);

    //
    $t->assign('vars', serialize($vars));
    $t->assign('count_members', count(select_members()));
    $t->display("admin/mass_subscribe_confirm.html");    

}

function mass_subscribe(){
    global $vars, $db, $t, $config;

    $vars = unserialize($vars['vars']);

    if (!check_form()){
        display_form();
        return;
    }

    $added = 0;
    foreach ( select_members() as $member_id => $m){
        switch ($vars['assign_date']){
            case 'SAME': 
                $b = $m['begin_date'];
                $e = $m['expire_date'];
            break;
            case 'FIXED':
                $b = date('Y-m-d', strtotime($vars['period_begin']));
                $e = date('Y-m-d', strtotime($vars['period_end']));
            break;
            case 'LIFETIME':
                $b = date('Y-m-d');
                $e = '2037-12-31';
            break;
            default:
                die("Unknown assign_date method - internal error");
        }
//        print "$b=$e<br />";
        $p = array(
            'member_id' => $member_id,
            'product_id' => $vars['product_id'],
            'begin_date' => $b,
            'expire_date' => $e,
            'completed' => 1,
            'paysys_id' => 'manual',
            'receipt_id' => $vars['receipt_id'],
            'amount' => $vars['amount'],
        );
        $db->add_payment($p);
        $added++;
    }

    admin_log("Mass Subscribe $sf to Product #$vars[product_id]", "products", $vars['product_id']);
    $t->assign('text', "$added subscriptions added succesfully.");
    $t->assign('link', "products.php");
    $t->display("admin/mass_subscribed.html");    

}

////////////////////////////////////////////////////////////////////////////
//
//                      M A I N
//
////////////////////////////////////////////////////////////////////////////

$vars = get_input_vars();
admin_check_permissions('manage_payments');
$error = array();
switch ($vars['action']){
    case 'mass_subscribe':
        check_lite();
        check_demo();
        mass_subscribe();
        break;
    case 'mass_subscribe_confirm':
        check_lite();
        check_demo();
        mass_subscribe_confirm();
        break;
    case 'browse': case '': 
        display_form();
        break;
    default: 
        fatal_error("Unknown action: '$action'");
}


?>
