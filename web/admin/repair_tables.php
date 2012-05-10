<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Fix corrupted MySQL tables
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1739 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

include "../config.inc.php";
$t = new_smarty();
include "login.inc.php";

foreach ($config['tables'] as $t){
    print "Reparing {$db->config[prefix]}$t..."; ob_end_flush();
    $db->query("REPAIR TABLE {$db->config[prefix]}$t");
    print "OK<br />";
}
print "tables restored.";
?>