<?php 

/*
*
*
*    Author: Alex Scott
*    Email: alex@cgi-central.net
*    Web: http://www.cgi-central.net
*    Details: Coupons management
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5135 $)
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
admin_check_permissions('manage_coupons');

$count = 20;

function display_batches_list(){
    global $db, $t;
    global $start, $count, $all_count;

    $list = $db->get_coupon_batches();
    $all_count = count($list);
    $t->assign('batches', @array_slice($list, $start, $count));
    $t->display('admin/coupon_batches.html');
}

function display_generate_form(){
    global $db, $t, $vars;
    global $start, $count, $all_count;

    $t->assign('discount_types', array('%' => '%', '' => 'USD'));
    $products = array();
    foreach ($db->get_products_list() as $p)
        $products[ $p['product_id'] ] = $p['title'];
    $t->assign('products', $products);
    $t->assign('action', 'generate');
    $t->assign('vars', $vars);
    $t->display('admin/coupon_gen.html');
}

function validate_generate_form(){
    global $db, $t, $vars;
    $error = array();
    if ($vars['count'] <= 0) $error[] = 'Please enter numeric Coupons Count';
    if ($vars['use_count'] <= 0) $error[] = 'Please enter numeric Coupon Usage Count';
    if ($vars['member_use_count'] <= 0) $error[] = 'Please enter numeric Member Coupon Usage Count';
    if ($vars['code_len'] <= 0) $error[] = 'Please enter numeric Code Length';
    if ($vars['code_len'] > 32) $error[] = 'Please enter numeric Code Length less or equal 32';
    if ($vars['discount_v'] <= 0) $error[] = 'Please enter numeric discount value';
    if ($error) {
        $t->assign('error', $error);
        display_generate_form();
        return 0;
    }
    return 1;
}

function generate_coupons(){
    global $db, $t, $vars;
    $vars['discount'] = trim("$vars[discount_v] $vars[discount_t]");
    set_date_from_smarty('begin_date', $vars);
    set_date_from_smarty('expire_date', $vars);
    if ($vars['disable_date']){
        unset($vars['begin_date']);
        unset($vars['expire_date']);
    }
    $batch_id = $db->generate_coupons($vars);
    $coupons = $db->get_coupons('batch_id', $batch_id);
    admin_log("Coupons generated", "coupons", $batch_id);
    $t->assign('coupons', $coupons);
    $t->display('admin/coupon_generated.html');
}

function view_batch(){
    global $db, $t, $vars;
    global $all_count, $start, $count;
    $all_count = $db->get_coupons_c('batch_id', $vars['batch_id']);
    $coupons = $db->get_coupons('batch_id', $vars['batch_id'], $start, $count);
    $t->assign('coupons', $coupons);
    $t->display('admin/coupon_batch.html');
}

function edit_batch(){
    global $db, $t, $vars;
    $coupons = $db->get_coupons('batch_id', $vars['batch_id'],0,1);
    $batch = $coupons[0];
    list($batch['discount_v'], $batch['discount_t']) = split(' ', $batch['discount']);
    $t->assign('discount_types', array('' => 'USD', '%' => '%'));
    $products = array();
    foreach ($db->get_products_list() as $p)
        $products[ $p['product_id'] ] = $p['title'];
    $t->assign('products', $products);
    $t->assign('batch', $batch);
    $batch_selected = split(',', $batch['product_id']);
    $t->assign('batch_selected', $batch_selected);
    $t->display('admin/coupon_batch_edit.html');
}

function save_batch(){
    global $db, $t, $vars;

    // validate first    
    $error = array();
    if ($vars['use_count'] <= 0) $error[] = 'Please enter numeric Coupons Usage Count';
    if ($vars['member_use_count'] <= 0) $error[] = 'Please enter numeric Member Coupons Usage Count';
    if ($vars['discount_v'] <= 0) $error[] = 'Please enter numeric discount value';
    if ($error) {
        $t->assign('error', $error);
        edit_batch();
        return 0;
    }

    set_date_from_smarty('begin_date', $vars);
    set_date_from_smarty('expire_date', $vars);
    $vars['discount'] = trim("$vars[discount_v] $vars[discount_t]");
    if ($vars['disable_date']){
        unset($vars['begin_date']);
        unset($vars['expire_date']);
    }

    admin_log("Coupon batch changed", "coupons", $vars['batch_id']);
    $db->coupons_batch_edit($vars['batch_id'], $vars);
    view_batch();
}

function view_coupon(){
    global $db, $t, $vars;
    global $all_count, $start, $count;
    $coupons = $db->get_coupons('coupon_id', $vars['coupon_id']);
    $all_count = count($coupons);
    $coupons = array_slice($coupons, $start, $count);
    $t->assign('coupons', $coupons);
    $t->display('admin/coupon_view.html');
}

function delete_coupon(){
    global $db, $t, $vars;

    $db->delete_coupon($vars['coupon_id']);
    $t->assign('msg', 'Coupon Deleted');
    $t->display('admin/coupon_msg.html');

}

function edit_coupon(){
    global $db, $t, $vars;

    $c = $db->get_coupons('coupon_id', $vars['coupon_id']);
    $c = $c[0];
    $t->assign('vars', $c);
    $t->assign('action', 'save_coupon');
    $t->display('admin/coupon_edit.html');
}

function save_coupon(){
    global $db, $t, $vars;

    $db->coupon_edit($vars['coupon_id'], $vars);
    view_batch();
}

function delete_batch(){
    global $db, $t, $vars;

    $db->coupons_batch_delete($vars['batch_id']);
    display_batches_list();
}

////////////////////////////// MAIN ///////////////////////////////////
$vars = get_input_vars();
extract($vars, EXTR_OVERWRITE);

$start = intval($vars['start']);
$count = 20;

switch (@$vars['action']){
    case '': 
        display_batches_list();
        break;
    case 'generate_form':
        display_generate_form();
        break;
    case 'generate':
        if (validate_generate_form())
            generate_coupons();
        break;
    case 'view_batch':
        view_batch();
        break;
    case 'edit_batch':
        edit_batch();
        break;
    case 'save_batch':
        save_batch();
        break;
    case 'delete_batch':
        delete_batch();
        break;
    case 'view_coupon':
        view_coupon();
        break;
    case 'delete_coupon':
        delete_coupon();
        break;
    case 'edit_coupon':
        edit_coupon();
        break;
    case 'save_coupon':
        save_coupon();
        break;
    default: 
        fatal_error("Unknown action: '$action' for coupons.php");
}

?>
