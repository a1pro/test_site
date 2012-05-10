<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Epx';
config_set_notebook_comment($notebook_page, 'Epx plugin configuration');

add_config_field('payment.epx.cust_nbr', 'Customer number',
    'text', "This number represents the sponsoring merchant <br>
	bank level of hierarchy within EPX internal systems.",
    $notebook_page, 
    '');
add_config_field('payment.epx.merch_nbr', 'Merchant number',
    'text', "This number represents the settle to level of hierarchy <br>
	within EPX internal systems, and contains the account numbers for settlement.",
    $notebook_page, 
    '', '', '');
?>