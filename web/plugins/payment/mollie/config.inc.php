<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Mollie';
config_set_notebook_comment($notebook_page, 'Mollie plugin configuration');

add_config_field('payment.mollie.id', 'Your Mollie Partner ID',
    'text', "", $notebook_page, '');
?>
