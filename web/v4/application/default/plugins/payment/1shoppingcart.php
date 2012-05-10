<?php

class Am_Paysystem_1shoppingcart extends Am_Paysystem_Abstract{
    const PLUGIN_STATUS = self::STATUS_BETA;
    const PLUGIN_REVISION = '4.1.10';
    
    const URL = "http://www.marketerschoice.com/app/javanof.asp";
    
    protected $defaultTitle = '1ShoppingCart';
    protected $defaultDescription = 'All major credit cards accepted';
    
    public function _initSetupForm(Am_Form_Setup $form) {
        $form->addInteger('merchant_id', array('size'=>20))
            ->setLabel('Your Merchant ID#');
        
        $form->addText('password')->setLabel(array('Postback Password', 'Should be the same as in in your 1SC account'));
        /* $form->addText('key', array('size'=>30))
            ->setLabel(array('API Key', '1SC -> My Account -> API Settings -> Your Current Merchant API Key')); */
        
    }
    public function init(){
        parent::init();
        $this->getDi()->billingPlanTable->customFields()
            ->add(new Am_CustomFieldText('1shoppingcart_id', "1ShoppingCart Product#", 
            "for any products you have to create corresponding product in 1SC 
                admin panel and enter the id# here"));
        
    }

    public function _process(Invoice $invoice, Am_Request $request, Am_Paysystem_Result $result) {
        $a = new Am_Paysystem_Action_Redirect(self::URL);
        $result->setAction($a);
        $a->MerchantID  =   $this->config['merchant_id'];
        $a->ProductID   =   $invoice->getItem(0)->getBillingPlanData('1shoppingcart_id');
        $a->AMemberID   =   $invoice->invoice_id;
        $a->PostBackURL =   $this->getDi()->config->get('root_url')."/payment/1shoppingcart/ipn";
        $a->filterEmpty();
        $result->setAction($a);
    }

    public function createTransaction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs) {
        return new Am_Paysystem_Transaction_1shoppingcart($this, $request, $response, $invokeArgs);
    }

    public function getRecurringType() {
        return self::REPORTS_REBILL;
    }
    
    public function getReadme(){
        $rootURL = $this->getDi()->config->get('root_url');
        return <<<CUT
<b>1ShoppingCart payment plugin configuration</b>
        
1. Enable "1shoppingcart" payment plugin at aMember CP->Setup->Plugins

2. Configure "1shoppingcart" payment plugin at aMember CP -> Setup/Configuration -> 1ShoppingCart
   Make sure you set the same API Key in aMember CP and 1ShoppingCart
   Merchants CP  -> My Account -> API Settings -> Your Current Merchant API Key
   
3. Create equivalents for all aMember products in 1ShoppingCart Merchants CP.
   Make sure it has the same subscription terms (period, price) as aMember
   Products. Set "Thanks URL" for all 1ShoppingCart products to 
   $rootURL/thanks
   Write down product# of all 1ShoppingCart products. 
   
4. Visit aMember CP -> Manage Products, click "Edit" on each product
   and enter "1ShoppingCart Product#" for each corresponding billing plan, 
   then click "Save".
   
5. Try your integration - go to aMember signup page, and try to make new signup.


----------------
   
   In case of any issues with IPN Notifications (if members is not activated in aMember automatically)
   Please try to click 'Repost Order To aMember' link at your 1SC account -> Orders -> Order Details
   and check is notification receved at aMember CP -> Utilites -> Logs

----------------

CUT;
    }
    
}
class Am_Paysystem_Transaction_1shoppingcart extends Am_Paysystem_Transaction_Incoming{
    const START_RECURRING = 'start_recurring';
    const REBILL = 'rebill';
    const PAYMENT = 'payment';
    const RECURRING_EOT = 'recurring_eot';
    
    function validateSource(){
        $vars = $this->request->getPost();
        $sign = $vars['VerifySign'];
        unset($vars['VerifySign']);
        $vars['PostbackPassword'] = $this->plugin->getConfig('password'); 
        $str = join('', array_values($vars));
        $md5 = md5($str);
        
        if ($md5 != $sign){
            throw new Am_Exception_Paysystem_TransactionInvalid("Verify sign incorrect.");
        }
        return true;
    }
    function getUniqId(){
        return $this->request->get('OrderID');
    }
    function validateTerms() {
        
        $amount = $this->request->get("Amount");
        $type = $this->request->get("Status");
        if($type == self::RECURRING_EOT) return true;
        return ($amount  == ($type == self::REBILL ? $this->invoice->second_total : $this->invoice->first_total));
    }
    function findInvoiceId() {
        return $this->request->get("AMemberID");
    }
    
    function loadInvoice($invoiceId){
        $invoiceId = preg_replace('/-.*/', '', $invoiceId);
        $invoice = Am_Di::getInstance()->invoiceTable->load($invoiceId);
        // update invoice_id in the log record
        if ($invoice && $this->log)
        {
            $this->log->updateQuick(array(
                'invoice_id' => $invoice->pk(),
                'user_id' => $invoice->user_id,
            ));
        }
        return $invoice;
    }
    
    function processValidated() {
        switch($this->request->get("Status")){
            case self::START_RECURRING :
            case self::PAYMENT :
            case self::REBILL :
               $this->invoice->addPayment($this);
                break;
            case self::RECURRING_EOT : 
               $this->invoice->stopAccess($this);
                break;
        }
    }

    public function validateStatus() {
        return true;
    }
    
}
?>