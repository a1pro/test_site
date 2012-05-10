            2Checkout payment plugin configuration
           -----------------------------------------

CONFIUGURATION OF ACCOUNT 

1. Login into your 2Checkout account:
   https://www.2checkout.com/va/

2. Go to "Account->Site Management".
   Set  
   <b>Approved URL:</b> {$config.root_url}/plugins/payment/twocheckout_r/thanks.php

   <b>Pending URL:</b> {$config.root_url}/

   <b>Your Secret Word:</b> 
   set to any value you like. the same value must be entered
   in aMember 2Checkout plugin settings on this page

3. Configure INS URL in your 2checkout account (Notifications->Settings) 
   to this URL:
    {$config.root_surl}/plugins/payment/twocheckout_r/ipn.php 
          