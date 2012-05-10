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
 * All support questions regarding this plugin should be sent to:
 *		http://kencinnus.com/contact
 *
 * ============================================================================
 *
 *	Revision History:
 *	----------------
 *	2010-11-24	v3.2.3.1	K.Gary	Handled situation where _amember_user is not always in the session yet.
 *	2010-10-22	v3.2.3.0	K.Gary	Tested to make sure it is compatible with v3.2.3
 *									Changed the thank you page logic so no template needs to be updated.
 *	2010-04-29	v3.1.9.0	K.Gary	Tested to make sure it is compatible with v3.1.9
 *	2009-09-06	v3.1.8.2	K.Gary	Fixed bug where set wrong session variable name.
 *	2009-09-03	v3.1.8.1	K.Gary	Clear one-click session variables in receipts when upsells and offers are complete.
 *	2009-05-28	v3.1.8.0	K.Gary	Tested with aMember version 3.1.8
 *	2009-05-28	v3.1.7.1	K.Gary	Changed revision naming rules to match aMembers current level
 *	2009-05-26	v1.1		K.Gary	Added field for copy to display on thank you page when a product is purchased.
 *	2009-05-20	v1.0		K.Gary	Original Version.
 *
 * =============================================================================
 *
 **/

if (!defined('INCLUDED_AMEMBER_CONFIG')) die("Direct access to this location is not allowed");

global $db;

add_product_field('amthankyou_header',
                  'amThankYou v3.2.3.1',
                  'header','','',
                  array('insert_before' =>  '##12')
                 );


add_product_field('amthankyou_copy',
                  'Thank You Page Copy',
                  'textarea',
                  'Enter the copy you want to show on the<br />
                   thank you page when this product is purchased.',
                  '',
                  array('insert_after'=>'amthankyou_header')
                 );

function check_setup_amthankyou()
{
    global $plugin_config, $config, $db;

    $this_config = $plugin_config['protect']['amthankyou'];

    return '';

} // end check_setup_amthankyou

if (!check_setup_amthankyou())
{
    // setup_plugin_hook('subscription_updated', 'amthankyou_updated');
    // setup_plugin_hook('subscription_rebuild', 'amthankyou_rebuild');
    // setup_plugin_hook('subscription_added',   'amthankyou_added');
    // setup_plugin_hook('subscription_deleted', 'amthankyou_deleted');
    // setup_plugin_hook('subscription_removed', 'amthankyou_removed');
	setup_plugin_hook('filter_output', 'amthankyou_filter_output');
}

//
// Thank You Page Insertion
//

function amthankyou_filter_output(&$source, $resource_name, $smarty)
{

    global $db, $plugin_config, $config;

	if ($resource_name == "thanks.html")
	{

	    //
	    // Get Smarty Template Variables
	    //

		$tplvars   = $smarty->_tpl_vars;

		$payment   = $tplvars['payment'];

		$member    = $_SESSION['_amember_user'];

		$member_id = $member['member_id'];

		if (empty($member) || empty($member_id))
		{

			$member_login = $_SESSION['_amember_login'];

			$q = $db->query("SELECT member_id FROM {$db->config['prefix']}members WHERE login='".$member_login."'");

			if (!$r = mysql_fetch_assoc($q))
			{
				$db->log_error('amThankYou: User not found: '.$member_login);
				$r = array();
			}

			$member_id = $r['member_id'];

		} // end if member not in session yet

		//
		// See if we should show the AWeber message...
		//

		if (!empty($tplvars)    &&
		    !empty($payment)    &&
		    !empty($member_id)  &&
		    $payment['member_id'] == $member_id			// make sure they only see their own payments!
		   )
		{

		    $this_payment_id = $payment['payment_id'];

			//
			// The Plugin...
			//

		    $this_config = $plugin_config['protect']['amthankyou'];

		    if (empty($this_config)) return;								// no need to go on if this has not got data

		    $debug       = $this_config['debug'];

		    if ($debug) $db->log_error ('amThankYou: Custom Filter: Start');

			$enjoytext   = (!empty($this_config['enjoytext'])) ? $this_config['enjoytext'] : "Please enjoy the products you have purchased<br />today by clicking on a link below...";

			//
			// The Payment...
			//

			$pmt = $payment;

			if ($debug) $db->log_error('amThankYou: Product Links: pmt = '.print_r($pmt,1));

			//
			// Store the Payment ID
			//

			$_SESSION['last_payment_id'] = $pmt['payment_id'];				// store this for going to the thank you page at the end

			$_SESSION['last_paysys_id']  = $pmt['paysys_id'];				// store this too

			$_SESSION['amPayments'][$pmt['payment_id']] = $pmt['amount'];	// all payments made during this session...for receipt page

			//
			// Get the products on this payment and see if they trigger a strategy session or not...
			//

			if (!$pmt['data']['0']['BASKET_PRICES'])
				$pmt['data']['0']['BASKET_PRICES'] = array($pmt['product_id'] => $pmt['amount']);

			if (is_array($pmt['data']['0']['BASKET_PRICES']))
			{

				foreach ($pmt['data']['0']['BASKET_PRICES'] as $pid => $price)
				{

				    $_SESSION['amProducts'][$pid] = true;

				    //
				    // How about the complimentary products...
				    //

				    $pr = $db->get_product($pid);

				    if (!empty($pr['complimentary_product']))
					    $_SESSION['amProducts'][$pr['complimentary_product']] = true;

				} // end for each product on this payment

			} // end if is_array

			//
			// Display links to all of the products member has purchased this session...
			//

			if (is_array($_SESSION['amProducts']))
			{

				$output  = '<center>'."\n";
				$output .= '<div id="thankyouproductlinks">'."\n";
				$output .= '<h3 style="margin-bottom:20px;">'.$enjoytext.'</h3>'."\n";
				$output .= '<div id="productlinks" style="width:300px;text-align:left;">'."\n";
				$output .= '<ul>'."\n";

				$buffer = '';

				foreach ($_SESSION['amProducts'] as $pid=>$active)
				{

					$product = $db->get_product($pid);

					if (!empty($product) && is_array($product) && !empty($product['title']))
					{

						//
						// Does this product have Thank You Page copy?...
						//

						if (!empty($product['amthankyou_copy'])) $buffer .= '<br />'.$product['amthankyou_copy']."\n";

						$output .= '<li>'."\n";

						if (!empty($product['url']))
						{

							$output .= '  <h4><a href="'.$product['url'].'">'.$product['title'].'</a></h4>'."\n";

						} else {

							$output .= '  <h4>'.$product['title'].'</h4>'."\n";

						} // end if the product has a URL

						//
						// Does this product have additional URLS or Incremental Content?...
						//

						if (is_array($_SESSION['_amember_links']) || !empty($product['add_urls']))
						{

							$output .= '<ul>'."\n";

							//
							// Additional URLs for this product
							//

							if (!empty($product['add_urls']))
							{

								$add_urls = nl2br($product['add_urls']);

								$add_urls = explode('<br />',$add_urls);

								if (is_array($add_urls))
								{

									foreach ($add_urls as $add_url)
									{

										$the_url = explode('|',$add_url);

										$output .= '<li><a href="'.$the_url[0].'">'.$the_url[1].'</a></li>'."\n";

									} // end foreach additional url

								} // end if got an array

							} // end if product has additional urls

							//
							// Incremental content for this product...
							//

							if (is_array($_SESSION['_amember_links']))
							{

								foreach ($_SESSION['_amember_links'] as $link_id => $link)
								{

									if ($link['link_product_id'] == $product['product_id'])
									{

										$output .= '<li><p><a href="'.$link['link_url'].'">'.$link['link_title'].'</a></p></li>'."\n";

									} // end if this link is for this product

								} // end for each left link

							} // end if session links

							$output .= '</ul>'."\n";

						} // end if additional urls or incremental content

						$output .= '</li>'."\n";

					} // end if found the product in the database

				} // end foreach

				$output .= '</ul></div><br /><hr style="width:250px;" /><br /></div></center>'."\n\n";

				if (!empty($buffer))
				{

					$output .= '<div id="productthankyoucopy">'."\n";

					$output .= $buffer."\n<br />\n";

					$output .= '</div>'."\n";

					$output .= '<center><hr style="width:250px;" /><br /></center>'."\n";

				} // end if any products had thank you page copy

			} // got product purchased

			//
			// Show Output Buffer :: Products
			//

			if (!empty($output))
			{

				//
				// Pick the point in the content to insert this ouput.  We want it right above the login link.
				//

				$startmark    = '<!-- content_start mark -->';		// shows where the top of the content is

				$amailendmark = '<!-- amail_end mark -->';			// want it below amail if it is there

				//
				// Need to make sure it comes after the amail opt-in message if it is there...
				//

				$target = (strpos($source,$amailendmark) !== false) ? $amailendmark : $startmark;

				$parts  = explode($target,$source);

				if (count($parts) == 2)
				{

					//
					// Insert the buffer onto the page...
					//

					$output = "\n\n<!-- amthankyou_products_start mark -->\n".$output."\n<!-- amthankyou_products_end mark -->\n\n";

					$source = $parts[0].$target.$output.$parts[1];	// Do NOT forget to put the content start mark back in!

				} else {

					//
					// Something messed up with layout.html or because another plugin took out the content start mark?
					//

					$db->log_error('amThankYou: Custom Filter: Products: Could not find content start mark!');

				} // end if source has proper template

			} // end if message for thanks page

			$output = null;											// clear our output buffer for the next part

			//
			// Display reciepts for all of the products this member has purchased during this session...
			//

			if (is_array($_SESSION['amPayments']))
			{

				if ($debug) $db->log_error('amThankYou: Receipts: SESSION[amPayments] is an array...');

				foreach ($_SESSION['amPayments'] as $payment_id=>$payment_amount)
				{

					$p == null;												// initialize

					if ($debug) $db->log_error('amThankYou: Receipts: payment_id = '.$payment_id.', this_payment_id = '.$this_payment_id.', payment_amount = '.$payment_amount);

					$p = $db->get_payment($payment_id);

					$subtotal         = 0;
					$pr               = array();
					$receipt_products = array();

					if (!$p['data']['0']['BASKET_PRICES'])
					    $p['data']['0']['BASKET_PRICES'] = array($p['product_id'] => $p['amount']);

					foreach ($p['data']['0']['BASKET_PRICES'] as $pid => $price)
					{

					    $pr = $db->get_product($pid);

					    $pr['subtotal'] = $pr['trial1_days'] ? $pr['trial1_price'] : $pr['price'];

					    $subtotal += $pr['subtotal'];

					    $receipt_products[$pid] = $pr;

					}

					$total = array_sum($p['data']['0']['BASKET_PRICES']);

  					if ($payment_id != $this_payment_id && ($payment_amount > "0.0" || $p['paysys_id'] == 'cc_demo'))
  					{

						if ($debug) $db->log_error('amThankYou: Receipts: Yep, got a good payment, printing receipt...');

						$output = str_replace("%d",$payment['payment_id'],_TPL_THX_ORDERREF);
						$output = str_replace("%s",$payment['receipt_id'],$output);
						$output .= '<br />'."\n";

						$output .= str_replace("%s",$payment['tm_completed'],_TPL_THX_DATETIME);
						$output .= '<br />'."\n";

						$output .= '<br /><br />'."\n";
						$output .= '<table class="receipt">'."\n";
						$output .= '<tr>'."\n";
						$output .= '    <th>'._TPL_THX_PRDTITLE.'</th>'."\n";
						$output .= '    <th style="width: 10%; text-align: right;">'._TPL_THX_PRICE.'</th>'."\n";
						$output .= '</tr>'."\n";

						foreach ($receipt_products as $p)
						{

							$output .= '<tr>'."\n";
							$output .= '    <td>'.$p['title'].'</td>'."\n";
							$output .= '    <td style="text-align: right">'.$config['currency'].number_format($p['subtotal'],2).'</td>'."\n";
							$output .= '</tr>'."\n";

						} // end foreach receipt products

						$output .= '<tr>'."\n";
						$output .= '    <td class="total"><strong>'._TPL_THX_SUBTOTAL.'</strong></td>'."\n";
						$output .= '    <td class="total" style="text-align: right"><strong>'.$config['currency'].number_format($subtotal,2).'</strong></td>'."\n";
						$output .= '</tr>'."\n";

						if (!empty($payment['data']['COUPON_DISCOUNT']))
						{

							$output .= '<tr>'."\n";
							$output .= '    <td><strong>'._TPL_THX_DISCOUNT.'</strong></td>'."\n";
							$output .= '    <td style="text-align: right"><strong>'.$config['currency'].number_format($payment['data']['COUPON_DISCOUNT'],2).'</strong></td>'."\n";
							$output .= '</tr>'."\n";

						} // end if coupont discount

						if (!empty($payment['tax_amount']))
						{
							$output .= '<tr>'."\n";
							$output .= '    <td><strong>'._TPL_THX_TAX.'</strong></td>'."\n";
							$output .= '    <td style="text-align: right"><strong>'.$config['currency'].number_format($payment['tax_amount'],2).'</strong></td>'."\n";
							$output .= '</tr>'."\n";
						} // end if tax amount

						$output .= '<tr>'."\n";
						$output .= '    <td class="total"><strong>'._TPL_THX_TOTAL.'</strong></td>'."\n";
						$output .= '    <td class="total" style="text-align: right"><strong>'.$config['currency'].number_format($total,2).'</strong></td>'."\n";
						$output .= '</tr>'."\n";
						$output .= '</table>'."\n";
						$output .= '<br /><br />'."\n";

					} // end if this is the receipt that has already been shown

				} // end foreach receipt

			} // end if receipts

			//
			// Show Output Buffer :: Receipts
			//

			if (!empty($output))
			{

				//
				// Pick the point in the content to insert this ouput.  We want it right before the footer.
				//

				$endmark   = '<!-- content_end mark -->';			// shows where the end of the content is

				$parts     = explode($endmark,$source);

				if (count($parts) == 2)
				{

					//
					// Insert the buffer onto the page...
					//

					$output = "\n\n<!-- amthankyou_receipts_start mark -->\n".$output."\n<!-- amthankyou_receipts_end mark -->\n\n";

					$source = $parts[0].$output.$endmark.$parts[1];	// Do NOT forget to put the content end mark back in!

				} else {

					//
					// Something messed up with layout.html or because another plugin took out the content start mark?
					//

					$db->log_error('amThankYou: Custom Filter: Receipts: Could not find content end mark!');

				} // end if source has proper template

			} // end if message for thanks page

			//
			// Center the login link line...
			//

			$source = str_replace('<strong>Enjoy your membership.','<center><strong>Enjoy your membership.',$source);

			$source = str_replace('Login</a></strong>','Login</a></strong></center><br />',$source);

/*
			//
			// Since we are here we must be done with one-click offers so erase the session variables.
			//

			if (function_exists(amoneclick_clear_session) &&
			    empty($_SESSION['doingoffers'])           &&
			    empty($_SESSION['doingupsell'])
			   ) amoneclick_clear_session();
*/
		    if ($debug) $db->log_error ('amThankYou: Custom Filter: End');

		} // end if show

	} // end if thank you page

} // end amthankyou_custom_filter

function amthankyou_updated($member_id, $oldmember, $newmember)
{

    global $config, $db, $plugin_config;

    $this_config = $plugin_config['protect']['amthankyou'];

    /// this function will be called when member updates
    /// his profile. If user profile is exists in your
    /// database, you should update his profile with
    /// data from $newmember variable. You should use
    /// $oldmember variable to get old user profile -
    /// it will allow you to find original user record.
    /// Don't forget - login can be changed too! (by admin)

} // end amthankyou_updated

function amthankyou_rebuild(&$members)
{

    global $config, $db, $plugin_config;

    $this_config = $plugin_config['protect']['amthankyou'];

    /// some actions when admin click aMember CP -> Rebuild Db
    /// it should compare all records in your third-party
    /// database with aMember supplied-list ($members)
    /// Or you may just skip this hook

}

function amthankyou_added($member_id, $product_id, $member)
{

    global $config, $db, $plugin_config;

    $this_config = $plugin_config['protect']['amthankyou'];

    /// It's a most important function - when user subscribed to
    /// new product (and his subscription status changed to ACTIVE
    /// for this product), this function will be called
    /// In fact, you should add user to database here or update
    /// his record if it is already exists (it is POSSIBLE that
    /// record exists)

}

function amthankyou_deleted($member_id, $product_id, $member)
{

    global $config, $db, $plugin_config;

    $this_config = $plugin_config['protect']['amthankyou'];

    /// This function will be called when user subscriptions
    /// status for $product_id become NOT-ACTIVE. It may happen
    /// if user payment expired, marked as "not-paid" or deleted
    /// by admin
    /// Be careful here - user may have active subscriptions for
    /// another products and he may be should still in your
    /// database - check $member['data']['status'] variable

}

function amthankyou_removed($member_id, $member)
{

    global $config, $db, $plugin_config;

    $this_config = $plugin_config['protect']['amthankyou'];

    /// This function will be called when member profile
    /// deleted from aMember. Your plugin should delete
    /// user profile from database (if your application allows it!),
    /// or it should just disable member access if your application
    /// if application doesn't allow profiles deletion

}

?>