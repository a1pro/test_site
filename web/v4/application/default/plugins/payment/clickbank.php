<?php

class Am_Paysystem_Clickbank extends Am_Paysystem_Abstract
{
    const PLUGIN_STATUS = self::STATUS_BETA;
    
    protected $_canResendPostback = true;
    
    protected $url = 'http://www.clickbank.net/sell.cgi';
    
    public function __construct(Am_Di $di, array $config)
    {
        $this->defaultTitle = ___("ClickBank");
        $this->defaultDescription = ___("pay using credit card or PayPal");
        parent::__construct($di, $config);
        $di->billingPlanTable->customFields()->add(
            new Am_CustomFieldText(
            'clickbank_product_id', 
            'ClickBank Product#', 
            'you have to create similar product in ClickBank and enter its number here'
            ,array(/*,'required'*/)
            )
            /*new Am_CustomFieldSelect(
            'clickbank_product_id', 
            'ClickBank Product#', 
            'you have to create similar product in ClickBank and enter its number here', 
            'required', array('options' => array('' => '-- Please select --', '11' => '#11', '22' => '#22')))*/
        );
    }
    public function isConfigured()
    {
        return strlen($this->getConfig('account'));
    }
    public function canAutoCreate()
    {
        return true;
    }
    public function isNotAcceptableForInvoice(Invoice $invoice)
    {
        foreach ($invoice->getItems() as $item)
        {
            /* @var $item InvoiceItem */
            if (!$item->getBillingPlanData('clickbank_product_id'))
                return "item [" . $item->item_title . "] has no related ClickBank product configured";
        }
    }
    public function _initSetupForm(Am_Form_Setup $form)
    {
        $form->addText('account', array('size' => 20, 'maxlength' => 16))
            ->setLabel("ClickBank account id\n".
                "your ClickBank username")
            ->addRule('required');
        $form->addText('secret', array('size' => 20, 'maxlength' => 16))
            ->setLabel("ClickBank secret phrase\n".
                "defined at clickbank.com -> login -> My Site -> Advanced Tools -> edit -> Secret Key")
            ->addRule('required');
        $form->addText('clerk_key', array('size' => 50))
            ->setLabel("ClickBank Clerk API Key\n".
                "defined at clickbank.com -> login -> My Account -> Clerk API Keys -> edit")
            ->addRule('required');
        $form->addText('dev_key', array('size' => 50))
            ->setLabel("Developer API Key\n".
                "defined at clickbank.com -> login -> My Account -> Developer API Keys -> edit")
            ->addRule('required')
            ->addRule('callback2', '-- wrong keys --', array($this, 'checkApiKeys'));
    }
    function checkApiKeys($vals)
    {
        $c = new Am_HttpRequest('https://sandbox.clickbank.com/rest/1.2/sandbox/product/list');
        $c->setHeader('Accept', 'application/xml')
          ->setHeader('Authorization', $vals['dev_key'] .':'. $vals['clerk_key']);
        $res = $c->send();
    }
    
    public function _process(Invoice $invoice, Am_Request $request, Am_Paysystem_Result $result)
    {
        $a = new Am_Paysystem_Action_Redirect($this->url);
        $a->link = sprintf('%s/%d/%s', 
            $this->getConfig('account'),
            $this->invoice->getItem(0)->getBillingPlanData('clickbank_product_id'),
            $this->invoice->getLineDescription()
            );
        $a->seed = $invoice->public_id;
        
        $a->name = $invoice->getName();
        $a->email = $invoice->getEmail();
        $a->country = $invoice->getCountry();
        $a->zipcode = $invoice->getZip();
        $a->filterEmpty();
        $result->setAction($a);
    }
    public function directAction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        if ($request->getActionName() == 'thanks')
            return $this->thanksAction($request, $response, $invokeArgs);
        else
            return parent::directAction($request, $response, $invokeArgs);
    }
    public function createTransaction(Am_Request $request, Zend_Controller_Response_Http $response, 
        array $invokeArgs)
    {
        return new Am_Paysystem_Transaction_Clickbank($this, $request, $response, $invokeArgs);
    }
    public function createThanksTransaction(Am_Request $request, Zend_Controller_Response_Http $response, 
        array $invokeArgs)
    {
        return new Am_Paysystem_Transaction_ClickBank_Thanks($this, $request, $response, $invokeArgs);
    }
    public function getRecurringType()
    {
        return self::REPORTS_REBILL;
    }
    public function getReadme()
    {
        return <<<CUT
                      ClickBank plugin installation

 1. Enable plugin: go to aMember CP -> Setup/Configuration -> Plugins and enable
	"ClickBank" payment plugin.
    
 2. Configure plugin: go to aMember CP -> Setup/Configuration -> ClickBank
	and configure it.
    
 3. For each your product and billing plan, configure ClickBank Product ID at 
        aMember CP -> Manage Products -> Edit
        
 4. Configure ThankYou Page URL in your ClickBank account (for each Product) to this URL:
    %root_url%/payment/clickbank/thanks
    
 5. Configure Instant Notification URL in your ClickBank account
    ( Account Settings -> My Site -> Advanced Tools -> Edit )
    to this URL: %root_url%/payment/clickbank/ipn
    
 6. Run a test transaction to ensure everything is working correctly.
 

THIS PLUGIN DOES NOT SUPPORT RECURRING SUBSCRIPTIONS YET.

------------------------------------------------------------------------------

CUT;
    }
}

class Am_Paysystem_Transaction_Clickbank extends Am_Paysystem_Transaction_Incoming
{
    protected $_autoCreateMap = array(
        'name' => 'ccustname',
        'country' => 'ccustcc',
        'state' => 'ccuststate',
        'email' => 'ccustemail',
        'user_external_id' => 'ccustemail',
        'invoice_external_id' => 'ccustemail',
    );
    
    /*
     * ccustname 	customer name 	1-510 Characters
ccuststate 	customer state 	0-2 Characters
ccustcc 	customer country code 	0-2 Characters
ccustemail 	customer email 	1-255 Characters
cproditem 	ClickBank product number 	1-5 Characters
cprodtitle 	title of product at time of purchase 	0-255 Characters
cprodtype 	type of product on transaction (STANDARD, and RECURRING) 	8-11 Characters
ctransaction * 	action taken 	4-15 Characters
ctransaffiliate 	affiliate on transaction 	0-10 Characters
ctransamount 	amount paid to party receiving notification (in pennies (1000 = $10.00)) 	3-10 Characters
ctranspaymentmethod 	method of payment by customer 	0-4 Characters
ctransvendor 	vendor on transaction 	5-10 Characters
ctransreceipt 	ClickBank receipt number 	8-13 Characters
cupsellreceipt ** § 	Parent receipt number for upsell transaction 	8-13 Characters
caffitid 	affiliate tracking id 	0 – 24 Characters
cvendthru 	extra information passed to order form with duplicated information removed 	0-1024 Characters
cverify ** 	the “cverify” parameter is used to verify the validity of the previous fields 	8 Characters
ctranstime ** 	the Epoch time the transaction occurred (not included in cverify)
     */
    public function getUniqId()
    {
         return $this->request->get('ctransreceipt');       
    }
    public function getReceiptId()
    {
        return $this->request->get('ctransreceipt');
    }
    public function getAmount()
    {
        return moneyRound($this->request->get('ctransamount'));
    }
    public function validateSource()
    {
        $ipnFields = $this->request->getPost();
        unset($ipnFields['cverify']);
        ksort($ipnFields);
        $pop = implode('|', $ipnFields) . '|' . $this->getPlugin()->getConfig('secret');
        if (function_exists('mb_convert_encoding'))
            $pop = mb_convert_encoding($pop, "UTF-8");
        $calcedVerify = strtoupper(substr(sha1($calcedVerify),0,8));
        return $this->request->get('cverify') == $calcedVerify;
    }
    public function validateStatus()
    {
        return true;
    }
    public function validateTerms()
    {
        return true;
    }
    public function processValidated()
    {
        $this->invoice->addPayment($this);
    }
}

class Am_Paysystem_Transaction_ClickBank_Thanks extends Am_Paysystem_Transaction_Incoming
{
    public function findTime()
    {
    	$dt = new DateTime('@' . $this->request->getInt('time'));
    	$dt->setTimezone(new DateTimeZone('Canada/Central'));
        return $dt;
    }
    
    public function fetchUserInfo()
    {
        $names = preg_split('/\s+/', $this->request->get('cname'), 2);
        $names[0] = preg_replace('/[^a-zA-Z0-9._+-]/', '', $names[0]);
        $names[1] = preg_replace('/[^a-zA-Z0-9._+-]/', '', $names[1]);
        $email = $this->request->get('cemail');
        $email = preg_replace('/[^a-zA-Z0-9._+@-]/', '', $email);
        return array(
            'name_f' => $names[0],
            'name_l' => $names[1],
            'email'  => $email,
            'country' => $this->request->getFiltered('ccountry'),
            'zip' => $this->request->getFiltered('czip'),
        );
    }
    public function generateInvoiceExternalId()
    {
        return $this->getUniqId();
    }
    public function autoCreateGetProducts()
    {
        $cbId = $this->request->getFiltered('item');
        if (empty($cbId)) return;
        $pl = $this->getPlugin()->getDi()->billingPlanTable->findFirstByData('clickbank_product_id', $cbId);
        if (!$pl) return;
        $pr = $pl->getProduct();
        if (!$pr) return;
        return array($pr);
    }
    public function getUniqId()
    {
        return $this->request->getFiltered('cbreceipt');
    }
    public function findInvoiceId()
    {
        return $this->request->getFiltered('seed');
    }
    public function validateSource()
    {
        $vars = array(
            $this->getPlugin()->getConfig('secret'),
            $this->request->get('cbreceipt'),
            $this->request->get('time'),
            $this->request->get('item'),
        );
        $hash = sha1(implode('|', $vars));
        return strtolower($this->request->get('cbpop')) == substr($hash, 0, 8);
    }
    public function validateStatus()
    {
        return true;
    }
    public function validateTerms()
    {
        return true;
    }
    public function getInvoice()
    {
        return $this->invoice;
    }
}