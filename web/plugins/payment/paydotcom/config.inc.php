<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'paydotcom';
config_set_notebook_comment($notebook_page, 'paydotcom plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.paydotcom.secret', 'Your paydotcom secret phrase',
    'text', "your paydotcom secret phrase<br>
    exactly as at your product<br>configuration under PDC account    
    ",
    $notebook_page, 
    '');
?>