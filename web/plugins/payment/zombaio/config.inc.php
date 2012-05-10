<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
$notebook_page = 'Zombaio';
config_set_notebook_comment($notebook_page, 'Zombaio plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.zombaio.site_id', 'Your Zombaio Site ID',
    'integer', "your AllPay installation ID",
    $notebook_page, 
    '');
add_config_field('payment.zombaio.password', 'Your Zombaio Password',
    'password_c', "",
    $notebook_page, 
    'validate_password', '', '',
    array('store_type' => 3));

add_config_field('payment.zombaio.lang', 'Payment Language',
    'select', "this defines the language which will be used during the payment process",
    $notebook_page,
    '', '', '',
    array('options' => array(
                'ZOM' => 'Default (Script will detect user language based on IP)',
				'US' => 'English',
				'FR' => 'French',
				'DE' => 'German',
				'IT' => 'Italian',
				'JP' => 'Japanese',
				'ES' => 'Spanish',
				'SE' => 'Swedish',
				'KR' => 'Korean',
				'CH' => 'Traditional Chinese',
				'HK' => 'Simplified Chinese')
        )
    );
add_config_field('payment.zombaio.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Zombaio'));
add_config_field('payment.zombaio.description', 'Payment Method Description',
    'textarea', "displayed on signup page",
    $notebook_page, 
    '', '', '',
    array('default' => 'Pay by credit card/debit card - Visa, Diners Club, MasterCard/EuroCard, JCB, PBK Styl, PolCard, VISA ELECTRON of the banks: Inteligo, Deutsche Bank PBC and Millenium'));
?>
