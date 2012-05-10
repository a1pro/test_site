<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin clear old records
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3236 $)
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

if ($clear){
    $vars = get_input_vars();$tt=array();
    set_date_from_smarty('dat', $vars);    
    if ($vars['dat'] >= date('Y-m-d'))
        fatal_error("Please select date before today", 0);

    if ($vars['access_log']){
    	$tt[] = "access_log";
    	$db->clear_access_log($vars['dat']);
    }

    if ($vars['admin_log']){
    	$tt[] = "admin_log";
    	$db->clear_admin_log($vars['dat']);
    }
    
    if ($vars['error_log']){    
    	$tt[] = "error_log";
    	$db->clear_error_log($vars['dat']);
    }
    
    
    if ($vars['inc_users']){    
    	$tt[] = "inc_users";
    	$db->clear_incomplete_users($vars['dat']);
    }
    
    if ($vars['inc_payments']){ 
    	$tt[] = "inc_payments";
    	$db->clear_incomplete_payments($vars['dat']);
    }
    
    
    if ($vars['exp_users']){    
    	$tt[] = "exp_users";
    	$db->clear_expired_users($vars['dat']);
    }
			
    admin_log("Cleaned up old records to $vars[dat] (".join(',', $tt).")");
    $t->display('admin/clear_save.html');
    exit();
}


$t->assign('dt', time() - 3600 * 24 * 30);
$t->display("admin/clear.html");

?>
