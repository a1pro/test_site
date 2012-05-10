Epassporte payment plugin installation
----------------------------------------------------------------------
Please note, this plugin doesn't support recurring payments.

1. Enable plugin at aMember CP -> Setup -> Plugins

2. Configure plugin at aMember Cp -> Setup -> Epassporte

 - Create a "shared password."  This shared password will only be known to Frontkick
   and to Epassporte.  It will allow Frontkick to reject any requests that don't arrive
   from Epassporte.

3. Contact Epassporte support and request them to:

 - Set up data postback URL to {$config.root_url}/plugins/payment/epassporte/ipn.php?cred=XXXX
   where XXXX is the shared password you created in step 2.
