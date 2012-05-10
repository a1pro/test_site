1. Enable and configure plugin in aMember Control Panel

2. You NEED to use external cron with this plugins
    (See Setup/Configuration -> Advanced)


3. Enable test mode and try it:

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

Thank you for contacting our customer service group.
Please let us know if there is anything we can do to help you in the
future.
