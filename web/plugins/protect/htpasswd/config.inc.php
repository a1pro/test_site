<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'htpasswd';
config_set_notebook_comment($notebook_page, '');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('protect.htpasswd.use_plain_text', 'Use Plain Text',
    'select', "set to Yes on the Windows platform, set to No otherwise",
    $notebook_page, 
    '', '', '',
    array('options' => array( 0 => 'No', 1 => 'Yes')));
/*
add_config_field('protect.htpasswd.add_htpasswd', 'Add another .htpasswd',
    'text', "add content of another .htpasswd file to generated file",
    $notebook_page
    );
*/
?>
