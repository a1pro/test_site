
            WORLDPAY INSTALLATION INSTRUCTIONS
1. Enable and configure WorldPay Plugin in aMember control panel.
2. Login into WorldPay Control Panel 
    http://support.worldpay.com/admin/ 
and set "Callback URL" as follows
    {$config.root_url}/plugins/payment/worldpay/ipn.php
(it will allow to work with several websites with just one account).
You also have to enable the callback, by checking 
the following box: "Callback enabled"
  You should also enable the printout of the receipt, 
by checking the box: "Use callback response"

3. Make test purchase. After your testing is done, 
disable testing in aMember Control Panel.
