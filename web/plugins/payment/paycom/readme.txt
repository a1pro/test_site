PayCom payment plugin installation
----------------------------------------------------------------------
Please note, this plugin doesn't support recurring payments.

1. Enable plugin at aMember CP -> Setup -> Plugins

2. Configure plugin at aMember Cp -> Setup -> PayCom

 - Create a "shared password."  This shared password will only be known to Frontkick
   and to paycom.  It will allow Frontkick to reject any requests that don't arrive
   from paycom.

3. Contact PayCom support and request them to:

 - Set up products with the same settings as you have defined in 
   aMember and have them give you the product's corresponding PayCom Product IDs. 
   Then enter PayCom Product IDs into corresponding field in aMember 
   Product settings (aMember Cp -> Manage Products)
   
 - Set up the data postback URL to 
   {$config.root_url}/plugins/payment/paycom/ipn.php?cred=XXXXX,
   where XXXX is the shared password you created in step 2.

4. Run test payments

