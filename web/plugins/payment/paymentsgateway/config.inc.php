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
require_once(dirname(__FILE__)."/paymentsgateway.inc.php");
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

$notebook_page = 'PaymentsGateway.Net';
config_set_notebook_comment($notebook_page, 'PaymentsGateway configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.paymentsgateway.merchant_id', 'PaymentsGateway Merchant Id',
    'text', "your PaymentsGateway merchant id",
    $notebook_page, 
    '');
add_config_field('payment.paymentsgateway.password', 'PaymentsGateway Password',
    'password_c', 
    "",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));
add_config_field('payment.paymentsgateway.avs_method0', 'AVS checking method for Credit Card/Zipcode',
    'select', "
    ",
    $notebook_page, 
    '','','',
    array('options' => array(
        0 => "Don't check", 
        1 => "Check only, do not decline on fail",
        2 => "Check and decline on fail",
)));
add_config_field('payment.paymentsgateway.avs_method1', 'AVS checking method for Credit Card/Street Number',
    'select', "
    ",
    $notebook_page, 
    '','','',
    array('options' => array(
        0 => "Don't check", 
        1 => "Check only, do not decline on fail",
        2 => "Check and decline on fail",
)));
add_config_field('payment.paymentsgateway.avs_method2', 'AVS checking method for State/Zipcode',
    'select', "
    ",
    $notebook_page, 
    '','','',
    array('options' => array(
        0 => "Don't check", 
        1 => "Check only, do not decline on fail",
        2 => "Check and decline on fail",
)));
add_config_field('payment.paymentsgateway.avs_method3', 'AVS checking method for State/Area Code',
    'select', "
    ",
    $notebook_page, 
    '','','',
    array('options' => array(
        0 => "Don't check", 
        1 => "Check only, do not decline on fail",
        2 => "Check and decline on fail",
)));

add_config_field('payment.paymentsgateway.avs_method4', 'AVS checking method for Anonymous Email',
    'select', "
    ",
    $notebook_page, 
    '','','',
    array('options' => array(
        0 => "Don't check", 
        1 => "Check only, do not decline on fail",
        2 => "Check and decline on fail",
)));

add_config_field('payment.paymentsgateway.live', 'Test/Live Mode',
    'select', "
    ",
    $notebook_page, 
    '','','',
    array('options' => array(
        0 => "Test Mode", 
        1 => "Live Mode",
)));

cc_core_add_config_items('paymentsgateway', $notebook_page);

?>
