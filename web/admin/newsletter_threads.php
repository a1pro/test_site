<?php

$avoid_timeout = 1;

include "../config.inc.php";
$t = new_smarty();
include "login.inc.php";
ignore_user_abort(true);
@set_time_limit(0);


check_lite();
admin_check_permissions('email');

function get_available_to_list(){
    global $db, $config;
    $res = array();
    $res['guest']      = '* Guests';
    $res['active']     = '* All Active Members (paid and not-expired)';
    $res['expired']  = '* All Expired Members (paid and expired)';
    $products = $db->get_products_list();
    foreach ($products as $p){
        $id = $p['product_id'];
        $n  = $p['title'];
        $res["active_product-$id"] = "Active Members of '$n'";
        $res["expired_product-$id"] = "Expired Members of '$n'";
    }
    return $res;
}

function get_auto_subscribe_list(){
    global $db, $config;
    $res = array();
    $res['all']      = '* All Members';
    if ($config['use_affiliates'])
    	$res['aff']      = '* All Affiliates';
    $products = $db->get_products_list();
    foreach ($products as $p){
        $id = $p['product_id'];
        $n  = $p['title'];
        $res["purchase_product-$id"] = "Members, purchased '$n' first time";
    }
    return $res;
}



///////////////////////////////////////////////////////////////

function display_confirm($title, $message){
    global $t, $vars;
    $t->assign('title', $title);
    $t->assign('message', $message);
    $t->assign('vars', $vars);
    $t->display('admin/confirm.html');
    exit();
}

function display_form($vars=''){
    global $t, $config;
    settype($vars, 'array');
    $t->assign('available_to_list', get_available_to_list());
    $t->assign('auto_subscribe_list', get_auto_subscribe_list());
    $t->assign('config', $config);
    $t->assign('vars', $vars);
    $t->assign('add', 1);
    $t->display("admin/newsletter_thread_form.html");
}

function display_threads_list(){
    global $db, $t, $vars;
    global $all_count, $count, $start;
    $all_count = $db->get_threads_list_c();
    $tl = & $db->get_threads_list($start, $count, '', $amember_cp=true);
    $t->assign('tl', $tl);
    $t->display('admin/newsletter_threads.html');
}

function subscribe_members ( $auto_subscribe = array() , $thread_id = '' ) {
	global $db, $config;
	if (!count($auto_subscribe)) return;

    $emails = array();
    $output = array();

    if (!in_array('all', $auto_subscribe)) {
	    // products selected
	    $product_ids = array();
	    foreach (array_unique((array)$auto_subscribe) as $autos){
	        if (preg_match('/^purchase_product-(\d+)$/', $autos, $regs)){
	            $product_ids[] = $regs[1];
	        }
	    }

	    if ($product_ids){
	        $q = $db->query($s = "
	            SELECT DISTINCT u.*
	            FROM {$db->config['prefix']}members u
	                LEFT JOIN {$db->config['prefix']}payments p
	                ON (p.member_id=u.member_id)
	            WHERE p.completed > 0 AND p.product_id IN (".join(',',$product_ids).")
	            AND p.begin_date <= NOW() AND p.expire_date >= NOW()
	            AND IFNULL(u.unsubscribed,0) = 0
	            GROUP BY u.member_id
	        ");
	        while ($u = mysql_fetch_assoc($q)){
	            if ($emails[$u['email']]) continue;

	            $u['data'] = unserialize($u[data]);
	            $output[] = $u;

	            $emails[$u['email']]++;
	        }
	    }
	 }

    $where_conds = array();
    foreach (array_unique((array)$auto_subscribe) as $autos){
        switch ($autos){
            case 'all':
                $where_conds[] = 1; break;
        }
    }
    if ($where_conds){
        $where_conds = join(' OR ', $where_conds);
        if ($where_conds)
            $where_conds = " AND $where_conds ";
        $q = $db->query($s = "
            SELECT DISTINCT u.*
            FROM {$db->config['prefix']}members u
            WHERE IFNULL(u.unsubscribed,0) = 0 $where_conds
        ");
        while ($u = mysql_fetch_assoc($q)){
            if ($emails[$u['email']]) continue;

            $u['data'] = unserialize($u[data]);
            $output[] = $u;

            $emails[$u['email']]++;
        }
    }

    foreach ($output as $user) {

    	//auto subscribe members to added thread
    	$db->add_member_threads($user['member_id'], array($thread_id));

    }

}

function create_thread($vars='') {
	 global $db, $config, $t;
	 settype($vars, 'array');

    $errors = array();
    $vars = get_input_vars();

    if (!strlen($vars['thread_title'])){
        $errors[] = "'Title' is a required field";
    }
    if (!count($vars['available_to'])){
        $errors[] = "'Available to' is a required field";
    }

    if ($res = $db->test_autosubscribe ($vars['available_to'], $vars['auto_subscribe'])){
        $errors[] = "'Auto Subscribe' field not correspond to 'Available to' field";
        foreach ($res as $err)
            $errors[] = $err;
    }

    if ($errors){
        $t->assign('errors', $errors);
        //display_threads_list();
        display_form($vars);
        return;

    }

    $vars['available_to'] = $db->escape_array ($vars['available_to']);
    $vars['auto_subscribe'] = $db->escape_array ($vars['auto_subscribe']);

    $available_to = implode(",", $vars['available_to']);
    $auto_subscribe = implode(",", $vars['auto_subscribe']);

    $q = $db->query($s = "
            INSERT INTO {$db->config['prefix']}newsletter_thread
            (thread_id, title, description, is_active, blob_available_to, blob_auto_subscribe)
            VALUES (null, '".$db->escape($vars['thread_title'])."', '".$db->escape($vars['thread_description'])."',
            '".intval($vars['is_active'])."', '$available_to', '$auto_subscribe')
        ");

    $thread_id = mysql_insert_id ($db->conn);

    if ($vars['is_subscribe_members']) {
    	subscribe_members ($vars['auto_subscribe'], $thread_id);
    }

    $t->assign('link', "newsletter_threads.php");
    $t->display("admin/newsletter_thread_saved.html");

}

function display_edit_form(){
    global $vars, $db, $t;

    $tr = & $db->get_thread(intval($vars['thread_id']));

    $tr['available_to']     = $vars['available_to']     ? $vars['available_to']   : explode(",", $tr['available_to']);
    $tr['auto_subscribe'] 	= $vars['auto_subscribe']   ? $vars['auto_subscribe'] : explode(",", $tr['auto_subscribe']);

    $t->assign('vars', $tr);
    $t->assign('available_to_list', get_available_to_list());
    $t->assign('auto_subscribe_list', get_auto_subscribe_list());
    $t->display('admin/newsletter_thread_form.html');
}

function update_thread($vars='') {
	 global $db, $config, $t;
	 settype($vars, 'array');

    $errors = array();
    $vars = get_input_vars();

    if (!strlen($vars['thread_title'])){
        $errors[] = "'Title' is a required field";
    }
    if (!count($vars['available_to'])){
        $errors[] = "'Available to' is a required field";
    }

    if ($res = $db->test_autosubscribe ($vars['available_to'], $vars['auto_subscribe'])){
        $errors[] = "'Auto Subscribe' field not correspond to 'Available to' field";
        foreach ($res as $err)
            $errors[] = $err;
    }

    if ($errors){
        $t->assign('errors', $errors);
        return display_edit_form();
    }

    $vars['available_to'] = $db->escape_array ($vars['available_to']);
    $vars['auto_subscribe'] = $db->escape_array ($vars['auto_subscribe']);

    $available_to = implode(",", $vars['available_to']);
    $auto_subscribe = implode(",", $vars['auto_subscribe']);

    $q = $db->query($s = "
            UPDATE {$db->config['prefix']}newsletter_thread SET
            title='".$db->escape($vars['thread_title'])."',
            description='".$db->escape($vars['thread_description'])."',
            is_active='".intval($vars['is_active'])."',
            blob_available_to='$available_to',
            blob_auto_subscribe='$auto_subscribe'
            WHERE thread_id = '".intval($vars['thread_id'])."'
        ");

    $t->assign('link', "newsletter_threads.php");
    $t->display("admin/newsletter_thread_saved.html");

}

function delete_thread(){
    global $db, $t, $vars;
    if (!$vars['confirm']){
        $tr = $db->get_thread($vars['thread_id']);
        display_confirm("Delete thread: $tr[thread_title]", "
        <center>Do you really want to delete newsletter thread: $tr[thread_title]?
        </center>
        ");
        return;
    } elseif ($vars['confirm'] != 'Yes'){
        display_edit_form();
        return;
    }
    $tr = $db->get_thread($vars['thread_id']);
    $db->delete_thread($vars['thread_id']);
    $t->assign('msg', "Thread deleted");
    $t->assign('link', 'newsletter_threads.php');
    $t->display("admin/newsletter_thread_saved.html");
}



//////////////////// main ////////////////////////////////////////

$vars = get_input_vars();
if ($vars['thread_id']) $t->assign('thread_id', $vars['thread_id']);

switch ($vars['action']){
    case 'new':
    	  display_form();
        break;
    case 'create':
    	  create_thread($vars);
        break;
    case 'edit':
    	  display_edit_form();
        break;
    case 'update':
    	  update_thread($vars);
        break;
    case 'delete':
        delete_thread();
        break;
    default:
    	  display_threads_list();
        break;
}

?>
