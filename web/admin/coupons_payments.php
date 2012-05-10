<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Payments
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1739 $)
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
admin_check_permissions('list_payments');
extract($vars, EXTR_OVERWRITE);

$vars['only_completed'] = '1'; // Display completed payments only
$vars['q_where'] = 'coupon_code'; // Search by Coupon code

function get_payments_by_date($vars){
    global $db, $t;

    $count = 20;
    list($all_count, $all_amount) = $db->get_payments_c($vars['beg_date'], $vars['end_date'], 
                intval($vars['only_completed']), $vars['list_by'], '', 0, '', '', 1); 
    $list = $db->get_payments($vars['beg_date'], $vars['end_date'], 
                intval($vars['only_completed']), $vars['start'], $count, $vars['list_by'], '', 0, '', '', 1); 

    return array($all_count, $all_amount, $list);
}


function get_payments_by_string($vars){
    global $db, $t;

    $count = 20;

    list($all_count, $all_amount) = $db->get_payments_c($vars['beg_date'], $vars['end_date'], 
                intval($vars['only_completed']), $vars['list_by'], null,
                1, $vars['q'], $vars['q_where'], 1); 
    $list = $db->get_payments($vars['beg_date'], $vars['end_date'], 
                intval($vars['only_completed']), $vars['start'], $count, $vars['list_by'],
                null, 1, $vars['q'], $vars['q_where'], 1); 
    return array($all_count, $all_amount, $list);
}

if ($vars['beg_dateDay'])
   set_date_from_smarty('beg_date', $vars);
elseif ($vars['beg_date'] == '') 
   $vars['beg_date'] = date('Y-m-d');

if ($end_dateDay)
   set_date_from_smarty('end_date', $vars);
elseif ($vars['end_date'] == '') 
   $vars['end_date'] = date('Y-m-d');

if ($vars['beg_date'] > $vars['end_date']){
    $s = $vars['beg_date'];
    $vars['beg_date'] = $vars['end_date'];
    $vars['end_date'] = $s;
}
$t->assign('beg_date', $vars['beg_date']);
$t->assign('end_date', $vars['end_date']);

if ($vars['type'] == 'string'){
    list($all_count, $all_amount, $list) = get_payments_by_string($vars);
} else {
    list($all_count, $all_amount, $list) = get_payments_by_date($vars);
}


$t->assign('list', $list);
$t->assign('all_count', $all_count);
$t->assign('all_amount', $all_amount);
///////////////////////////////////////////
$t->assign('list_by_options', array(
    '' =>         'Record change date',
    'add' =>      'Record insertion date',
    'complete' => 'Payment completion date'
));

switch ($vars['list_by']){
    case 'add':
        $list_by_field = 'tm_added';
        $list_by_title = 'Record<br />Added';
    break;
    case 'complete':
        $list_by_field = 'tm_completed';
        $list_by_title = 'Record<br />Completed';
    break;
    case '': default:
        $list_by_field = 'time';
        $list_by_title = 'Change Time';
}
$t->assign('list_by_field', $list_by_field);
$t->assign('list_by_title', $list_by_title);
$t->display('admin/coupons_payments.html');


?>
