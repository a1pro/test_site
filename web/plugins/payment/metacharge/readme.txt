            METACHARGE INSTALLATION INSTRUCTIONS
1. Enable and configure Metacharge Plugin in aMember control panel.

2. PRN is enabled and configured on a per-installation basis via the Merchant Extranet. Click on Account Management and
then Installations, then select the relevant installation from the pop-up menu.
Please complete the following fields to enable PRNs:
- Response URL: The URL where you want the PRN to be sent: {$config.root_url}/plugins/payment/metacharge/ipn.php
- Scheduled Payment Response URL: Configured as above if subscriptions have been enabled on your installation.

3. Go to Merchant Extranet. Click Account Management then Installations and select the installation you wish to configure from the pop-up menu.
Please complete the following fields to redirect customer to your website after completed transaction.
- return URL: {$config.root_surl}/plugins/payment/metacharge/thanks.php

4. We recommend that you perform HTTP Basic Authorisation on your server to ensure that the response is coming from a
trusted source. If you have enabled HTTP Basic Authorisation on your server, you will need to specify:
- Response HTTP Auth Username
- Response HTTP Auth Password