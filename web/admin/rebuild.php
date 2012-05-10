<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Info / PHP
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4319 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

/*
 * 
 * @todo --- rewrite htpasswd rebuild
 * @todo +++ check all plugins rebuild functions
 * @todo --- Add error checking
 * @todo +++ Test in Chrome, IE, Opera
 */

@set_time_limit(0);

include "../config.inc.php";
include "login.inc.php";

require_once $config['root_dir'] . '/includes/pear/Services/JSON.php';

function ajaxResponse($resp)
{
	$j = & new Services_JSON();
	echo $j->encode($resp);
	return true;
}

function ajaxError($msg)
{
	ajaxResponse(array('msg' => $msg, 'errorCode'=>1));
}

$vars = get_input_vars();
extract($vars, EXTR_OVERWRITE);

switch($vars['action']){
    case "rebuild" : Rebuild($vars); break;
    case "getcount" : showUsersCount($vars); break;
    case "rebuild_htpasswd" : RebuildHtpasswd($vars); break;
    default : showRebuildPage(); break;
}


function RebuildHtpasswd($vars){
    global $config;
    if(in_array('htpasswd', $config['plugins']['protect']))
        htpasswd_update();
    ajaxResponse(array("msg"=>"success"));
}

function Rebuild($vars){
    global $db,$__hooks,$config;
$start = intval($vars['start']);
    $count = intval($vars['count']);
    $ul = $db->get_users_list("%", "", $start, $count);
    $users=array();
    foreach($ul as $u){
            $db->check_subscriptions($u['member_id']);
            $u = $db->get_user($u['member_id']);
            if ($u['data']['is_locked'] > 0) continue; //auto-locking
            if ($config['manually_approve'] && !$u['data']['is_approved']) continue;
            if($u['status']!=1) continue; // Skip expired and pending records.
            $users[$u['login']]['pass'] = $u['pass'];
            foreach((array)$u['data']['status'] as $k=>$v){
                if($v) $users[$u['login']]['product_id'][] = $k;
            }

    }

    foreach ((array)$__hooks['subscription_rebuild'] as $func_name){
        if($func_name == 'htpasswd_update') continue ; // Will be handled separately
        call_user_func($func_name, $users);
    }

        ajaxResponse(array("msg"=>"success"));
}

function showUsersCount($vars){
    global $db;
    $cnt = $db->query_one("select count(*) from {$db->config['prefix']}members");
    ajaxResponse(array('msg'=>$cnt,'errorCode'=>0));

}
function showRebuildPage(){
    $t = new_smarty();
    $t->display("admin/rebuild.html");

}


?>