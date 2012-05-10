<?php 

$avoid_timeout = 1;

include "../config.inc.php";
$t = new_smarty();
include "login.inc.php";
ignore_user_abort(true);
@set_time_limit(0);


check_lite();
admin_check_permissions('email');


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
    global $t, $config, $db;
    settype($vars, 'array');

	 $threads_list = $db->get_newsletter_threads();
	 $guest_threads_list = array();
	 foreach ($threads_list as $thread_id=>$thread_title) {
		if ($db->is_thread_available_to_guests ($thread_id))
	   	$guest_threads_list[$thread_id] = $thread_title;
	 }

    $t->assign('threads_list', $guest_threads_list);
    $t->assign('config', $config);
    $t->assign('vars', $vars);
    $t->assign('add', 1);
    $t->display("admin/newsletter_guest_form.html");
}

function display_guest_list(){
    global $db, $t, $vars;
    global $all_count, $count, $start;
    $count = 20;
    
    if ($vars['type'] == 'string'){
    	$all_count = $db->get_guests_list_c( $vars['q'] );
    	$gl = & $db->get_guests_list($start, $count, $vars['q']);
    } else {
    	$all_count = $db->get_guests_list_c( '', $vars['threads'] );
    	$gl = & $db->get_guests_list($start, $count, '', $vars['threads']);
    }
    
    $t->assign('gl', $gl);

	 $threads_list = $db->get_newsletter_threads();
	 $guest_threads_list = array();
	 foreach ($threads_list as $thread_id=>$thread_title) {
		if ($db->is_thread_available_to_guests ($thread_id))
	   	$guest_threads_list[$thread_id] = $thread_title;
	 }

    $t->assign('threads_list', $guest_threads_list);

    $t->display('admin/newsletter_guest.html');
}

function create_guest($vars='') {
	 global $db, $config, $t;
	 settype($vars, 'array');

    $errors = array();
    $vars = get_input_vars();

    if (!strlen($vars['guest_name'])){
        $errors[] = "'Name' is a required field";
    }
    if (!strlen($vars['guest_email'])){
        $errors[] = "'Email' is a required field";
    }

    if ($errors){
        $t->assign('errors', $errors);
        display_guest_list();
        display_form($vars);
        return;
        
    }

    //check member
    $is_member = ($db->users_find_by_string($vars['guest_email'], 'email', 1)) ? true : false;;
    if (!$is_member) {
    
	    $q = $db->query($s = "
	            INSERT INTO {$db->config['prefix']}newsletter_guest
	            (guest_id,guest_name,guest_email)
	            VALUES (null, '".$db->escape($vars['guest_name'])."', '".$db->escape($vars['guest_email'])."')
	        ");
	    $guest_id = mysql_insert_id($db->conn);
	    $db->add_guest_threads($guest_id, $vars['threads']);
	 }

    $t->assign('link', "newsletter_guests.php");
    $t->display("admin/newsletter_guest_saved.html");
    
}

function display_edit_form(){
    global $vars, $db, $t;
    $g = & $db->get_guest(intval($vars['guest_id']));
    $threads = $db->get_guest_threads($vars['guest_id']);
    $threads = array_keys($threads);

    $t->assign('vars', $g);
    $t->assign('threads', $threads);

	 $threads_list = $db->get_newsletter_threads();
	 $guest_threads_list = array();
	 foreach ($threads_list as $thread_id=>$thread_title) {
		if ($db->is_thread_available_to_guests ($thread_id))
	   	$guest_threads_list[$thread_id] = $thread_title;
	 }

    $t->assign('threads_list', $guest_threads_list);

    $t->display('admin/newsletter_guest_form.html');
}

function update_guest($vars='') {
	 global $db, $config, $t;
	 settype($vars, 'array');

    $errors = array();
    $vars = get_input_vars();

    if (!strlen($vars['guest_name'])){
        $errors[] = "'Name' is a required field";
    }
    if (!strlen($vars['guest_email'])){
        $errors[] = "'Email' is a required field";
    }

    if ($errors){
        $t->assign('errors', $errors);
        return display_edit_form();
    }
    
    //check member
    $is_member = ($db->users_find_by_string($vars['guest_email'], 'email', 1)) ? true : false;;
    if (!$is_member) {
    
	    $q = $db->query($s = "
	            UPDATE {$db->config['prefix']}newsletter_guest SET
	            guest_name='".$db->escape($vars['guest_name'])."',
	            guest_email='".$db->escape($vars['guest_email'])."'
	            WHERE guest_id = '".intval($vars['guest_id'])."'
	        ");
	    
	    if (count($vars['threads']) > 0) {
	    	$db->delete_guest_threads($vars['guest_id']);
	    	$db->add_guest_threads($vars['guest_id'], $vars['threads']);
	    }
	 }

    $t->assign('link', "newsletter_guests.php");
    $t->display("admin/newsletter_guest_saved.html");
    
}

function delete_guest(){
    global $db, $t, $vars;
    if (!$vars['confirm']){
        $g = $db->get_guest($vars['guest_id']);
        display_confirm("Delete newsletter: $g[guest_name]", "
        <center>Do you really want to delete guest: $g[guest_name]?
        </center>
        ");
        return;
    } elseif ($vars['confirm'] != 'Yes'){
        display_edit_form();
        return;
    }
    $g = $db->get_guest($vars['guest_id']);
    
    $db->delete_guest($vars['guest_id']);
    $db->delete_guest_threads($vars['guest_id']);
    
    $t->assign('msg', "Guest deleted");
    $t->assign('link', 'newsletter_guests.php');
    $t->display("admin/newsletter_guest_saved.html");
}



//////////////////// main ////////////////////////////////////////

$vars = get_input_vars();
if ($vars['guest_id']) $t->assign('guest_id', $vars['guest_id']);
if (isset($vars['start'])) $start = $vars['start'];

switch ($vars['action']){
    case 'create':
    	  create_guest($vars);
        break;
    case 'edit':
    	  display_edit_form();	
        break;
    case 'update':
    	  update_guest($vars);	
        break;
    case 'delete':
        delete_guest();
        break;
    default: 
    	  display_guest_list();	
        display_form();
        break;
}

?>
