            Paypoint payment plugin configuration
           -----------------------------------------

CONFIUGURATION OF ACCOUNT 

1. Login into your Paypoint account   

2. Configure Payment Response Notification URL in your Paypoint account 
   to this URL:
    {$config.root_surl}/plugins/payment/paypoint/thanks.php 
          
3. Configure Scheduled Payment Response URL in your Paypoint account 
   to this URL:
    {$config.root_surl}/plugins/payment/paypoint/ipn.php 
	