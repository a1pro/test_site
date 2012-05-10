<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: PayPal Payment Plugin IPN
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5205 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/
require_once("../../../config.inc.php");

$pl = & instantiate_plugin('payment', 'paypal_r');
$vars = $_POST;
if($vars['txn_type']=='cart' || $vars['txn_type']=='web_accept')
	$GLOBALS['amember_is_recurring'] = 0;
else
	$GLOBALS['amember_is_recurring'] = 1;
$pl->handle_postback($vars);

?>
