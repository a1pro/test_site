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

function display_members_list(){
    global $db, $t, $vars;
    global $all_count, $count, $start;
    $count = 20;
    
  	 $all_count = $db->get_members_list_c( '', array($vars['tr']) );
  	 $ml = & $db->get_members_list($start, $count, '', array($vars['tr']));
    
    $t->assign('ml', $ml);
    $t->display('admin/newsletter_view_members.html');
}




//////////////////// main ////////////////////////////////////////

$vars = get_input_vars();
if (isset($vars['start'])) $start = $vars['start'];

display_members_list();	

?>
