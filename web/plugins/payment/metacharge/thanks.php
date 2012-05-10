<?php 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: The installation file
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1640 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

$paysys_id = 'metacharge';
include "../../../thanks.php";

/*
Once the consumer has completed a transaction, whether authorised or declined, we will return them to your website via
HTTP POST. The location we redirect to is called the return URL and is entered in the Merchant Extranet. Click Account
Management then Installations and select the installation you wish to configure from the pop-up menu.
This user return POST contains a single field: strCartID. You can perform a look up in your database against this field to
retrieve the consumers information.
*/

?>
