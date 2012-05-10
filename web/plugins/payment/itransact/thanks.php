<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Revecom Payment Plugin IPN
*    FileName $RCSfile: ipn.php,v $
*    Release: 3.1.9PRO ($Revision: 1.1.2.1 $)
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

$pl = & instantiate_plugin('payment', 'itransact');
$pl->handle_postback(get_input_vars());

?>
