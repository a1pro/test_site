<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: beanstream_remote payment plugin config
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5012 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/

global $config;

$notebook_page = "Beanstream (remote)";

config_set_notebook_comment($notebook_page, $notebook_page . ' configuration');
if (file_exists($rm = dirname(__FILE__) . "/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.beanstream_remote.merchant_id', 'beanstream_remote Merchant ID',
    'text', "your beanstream_remote 9-digit merchant id",
    $notebook_page, 
    '');
add_config_field('payment.beanstream_remote.passcode', 'Recurring billing passcode',
    'password_c', "note that this is not the same passcode<br>
		 used for Username/Passcode validation<br>
		 in the Process Transaction API.",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));

add_config_field('payment.beanstream_remote.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'BeanStream Remote'));
add_config_field('payment.beanstream_remote.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Credit card payment'));

?>
