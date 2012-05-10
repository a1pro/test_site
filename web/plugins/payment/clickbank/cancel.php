<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    FileName $RCSfile: ipn.php,v $
*    Release: 3.1.9PRO ($Revision: 1.2 $)
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

$pl = & instantiate_plugin('payment', 'clickbank');
$vars = get_input_vars();
$pl->handle_cancel($vars);

?>
