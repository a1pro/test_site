                      FastSpring plugin installation

 1. Enable plugin: go to aMember CP -> Setup/Configuration -> Plugins and enable
	"FastSpring" payment plugin.
 2. Configure plugin at aMember CP -> Setup/Configuration -> FastSpring

 3. Configure FastSpring Product ID at aMember CP -> Manage Products -> Edit
 
 4. Configure Remote Server URL (Type: Order Notification) in your FastSpring account
 
    ( Account -> Notification Configuration -> Add Notification Rule -> HTTP Remote Server Call )

    to this URL: {$config.root_surl}/plugins/payment/fastspring/ipn.php

 6. Run a test transaction to ensure everything is working correctly.


