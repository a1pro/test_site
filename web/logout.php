<?php 
/*
*   Members page, used to logout.
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Member display page
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1943 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

require_once('./config.inc.php');
if ($_GET['amember_redirect_url'] != '')
    $config['protect']['php_include']['redirect'] = $_GET['amember_redirect_url'];
require_once($config['root_dir'] . '/plugins/protect/php_include/logout.php');

?>
