<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin accounts
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3914 $)
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
require "admin_permissions.inc.php";
admin_check_permissions('super_user');

function admins_list(){
    global $db, $t, $vars;
    $l = $db->get_admins_list();
    $t->assign('list', $l);
    $t->display("admin/admins.html");
}
function admins_add_form($err=''){
    global $db, $t, $vars;
    if ($err)
        $t->assign('errors', $err);
    $t->assign('r', $vars);
    $t->assign('permissions', get_admin_permissions_list());
    $t->display("admin/admins_form.html");
}
function admins_edit_form($err=''){
    global $db, $t, $vars;
    if ($err)
        $t->assign('errors', $err);
    if ($vars['action'] == 'edit')
        $r = $db->get_admin($vars['admin_id']);
    else
        $r = $vars;
    $t->assign('r', $r);
    $t->assign('permissions', get_admin_permissions_list());
    $t->display("admin/admins_form.html");
}
function validate_form($rec){
    global $db, $t, $vars;
    $err = array();
    if ($rec['login'] == '')
        $err[] = "Please enter admin username";
    elseif (!preg_match('/^[a-zA-Z0-9_@-]+$/', $rec['login']))
        $err[] = "Admin username may contain only letters, digits, '_', '-' and '@'";
    if ($rec['action'] == 'add_save'){        
        if ($rec['pass0'] == '')
            $err[] = "Please enter admin password";
        else {
            if (!preg_match('/^[a-zA-Z0-9_@-]+$/', $rec['pass0']))
                $err[] = "Admin password may contain only letters, digits, '_', '-' and '@'";
            if ($rec['pass0'] != $rec['pass1'])
                $err[] = "Password and password confirmation doesn't match";
        }
    } else {
        if ($rec['pass0'] != ''){
            if (!preg_match('/^[a-zA-Z0-9_@-]+$/', $rec['pass0']))
                $err[] = "Admin password may contain only letters, digits, '_', '-' and '@'";
            if ($rec['pass0'] != $rec['pass1'])
                $err[] = "Password and password confirmation doesn't match";
        }
    }        
    return $err;        
}
function add_admin($rec){
    global $db, $t, $vars;
    $rec['pass'] = $rec['pass0'];
    if ($err = $db->add_admin($rec))
        return $err;
}
function edit_admin($rec){
    global $db, $t, $vars;
    $rec['pass'] = $rec['pass0'];
    if ($err = $db->update_admin($rec['admin_id'], $rec))
        return $err;
}
function delete_admin(){
    global $db, $t, $vars;
    if ($err = $db->delete_admin($vars['admin_id']))
        return $err;
}

$vars = get_input_vars();
admin_check_permissions('super_user');
/*******************************************/
switch ($vars['action']){
    case 'add':
        admins_add_form();
        break;
    case 'edit':
        admins_edit_form();
        break;
    case 'delete':
        check_demo();
        if ($err = delete_admin())
            fatal_error($err . ". <a href='admins.php'>Continue</a>", 0,1);
        else
            admin_html_redirect("admins.php",  $title='Admin record removed', $text='Admin record removed');
        break;
    case 'add_save':
	if (!$_POST['action']) die("POST request expected");
        check_demo();
        if ($err = validate_form($vars))
            admins_add_form($err);
        elseif ($err = add_admin($vars))
            admins_add_form($err);
        else
            admin_html_redirect("admins.php",  $title='Admin record added', $text='Admin record added');
        break;
    case 'edit_save':
	if (!$_POST['action']) die("POST request expected");
        check_demo();
        if ($err = validate_form($vars))
            admins_edit_form($err);
        elseif ($err = edit_admin($vars))
            admins_edit_form($err);
        else
            admin_html_redirect("admins.php",  $title='Admin record updated', $text='Admin record updated');
        break;
    default: 
        admins_list();
}

?>
