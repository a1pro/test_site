<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Glo-Bill';
config_set_notebook_comment($notebook_page, 'Glo-Bill plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.globill.site_id', 'Your Site Id in Glo-Bill',
    'integer', "your Glo-Bill site id#",
    $notebook_page, 
    '', '','',
    array('size' => 10));
add_config_field('payment.globill.wusername', 'Glo-Bill webmaste username',
    'text', "your webmaster username in Glo-Bill",
    $notebook_page
);
?>
