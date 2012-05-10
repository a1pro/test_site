<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'MIGS CC';
config_set_notebook_comment($notebook_page, 'MIGS CC configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);
global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

add_config_field('payment.migs_cc.merchant_id', 'Merchant ID',
    'text', "your MIGS Merchant ID",
    $notebook_page
);
add_config_field('payment.migs_cc.access_code', 'Access Code',
    'password_c', "your MIGS access code",
    $notebook_page,
    ''
);

add_config_field('payment.migs_cc.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'MIGS CC')
);
add_config_field('payment.migs_cc.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => 'MasterCard Internet Gateway Service')
);

cc_core_add_config_items('migs_cc', $notebook_page);
?>
