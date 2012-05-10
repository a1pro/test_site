<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Free';
config_set_notebook_comment($notebook_page, 'Free payment plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.free.admin_approval', 'Require admin approval for new payments',
    'checkbox', "",
    $notebook_page, 
    '','','',
    array());
add_config_field('payment.free.mail_admin', 'Send E-Mail to admin about new subscription',
    'checkbox', "",
    $notebook_page, 
    '','','',
    array());
?>
