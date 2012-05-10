<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: beanstream payment plugin config
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1785 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/

global $config;
require_once(dirname(__FILE__)."/beanstream.inc.php");
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

$notebook_page = "BeanStream";

config_set_notebook_comment($notebook_page, $notebook_page . ' configuration');
if (file_exists($rm = dirname(__FILE__) . "/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.beanstream.merchant_id', 'BeanStream Merchant ID',
    'text', "your beanstream 9-digit merchant id",
    $notebook_page, 
    '');
add_config_field('payment.beanstream.username', 'BeanStream Merchant Username',
    'text', "your beanstream username",
    $notebook_page, 
    '');
add_config_field('payment.beanstream.password', 'BeanStream Marchant Password',
    'password_c', "your beanstream Password",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));

cc_core_add_config_items('beanstream', $notebook_page);

?>
