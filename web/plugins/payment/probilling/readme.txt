PROBILLING PLUGIN INSTALLATION

1. To use this plugin enable and configure it in aMember control panel.

2. Login into your Probilling.com account

3. Set Notify URL:
 - open "Account Settings" -> "Website Information"
 - click on website name where aMember Pro installed
 - click on link:
   "click here to tell us how you would like to be notified when 
   transactions are processed for this website."
 - Set:
    "Would you like proBilling to collect (or generate) a username and 
    password for each transaction we process for your site?"
        NO
    "Would you like to use proBilling's automated password management 
    system to update a password file on your server?"
        NO
    Email & email confirmations
        AS YOU LIKE
    "If you would like to receive a form post each time a transaction is 
    processed please enter the URL of the location where you would like 
    the form to be posted."
        {$config.root_url}/plugins/payment/probilling/ipn.php
 - Click Save

4. Go to "Home", in left-bottom you will see options to create
   new subscription types. Create everything you want, then
   in Subscription Types list you will see link named "HTML Link"
   near each subscription type.
   Open this link and copy PON (payment option number).
   Remeber PON for every subscription type you created.
   Then go to aMember Pro control panel and create corresponding
   subscription types (products). Enter PON into product settings.
   Please make sure that Period/Recurring settings is the same 
   in aMember and Probilling.

5. You can switch any Probilling subscription type into testing
   mode and test your installation. No changes in aMember required.
   
   Enjoy!
