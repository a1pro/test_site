             PayPal payment plugin installation
  --------------------------------------------------------------------
             
Please visit http://manual.amember.com/PayPal_Pro_Plugin_Configuration
to find out most recent plugin configuration instructions.

This plugin require both WebSitePaymetns PRO and Paypal PRO Recurring payments to be enabled in your paypal account.

"Paypal PRO Recurring payments" functionality required to create recurring subscriptions for DirectPayments (when user specify CC info in aMember)


Plugin make these API requests so you should enable this in API access section in your paypal account for API user:
SetExpressCheckout
GetExpressCheckoutDetails
DoExpressCheckoutPayment
DoVoid
CreateRecurringPaymentsProfile
DoDirectPayment
ManageRecurringPaymentsProfileStatus
UpdateRecurringPaymentsProfile


Also this is necessary to set IPN url in your paypal account. 
It should be set to 
{$config.root_url}/plugins/payment/paypal_pro/ipn.php