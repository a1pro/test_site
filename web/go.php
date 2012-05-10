<?php 

/*
*  Receive affiliate clicks and redirect customers to right direction
*  http://yoursite.com/amember/go.php?r=NUMBER&l=ENCODED_LINK
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Signup Page
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1860 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/
include('./config.inc.php');

$vars = get_input_vars();

if ($vars['i'] != ''){
    $link_type = $vars['i'][0];
    $link_id = substr($vars['i'], 1);
    $links = ($link_type == 'l') ? $config['aff']['links'] : $config['aff']['banners'];
    $link = $links[$link_id]['url'] ? $links[$link_id]['url'] : "/";
} else {
    $link = $vars['l'] ? aff_decrypt($vars['l']) : "/";
}

//$u = parse_url($config['root_url']);
//$host = $u['scheme'] . "://" . $u['host'];

function find_id_by_login($login){
    global $db;
    $ul = $db->users_find_by_string($login, 'login', 1);
    if (!count($ul)) return 0;
    return $ul[0]['member_id'];
}

if ($aff_id = $vars['r']){
    if ($aff_id<=0) 
        $aff_id = find_id_by_login($aff_id);
    if ($aff_id > 0){
        aff_set_cookie($vars['r']);
        $db->log_aff_click($aff_id, $link);
    }
}

$link = preg_replace('/\s+/', ' ', $link);
header("Location: $link");
?>
