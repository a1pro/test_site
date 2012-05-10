<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: echo payment plugin config
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1785 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/

global $config;
//require_once(dirname(__FILE__)."/echo.inc.php");
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

$notebook_page = "ECHO";

config_set_notebook_comment($notebook_page, $notebook_page . ' configuration');
if (file_exists($rm = dirname(__FILE__) . "/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.echo.merchant_id', 'ECHO Merchant ID',
    'text', "your echo 10-digit merchant id",
    $notebook_page, 
    '');
add_config_field('payment.echo.pin', 'ECHO PIN number',
    'password_c', "your PIN number, issued by ECHO",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));

cc_core_add_config_items('echo', $notebook_page);

?>
