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
    $t->assign('threads_list', $db->get_newsletter_threads());
    $t->assign('config', $config);
    $t->assign('vars', $vars);
    $t->assign('add', 1);
    $t->display("admin/newsletter_archive_form.html");
}

function display_archive_list(){
    global $db, $t, $vars;
    global $all_count, $count, $start;
    //$db->delete_old_newsletters();
    $all_count = $db->get_archive_list_c();
    $count = 20;
    $al = & $db->get_archive_list($start, $count);
    $t->assign('al', $al);
    $t->display('admin/newsletter_archive.html');
}

function create_newsletter($vars='') {
	 global $db, $config, $t;
	 settype($vars, 'array');

    $errors = array();
    $vars = get_input_vars();

    if (!strlen($vars['subject'])){
        $errors[] = "'Subject' is a required field";
    }
    if (!strlen($vars['message'])){
        $errors[] = "'Message' is a required field";
    }

    if ($errors){
        $t->assign('errors', $errors);
        display_archive_list();
        display_form($vars);
        return;
        
    }
    
    $vars['threads'] = $db->escape_array ($vars['threads']);
    $threads = "," . implode(",", $vars['threads']) . ",";

    $q = $db->query($s = "
            INSERT INTO {$db->config['prefix']}newsletter_archive
            (archive_id,threads,subject,message,add_date)
            VALUES (null, '".$db->escape($threads)."', '".$db->escape($vars['subject'])."',
            '".$db->escape($vars['message'])."', NOW())
        ");

    $t->assign('link', "newsletter_archive.php");
    $t->display("admin/newsletter_archive_saved.html");
    
}

function display_edit_form(){
    global $vars, $db, $t;
    $nl = & $db->get_newsletter(intval($vars['archive_id']));

    //$threads = explode(",", $nl['threads']);
    if (is_array($nl['threads']))
        $threads = array_keys($nl['threads']);
    else
        $threads = array();
    
    $a = & $db->get_newsletter($vars['archive_id'], 0);
    $t->assign('a', $a);

    $t->assign('vars', $nl);
    $t->assign('threads', $threads);
    $t->assign('threads_list', $db->get_newsletter_threads());
    $t->display('admin/newsletter_archive_form.html');
}

function update_newsletter($vars='') {
	 global $db, $config, $t;
	 settype($vars, 'array');

    $errors = array();
    $vars = get_input_vars();

    if (!strlen($vars['subject'])){
        $errors[] = "'Subject' is a required field";
    }
    if (!strlen($vars['message'])){
        $errors[] = "'Message' is a required field";
    }

    if ($errors){
        $t->assign('errors', $errors);
        return display_edit_form();
    }
    
    $vars['threads'] = $db->escape_array ($vars['threads']);
    $threads = "," . implode(",", $vars['threads']) . ",";

    $q = $db->query($s = "
            UPDATE {$db->config['prefix']}newsletter_archive SET
            subject='".$db->escape($vars['subject'])."',
            message='".$db->escape($vars['message'])."',
            threads='".$db->escape($threads)."'
            WHERE archive_id = '".intval($vars['archive_id'])."'
        ");
    $t->assign('link', "newsletter_archive.php");
    $t->display("admin/newsletter_archive_saved.html");
    
}

function delete_newsletter(){
    global $db, $t, $vars;
    if (!$vars['confirm']){
        $nl = $db->get_newsletter($vars['archive_id']);
        display_confirm("Delete newsletter: $nl[subject]", "
        <center>Do you really want to delete newsletter: $nl[subject]?
        </center>
        ");
        return;
    } elseif ($vars['confirm'] != 'Yes'){
        display_edit_form();
        return;
    }
    $db->delete_newsletter($vars['archive_id']);
    $t->assign('msg', "Newsletter deleted");
    $t->assign('link', 'newsletter_archive.php');
    $t->display("admin/newsletter_archive_saved.html");
}



//////////////////// main ////////////////////////////////////////

$vars = get_input_vars();
if ($vars['archive_id']) $t->assign('archive_id', $vars['archive_id']);
if (isset($vars['start'])) $start = $vars['start'];

switch ($vars['action']){
    case 'create':
    	  create_newsletter($vars);
        break;
    case 'edit':
    	  display_edit_form();	
        break;
    case 'update':
    	  update_newsletter($vars);	
        break;
    case 'delete':
        delete_newsletter();
        break;
    default: 
    	  display_archive_list();	
        //display_form();
        break;
}

?>
