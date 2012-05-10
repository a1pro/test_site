<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 

global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");
$notebook_page = "Manual CC Processing";

config_set_notebook_comment($notebook_page, $notebook_page . ' configuration');
if (file_exists($rm = dirname(__FILE__) . "/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.manual_cc.notification', 'Singup Notification',
    'select', "How to notify about new user signup",
    $notebook_page,
	'','','',
    array('options' => array(1 => 'Send to email',
							2 => 'Save to Member fields',
							3 => 'Email and Save'),
		'default' => 3));

cc_core_add_config_items('manual_cc', $notebook_page);

?>