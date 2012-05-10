<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: securepay payment plugin config
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1785 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");
include_once(dirname(__FILE__)."/securepay.php");

$notebook_page = "SecurePay";

config_set_notebook_comment($notebook_page, $notebook_page . ' configuration');
if (file_exists($rm = dirname(__FILE__) . "/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.securepay.host', 'SecurePay Host', 'text', 
    "Your SecurePay host (e.g. https://www.securepay.com/secure1/index.asp).", $notebook_page, ''
);

add_config_field('payment.securepay.timeout', 'SecurePay Timeout', 'text', 
    "Custom timeout value in seconds, default 120 seconds.", $notebook_page, ''
);

add_config_field('payment.securepay.merchant_id', 'SecurePay Default Merchant Identifier', 'text', 
    "Your default SecurePay Merchant Identifier (usually a number, e.g. 123456).", $notebook_page, ''
);

add_config_field('payment.securepay.debug', 'Test Mode Enabled', 'select', 
    "Set to No after you complete testing.", $notebook_page, '','','',
    array('options' => array(0 => 'No', 1 => 'Yes'))
);

add_config_field('payment.securepay.avsreq', 'AVS Check', 'select', 
    "Use AVS system check (The AVS system used by SecurePay.Com supports the United States)", $notebook_page, '','','',
    array('options' => array(
    	0 => 'Do not use AVS Check',
    	1 => 'Full AVS (both street address and zip code)',
    	2 => 'AVS only, Full AVS but do not authorize the Credit Card',
    	3 => 'Credit Card Authorization and Zip Code AVS Only',
    	4 => 'AVS with Zip Code only, do not authorize the Credit Card'))
);

cc_core_add_config_items('securepay', $notebook_page);

?>
