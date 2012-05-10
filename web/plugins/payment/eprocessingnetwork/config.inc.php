<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: eprocessingnetwork payment plugin config
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3933 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/

global $config;
require_once(dirname(__FILE__)."/eprocessingnetwork.inc.php");
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

$notebook_page = "eProcessingNetwork";

config_set_notebook_comment($notebook_page, $notebook_page . ' configuration');
if (file_exists($rm = dirname(__FILE__) . "/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.eprocessingnetwork.account', 'eProcessingNetwork Merchant ID',
    'text', "your eprocessingnetwork digital merchant id",
    $notebook_page, 
    '');

add_config_field('payment.eprocessingnetwork.restrictkey', 'eProcessingNetwork RestrictKey',
    'text', "Security feature, like a password to indicate it came from you.",
    $notebook_page, 
    '');

cc_core_add_config_items('eprocessingnetwork', $notebook_page);

?>
