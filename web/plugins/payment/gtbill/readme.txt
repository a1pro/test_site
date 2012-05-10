
***** IMPORTANT : THIS PLUGIN HAS NOT BEEN TESTED YET ****

1. Enable plugin in aMember CP -> Setup/Configuration -> Plugins

2. Configure it at aMember CP -> Setup/Configuration -> GTBill

3. Configure GTBill Price ID and GTBill Currency
   at aMember CP -> Manage Products -> Edit

4. Login at: https://merchant.381808.com/index.aspx
   Click on Merch Setup' -> Site Management -> Configure Site
   Under the 'Configure Site' section specify the URL of the member management
   script: {$config.root_url}/plugins/payment/gtbill/ipn.php

5. Run test transaction to test the integration
