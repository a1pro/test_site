<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: PayPal Payment Plugin IPN
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1785 $)
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

$pl = & instantiate_plugin('payment', 'beanstream');
$vars = get_input_vars();
$pl->handle_postback($vars);

?>
