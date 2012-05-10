
              CashU payment plugin configuration
        
1. Enable "CashU" payment plugin at aMember CP->Setup->Plugins
2. Configure "CashU" payment plugin at aMember CP->Setup->CashU
   Make sure you set the same Encryption Keyword in aMember CP and CashU
   Merchants CP.
3. Set up “Return URL” to {$config.root_url}/plugins/payment/cashu/thanks.php
   inside the CashU merchant account using the tab “Encryption Information”.
4. Try your integration - go to aMember signup page, and try to make new signup.
   {$config.root_url}/signup.php