<?php

class Am_Paysystem_Firstdata extends Am_Paysystem_CreditCard
{
    const PLUGIN_STATUS = self::STATUS_DEV; // this plugin must be kept not-public as it stored cc info
    const PLUGIN_DATE = '$Date$';
    const PLUGIN_REVISION = '$Revision$';

    protected $defaultTitle = "Pay with your Credit Card";
    protected $defaultDescription  = "accepts all major credit cards";
    
    public function getRecurringType()
    {
        return self::REPORTS_REBILL;
    }
    
    public function getSupportedCurrencies()
    {
        return array('USD', 'EUR', 'GBP', 'CAD');
    }
    
    public function isConfigured()
    {
        return strlen($this->getConfig('keyfile')) && strlen($this->getConfig('id'));
    }
    public function _doBill(Invoice $invoice, $doFirst, CcRecord $cc, Am_Paysystem_Result $result)
    {
        $user = $invoice->getUser();
        $tr = new Am_Paysystem_Transaction_Firstdata_Sale($this, $invoice, null, $doFirst, $cc);
        $tr->run($result);
    }
    
    public function _initSetupForm(Am_Form_Setup $form)
    {
        $form->addText("id")->setLabel('FirstData Account ID#');
        $s = $form->addSelect('keyfile')->setLabel("FirstData Web Service *.pem file\nupload to amember/application/configs/ folder, then it will appear in select");
        $dir = dirname(APPLICATION_CONFIG) . '/';
        $ff = glob($dir . '*.pem');
        $ff = str_replace($dir, '', $ff);
        if (!$ff) $ff = array('-- no key uploaded --');
        $s->loadOptions(array_combine($ff, $ff));
        $form->addAdvCheckbox('testing')->setLabel('Test Mode');
    }
    public function getReadme()
    {
        return <<<CUT
    FirstData plugin installation        
    
1. Login to your VirtualTerminal account at https://secure.linkpt.net/lpc/servlet/LPCLogin

2. Click to Support -> Download Center

3. Click on "Download Now" link.

4. Enter your TaxID and click "API" button. 

5. Upload .pem file into amember/application/configs/ folder using your FTP client.

6. Back to aMember CP -> Setup -> FirstData (this page), and fill-in the fields.
   
7. Run a test transaction.
CUT;
    }
}

class Am_Paysystem_Transaction_Firstdata_Sale extends Am_Paysystem_Transaction_CreditCard
{
    /** @var CcRecord */
    protected $cc;
    /**
     * Array
(
    [r_csp] => 
    [r_time] => 
    [r_ref] => 
    [r_error] => 
    [r_ordernum] => 
    [r_message] => This is a test transaction and will not show up in the Reports
    [r_code] => 
    [r_tdate] => Thu Dec 15 05:56:52 2011
    [r_score] => 
    [r_authresponse] => 
    [r_approved] => APPROVED
    [r_avs] => 
)
     */
    protected $ret;
    
    public function __construct(Am_Paysystem_Abstract $plugin, Invoice $invoice, $request, $doFirst, CcRecord $cc)
    {
        $this->cc = $cc;
        parent::__construct($plugin, $invoice, $request, $doFirst);
    }
    
    
    public function run(Am_Paysystem_Result $result)
    {
        require_once dirname(__FILE__) . "/lphp.php";
        $mylphp=new lphp;

        $myorder["host"]       = $this->getPlugin()->getConfig('testing') ? "staging.linkpt.net" : "secure.linkpt.net";
        $myorder["port"]       = "1129";
        $myorder["keyfile"]    = dirname(APPLICATION_CONFIG) . '/' . $this->getPlugin()->getConfig('keyfile');
        $myorder["configfile"] = $this->getPlugin()->getConfig('id');

        $myorder["ordertype"]    = "SALE";
        $myorder["result"]       = $this->getPlugin()->getConfig('testing') ? "GOOD" : "LIVE"; # For a test, set result to GOOD, DECLINE, or DUPLICATE
        $myorder["cardnumber"]   = $this->cc->cc_number;
        $myorder["cardexpmonth"] = $this->cc->getExpire('%1$02d');
        $myorder["cardexpyear"]  = $this->cc->getExpire('%2$02d');
        $myorder["chargetotal"]  = $this->doFirst ? $this->invoice->first_total : $this->invoice->second_total;

        $myorder["addrnum"]   = preg_replace('/^D/', '', $this->cc->cc_street);   
        $myorder["zip"]       = $this->cc->cc_zip; 
        
        if ($this->cc->getCvv())
        {
            $myorder["cvmindicator"] = "provided";
            $myorder["cvmvalue"]     = $this->cc->getCvv();
        }
        
        //if ($this->getPlugin()->getConfig('testing'))
        //    $myorder["debugging"] = "true";  # for development only - not intended for production use        
        // uncomment it to get debug info to screen! 
        $log = $this->getInvoiceLog();
        $log->add($mylphp->buildXML($myorder));
        $this->ret = $mylphp->curl_process($myorder); 
        $log->add(print_r($this->ret, true));
        if ($this->ret['r_approved'] == 'APPROVED')
        {
            $result->setSuccess($this);
            $this->processValidated();
        } else
            $result->setFailed(___("Payment failed"). ":" . $this->ret['r_error']);
    }
    
    public function parseResponse()
    {
        ;
    }
    
    public function getUniqId() {
        $x = $this->ret['r_ordernum'] ;
        if (empty($x) && $this->getPlugin()->getConfig('testing'))
            return uniqid('test-');
    }
    public function validate() 
    {
        $this->result->setSuccess($this);
    }
    public function processValidated()
    {
        $this->invoice->addPayment($this);
    }
}
