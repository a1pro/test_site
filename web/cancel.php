<?php                                                        
/*
*  User's cancel payment page. Displayed after failed payment.
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: User's failed payment page
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1640 $)
*
* Please direct bug reports,suggestions or feedbacks to the cgi-central support
* http://www.cgi-central.net/support/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*                                                                                 
*/
$rd = dirname(__FILE__);
include($rd.'/config.inc.php');

###############################################################################
##
##                             M  A  I  N 
##
###############################################################################
$t = & new_smarty();
$error = '';
$vars = & get_input_vars();

$t->display("cancel.html");

?>
