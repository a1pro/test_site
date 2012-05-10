<?php
/*
*  User's cancel payment page. Displayed after failed payment.
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: User's failed payment page
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 1785 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*                                                                                 
*/
$rd = dirname(__FILE__);
include('../../../config.inc.php');

###############################################################################
##
##                             M  A  I  N 
##
###############################################################################
$t = & new_smarty();
$error = '';
$vars = & get_input_vars();
if (!strlen($vars['errortext'])) 
    $vars['errortext'] = "Order declined";
$t->assign('error', array($vars['errortext']));

$t->display("cancel.html");

?>
