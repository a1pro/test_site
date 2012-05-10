<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'PHP Include';
config_set_notebook_comment($notebook_page, 'paypal plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('protect.php_include.redirect', 'Redirect after logout',
    'text', "enter full URL, starting from http://<br />
    keep empty for redirect to site homepage",
    $notebook_page
    );
add_config_field('protect.php_include.remember_login', 'Remember Login',
    'select', "allow remember login/password in cookies",
    $notebook_page, 
    '', '', '',
    array('options' => array( 0 => 'No', 1 => 'Yes')));
add_config_field('protect.php_include.remember_auto', 'Always remember',
    'select', "if set to Yes, don't ask customer - always remember",
    $notebook_page, 
    '', '', '',
    array('options' => array( 0 => 'No', 1 => 'Yes')));
add_config_field('protect.php_include.remember_period', 'Remember period',
    'integer', "cookie will be stored for ... days",
    $notebook_page,
    'validate_integer', '','',
    array('default' => 60)
    );

?>
