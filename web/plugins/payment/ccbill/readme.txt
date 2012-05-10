
             ccBill plugin setup

NOTE: If you are using this plugin, you don't need ccBill script to manage
.htpasswd file for protected area. aMember will handle all these things for 
your site.

1. Login into your ccBill account https://webadmin.ccbill.com/
2. Click QuickLinks: Account Setup : Account Admin
3. Choose an existing Subaccount, or create new one, then return to this step.
4. Create the same subscription types as you have in aMember control panel,
   make sure that all settings are the same.
5. Create a form for your subscription types.
6. Goto Modify Subaccount - Advanced.
   Set Background Post Information: 
    Approval Post URL:
     {$config.root_url}/plugins/payment/ccbill/ipn.php
    Denial Post URL:
     {$config.root_url}/plugins/payment/ccbill/ipn.php
    Click "button" button.

7. Click on "User Management" link and scroll down to "Username settings". Set:
   "Username Type" : "USER DEFINED"
   "Collect Username/Password" : "Display Username, Hide Password"
   "Min Username Length" : 4
   "Max Username Length" : 16
   "Min Password Length" : 4
   "Max Password Length" : 16
   Click "update" button.
 
8. Click "View Subaccount Info" in left menu to return to subaccount review
   screen. 
  Remember or write down the following parameters:
  In top left menu, you will see number, like "911399-0001"
  Here, 911399 - is your Account ID, and 0001 - is SubAccount ID.
  Have a look to "Forms" square: you will see form numbers.
  Write down form numbers with type "CREDIT". "Form name" looks like "22cc"
  and "Sub. Type ID" looks like "19".

9. Return back to aMember CP admin panel (most possible you're already here).
  Go to aMember CP -> Setup -> ccBill
  Enter your account and subaccount id. Click Save.
  Then go to aMember CP -> Edit Products, create or edit your products
  and don't forget to enter neccessary ccBill configuration parameters
  (form ID, ccbill Product ID) for each your aMember Product.

10. Try to run test payments. 
You may setup a testing account here:
     https://webadmin.ccbill.com/tools/accountMaintenance/testSignupSettings.cgi
And you may find test credit card numbers here:
     http://ccbillhelp.ccbill.com/content/test_numb_card_tls.htm

11. Contact suport@ccbill.com to obtain username and password for CCBill
Data Link System.  You will need to send them IP address of your site. If you
don't know it, ask your hosting support.

12. Enter datalink username and pasword into ccBill plugin settings.