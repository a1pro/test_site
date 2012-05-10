Multicards payment plugin installation
----------------------------------------------------------------------
Please note, this plugin doesn't support recurring payments.
Please contact CGI-Central if you need it.

1. Enable and configure plugin in aMember Control Panel.

2. Login into multicards merchant interface,  then open
Edit Orderpages -> Page you specifided in config

Set:
  - Post URL to:
    {$config.root_url}/plugins/payment/multicards/ipn.php

  - Silent Post : yes

  - Post Fields : 
    mer_id,item1_price,item1_qty,user1

  - AllowedReferer:
    {$config.root_url}/signup.php


It's all