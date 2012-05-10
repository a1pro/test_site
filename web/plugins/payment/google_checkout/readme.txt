             Google Checkout payment plugin installation

1. Enable plugin at aMember CP -> Setup / Configuration -> Plugins

2. Go to aMember CP -> Setup / Configuration -> Google Checkout
and enter a Seller's Merchant Id and Key.

3. Login into Google as Seller and setup this URL:
{$config.root_surl}/plugins/payment/google_checkout/ResponseHandler.php
at My Sales -> Settings -> Integration -> API callback URL
This URL should be https:// but you can use http:// with sandbox test account.

An integration errors (if any) can be found
in your Google Checkout account -> Tools -> Integration Console

4. Configure an additional settings at
   aMember CP -> Manage Products -> Edit


------------------
To implement the Notification API, you must establish a web service that receives
and processes Google Checkout notifications.
Your web service must be secured by SSL v3 or TLS and must use a valid SSL certificate.
http://checkout.google.com/support/sell/bin/answer.py?answer=57856&query=SSL

The API callback URL that you use for your production account must use port 443,
which is the default port for HTTPS. The API callback URL that you use
for your Sandbox account may use either port 443 or port 80.
------------------
