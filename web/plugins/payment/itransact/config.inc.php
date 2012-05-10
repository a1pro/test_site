<?php 

$notebook_page = 'iTransact';
config_set_notebook_comment($notebook_page, 'iTransact plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.itransact.company_id', 'Vendor ID',
    'text', "number, issued by iTransact",
    $notebook_page, 
    '');
add_config_field('payment.itransact.company_title', 'Your company title',
    'text', "to be displayed in the order form",
    $notebook_page, 
    '');

if (class_exists('payment_itransact')) {
    $pl = & instantiate_plugin('payment', 'itransact');
    $pl->add_config_items($notebook_page);
}

?>
