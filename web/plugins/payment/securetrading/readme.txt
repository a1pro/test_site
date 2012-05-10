    
         SecureTrading plugin installation

 1. Enable plugin: go to aMember CP -> Setup -> Plugins and enable 
 "securetrading" payment plugin.
 2. Configure plugin: go to aMember CP -> Setup -> SecureTrading 
 and configure it.
 3. Upload the following "callback.txt" file to SecureTrading site:
-- cut --
method1 POST
url1    {$config.root_url}/plugins/payment/securetrading/ipn.php
fields1 orderref, name, address, town, county, postcode, formattedamount, timestamp, streference, stauthcode 
pipe1 yes
-- cut --
  (of course, you need to upload only lines between "-- cut --" and "-- cut --"
  delimiters). Instructions how to upload info to SecureTrading site, can be
  "My-ST" guide (can be downloaded from SecureTrading website).
 4. Run a test transaction. 

