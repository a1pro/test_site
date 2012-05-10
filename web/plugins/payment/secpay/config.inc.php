<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: secpay payment plugin config
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3334 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/

global $config;
require_once(dirname(__FILE__)."/secpay.inc.php");
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

$notebook_page = "SecPay";

config_set_notebook_comment($notebook_page, $notebook_page . ' configuration');
if (file_exists($rm = dirname(__FILE__) . "/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.secpay.id', 'SecPay Merchant ID',
    'text', "your SecPay merchant id (Account Name)<br />
    use 'secpay' for tests",
    $notebook_page, 
    '');
add_config_field('payment.secpay.pass', 'SecPay VPN password',
    'text', "your SecPay VPN password (changed on SecPay web site)<br />
    use 'secpay' for tests",
    $notebook_page, 
    '');
add_config_field('payment.secpay.remote_pass', 'SecPay Remote Password',
    'text', "your SecPay Remote password (changed on SecPay web site)<br />
    use 'secpay' for tests",
    $notebook_page, 
    '');
add_config_field('payment.secpay.testing', 'Test Mode Enabled',
    'select', "set to No after you complete testing",
    $notebook_page, 
    '','','',
    array('options' => array(0 => 'No', 
        1 => 'Test mode, any transaction will be successfull', 
        2 => 'Test mode, any transaction will failed', 
        )));

cc_core_add_config_items('secpay', $notebook_page);

?>
