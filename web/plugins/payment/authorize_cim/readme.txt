Authorize.Net CIM 
---------------------------------------------------
  
The biggest advantage of this plugin is that altough credit card info
is entered on your website, it will be stored on Auth.Net secure servers
so recurring billing is secure and you do not have to store cc info on your
own website.

You need to enable CIM service in authorize.net 
(Tools -> Customer Information Manager -> Sign Up Now)
This is a paid service.
                  

1. Enable and configure plugin in aMember CP -> Setup -> Plugins

2. You NEED to use external cron with this plugins
    (See Setup/Configuration -> Advanced)

3. Please go to http://www.authorize.net and go to 
   ACCOUNT -> Settings -> Transaction Format Settings ->
    -> Virtual Terminal > Virtual Terminal Settings > Card Code
   Uncheck box "Required" to disable requirement of "Card Code" for
   every transaction. aMember will submit it for first transaction
   only.

4. Enable test mode and try it:

 TEST CARD NUMBER | CARD TYPE
---------------------------------------
370000000000002     American Express
6011000000000012    Discover
5424000000000015    MasterCard
4007000000027       Visa


-----------------------------------------------------------------------------
Q.: I receive the following message from Auhtorize.Net during transaction:
    "(92) The gateway no longer supports the requested method of integration."

A.: The reason why you are getting this error is because within your
account you have relay URL specified under your response receipt
settings. What you will need to do is delete the relay URL specified
under these settings.
