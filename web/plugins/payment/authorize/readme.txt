Login into your authorize.net merchant account.
Go to "Settings and Profile" menu.
Installation steps: 
1. Go to "Payment Form->Form Fields" menu. At least uncheck 
all boxes near "Customer ID". You can also disable another
fields to make signup a bit less painful for your customers.
2. Go to "Relay Response" menu. Set URL to:
    {$config.root_url}/plugins/payment/authorize/ipn.php
3. Go to "MD5 Hash" menu. Set secret word to desired values 
(it is important that it is the same as configured in aMember).

