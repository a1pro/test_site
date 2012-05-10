                      ClickBank plugin installation

 1. Enable plugin: go to aMember CP -> Setup/Configuration -> Plugins and enable
	"ClickBank" payment plugin.
 2. Configure plugin: go to aMember CP -> Setup/Configuration -> ClickBank
	and configure it.
 3. Configure ClickBank Product ID at aMember CP -> Manage Products -> Edit
 4. Configure ThankYou Page URL in your ClickBank account (for each Product) to this URL:
    {$config.root_url}/plugins/payment/clickbank/thanks.php
 5. Configure Instant Notification URL in your ClickBank account
    ( Account Settings -> My Site -> Advanced Tools -> Edit )
    to this URL: {$config.root_surl}/plugins/payment/clickbank/ipn.php
 6. Run a test transaction to ensure everything is working correctly.

------------------------------------------------------------------------------
CLICKBANK ACCOUNT SETUP

Now that you have been granted access to the feature and have conducted a successful test,
its time to complete the account setup of the Instant Notification service.
Setting up the service is straightforward and involves the following steps.

   1. Log into your account
   2. Click the "Account Settings" tab
   3. Click "My Site" in the sub nav
   4. Enter a Secret Key on your "My Site" page
   5. Enter an Instant Notification URL (SSL Recommended)
   6. Click "Test" to the right of the URL before you save the changes
   7. Review the response in the popup window to identify if the test was a success
   8. Click "Save Changes"

Once the setup is complete, the Instant Notification transmissions will begin immediately.

------------------------------------------------------------------------------

