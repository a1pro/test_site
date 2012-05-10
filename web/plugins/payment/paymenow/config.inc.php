<?php 

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'PayMeNow';
config_set_notebook_comment($notebook_page, 'PayMeNow plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.paymenow.accid', 'PayMeNow ACCID',
    'text', "use TEST0 for tests",
    $notebook_page, 
    '');

cc_core_add_config_items('paymenow', $notebook_page);
?>
