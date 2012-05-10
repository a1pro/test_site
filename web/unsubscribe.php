<?php
/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Unsubscribe page
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1640 $)
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/

require 'config.inc.php';


$vars = get_input_vars();

// check sign
$expected_sign = substr(md5($vars['e'].'-AMEMBER'), 0, 4);
if ($vars['s'] != $expected_sign){
    fatal_error(_UNSUBSCRIBE_ERROR, 0);
}

$t = &new_smarty();    
if ($vars['confirm'] != ''){
    // do unsubscribe
    $e = $db->escape($vars['e']);
    $db->query("UPDATE {$db->config[prefix]}members
        SET unsubscribed=1
        WHERE email='$e'");
    $t->assign('email', $vars['e']);
    $t->display('unsubscribed.html');
} else {
    // display confirmation page
    $t->assign('email', $vars['e']);
    $t->assign('s', $vars['s']);
    $t->display('unsubscribe_confirm.html');
}

?>
