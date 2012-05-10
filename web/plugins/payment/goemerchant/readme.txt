
***** IMPORTANT : THIS PLUGIN HAS NOT BEEN TESTED YET ****

1. Enable and configure plugin in aMember Control Panel

2. You NEED to use external cron with this plugins
    (See Setup/Configuration -> Advanced)

3. Run test transaction to test the integration

-----------------------

To test a successful response from the gateway,
please provide the values for the following variables:

merchant = 1264
password = password

To get an approved response, use the following information:
Visa: 4111111111111111 CVV2 = 123
Mastercard: 5000300020003003 CVC2 = 123
Discover: 6011111111111117 CVV2 = 123
Amex: 374255312721002 CVV2 = 1234
Address: 123 Test St
Zip: 12345-6789