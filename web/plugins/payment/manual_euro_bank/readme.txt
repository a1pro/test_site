    
         Manual Euro Bank plugin installation

 1. Enable plugin: go to aMember CP -> Setup -> Plugins and enable 
    "manual_euro_bank" payment plugin.
 2. Configure plugin: go to aMember CP -> Setup -> Euro Bank Payment
    and configure it.
 
 This plugin can use banks codes from a CSV file. For example, you can get it from an OsCommerce payment 
 module ( http://www.oscommerce.com/community/contributions,826 ).
 - download latest version of OsCommerce plugin from http://www.oscommerce.com/community/contributions,826
 - extract archive 
 - copy file "blz.csv" from extracted zip file (it can be found in \banktransfer_discus24\catalog\includes\data\)
   to amember/plugins/payment/manual_euro_bank/ folder
   
   
WARNING. This plugin can handle and e-mail about recurring payments, but ensure:
  1 - it is allowed by your country laws;
  2 - it does not handle correctly products with trial, but with no recurring billing enabled.
  