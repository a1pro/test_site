<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'passwordcall';
config_set_notebook_comment($notebook_page, 'passwordcall plugin configuration');
if (file_exists($rm = dirname(__FILE__)."/readme.txt"))
    config_set_readme($notebook_page, $rm);

add_config_field('payment.passwordcall.webmaster_id', 'Deine passwordcall.de Webmaster ID',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.aname_id1', 'Angebot Nr1: Titel/&Uuml;berschrift',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.tarif_id1', 'Angebot Nr1: passwordcall.de Tarif, "T4" oder "T5" eintragen!',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.angebots_id1', 'Angebot Nr1: Angebots ID, Siehe passwordcall.de unter Link Generieren',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.product_id1', 'Angebot Nr1: Product ID, siehe amember CP Manage Products',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.aname_id2', 'Angebot Nr2: Titel/&Uuml;berschrift',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.tarif_id2', 'Angebot Nr2: passwordcall.de Tarif, "T4" oder "T5" eintragen!',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.angebots_id2', 'Angebot Nr2: Angebots ID, Siehe passwordcall.de unter Link Generieren',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.product_id2', 'Angebot Nr2: Product ID, siehe amember CP Manage Products',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.aname_id3', 'Angebot Nr3: Titel/&Uuml;berschrift',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.tarif_id3', 'Angebot Nr3: passwordcall.de Tarif, "T4" oder "T5" eintragen!',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.angebots_id3', 'Angebot Nr3: Angebots ID, Siehe passwordcall.de unter Link Generieren',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.product_id3', 'Angebot Nr3: Product ID, siehe amember CP Manage Products',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.aname_id4', 'Angebot Nr4: Titel/&Uuml;berschrift',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.tarif_id4', 'Angebot Nr4: passwordcall.de Tarif, "T4" oder "T5" eintragen!',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.angebots_id4', 'Angebot Nr4: Angebots ID, Siehe passwordcall.de unter Link Generieren',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.product_id4', 'Angebot Nr4: Product ID, siehe amember CP Manage Products',
    'text', "",
    $notebook_page,
    '');
add_config_field('payment.passwordcall.title', 'Payment Method Title',
    'text', "displayed on signup page and on renewal page",
    $notebook_page,
    '', '', '',
    array('default' => 'PasswordCall'));
add_config_field('payment.passwordcall.description', 'Payment Method Description',
    'text', "displayed on signup page",
    $notebook_page,
    '', '', '',
    array('default' => 'Phonecall Payment - Only Germany, Austria and Switzerland'));
?>