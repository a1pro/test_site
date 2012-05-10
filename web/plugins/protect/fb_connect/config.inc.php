<?php 
/**
 *  Facebook Connect v1.4
 *  Copyright 2010 (c) R Woodgate
 *  All Rights Reserved
 *
 **/

if( ! defined( 'INCLUDED_AMEMBER_CONFIG' ) )
{
	die("Direct access to this location is not allowed");
}

$notebook_page = 'fb_connect';
config_set_notebook_comment( $notebook_page, 'Facebook Connect: Version 1.4' );
if( file_exists( $rm = dirname( __FILE__ ) . "/readme.txt" ) )
{
	config_set_readme( $notebook_page, $rm );
}


add_config_field(	"protect.{$notebook_page}.testmode",
					'Debug Mode?',
					'checkbox',
					"Debug statements will be written to the log file.",
					$notebook_page
                );

add_config_field(	"protect.{$notebook_page}.sqlupdated",
					'SQL Field installed?',
					'checkbox',
					"The plugin will attempt to add a 'fbuserid'<br/>
					field automatically. Uncheck if you need to reinstall.",
					$notebook_page
                );

add_config_field(	"fbfd.##3",
				 	'<a href="http://developers.facebook.com/setup/" target="_blank">Create Facebook Application</a>',
					'header',
					'',
					$notebook_page
				);

add_config_field(	"protect.{$notebook_page}.appid",
					'Facebook Application ID',
					'text',
					"Get this from Facebook when you<br/>
					create your application",
					$notebook_page
                );

add_config_field(	"protect.{$notebook_page}.appsecret",
					'Facebook Application Secret',
					'text',
					"Get this from Facebook when you<br/>
					create your application",
					$notebook_page
                );

add_config_field(	"protect.{$notebook_page}.likebutton",
					"Add 'Like' Button?",
					'checkbox',
					"Adds a Facebook 'Like' button to Useful Links",
					$notebook_page
                );

$likeurl = substr($config['root_url'], 0, strpos($config['root_url'],'/',8)+1 );
add_config_field(	"protect.{$notebook_page}.likeurl",
					'Like Button URL',
					'text',
					"Enter the web address of your homepage.<br/>
					 If you leave it blank, it will default to:<br/>
					<strong>$likeurl</strong>",
					$notebook_page
                );

add_config_field(	"protect.{$notebook_page}.likestyle",
					"Full Like Button?",
					'checkbox',
					"If ticked, Like button shows friend faces etc<br/>
					Otherwise Like button is a small button only.",
					$notebook_page
                );

add_config_field(	"protect.{$notebook_page}.forcelogout",
					"Force Facebook Logout?",
					'checkbox',
					"Forces logout from Facebook when logging out<br/>
					from aMember. Warning: This option will<br/>
					log the member out of Facebook and all <br/>
					other websites they have connected with.",
					$notebook_page
                );

add_config_field(	"fbfd.##8",
				 	'Signup Page Settings',
					'header',
					'',
					$notebook_page
				);

if (function_exists('amember_filter_output'))
	add_config_field(	"protect.{$notebook_page}.signupform",
						"Show on Signup Page?",
						'checkbox',
						"Tick to automagically include on signup page.<br/>
						Leave unticked to integrate button yourself",
						$notebook_page
					);

add_config_field(	"protect.{$notebook_page}.newaccount",
					"Create aMember account?",
					'checkbox',
					"Creates new aMember account automatically<br/>
					 if Facebook user doesn't have one. If unticked<br/>
					 plugin will pre-fill name and email on signup form only.",
					$notebook_page
                );

global $db;
$plist = array();
foreach ($db->get_products_list() as $pr) $plist[$pr['product_id']] = $pr['title']." ({$pr['expire_days']})";
add_config_field(	"protect.{$notebook_page}.newaccountproduct",
					"Facebook Product Subscription",
					'select',
					"New aMember account will be automatically<br/>
					 subscribed to selected product when created via Facebook.<br />
					Only works if 'Create aMember account' is selected above.",
					$notebook_page,
					'',
					'',
					'',
					array('options' => array('' => '*** None') + $plist )				
                );

if (function_exists('amember_filter_output'))
	add_config_field(	"protect.{$notebook_page}.signupblurb",
						'Signup Page Heading',
						'textarea',
						"This is the text that is displayed at the top of the<br/>
						Facebook side of the signup page.",
						$notebook_page,
						'',
						'',
						'',
						array('default' => "<h2 style=\"text-align:center;\">Use your existing account...</h2>" )
					);

add_config_field(	"protect.{$notebook_page}.signupbtntxt",
					'Signup Button Text',
					'text',
					"If you leave this blank, the Signup page<br/>
					button will say <strong>Connect with Facebook</strong>",
					$notebook_page
                );

add_config_field(	"protect.{$notebook_page}.price_group",
					'Signup Page Button Price Groups',
					'text',
					"Enter a comma separated list of Price Groups to<br/>
					restrict the Signup page button to those groups only.<br/>
					Leave blank to always show the Signup page button.",
					$notebook_page
                );

add_config_field(	"fbfd.##13",
				 	'Login Page Settings',
					'header',
					'',
					$notebook_page
				);

if (function_exists('amember_filter_output'))
	add_config_field(	"protect.{$notebook_page}.loginform",
						"Show on Login Page?",
						'checkbox',
						"Tick to automagically include on login page.<br/>
						Leave unticked to integrate button yourself",
						$notebook_page
					);

add_config_field(	"protect.{$notebook_page}.loginbtntxt",
					'Login Button Text',
					'text',
					"If you leave this blank, the Login page<br/>
					button will say <strong>Connect with Facebook</strong>",
					$notebook_page
                );

add_config_field(	"protect.{$notebook_page}.loginoffer",
					"Offer enhanced permissions at connect?",
					'checkbox',
					"If you have enabled the enhanced permissions<br/>
					 below, and are not forcing them, this option<br/>
					 lets you offer them at first connect.",
					$notebook_page
                );

add_config_field(	"fbfd.##16",
				 	'Enhanced Permission Settings',
					'header',
					'',
					$notebook_page
				);

add_config_field(	"protect.{$notebook_page}.publish_stream",
					"Post Updates to User's Wall?",
					'checkbox',
					"aMember will update user's Wall when they first<br />
					connect, and when they order a new product.<br />
					<span style='color:red;'>NB: Users will need to grant permission!<br />
					You may also need <a href='http://developers.facebook.com/policy/' target='_blank'>Facebook approval</a></span>",
					$notebook_page
                );

add_config_field(	"protect.{$notebook_page}.fbemail",
					"Use Facebook Email?",
					'checkbox',
					"Allows aMember name and email to be<br/>
					 updated when changed in Facebook.<br />
					<span style='color:red;'>NB: Users will need to grant permission!</span>",
					$notebook_page
                );

add_config_field(	"protect.{$notebook_page}.forceperms",
					"Force Facebook permissions?",
					'checkbox',
					"If you have enabled the enhanced permissions<br/>
					 above, this option will make them mandatory.",
					$notebook_page
                );

global $db;
$plist = array();
foreach ($db->get_products_list() as $pr) $plist[$pr['product_id']] = $pr['title'];
add_config_field(	"protect.{$notebook_page}.no_wall_update",
					"Exclude From Wall Updates",
					'multi_select',
					"Select the products which should NOT trigger a Facebook<br/>
					 wall update when purchased/added by your members.",
					$notebook_page,
					'',
					'',
					'',
					array('options' => array('' => '*** None') + $plist, 'store_type' => 1 )				
                );

?>