<?php 
/**
 *  Facebook Connect v1.3
 *  Copyright 2010 (c) R Woodgate
 *  All Rights Reserved
 *
 **/

if( ! defined( 'INCLUDED_AMEMBER_CONFIG' ) )
{
	die("Direct access to this location is not allowed");
}

$notebook_page = 'fb_connect';
config_set_notebook_comment( $notebook_page, 'Facebook Connect' );
if( file_exists( $rm = dirname( __FILE__ ) . "/readme.txt" ) )
{
	config_set_readme( $notebook_page, $rm );
}


add_config_field(	"protect.{$notebook_page}.testmode",
					'Test Mode?',
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

add_config_field(	"protect.{$notebook_page}.forcelogout",
					"Force Facebook Logout?",
					'checkbox',
					"Forces logout from Facebook when logging out<br/>
					from aMember. Not usually recommended, as it<br/>
					will log the member out of Facebook and all <br/>
					other websites they have connected with.",
					$notebook_page
                );

add_config_field(	"fbfd.##7",
				 	'New Member Settings',
					'header',
					'',
					$notebook_page
				);

add_config_field(	"protect.{$notebook_page}.newaccount",
					"Create aMember account?",
					'checkbox',
					"Creates new aMember account automatically<br/>
					 if Facebook user doesn't have one.",
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

add_config_field(	"fbfd.##11",
				 	'Enhanced Permission Settings',
					'header',
					'',
					$notebook_page
				);

add_config_field(	"protect.{$notebook_page}.forceperms",
					"Force Facebook permissions?",
					'checkbox',
					"If you have enabled the enhanced permissions<br/>
					 below, this option will make them mandatory.<br />
					 Not recommended, as may lower take up rate.",
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

?>