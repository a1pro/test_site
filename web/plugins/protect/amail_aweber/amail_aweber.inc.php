<?php

/**
 *
 * Copyright (C) 2011 Kencinnus, LLC. All rights reserved.
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
 * All Support Questions regarding this plugin should be sent to http://kencinnus.com/contact
 *
 * ============================================================================
 *
 *	Revision History:
 *	----------------
 *	2011-04-26	v3.2.3.2	K.Gary	Fixed a bug with sending additional fields if SQL.
 *	2011-03-07	v3.2.3.1	K.Gary	Fixed a bug with get_user.
 *	2010-10-22	v3.2.3.0	K.Gary	Made sure everything works with 3.2.3
 *									Changed the thank you page logic so no template needs to be updated.
 *	2010-04-16	v3.1.9.0	K.Gary	Made sure everything works with 3.1.9
 *	2010-03-10	v3.1.8.8	K.Gary	Added cron logic to unsubscribe expired users after xx days
 *	2010-02-01	v3.1.8.7	K.Gary	Changed rebuild function to assume local subscribe/unsubscribe state is valid.
 *									Will not re-send subscribes/unsubscribes that it has already sent to AWeber.
 *	2009-12-17	v3.1.8.6	K.Gary	Changed rebuild function to not resend subscribes/unsubscribes unless told to via option.
 *	2009-10-30	v3.1.8.5	K.Gary	Fixed bug in remove routine that did not properly remove subscribers when they are deleted.
 *	2009-08-26	v3.1.8.4	K.Gary	Fixed a bug with the database rebuild routine. Now it access every member.
 *	2009-08-19	v3.1.8.3	K.Gary	Added capability to pass member id to AWeber.
 *	2009-08-10	v3.1.8.2	K.Gary	Switched to mail_customer function and used outgoing admin email.
 *									Email parser at AWeber should now match OUTGOING EMAIL ADDRESS!
 *	2009-06-01	v3.1.8.1	K.Gary	Add subscribers at finish_waiting_payment instead of subscription_added.
 *									If a product is set to "nolist" then do not add subscribers to any list.
 *	2009-05-28	v3.1.8.0	K.Gary	Tested with aMember version 3.1.8
 *	2009-05-28	v3.1.7.5	K.Gary	Changed revision naming rules to match aMembers current level
 *	2009-05-14	v1.5		K.Gary	Changed the amail_aweber_deleted function to ignore recurring products.
 *	2009-03-24	v1.4		K.Gary	Removed periods from site titles so the from trigger rule works at AWeber.
 *	2009-03-23	v1.4		K.Gary	Added a hook in member.php to capture when they change their unsubscribed flag themselves.
 *	2009-03-21	v1.3		K.Gary	Changed name to aMail.  Added more integration with unsubscribed field in member record.
 *  2009-03-19  v1.2   		K.Gary	Added checkboxes to control what fields are submitted to AWeber, and CC admin.
 *	2009-03-18	v1.1		K.Gary	Changed from submitting the form with curl to sending an email for the AWeber email parser.
 *	2009-03-17	v1.0		K.Gary	Added login and password paramaters being passed to AWeber.
 *									Also added Street, City, Zip, State and Phone
 *	2009-03-16	v1.0		K.Gary	Original Version.
 *
 * =============================================================================
 *
 **/

/*

According to http://www.amember.com/forum/showthread.php?t=12918 ...

In 3.2.4 we are going to get a new hook. "newsletters_changed".

It will be called with the following arguments (for example):

PHP Code:

user Array(
    [member_id] => 2
    [login] => alex
    [pass] => 1234
    [email] => alex@cgi-central.net
    [name_f] => Alex
    [name_l] => Scott
    [street] =>
    [city] =>
    [state] =>
    [zip] =>
    [country] =>
    [is_male] => 0
    [added] => 2010-08-24 22:37:03
    [remote_addr] => 127.0.1.1
    [data] => Array
        (
            [status] => Array
                (
                    [2] => 0
                    [1] => 0
                )

            [is_active] => 0
            [signup_email_sent] => 1
        )

    [status] => 2
    [aff_id] => 4
    [is_affiliate] => 0
    [aff_payout_type] =>
    [unsubscribed] => 0
    [email_verified] => 0
    [security_code] =>
    [securitycode_expire] => 0000-00-00 00:00:00
)


before Array
(
    [1] => xxx
    [2] => yyy
    [unsubscribed] => 0
)


after Array
(
    [unsubscribed] => 1
)

*/


if (!defined('INCLUDED_AMEMBER_CONFIG')) die("Direct access to this location is not allowed");

add_product_field('amail_aweber_header',
                  'aMail for AWeber v3.2.3.2',
                  'header',"",'',array('insert_before' =>  '##12')
                 );

add_product_field('amail_aweber_listnames',
                  'AWeber Listname For This Product',
                  'text',
                  'e.g.: If your list is yourlistname@aweber.com then just enter <strong>yourlistname</strong>.<br />(Use commas to separate multiple listnames.  But it is best if there is only one.)',
                  '',array('insert_after' =>  'amail_aweber_header')
                 );

add_member_field('amail_aweber_subscriptions',
                 'AWeber Subscriptions',
                 'text',
                 "AWeber lists member has been subscribed to so far.<br />NOTE: It does not mean they confirmed or are still subscribed.",
                 '',
                 null
                );

add_member_field('amail_aweber_unsubscriptions',
                 'AWeber Un-Subscriptions',
                 'text',
                 "AWeber lists member has been unsubscribed from.",
                 '',
                 null
                );

define ('_TPL_SIGNUP_NEWSLETTERS_SUBSCRIBE_2', 'Your password will be emailed to you.');

function check_setup_amail_aweber()
{

    global $plugin_config, $config, $db;

    $this_config = $plugin_config['protect']['amail_aweber'];
    $listname    = $this_config['listname'];

    if (!$listname)
    {
        $error = "Error. Please configure 'amail_aweber' plugin at aMember CP -> Setup -> amail_aweber";
        if (!$_SESSION['check_setup_amail_aweber_error']) $db->log_error ($error);
        $_SESSION['check_setup_amail_aweber_error'] = $error;
        return $error;
    }

    return '';

} // end check_setup_amail_aweber


if (!check_setup_amail_aweber())
{
	// setup_plugin_hook('subscription_added'    , 'amail_aweber_added'                 );	// product subscription added but not paid for yet
    setup_plugin_hook('finish_waiting_payment', 'amail_aweber_finish_waiting_payment');	// product subscription has now been paid for
	setup_plugin_hook('subscription_deleted'  , 'amail_aweber_deleted'               );	// product subscription deleted
    setup_plugin_hook('subscription_removed'  , 'amail_aweber_removed'               );	// member profile removed
    setup_plugin_hook('subscription_updated'  , 'amail_aweber_updated'               );	// member or admin updated member profile
    setup_plugin_hook('subscription_rebuild'  , 'amail_aweber_rebuild'               );	// rebuild the database
	setup_plugin_hook('daily',  'amail_aweber_daily');
	// setup_plugin_hook('hourly', 'amail_aweber_hourly');
	setup_plugin_hook('filter_output', 'amail_aweber_filter_output');
}

//
// Filter Output (on the Thank You page)
//

function amail_aweber_filter_output(&$source, $resource_name, $smarty)
{

    global $db, $plugin_config, $config;

	if ($resource_name == "thanks.html")
	{

	    //
	    // Get Smarty Template Variables
	    //

		$tplvars = $smarty->_tpl_vars;

		$payment = $tplvars['payment'];

		$member  = $_SESSION['_amember_user'];

		if (empty($member)) $member = $tplvars['member'];

		if (empty($member) && !empty($payment['member_id'])) $member = $db->get_user($payment['member_id']);

		//
		// See if we should show the AWeber message...
		//

		if (!empty($tplvars)         &&
		    !empty($payment)         &&
		    !empty($member)          &&
		    !$member['unsubscribed'] &&
		    $payment['member_id'] == $member['member_id']			// make sure they only see their own payments!
		   )
		{

		    global $db, $plugin_config, $config;

			//
			// The Plugin...
			//

		    $this_config = $plugin_config['protect']['amail_aweber'];

		    if (empty($this_config)) return;								// no need to go on if this has not got data

		    if (!empty($this_config['debug'])) $db->log_error ('aMail for AWeber: Filter Output: Start');

		    $thanks_title   = trim($this_config['thankstitle']);			// title for thanks page

		    $thanks_message = trim($this_config['thanksmessage']);			// message for thanks page

		    if (empty($thanks_title) && empty($thanks_message)) return;		// nothing to do

			//
			// Show Thank You Message
			//

			if (!empty($thanks_message))
			{

				$startmark = '<!-- content_start mark -->';			// shows where the top of the content is
				$endmark   = '<!-- content_end mark -->';			// shows where the end of the content is

				$parts     = explode($startmark,$source);

				if (count($parts) == 2)
				{

					//
					// Do variable substitution on the thanks message...
					//

					$thanks_message = str_replace('[[first_name]]' , $member['name_f'], $thanks_message);
					$thanks_message = str_replace('[[last_name]]'  , $member['name_l'], $thanks_message);
					$thanks_message = str_replace('[[email]]'      , $member['email'] , $thanks_message);

					$thanks_message = str_replace('[[admin_email]]', $tplvars['config']['admin_email_from'], $thanks_message);
					$thanks_message = str_replace('[[admin_name]]' , $tplvars['config']['admin_email_name'], $thanks_message);
					$thanks_message = str_replace('[[site_title]]' , $tplvars['config']['site_title']      , $thanks_message);

					//
					// Now insert the message onto the page...
					//

					$thanks_message = "\n\n<!-- amail_start mark -->\n".$thanks_message."\n<!-- amail_end mark -->\n\n";

					$source = $parts[0].$startmark.$thanks_message.$parts[1];	// Do NOT forget to put the content start mark back in!

				} else {

					//
					// Something messed up with layout.html or because another plugin took out the content start mark?
					//

					$db->log_error('aMail for AWeber: Filter Output: Could not find content start mark!');

				} // end if source has proper template

			} // end if message for thanks page

			//
			// Change the title on the page...
			//

			if (!empty($thanks_title))
			{

				$title  = $tplvars['title'];

				$source = str_replace($title,$thanks_title,$source);

			} // end if title for thanks page

		    if (!empty($this_config['debug'])) $db->log_error ('aMail for AWeber: Filter Output: End');

		} // end if show

	} // end if thank you page

} // end amail_aweber_filter_output


function amail_aweber_added($member_id, $product_id, $member=null)
{

    /// It is a most important function - when user subscribed to
    /// new product (and his subscription status changed to ACTIVE
    /// for this product), this function will be called
    /// In fact, you should add user to database here or update
    /// his record if it is already exists (it is POSSIBLE that
    /// record exists)

    global $db, $plugin_config, $config;

	//
	// The Plugin...
	//

    $this_config = $plugin_config['protect']['amail_aweber'];

    if (empty($this_config)) return;								// no need to go on if this has not got data

    if (!empty($this_config['debug'])) $db->log_error ('aMail for AWeber: amail_aweber_added');

    if (empty($this_config['listname'])) return;					// plugin must not be fully configured yet

    $aweber_default_listname = trim($this_config['listname']);		// do no make this into an array

    //
    // The Member...
    //

    if (empty($member) && !empty($member_id)) $member = $db->get_user($member_id);

    if (empty($member)) return;										// cannot do anything on an empty member record

    if (empty($member['member_id'])) return;						// ditto

	if (!empty($member['unsubscribed'])) return;					// make sure this member allows themself to be subscribed

	//
	// The Product...
	//

	$product = (empty($_SESSION['amproducts'][$product_id])) ? $db->get_product($product_id) : $_SESSION['amproducts'][$product_id];

	if (!empty($product) && is_array($product)) $_SESSION['amproducts'][$product_id] = $product;

	$product_title = $product['title'];								// for the email

	$product_price = $product['price'];								// for the email

	//
	// Process the list names...
	//

    $lists = trim($product['amail_aweber_listnames']);

    if (!empty($lists))
    {

		//
		// Get all of the listnames this product triggers subscriptions for...
		//

    	$listnames = explode(',',$lists);

	} else {

	    //
	    // If the product listname is blank use the plugin default listnames...
	    //

	    $listnames = explode(',',$aweber_default_listname);

    } // should have an array with at least one list name in it now.

	if (empty($listnames)) return;									// no need to go on if nothing to subscribe to

	$listnames = array_unique($listnames);							// remove duplicates

	//
	// Get rid of any unwanted white space in the array of listnames...
	//

	foreach($listnames as $key=>$value) $listnames[$key] = trim($value);

	//
	// Get an array of AWeber lists that the member has already been subscribed to...
	//

	$amail_aweber_subscriptions = trim($member['data']['amail_aweber_subscriptions']);

	if (!empty($amail_aweber_subscriptions))
	{

		$already_on = explode(',',$amail_aweber_subscriptions);		// need this a little later on

		$already_on = array_unique($already_on);					// remove duplicates

	}

    //
    // Now subscribe the member to AWeber...
    //

	foreach ($listnames as $listname)
	{

		if ((empty($amail_aweber_subscriptions) || !in_array($listname,$already_on)) && $listname != 'nolist')
		{

			amail_aweber_subscribe($listname, $member, $product_title, $product_price);

			//
			// Add to the members list...
			//

			$already_on[] = $listname;								// add the list we just subscribed to the member list array for this loop

			if (empty($amail_aweber_subscriptions))
			{

				$amail_aweber_subscriptions  = $listname;				// add the list we just subscribed to the member list to save later

			} else {

				$amail_aweber_subscriptions .= ",".$listname;			// add the list we just subscribed to the member list to save later

			} // end if this is the first list or not

		} else if (in_array($listname,$already_on)) {

			if ($debug) $db->log_error('aMail for AWeber: Subscriber already on '.$listname);

		} else if ($listname == 'nolist') {

			if ($debug) $db->log_error('aMail for AWeber: Not subscribing because product set to no list.');

    	} // end if this member is already subscribed to this list

    } // end for all list names members who purchase this product should be subscribed to

    //
    // Now Update the Member Record with new list of subscriptions...
    //

	$member['data']['amail_aweber_subscriptions'] = $amail_aweber_subscriptions;

	$db->update_user($member_id, $member);

} // end amail_aweber_added


//
// Add member to list now
//

function amail_aweber_finish_waiting_payment($payment_id,$receipt_id=null,$amount=null)
{

	// Product Subscription is now paid for.

    global $config, $db, $plugin_config;

	//
	// The Plugin...
	//

    $this_config = $plugin_config['protect']['amail_aweber'];

    if (empty($this_config)) return;								// no need to go on if this has not got data

    $debug                = trim($this_config['debug']);		// write debug statements

    if ($debug) $db->log_error ('aMail for AWeber: Finish Waiting Payment Begin.');

    if (empty($this_config['listname'])) return;					// plugin must not be fully configured yet

    $aweber_default_listname = trim($this_config['listname']);		// do no make this into an array

    //
    // The Payment...
    //

    $payment = $db->get_payment($payment_id); 						// $payment is now an array

	if (empty($payment)) return;									// no need to go on if this has not got data

    if ($debug) $db->log_error ('aMail for AWeber: Got a good payment.');

	//
	// If this is a multiple product order then we need to subscribe them all...
	//

	if (!empty($payment['data'][0]['product_id']))
	{

		//
		// Multiple products in this array so get them all
		//

		if (is_array($payment['data'][0]['product_id']))
		{

			$product_ids = $payment['data'][0]['product_id'];

		} else {

			$product_ids[] = $payment['data'][0]['product_id'];

		}

	} else {

		//
		// it is a single we need to process
		//

		if (is_array($payment['product_id']))
		{

			$product_ids = $payment['product_id'];

		} else {

	   		$product_ids[] = $payment['product_id'];

	   	}

	} // end if getting product_id out of multiple or single product record

	if (empty($product_ids)) return;								// no need to go on if we have no products

    if ($debug) $db->log_error ('aMail for AWeber: Got products.');

	//
	// The Member...
	//

	$member_id = $payment['member_id'];								// get the member id from the payment record

    $member    = $db->get_user($member_id);							// $member is now an array

	if (empty($member)) return;										// no need to go on if this has not got data

	if (empty($member['member_id'])) return;						// ditto

	if (!empty($member['unsubscribed'])) return;					// make sure this member allows themself to be subscribed

    if ($debug) $db->log_error ('aMail for AWeber: Member allows subscriptions.');

	//
	// Get an array of AWeber lists that the member has already been subscribed to...
	//

	$amail_aweber_subscriptions = trim($member['data']['amail_aweber_subscriptions']);

	$old_subscription_list      = $amail_aweber_subscriptions;		// save it to see if it changes

	$already_on = array();

	if (!empty($amail_aweber_subscriptions))
	{

		$already_on = explode(',',$amail_aweber_subscriptions);		// need this a little later on

		$already_on = array_unique($already_on);					// remove duplicates

	}

	//
	// Start subscribing member to lists
	//

	foreach ($product_ids as $product_id)
	{

		$product_title = null;

		$product_price = null;

		$lists         = null;

		$listnames     = null;

		$product       = (empty($_SESSION['amproducts'][$product_id])) ? $db->get_product($product_id) : $_SESSION['amproducts'][$product_id];

		if (!empty($product) && is_array($product)) $_SESSION['amproducts'][$product_id] = $product;

		if (!empty($product))
		{

			$product_title = $product['title'];								// for the email

			$product_price = $product['price'];								// for the email

		    if ($debug) $db->log_error ('aMail for AWeber: Processing '.$product_title.'...');

			//
			// Process the list names...
			//

		    $lists = trim($product['amail_aweber_listnames']);

		    if (!empty($lists))
		    {

				//
				// Get all of the listnames this product triggers subscriptions for...
				//

		    	$listnames = explode(',',$lists);

			} else {

			    //
			    // If the product listname is blank use the plugin default listnames...
			    //

			    $listnames = explode(',',$aweber_default_listname);

		    } // should have an array with at least one list name in it now.

			if (!empty($listnames))
			{

				$listnames = array_unique($listnames);							// remove duplicates

				//
				// Get rid of any unwanted white space in the array of listnames...
				//

				foreach($listnames as $key=>$value) $listnames[$key] = trim($value);

			    if ($debug) $db->log_error ('aMail for AWeber: listnames = '.print_r($listnames,1));

			    //
			    // Now subscribe the member to AWeber...
			    //

				foreach ($listnames as $listname)
				{

					if ((empty($amail_aweber_subscriptions) || !in_array($listname,$already_on)) && $listname != 'nolist')
					{

						amail_aweber_subscribe($listname, $member, $product_title, $product_price);

						//
						// Add to the members list...
						//

						$already_on[] = $listname;								// add the list we just subscribed to the member list array for this loop

						if (empty($amail_aweber_subscriptions))
						{

							$amail_aweber_subscriptions  = $listname;			// add the list we just subscribed to the member list to save later

						} else {

							$amail_aweber_subscriptions .= ",".$listname;		// add the list we just subscribed to the member list to save later

						} // end if this is the first list or not

					} else if (in_array($listname,$already_on)) {

						if ($debug) $db->log_error('aMail for AWeber: Subscriber already on '.$listname);

					} else if ($listname == 'nolist') {

						if ($debug) $db->log_error('aMail for AWeber: Not subscribing because product set to no list.');

			    	} // end if this member is already subscribed to this list

			    } // end for all list names members who purchase this product should be subscribed to

			} // end if listnames

		} // end if product

	} // end for each product_id

    //
    // Now Update the Member Record with new list of subscriptions...
    //

	if ($amail_aweber_subscriptions != $old_subscription_list)
	{

		$member['data']['amail_aweber_subscriptions'] = $amail_aweber_subscriptions;

		$db->update_user($member_id, $member);

	}

    if ($debug) $db->log_error ('aMail for AWeber: Finish Waiting Payment End.');

} // end amail_aweber_finish_waiting_payment

function amail_aweber_deleted($member_id, $product_id, $member=null)
{

    global $db, $plugin_config, $config;

	//
	// The Plugin...
	//

    $this_config = $plugin_config['protect']['amail_aweber'];

    if (empty($this_config)) return;								// no need to go on if this has not got data

    if (!empty($this_config['debug'])) $db->log_error ('aMail for AWeber: amail_aweber_deleted');

    if (!empty($this_config['noremove'])) return;					// not supposed to remove anyone

    if (empty($this_config['listname'])) return;					// plugin must not be fully configured yet

    $aweber_default_listname = trim($this_config['listname']);		// do no make this into an array

    //
    // The Member...
    //

    if (empty($member) && !empty($member_id)) $member = $db->get_user($member_id);

    if (empty($member)) return;										// cannot do anything on an empty member record

    if (empty($member['member_id'])) return;						// ditto

    //
    // The Product...
    //

	$product = (empty($_SESSION['amproducts'][$product_id])) ? $db->get_product($product_id) : $_SESSION['amproducts'][$product_id];

	if (!empty($product) && is_array($product)) $_SESSION['amproducts'][$product_id] = $product;

	if (empty($product)) return;									// no need to go on if this has not got data

	if ($product['is_recurring'] == 1) return;						// do not unsubscribe recurring payments

    if (!$product['amail_aweber_listnames']) return;				// this product has no lists for us to remove them from

   	$listnames = explode(',',trim($product['amail_aweber_listnames']));

   	$listnames = array_unique($listnames);							// remove duplicates

	//
	// Get the lists that this member is on to be sure we should remove them from this one...
	//

	$amail_aweber_subscriptions = trim($member['data']['amail_aweber_subscriptions']);

	if (empty($amail_aweber_subscriptions)) return;					// cannot unsubscribe them if they are not on any lists

	$already_on = explode(',',$amail_aweber_subscriptions);			// need this a little later on

	$already_on = array_unique($already_on);						// remove duplicates

	//
	// Unsubscribe them from all these lists and delete those listnames from the member record...
	//

	$new_subscriptions = '';										// will rebuild their new subscription list as we go

	foreach ($already_on as $liston)
	{

	    $stillon = $liston;											// so far the member is still on this list

		foreach($listnames as $listname)
		{

			//
			// Do the unsubscribe if they are on this list...
			//

			if ($liston == $listname)
			{

				$listname = trim($listname);

			    amail_aweber_unsubscribe($listname,$member,'amember_deleted');

			    $stillon  = '';										// the member is NOT still on this list now

			} // end if product list is on member list

		} // end for each product list

		//
		// Rebuild the subscription list for this member
		//

		if (!empty($stillon))
		{

			if (!empty($new_subscriptions)) $new_subscriptions .= ",";

			$new_subscriptions .= $stillon;

		} // end if the member is still on this list

	} // end for each member list

	//
	// Update member record to take this listname off of their subscribed lists...
	//

	$member['data']['amail_aweber_subscriptions'] = $new_subscriptions;

	$db->update_user($member_id, $member);

} // end amail_aweber_deleted


function amail_aweber_removed($member_id, $member=null)
{

    /// This function will be called when member profile
    /// deleted from aMember. Your plugin should delete
    /// user profile from database (if your application allows it!),
    /// or it should just disable member access if your application
    /// if application doesn't allow profiles deletion

    global $db, $plugin_config, $config;

	//
	// The Plugin...
	//

    $this_config = $plugin_config['protect']['amail_aweber'];

    if (empty($this_config)) return;								// no need to go on if this has not got data

    if (!empty($this_config['debug'])) $db->log_error ('aMail for AWeber: amail_aweber_removed');

    if (!empty($this_config['noremove'])) return;					// not supposed to remove anyone

    if (empty($this_config['listname'])) return;					// plugin must not be fully configured yet

    $aweber_default_listname = trim($this_config['listname']);		// do no make this into an array

    //
    // The Member...
    //

    if (empty($member) && !empty($member_id)) $member = $db->get_user($member_id);

    if (empty($member)) return;										// cannot do anything on an empty member record

    if (empty($member['member_id'])) return;						// ditto

	//
	// Get an array of AWeber lists that the member has already been subscribed to...
	//

	$amail_aweber_subscriptions = trim($member['data']['amail_aweber_subscriptions']);

	if (!empty($amail_aweber_subscriptions))
	{

		$already_on  = explode(',',$amail_aweber_subscriptions);		// get the list of their subscriptions

		$already_on  = array_unique($already_on);					// remove duplicates

		//
		// Unsubscribe them from each AWeber list they are on...
		//

		foreach ($already_on as $listname)
		{

			//
			// Do the unsubscribe...
			//

		    amail_aweber_unsubscribe($listname,$member,'amember_removed');

		} // end for each list the member is subscribed to

		//
		// Now Update the Member Record with new list of subscriptions...which is now empty!
		//

		$member['data']['amail_aweber_subscriptions'] = "";				// they are not on any lists anymore

		$db->update_user($member_id, $member);

	} // end if member is subscribed on any aweber lists

} // end amail_aweber_removed

function amail_aweber_updated($member_id, $oldmember, $newmember)
{

	// NOTE TO SELF: Could put logic in here for an email or listname change too!

    global $db, $plugin_config, $config;

	//
	// The Plugin...
	//

    $this_config = $plugin_config['protect']['amail_aweber'];

    if (empty($this_config)) return;								// no need to go on if this has not got data

    if (!empty($this_config['debug'])) $db->log_error ('aMail for AWeber: amail_aweber_updated');

    if (empty($this_config['listname'])) return;					// plugin must not be fully configured yet

    $aweber_default_listname = trim($this_config['listname']);		// do no make this into an array

	$noremove = trim($this_config['noremove']);						// do not do the unsubscribes if this is set

    //
    // Get the unsubscribe value from old and new and see if it changed...
    //

    if ($oldmember['unsubscribed'] == $newmember['unsubscribed']) return;

    //
    // Ok, the unsubscribe flag has been changed so we have to do something...
    //

    $amail_aweber_subscriptions   = trim($newmember['data']['amail_aweber_subscriptions']);

    $amail_aweber_unsubscriptions = trim($newmember['data']['amail_aweber_unsubscriptions']);

	if ($newmember['unsubscribed'])
    {

    	if (empty($noremove))
    	{

	    	if (!empty($amail_aweber_subscriptions))
	    	{

				$already_on = explode(',',$amail_aweber_subscriptions);			// turn the list into an array

				$already_on = array_unique($already_on);			// remove duplicates

		    	//
		    	// Unsubscribe member from all of their active lists...
		    	//

				foreach ($already_on as $listname)
				{

					//
					// Always unsubscribe from the old member email...
					//

			    	amail_aweber_unsubscribe($listname,$oldmember,'amember_updated',false,true);

			    	if (!empty($newmember['data']['amail_aweber_unsubscriptions'])) $newmember['data']['amail_aweber_unsubscriptions'] .= ',';

				    $newmember['data']['amail_aweber_unsubscriptions'] .= $listname;

			    }

			    $newmember['data']['amail_aweber_subscriptions']   = "";		// there should be none left now

			} // end if there is anything to subscribe them to

		} // end if we are supposed to actually unsubscribe anyone

    } else { // need to re-subscribe them

    	if (!empty($amail_aweber_unsubscriptions))
    	{

			$already_off = explode(',',$amail_aweber_unsubscriptions);			// turn the list into an array

			$already_off = array_unique($already_off);				// remove duplicates

	    	//
	    	// Subscribe member back to all of their unsubscribed lists...
	    	//

			foreach ($already_off as $listname)
			{

				//
				// Always subscribe them to the new member email...
				//

				amail_aweber_subscribe($listname,$newmember);

		    	if (!empty($newmember['data']['amail_aweber_subscriptions'])) $newmember['data']['amail_aweber_subscriptions'] .= ',';

			    $newmember['data']['amail_aweber_subscriptions'] .= $listname;

		    }

		    $newmember['data']['amail_aweber_unsubscriptions'] = "";			// there should be none left now

		} // end if there are any subscriptions to be re-subscribed

    } // end if subscribe or unsubscribe

	$db->update_user($member_id, $newmember);

} // end amail_aweber_updated

function amail_aweber_daily()
{

    global $db, $plugin_config, $config;

    $nocc = true;													// do not cc admin on these

	//
	// The Plugin...
	//

    $this_config = $plugin_config['protect']['amail_aweber'];

    if (empty($this_config)) return;								// no need to go on if this has not got data

    $debug = $this_config['debug'];

    if ($debug) $db->log_error ('aMail for AWeber: Cron: Daily: Begin');

    if (empty($this_config['listname']))
    {

	    if ($debug) $db->log_error ('aMail for AWeber: Cron: Daily: End: ERROR: listname is not set!');

	} else if (empty($this_config['docron'])) {

	    if ($debug) $db->log_error ('aMail for AWeber: Cron: Daily: End: ERROR: docron flag is not set!');

	} else if (!empty($this_config['noremove'])) {

	    if ($debug) $db->log_error ('aMail for AWeber: Cron: Daily: End: ERROR: noremove flag is set!');

	} else if (!empty($this_config['donotsend'])) {

	    if ($debug) $db->log_error ('aMail for AWeber: Cron: Daily: End: ERROR: donotsend flag is set!');

	} else {

		//
		// Look for expired members that need to be unsubscribed...
		//

		$members = $db->get_users_list();

		if (!empty($members) && is_array($members))
		{

			$total_members = count($members);

			$members_done  = 0;

			foreach ($members as $member)
			{

				$members_done++;

				$member_changed = false;

				//
				// Get what their lists should be...
				//

				$mylists           = amail_aweber_get_eligible_lists($member);

				$mysubscriptions   = trim($member['data']['amail_aweber_subscriptions']);

				$already_on        = array();

				if (!empty($mysubscriptions))
				{

					$already_on  = explode(',',$mysubscriptions);		// get the list of their subscriptions

					$already_on  = array_unique($already_on);			// remove duplicates

				}

				$myunsubscriptions = trim($member['data']['amail_aweber_unsubscriptions']);

				$already_off       = array();

				if (!empty($myunsubscriptions))
				{

					$already_off = explode(',',$myunsubscriptions);	// get the list of their unsubscriptions

					$already_off = array_unique($already_off);			// remove duplicates

				}

				/*
				// The Member:
				// -----------
				//
				// $member['status']            : 0 = Pending
				//                                1 = Active
				//                                2 = Expired
				//
				// $member['unsubscribed']      : 0 = You may send them emails
				//                                1 = You may NOT send them emails
				//
				// $member['data']['is_active'] : 0 = inactive - no products are active
				//                                1 = active   - at least one product is active
				//
				// $member['data']['status']    : array of product_ids where 0 = inactive, 1 = active  (NO PENDING!)
				//
				*/

				if ($member['status'] != 1)
				{

					//
					// Pending or Expired Member...remove from everything...
					//

					if (!empty($already_on) && is_array($already_on))
					{

						foreach ($already_on as $listname)
						{

							amail_aweber_unsubscribe($listname,$member,'amember_cron',$nocc);

							$already_off[] = $listname;

						} // end for each list already on

						$already_on = array();						// make sure it is empty

						$member_changed = true;

					} // end if lists they are on that they need to be off

					if (!empty($mylists) && is_array($mylists))
					{

						foreach ($mylists as $listname=>$status)
						{

							if (!in_array($listname,$already_off))
							{

								amail_aweber_unsubscribe($listname,$member,'amember_cron',$nocc);

								$already_off[] = $listname;

								if (in_array($listname,$already_on)) $unset($already_on[$listname]);

								$member_changed = true;

							} // end if need to unsubscribe

						} // end foreach of my lists

					} // end if lists on my lists

				} else {

					//
					// Active on at least one thing...but inactive on any others?
					//

					if (!empty($mylists) && is_array($mylists))
					{

						foreach ($mylists as $listname=>$status)
						{

							if ($status != 1)
							{

								$is_still_active = amail_aweber_still_active($listname, $member);

								if (!$is_still_active && !in_array($listname,$already_off))
								{

									amail_aweber_unsubscribe($listname,$member,'amember_cron',$nocc);

									$already_off[] = $listname;

									// remove it from the array of lists this member is on

									if (in_array($listname,$already_on)) unset($already_on[$listname]);

									$member_changed = true;

								} // not already unsubscribed

							} // end if inactive on this list

						} // end foreach of my lists

					} // end if my lists

				} // end if active or not

				//
				// Update Member Data
				//

				if ($member_changed)
				{

					$member['data']['amail_aweber_subscriptions']   = '';

					if (!empty($already_on) && is_array($already_on))
					{

						$already_on = array_unique($already_on);

						foreach ($already_on as $listname)
						{

							if (!empty($member['data']['amail_aweber_subscriptions'])) $member['data']['amail_aweber_subscriptions'] .= ', ';

							$member['data']['amail_aweber_subscriptions'] .= $listname;

						} // end foreach already on

					} // end if already on

					$member['data']['amail_aweber_unsubscriptions'] = '';

					if (!empty($already_off) && is_array($already_off))
					{

						$already_off = array_unique($already_off);

						foreach ($already_off as $listname)
						{

							if (!empty($member['data']['amail_aweber_unsubscriptions'])) $member['data']['amail_aweber_unsubscriptions'] .= ', ';

							$member['data']['amail_aweber_unsubscriptions'] .= $listname;

						} // end foreach already on

					} // end if already off

					$db->update_user($member['email'],$member);

				} // end if member changed

			} // end foreach members

		} // end if members found

    } // end if going to process today

    if ($debug) $db->log_error ('aMail for AWeber: Cron: Daily: End');

} // end amail_aweber_daily

function amail_aweber_rebuild(&$somemembers)
{

    /// some actions when admin click aMember CP -> Rebuild Db
    /// it should compare all records in your third-party
    /// database with aMember supplied-campaign ($members)
    /// Or you may just skip this hook

    global $db, $plugin_config, $config;

    $db->log_error('aMail for AWeber: Rebuild Begin');

    $nocc = true;													// do not cc admin on these

	/*
	// The Member:
	// -----------
	//
	// $member['status']            : 0 = Pending
	//                                1 = Active
	//                                2 = Expired
	//
	// $member['unsubscribed']      : 0 = You may send them emails
	//                                1 = You may NOT send them emails
	//
	// $member['data']['is_active'] : 0 = inactive - no products are active
	//                                1 = active   - at least one product is active
	//
	// $member['data']['status']    : array of product_ids where 0 = inactive, 1 = active  (NO PENDING!)
	//
	*/

	//
	// The Plugin...
	//

    $this_config = $plugin_config['protect']['amail_aweber'];

    if (empty($this_config)) return;								// no need to go on if this has not got data

    $debug = $this_config['debug'];

    if ($debug) $db->log_error ('aMail for AWeber: amail_aweber_rebuild: Begin');

    if (empty($this_config['rebuilddb']))
    {

	    if ($debug) $db->log_error ('aMail for AWeber: amail_aweber_rebuild: End: Rebuild DB flag is not set!');

    	return;					// does not want to participate

    }

    if (empty($this_config['listname']))
    {

	    if ($debug) $db->log_error ('aMail for AWeber: amail_aweber_rebuild: End: Primary listname is not set!');

    	return;					// plugin must not be fully configured yet

    }

    $aweber_default_listname = trim($this_config['listname']);		// do not make this into an array

    $noremove   = trim($this_config['noremove']);					// are we supposed to remove anyone or not?

	//
	// Send Re-subscribe for every member who is not unsubscribed...send REMOVE for every member who is unsubscribed...
	//

	// $members = $db->get_users_list();

	if (!empty($somemembers) && is_array($somemembers))
	{

		$total_members = count($somemembers);

		//
		// Start processing members and showing where we are on the rebuild page...
		//

		if ($total_members > 0) echo "<br />&nbsp;<br />aMail for AWeber: Rebuilding ".$total_members." members...<br />\n";

		$counter = 0;

		foreach ($somemembers as $key=>$value)
		{

			$member = amail_aweber_get_user_by_username($key);

			//
			// Counter logic...
			//

			$remainder = $counter % 100;

			if ($remainder == 0 && $counter > 0) echo $counter."<br />";

			echo '.';

			$counter++;

			//
			// Initialize variables for this member
			//

			$already_on        = array();

			$already_off       = array();

			$mylists           = array();

			$mysubscriptions   = array();

			$myunsubscriptions = array();

			//
			// Get what their lists should be...
			//

			$mylists = amail_aweber_get_eligible_lists($member);

			if ($debug) $db->log_error('aMail for AWeber: Rebuild: Member '.$member['email'].': mylists = '.print_r($mylists,1));

			if (!empty($mylists) && is_array($mylists))
			{

				foreach ($mylists as $listname=>$status)
				{

					if ($status)
					{

						$mysubscriptions[] = $listname;				// should match already_on

					} else {

						$myunsubscriptions[] = $listname;				// should match already_off

					} // end if

				} // end foreach

			} // end if

			$mysubscriptions   = array_unique($mysubscriptions);

			if ($debug) $db->log_error('aMail for AWeber: Rebuild: Member '.$member['email'].': mysubscriptions = '.print_r($mysubscriptions,1));

			$myunsubscriptions = array_unique($myunsubscriptions);

			if ($debug) $db->log_error('aMail for AWeber: Rebuild: Member '.$member['email'].': myunsubscriptions = '.print_r($myunsubscriptions,1));

			//
			// Get the subscriptions this plugin thinks they are on now...
			//

			$amail_aweber_subscriptions = trim($member['data']['amail_aweber_subscriptions']);

			if (!empty($amail_aweber_subscriptions))
			{

				$already_on  = explode(',',$amail_aweber_subscriptions);		// get the list of their subscriptions

				$already_on  = array_unique($already_on);			// remove duplicates

			}

			if ($debug) $db->log_error('aMail for AWeber: Rebuild: Member '.$member['email'].': already_on = '.print_r($already_on,1));

			$not_on  = array_diff($mysubscriptions,$already_on);		// find what is missing

			if ($debug) $db->log_error('aMail for AWeber: Rebuild: Member '.$member['email'].' not_on = '.print_r($not_on,1));

			$not_off = array_diff($already_on,$mysubscriptions);		// find what should not be there

			if ($debug) $db->log_error('aMail for AWeber: Rebuild: Member '.$member['email'].' not_off = '.print_r($not_off,1));

			//
			// Get the unsubscriptions this plugin thinks they should have...
			//

			$amail_aweber_unsubscriptions = trim($member['data']['amail_aweber_unsubscriptions']);

			if (!empty($amail_aweber_unsubscriptions))
			{

				$already_off  = explode(',',$amail_aweber_unsubscriptions);		// get the list of their subscriptions

				$already_off  = array_unique($already_off);			// remove duplicates

			}

			if ($debug) $db->log_error('aMail for AWeber: Rebuild: Member '.$member['email'].': already_off = '.print_r($already_off,1));

			//
			// Now unsubscribe and resubscribe them based on their updated lists which now match their product subscriptions...
			//

			if (!$member['unsubscribed'])
			{

				//
				// Add them to the lists they are missing...
				//

				if (!empty($not_on) && is_array($not_on))
				{

					//
					// Subscribe them to each AWeber list they are not already on that they should be...
					//

					foreach ($not_on as $listname)
					{

						if ($listname != 'nolist')
						{

							amail_aweber_subscribe($listname,$member,'','',$nocc);

						} // end if ignore nolist

					} // end for each list the member is not already on

				} // end if lists to be added to

				//
				// Remove them from the lists they should not still be on...
				//

				if (empty($noremove))
				{

					if (!empty($not_off) && is_array($not_off))
					{

						foreach ($not_off as $listname)
						{

							if ($listname != 'nolist')
							{

						    	amail_aweber_unsubscribe($listname,$member,'amember_rebuild',$nocc);

						    } // end if ignore listname

						} // end for each list the member should not really be on

					} // end if

				} // end if we are doing any removes

			} else {

				//
				// Only unsubscribe members if we are supposed to...
				//

				if (empty($noremove))
				{

					//
					// Unsubscribe them from any-and-all AWeber lists that have shown up anywhere so far...
					//

					$get_off[] = $aweber_default_listname;			// make sure they are taken off of master list

					//
					// Now merge the rest of the arrays...
					//

					if (!empty($already_on)         && is_array($already_on)       ) $get_off = array_merge($get_off, $already_on);

					if (!empty($already_off)        && is_array($already_off)      ) $get_off = array_merge($get_off, $already_off);

					if (!empty($mysubscriptions)    && is_array($mysubscriptions)  ) $get_off = array_merge($get_off, $mysubscriptions);

					if (!empty($myunsubscriptions)  && is_array($myunsubscriptions)) $get_off = array_merge($get_off, $myunsubscriptions);

					$get_off = array_unique($get_off);				// remove duplicates

					if ($debug) $db->log_error('aMail for AWeber: Rebuild: Member '.$member['email'].': get_off = '.print_r($get_off,1));

					if (!empty($get_off) && is_array($get_off))
					{

						foreach ($get_off as $listname)
						{

							if ($listname != 'nolist')
							{

						    	amail_aweber_unsubscribe($listname,$member,'amember_rebuild',$nocc);

						    } // end if ignore nolist

						} // end for each list the member is subscribed to

					} // end if anything to take them off of

				} // end if we are supposed to unsubscribe members

			} // end if the member is not unsubscribed or is

			//
			// Rebuild the member record based on their eligible lists...
			//

			$member['data']['amail_aweber_subscriptions'] = "";

			if (!empty($mysubscriptions) && is_array($mysubscriptions))
			{

				foreach($mysubscriptions as $listname)
				{

					if ($listname != 'nolist')
					{

						if (!empty($member['data']['amail_aweber_subscriptions'])) $member['data']['amail_aweber_subscriptions'] .= ',';

						$member['data']['amail_aweber_subscriptions'] .= $listname;

					} // end if ignore nolist

				} // end foreach subscriptions

			} // subscriptions

			$member['data']['amail_aweber_unsubscriptions'] = "";

			if (!empty($myunsubscriptions) && is_array($myunsubscriptions))
			{

				foreach($myunsubscriptions as $listname)
				{

					if ($listname != 'nolist')
					{

						if (!empty($member['data']['amail_aweber_unsubscriptions'])) $member['data']['amail_aweber_unsubscriptions'] .= ',';

						$member['data']['amail_aweber_unsubscriptions'] .= $listname;

					} // end if ignore nolist

				} // end foreach subscriptions

			} // unsubscriptions

			$db->update_user($member['member_id'], $member);

		} // end foreach member

		if ($total_members > 0) echo $counter."<br />&nbsp;<br />\n";

	} else {

		if ($debug) $db->log_error('aMail for AWeber: Rebuild: Done: No members!');

		return;

	} // end if any members found

	$db->log_error('aMail for AWeber: Rebuild: End');

} // end amail_aweber_rebuild


function amail_aweber_subscribe($listname,
                                $member,
                                $product_title = 'Email Subscription',
                                $product_price = '0.00',
                                $nocc = false
                               )
{

    global $db, $plugin_config, $config;

	//
	// The Plugin...
	//

    $this_config = $plugin_config['protect']['amail_aweber'];

    if (empty($this_config)) return;								// no need to go on if this has not got data

    if (!empty($this_config['debug'])) $db->log_error ('aMail for AWeber: amail_aweber_subscribe:'.$listname.':'.$member['email']);

    if (empty($this_config['listname'])) return;					// plugin must not be fully configured yet

    if (empty($listname)   ) return;								// cannot subscribe to an empty list

    if ($listname == 'nolist') return;								// not supposed to subscribe to 'nolist'

    if (empty($member)     ) return;                                // cannot subscribe a non-existant member

    if ($member['unsubscribed']) return;							// this member does not want to be subscribed

	//
	// Good to go...start setting up the subscription email...
	//

    $ccadmin     = trim($this_config['ccadmin']);				// send a copy of the email to the admin

    $ccguest     = trim($this_config['ccguest']);				// send a copy of the email to a guest

    $sendsignup  = trim($this_config['sendsignup']);			// send signup information to AWeber

    $sendaddress = trim($this_config['sendaddress']);			// send address information to AWeber

    $sendcustom  = trim($this_config['sendcustom']);			// send custom information to AWeber

    $debug    = trim($this_config['debug']);				// print debug statements

    $donotsend   = trim($this_config['donotsend']);				// do we want to prevent emails going to AWeber?

	//
	// Start setting up the email message that will be sent to AWeber...
	//

	$message     = "New subscription completed:\n\n";
	$message    .= "   Product:    ".$product_title."\n";
	$message    .= "   Amount:     $".$product_price."\n\n";
	$message    .= "User details:\n\n";

	//
	// Required fields that are always sent...
	//

    $email       = strtolower(trim($member['email']));
    $name        = strtolower(trim($member['name_f'])).' '.strtolower(trim($member['name_l']));
    $name        = ucwords($name);
    $remote_addr = $member['remote_addr'];

	$message    .= "   Email: ".strtolower($email)."\n";
	$message    .= "   Name: ".$name."\n";
	$message    .= "   Subscribe IP: ".$remote_addr."\n";

	//
	// Signup Information
	//

    if (!empty($sendsignup))
    {

    	$memberid    = $member['member_id'];
    	$username    = $member['login'];
    	$password    = $member['pass'];

		if (!empty($memberid)) $message .= "   MemberID: ".$memberid."\n";
		if (!empty($username)) $message .= "   Username: ".$username."\n";
		if (!empty($password)) $message .= "   Password: ".$password."\n";

    }

	//
	// Address Information
	//

    if (!empty($sendaddress))
    {

    	$street      = (!empty($member['street'])       ) ? trim($member['street'])        : trim($member['data']['cc_street']);
    	$city        = (!empty($member['city'])         ) ? trim($member['city'])          : trim($member['data']['cc_city']);
    	$state       = (!empty($member['state'])        ) ? trim($member['state'])         : trim($member['data']['cc_state']);
    	$zip         = (!empty($member['zip'])          ) ? trim($member['zip'])           : trim($member['data']['cc_zip']);
    	$country     = (!empty($member['country'])      ) ? trim($member['country'])       : trim($member['data']['cc_country']);

		if (!empty($street) ) $message .= "   Street: ".$street."\n";
		if (!empty($city)   ) $message .= "   City: ".$city."\n";
		if (!empty($state)  ) $message .= "   State: ".$state."\n";
		if (!empty($zip)    ) $message .= "   Zip: ".$zip."\n";
		if (!empty($country)) $message .= "   Country: ".$country."\n";

    }

    //
    // Got an ad tracking code cookie?
    //

	if (!empty($_COOKIE['adtracking']))
	{

		$message .= "   adtracking: ".$_COOKIE['adtracking']."\n";

		if (!empty($this_config['debug'])) $db->log_error('aMail for AWeber: amail_aweber_subscribe: Found Ad Tracking Cookie: '.$_COOKIE['adtracking']);

	}

    //
    // Additional Fields...
    //

    if (!empty($sendcustom))
    {

    	$addfields = explode(',',$sendcustom);

		$addfields = array_unique($addfields);						// remove duplicates

    	if (!empty($this_config['debug'])) $db->log_error ('aMail for AWeber: amail_aweber_subscribe: Sending Additional Fields: '.print_r($addfields,1).': Member: '.print_r($member,1));

    	foreach ($addfields as $afield)
    	{

    		$afield = trim($afield);							// make sure there is no white space

	    	if (!empty($this_config['debug'])) $db->log_error ('aMail for AWeber: amail_aweber_subscribe: Testing Additional Field: '.$afield.'...');

    		if (!empty($member['data'][$afield]))
    		{

    			$message .= "   ".$afield.": ".$member['data'][$afield]."\n";

		    	if (!empty($this_config['debug'])) $db->log_error ('aMail for AWeber: amail_aweber_subscribe: Adding Additional Field: '.$afield.': '.$member['data']['afield']);

		    } else if (!empty($member[$afield])) {

    			$message .= "   ".$afield.": ".$member[$afield]."\n";

		    	if (!empty($this_config['debug'])) $db->log_error ('aMail for AWeber: amail_aweber_subscribe: Adding Additional Field: '.$afield.': '.$member['afield']);

		    }

    	} // end foreach additional field

    }

	//
	// Mail to AWeber...
	//

	$email   = $listname.'@aweber.com';

	$subject = "aMail for AWeber Subscribe Parser";

	$bcc     = array();

	if (empty($nocc) && (!empty($ccadmin) || !empty($donotsend)))
	{

		// mail_customer($config['admin_email'], $message, $subject);

		$bcc[] = $config['admin_email'];

	}

	if (!empty($ccguest))
	{

		// mail_customer($ccguest, $message, $subject);

		$bcc[] = $ccguest;


	}

	if (empty($donotsend))
	{

	    $db->log_error ('aMail for AWeber: Subscribing: '.$listname.': '.$member['email']);

		mail_customer($email, $message, $subject, 0, '', 0, "", '0', '0', $bcc);

	} else {

	    $db->log_error ('aMail for AWeber: NOT Subscribing: '.$listname.': '.$member['email'].' because the plugin says not to!');

	}

} // end amail_aweber_subscribe


function amail_aweber_unsubscribe($listname, $member, $whyunsubscribe, $nocc=false, $force=false)
{

    global $db, $plugin_config, $config;

    //
    // The Input
    //

    if (empty($listname)) return;									// cannot unsubscribe from an empty list

    if (empty($member)) return;                               		// cannot subscribe a non-existant member

    if (empty($member['member_id'])) return;						// ditto

    if (empty($member['email'])) return;							// cannot unsubscribe nobody

    $isactive = amail_aweber_still_active($listname,$member);		// still supposed to be active on this list?

    if ($isactive && !$force)
    {

    	$db->log_error('aMail for AWeber: Cannot unsubscribe because member should still be active on '.$listname);

    	return;
    }

    $memberemail = trim(strtolower($member['email']));				// make sure it is lowercase

	//
	// The Plugin...
	//

    $this_config = $plugin_config['protect']['amail_aweber'];

    if (empty($this_config)) return;								// no need to go on if this has not got data

    if (!empty($this_config['debug'])) $db->log_error ('aMail for AWeber: amail_aweber_unsubscribe:'.$listname.':'.$memberemail.':'.$whyunsubscribe);

    if (empty($this_config['listname'])) return;					// plugin must not be fully configured yet

    if (!empty($this_config['noremove'])) return;					// if we are not removing subscribers then we are done

    $ccadmin     = trim($this_config['ccadmin']);		            // send a copy of the email to the admin?

    $ccguest     = trim($this_config['ccguest']);					// send a copy of the email to a guest

    $debug       = trim($this_config['debug']);						// print debug statements

    $donotsend   = trim($this_config['donotsend']);					// do we want to prevent emails going to AWeber?

    $aweber_default_listname = trim($this_config['listname']);		// do no make this into an array

	//
	// Mail to AWeber...
	//

	$email   = $listname.'@aweber.com';

	$message = "amember unsubscribe request";

	$subject = "REMOVE#".$memberemail."#".$whyunsubscribe."#".$listname;

	$bcc     = array();

	if (empty($nocc) && (!empty($ccadmin) || !empty($donotsend)))
	{

		mail_customer($config['admin_email'], $message, $subject);

		$bcc[] = $config['admin_email'];

	}

	if (!empty($ccguest))
	{

		// mail_customer($ccguest, $message, $subject);

		$bcc[] = $ccguest;


	}

	if (empty($donosend))
	{

	    $db->log_error ('aMail for AWeber: Unsubscribing:'.$listname.': '.$memberemail.': '.$whyunsubscribe);

		// mail_customer($email, $message, $subject, 0, '', 0, "", '0', '0', $bcc);

	} else {

	    $db->log_error ('aMail for AWeber: NOT Unsubscribing:'.$listname.': '.$memberemail.': '.$whyunsubscribe.' because the plugin says not to!');

	}

} // end amail_aweber_unsubscribe


function amail_aweber_get_eligible_lists($member)
{

    global $db, $plugin_config, $config;

    $mylists = null;

	//
	// The Plugin...
	//

    $this_config = $plugin_config['protect']['amail_aweber'];

    if (empty($this_config)) return;								// no need to go on if this has not got data

    $debug = $this_config['debug'];

    if ($debug) $db->log_error ('aMail for AWeber: get_eligible_lists: Begin');

    if (empty($this_config['listname'])) return;					// plugin must not be fully configured yet

    $aweber_default_listname = trim($this_config['listname']);		// do no make this into an array

    //
    // The Member...
    //

    if (empty($member)) return;										// cannot check a non-existant member

    //
    // Get the list of products this member has on their record...
    //

    $product_ids = $member['data']['status'];						// the product status for this member

    //
    // Go through the products and get their listnames
    //

    if (!empty($product_ids))
    {

		foreach ($product_ids as $product_id=>$status)
	    {

	    	//
	    	// Get the data for this product...
	    	//

			$product = (empty($_SESSION['amproducts'][$product_id])) ? $db->get_product($product_id) : $_SESSION['amproducts'][$product_id];

			if (!empty($product) && is_array($product)) $_SESSION['amproducts'][$product_id] = $product;

	    	//
	    	// Get the listnames for this product, but if it is empty then use the default plugin listname
	    	//

	    	$listnames = (!empty($product['amail_aweber_listnames'])) ? trim($product['amail_aweber_listnames']) : $aweber_default_listname;

			if ($debug) $db->log_error('aMail for AWeber: get_eligible_lists: email = '.$member['email'].', product = '.$product_id.', status = '.$status.', listnames = '.$listnames);

	    	//
	    	// If this product has lists, process them...
	    	//

			if (!empty($listnames))
			{

				$lists     = explode(',',$listnames);

				$lists     = array_unique($lists);

				foreach ($lists as $listname)
				{

					if ($listname != 'nolist')
					{

						//
						// Do NOT overwrite active with inactive...
						//

						if (empty($mylists[$listname])) $mylists[$listname] = $status;

						//
						// DO overwrite with inactive status if member unsubscribe flag set...
						//

						if ($member['unsubscribed'] || $member['status'] != 1) $mylists[$listname] = 0;

					} // end if ignore nolist

		    	} // end foreach list on this product

		    } // end if lists exist for this product

		} // end foreach product

	} // end if this user has any status

	//
	// Save off the corrected and current member data...
	//

	// $member['data']['amail_aweber_subscriptions']   = "";

	// $member['data']['amail_aweber_unsubscriptions'] = "";

	if (!empty($mylists) && is_array($mylists))
	{
/*
		foreach($mylists as $listname=>$status)
		{

			if ($status)
			{

				// active on this list

				if (!empty($member['data']['amail_aweber_subscriptions'])) $member['data']['amail_aweber_subscriptions'] .= ',';

				$member['data']['amail_aweber_subscriptions'] = $listname;

			} else {

				// inactive on this list

				if (!empty($member['data']['amail_aweber_unsubscriptions'])) $member['data']['amail_aweber_unsubscriptions'] .= ',';

				$member['data']['amail_aweber_unsubscriptions'] = $listname;

			}

		} // end foreach list
*/
	} else {

		//
		// Put the master list in as a default if no product lists were found...
		//

		if ($member['unsubscribed'] || $member['status'] != 1)
		{

			// unsubscribed or not active

			// $member['data']['amail_aweber_subscriptions']   = "";

			// $member['data']['amail_aweber_unsubscriptions'] = $aweber_default_listname;

			$mylists[$aweber_default_listname] = 0;

		} else {

			// subscribed and active

			// $member['data']['amail_aweber_subscriptions']   = $aweber_default_listname;

			// $member['data']['amail_aweber_unsubscriptions'] = "";

			$mylists[$aweber_default_listname] = 1;

		} // end if member allows subscriptions

	} // end if no product subscriptions

	// $db->update_user($member['member_id'], $member);

	return $mylists;

} // end amail_aweber_get_eligible_lists

function amail_aweber_still_active($listname, $member)
{

    global $db, $plugin_config, $config;

	$isactive = false;

	$listname = strtolower(trim($listname));

	//
	// If their unsubscribed flag is set then they cannot be active on anything...
	//

	if (!empty($member['unsubscribed'])) return $isactive;

    //
    // Inputs
    //

    if (empty($listname)           ) return;

    if (empty($member)             ) return;

    if (empty($member['member_id'])) return;

    //
    // The Plugin
    //

    $this_config         = $plugin_config['protect']['amail_aweber'];

	$debug 	         = trim($this_config['debug']);

	$active_master       = strtolower(trim($this_config['listname']));

    //
    // Is Member Active on Master Active Campaign and Is It This Campaign
    //

	if ($listname == $active_master)
		if ($member['status'] == 1)
    		if ($member['data']['is_active'] == 1)
   				$isactive = true;

    //
    // Member Products
    //

	$product_ids = array();

	if (!empty($member['data']['status']))
		if (is_array($member['data']['status']))
			foreach ($member['data']['status'] as $pid=>$status)
				if ($status == 1)
					$product_ids[] = $pid;

	//
	// Check Active Products For Matching List
	//

	if (!empty($product_ids) && is_array($product_ids))
	{

		foreach ($product_ids as $pid)
		{

			$product = (empty($_SESSION['amproducts'][$pid])) ? $db->get_product($pid) : $_SESSION['amproducts'][$pid];

			if (!empty($product) && is_array($product)) $_SESSION['amproducts'][$pid] = $product;

		   	$active_listnames  = strtolower(trim($product['amail_aweber_listnames']));

		   	$active_lists      = explode(",",$active_listnames);

		   	if (!empty($active_lists))
		   		if (is_array($active_lists))
		   			foreach($active_lists as $active_list)
		   				if ($listname == trim(strtolower($active_list)))
		   					$isactive = true;

		} // end foreach active listname

	} // if any products are active

	return $isactive;

} // end amail_aweber_still_active

function amail_aweber_get_user_by_username($login)
{

    global $db, $plugin_config, $config;

	$query = "SELECT * FROM {$db->config['prefix']}members WHERE login='".$login."'";

	$q = $db->query($s = $query);

	if (!$r = mysql_fetch_assoc($q))
	{

		$db->log_error("User not found: #$login");

		$r = array();

	}

	if ($r['data'])
		$r['data'] = $db->decode_data($r['data']);

	return $r;

} // end get_user_by_email


?>