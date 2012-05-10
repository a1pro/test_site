<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'ChronoPay';
config_set_notebook_comment($notebook_page, '');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.chronopay.keyword', 'ChronoPay KeyWord',
    'text', "You need to grant this keyword to ChronoPay",
    $notebook_page,
    '');
add_config_field('payment.chronopay.ip', 'ChronoPay Server IP',
    'text', "ChronoPay Server IP",
    $notebook_page,
    '');
         //	69.20.58.35
?>
