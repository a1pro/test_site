<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: ipayment payment plugin config
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3271 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

$notebook_page = "IPayment";

config_set_notebook_comment($notebook_page, $notebook_page . ' configuration');
if (file_exists($rm = dirname(__FILE__) . "/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.ipayment.account_id', 'IPayment Account ID',
    'text', "your ipayment account id",
    $notebook_page, 
    '');
add_config_field('payment.ipayment.user_id', 'IPayment Account ID',
    'text', "your ipayment user id",
    $notebook_page, 
    '');
add_config_field('payment.ipayment.pass', 'IPayment Transaction Password',
    'text', "ask IPayment support about",
    $notebook_page, 
    '');
add_config_field('payment.ipayment.actionpass', 'IPayment Admin Action Password',
    'text', "ask IPayment support about, it should be long string",
    $notebook_page, 
    '');
add_config_field('payment.ipayment.currency', 'Default Currency',
    'text', "ISO alpha order currency code, <br/>
	for example: EUR, USD, GBP, CHF, :",
    $notebook_page);


cc_core_add_config_items('ipayment', $notebook_page);

?>
