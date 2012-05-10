
              1ShoppingCart payment plugin configuration
        
1. Enable "1shoppingcart" payment plugin at aMember CP->Setup->Plugins

2. Configure "1shoppingcart" payment plugin at aMember CP -> Setup/Configuration -> 1ShoppingCart
   Make sure you set the same postback password in aMember CP and 1ShoppingCart
   Merchants CP  -> Setup -> Third-Party Integrations: aMember Password
   
3. Create equivalents for all aMember products in 1ShoppingCart Merchants CP.
   Make sure it has the same subscription terms (period, price) as aMember
   Products. Set "Thanks URL" for all 1ShoppingCart products to 
    {$config.root_url}/thanks.php
   Write down product# of all 1ShoppingCart products. 
   
4. Visit aMember CP -> Manage Products, click "Edit" on each product
   and enter "1ShoppingCart Product#" for each product, then click "Save".
   
5. Try your integration - go to aMember signup page, and try to make new signup.
   {$config.root_url}/signup.php

6. In order to use "Allow create new accounts" you must specify API Key in plugin configuration 
   Key can be found in your 1SC account -> Home -> My Account -> API Settings 
   Notification URL for API setting should be set to {$config.root_surl}/plugins/payment/1shoppingcart/api.php
   

----------------
   
   In case of any issues with IPN Notifications (if members is not activated in aMember automatically)
   Please try to click 'Repost Order To aMember' link at your 1SC account -> Orders -> Order Details
   and check is notification receved at aMember CP -> Error/Debug Log

----------------

