<?php

/**
 *
 * Copyright (C) 2010 Kencinnus, LLC. All rights reserved.
 *
 * This file may not be distributed by anyone outside of
 * Kencinnus, LLC or authorized contractors as specified.
 *
 * Purchasers of this plugin can modify it for the site
 * it is installed on.
 *
 * This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
 * THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE.
 *
 * All support wuestions regarding this plugin should be sent to:
 *		http://kencinnus.com/contact
 *
 * (See amthankyou.inc.php for revision history.)
 *
 **/

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

$notebook_page = 'amThankYou';

config_set_notebook_comment($notebook_page, 'amThankYou v3.2.3.1');

if (file_exists($rm = dirname(__FILE__)."/readme.txt")) config_set_readme($notebook_page, $rm);

add_config_field('protect.amthankyou.debug',
                 'Debug?',
                 'checkbox',
                 'Debug statements are written to the error log.',
                 $notebook_page,
                 ''
                );

add_config_field('protect.amthankyou.enjoytext',
                 'Text For Please Enjoy Phrase Above Product Links',
                 'textarea',
                 "This is the text that is displayed at the top of the Thank You page before the list of product links.",
                 $notebook_page,'','','',array('store_type' => 1,'default'=>'Please enjoy the products you have purchased<br />today by clicking on a link below...')
                );

?>
