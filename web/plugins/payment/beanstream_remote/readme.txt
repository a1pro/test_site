    
         beanstream_remote plugin installation

 1. Enable plugin: go to aMember CP -> Setup -> Plugins and enable 
 "beanstream_remote" payment plugin.
 2. Configure plugin: go to aMember CP -> Setup -> beanstream_remote 
 and configure it.
 3. Enable hidden payment notification in BeanStream control panel.

 "The merchant may also optionally specify a Server to Server Response Notification. When
enabled, the Beanstream System will POST the transaction response to a URL specified by the
merchant before redirecting the consumer's browser to the approved/declined page. This URL is
entered within the Order Settings module of the Membership Area. The same response variables
that are passed to the approved/decline page are passed to the Response Notification URL as
listed in Section 4.2 Response Variables.
"

 Please set it to 
 {$config.root_url}/plugins/payment/beanstream_remote/ipn.php


 4. Run a test transaction to ensure everthing is working correctly. 
 While your merchant account is in testing mode, you can use the following
 credit card numbers:

    TYPE      CARD NUMBER             | RESPONSE
    --------------------------------------------
    VISA       |  4030 0000 1000 1234 | Approved
    VISA       |  4003 0505 0004 0005 | Declined
    MasterCard |  5100 0000 1000 1004 | Approved
    MasterCard |  5100 0000 2000 2000 | Declined
    AMEX       |  3711 0000 1000 131  | Approved
    AMEX       |  3424 0000 1000 180  | Declined
