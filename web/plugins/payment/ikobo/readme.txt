    iKobo  payment plugin installation

1. To use this plugin enable it at aMember CP -> Setup -> Plugins.

2. Then configure the plugin at aMember Cp -> Setup -> iKobo

3. In the ikobo control panel, go to Sell -> Instant Payment Notification
and set
Check here to enable Enable IPN = check
Supply the IPN URL =
{$config.root_url}/plugins/payment/ikobo/ipn.php
Password = enter exactly the same as you entered into aMember at step (2)
Press "OK" button.

4. In the iKobo interface, you have to create products with exactly the same
settings as in aMember CP.  Go to Sell -> Single Item Purchases and do it.
When you receive HTML for "Buy Now" button, review it carefully. It contains
string like this: purchase.php?item_id=30329&poid=SA153123US
"poid" here is your iKobo account number, and item_id (30239) is iKobo item ID. 
You have to go back to aMember CP -> Edit Products screen and enter this
number for each product. When you create products, it will be required to
enter this number, so you have to add products to iKobo first, then to aMember.

Please note - iKobo doesn't support recurring billing.