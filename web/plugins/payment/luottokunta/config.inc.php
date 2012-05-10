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
*    Release: 2.4.0PRO ($Revision: 1848 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/

global $config;
require_once(dirname(__FILE__)."/luottokunta.inc.php");
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

$notebook_page = 'luottokunta';
config_set_notebook_comment($notebook_page, 'luottokunta configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.luottokunta.merchant_id', 'luottokunta Merchant Id',
    'text', "your luottokunta merchant id",
    $notebook_page, 
    '');
add_config_field('payment.luottokunta.secret', 'Secret key for MAC-sum',
    'text', 
    "",
    $notebook_page, 
    '');

cc_core_add_config_items('luottokunta', $notebook_page);

?>