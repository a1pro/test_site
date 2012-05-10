
            MICROPAYMENT INSTALLATION INSTRUCTIONS
1. Enable Micropayment payment system at aMember CP -> Setup/Configuration -> Plugins.

2. Configure it at aMember CP -> Setup/Configuration -> AllPay

3. Configure aMember CP -> Manage Products -> Edit -> Micropayment Project Name

4. Setup API URL in Micropayment Account to
   {$config.root_url}/plugins/payment/micropayment/ipn.php
   Go to --> My Configuration --> Projects
   Choose Actions for the product you want to configure
   Click on Configure Payment Methods
   For module CreditCard and service Event choose Actions
   Click on Configure
   Check Activate payment for product and fill in your API-URL
   {$config.root_url}/plugins/payment/micropayment/ipn.php
   Do the same steps for module debit (Lastschrift) and service Event
   
5. In your Micropayment Control Center go to My Configuration --> Payment Methods
   Choose your product and click on Actions for the respective payment method
   Click on Configure and go to Add GET-Parameters
   Add freepaymentid on left side and __$freepaymentid__ on right side
   Click Save settings