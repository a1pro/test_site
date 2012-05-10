<?php 
/**
 *  OpenID v1.1
 *  Copyright 2010 (c) R Woodgate
 *  All Rights Reserved
 *
 **/

if( ! defined( 'INCLUDED_AMEMBER_CONFIG' ) )
{
	die("Direct access to this location is not allowed");
}

$notebook_page = 'openid';
config_set_notebook_comment( $notebook_page, 'OpenID' );
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
					"The plugin will attempt to add a 'openid'<br/>
					field automatically. Uncheck if you need to reinstall.",
					$notebook_page
                );

add_config_field(	"protect.{$notebook_page}.ax_optional",
					"Request Profile Information",
					'multi_select',
					"Profile information you would like from the<br/>
					OpenID provider. You will need <a href='{$config['root_url']}/admin/fields.php'>aMember fields</a><br/>
					with the same name for any selected items.<br/>
					NB: Not all information may be available.",
					$notebook_page,
					'',
					'',
					'',
					array('options' => array('' => '*** None') + openid_ax2field(), 'store_type' => 1 )				
                );

add_config_field(	"oidfd.##3",
				 	'New Member Settings',
					'header',
					'',
					$notebook_page
				);

add_config_field(	"protect.{$notebook_page}.newaccount",
					"Create aMember account?",
					'checkbox',
					"Creates new aMember account automatically<br/>
					 if OpenID user doesn't have one.",
					$notebook_page
                );
global $db;
$plist = array();
foreach ($db->get_products_list() as $pr) $plist[$pr['product_id']] = $pr['title']." ({$pr['expire_days']})";
add_config_field(	"protect.{$notebook_page}.newaccountproduct",
					"OpenID Product Subscription",
					'select',
					"New aMember account will be automatically<br/>
					 subscribed to selected product when created<br/>
					 via OpenID. Only works if 'Create aMember<br/>
					 account' is selected above.",
					$notebook_page,
					'',
					'',
					'',
					array('options' => array('' => '*** None') + $plist )				
                );

add_config_field(	"protect.{$notebook_page}.ax_required",
					"Required Signup Information",
					'multi_select',
					"Information you require to create an account<br/>
					automatically. If any selected item is not<br/>
					available, member will be required to use the<br/>
					signup form. NB: You will need <a href='{$config['root_url']}/admin/fields.php'>aMember fields</a> with<br/>
					the same name for any selected items.",
					$notebook_page,
					'',
					'',
					'',
					array('options' => array('' => '*** None') + openid_ax2field(), 'store_type' => 1 )				
                );
?>