<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: logiccommerce payment plugin config
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1785 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/

global $config;
require_once(dirname(__FILE__)."/logiccommerce.inc.php");
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

$notebook_page = "LogicCommerce";

config_set_notebook_comment($notebook_page, $notebook_page . ' configuration');
if (file_exists($rm = dirname(__FILE__) . "/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.logiccommerce.merchant_id', 'LogicCommerce Merchant ID',
    'text', "your logiccommerce 9-digit merchant id",
    $notebook_page, 
    '');
add_config_field('payment.logiccommerce.customer_id', 'LogicCommerce Customer ID',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.logiccommerce.zone_id', 'LogicCommerce Zone ID',
    'text', "",
    $notebook_page, 
    '');
add_config_field('payment.logiccommerce.username', 'LogicCommerce Username',
    'text', "",
    $notebook_page, 
    '');

cc_core_add_config_items('logiccommerce', $notebook_page);

?>
