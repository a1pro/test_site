<?php

/**
 * Provides a helper to use PayPal HVP api from any of paypal plugins
 */
class Am_Paysystem_PaypalApiRequest extends Am_HttpRequest
{
    const SANDBOX_URL = "https://api-3t.sandbox.paypal.com/nvp";
    const LIVE_URL = "https://api-3t.paypal.com/nvp";
    
    /** @var Am_Paysystem_Abstract */
    protected $plugin;
    public function __construct(Am_Paysystem_Abstract $plugin)
    {
        $this->plugin = $plugin;
        
        parent::__construct($this->plugin->getConfig('testing') ? self::SANDBOX_URL : self::LIVE_URL, 
            self::METHOD_POST);
        
        if ($adapter = $this->plugin->createHttpRequest()->getConfig('adapter'))
            $this->setConfig('adapter', $adapter);
        
        $this->addPostParameter('VERSION', '63.0')
            ->addPostParameter('SIGNATURE', $this->plugin->getConfig('api_signature'))
            ->addPostParameter('USER', $this->plugin->getConfig('api_username'))
            ->addPostParameter('PWD', $this->plugin->getConfig('api_password'));
    }
    /**
     * Fills all but user and cc properties of request
     * @param Am_HttpRequest $this
     * @param Invoice $invoice 
     */
    function doSale(Invoice $invoice, CcRecord $cc)
    {
        $this->addPostParameter('METHOD', 'DoDirectPayment');
        if ($invoice->first_total > 0 )
        {
            $this->addPostParameter('PAYMENTACTION', 'Sale');
            $this->addPostParameter('AMT', $invoice->first_total);
        } else {
            $this->addPostParameter('PAYMENTACTION', 'Sale');
            $this->addPostParameter('AMT', 0.01);
        }
        $this->addPostParameter('DESC', $invoice->getLineDescription());
        $this->addPostParameter('RETURNFMFDETAILS', 0);
        $this->addPostParameter('INVNUM', $invoice->getSecureId('paypal'));
        $this->setCc($invoice, $cc);
    }
    function setCc(Invoice $invoice, CcRecord $cc)
    {
        $this->addPostParameter('IPADDRESS', $_SERVER['REMOTE_ADDR']);
        $this->addPostParameter('CREDITCARDTYPE', $cc->cc_type);
        $this->addPostParameter('ACCT', $cc->cc_number);
        $this->addPostParameter('CURRENCYCODE', 'USD'); // @todo
        $this->addPostParameter('EXPDATE', $cc->getExpire("%02d20%02d"));
        $this->addPostParameter('CVV2', $cc->getCvv());
        $this->addPostParameter('FIRSTNAME', $cc->cc_name_f);
        $this->addPostParameter('LASTNAME', $cc->cc_name_l);
        $this->addPostParameter('STREET', $cc->cc_street);
        $this->addPostParameter('CITY', $cc->cc_city);
        $this->addPostParameter('STATE', $cc->cc_state);
        $this->addPostParameter('ZIP', $cc->cc_zip);
        $this->addPostParameter('PHONENUM', $cc->cc_phone);
        $this->addPostParameter('COUNTRYCODE', strtoupper($cc->cc_country));
        $this->addPostParameter('EMAIL', $invoice->getEmail());
        return $this;
    }
    function createRecurringPaymentProfile(Invoice $invoice, CcRecord $cc = null, $token = null, $payerId = null)
    {
        if (!$cc && !$token)
            throw new Am_Exception_Paysystem("Either [token] or [cc] must be specified for " . __METHOD__ );
        $periodConvert = array(
            Am_Period::DAY => 'Day',
            Am_Period::MONTH => 'Month',
            Am_Period::YEAR => 'Year',
        );
        $this->addPostParameter('METHOD', 'CreateRecurringPaymentsProfile');
        if ($token)
        {
            $this->addPostParameter('TOKEN', $token);
            $this->addPostParameter('PAYERID', $payerId);
        }
        $this->addPostParameter('DESC', $invoice->getTerms());
        $this->addPostParameter('PROFILESTARTDATE', gmdate('Y-m-d\TH:i:s.00\Z'
            , strtotime($invoice->calculateRebillDate(1) . date( 'H:i:s', $invoice->getDi()->time))
        ));
        $this->addPostParameter('PROFILEREFERENCE', $invoice->getRandomizedId('site'));
        //$this->addPostParameter('MAXFAILEDPAYMENTS', '');
        //$this->addPostParameter('AUTOBILLOUTAMT', 'AddToNextBilling');
        $p = new Am_Period($invoice->first_period);
        $pp = $periodConvert[$p->getUnit()];
        if (!$pp) throw new Am_Exception_Configuration("Could not find billing unit for invoice#{$invoice->invoice_id}.first_period: {$invoice->first_period}");
        /// first period
        $this->addPostParameter('TRIALBILLINGPERIOD', $pp);
        $this->addPostParameter('TRIALBILLINGFREQUENCY', $p->getCount());
        $this->addPostParameter('TRIALTOTALBILLINGCYCLES', '1');
        $this->addPostParameter('TRIALAMT', $invoice->second_total); // bill at the end of trial period

        // it may take up to 24hours to process it! so enabled only for credit card payments
        if ($cc && ($invoice->first_total > 0))
            $this->addPostParameter('INITAMT', $invoice->first_total); // bill right now
        
        /// second period
        $p = new Am_Period($invoice->second_period);
        $pp = $periodConvert[$p->getUnit()];
        if (!$pp) throw new Am_Exception_Configuration("Could not find billing unit for invoice#{$invoice->invoice_id}.second_period: {$invoice->second_period}");
        
        $this->addPostParameter('BILLINGPERIOD', $pp);
        $this->addPostParameter('BILLINGFREQUENCY', $p->getCount());
        if ($invoice->rebill_times != IProduct::RECURRING_REBILLS)
            $this->addPostParameter('TOTALBILLINGCYCLES', $invoice->rebill_times);
        $this->addPostParameter('AMT', $invoice->second_total); // bill at end of each payment period
        $this->addPostParameter('CURRENCYCODE', 'USD'); // @todo
        $this->addPostParameter('NOTIFYURL', $this->plugin->getPluginUrl('ipn'));
        $i = 0;
        foreach ($invoice->getItems() as $item)
        {
            /* @var $item InvoiceItem */
            $this->addPostParameter("L_PAYMENTREQUEST_0_NAME$i", $item->item_title);
            $this->addPostParameter("L_PAYMENTREQUEST_0_NUMBER$i", $item->item_id);
            $this->addPostParameter("L_PAYMENTREQUEST_0_QTY$i", $item->qty);
            $i++;
        }
        $this->addPostParameter('L_BILLINGTYPE0', 'RecurringPayments');
        $this->addPostParameter('L_BILLINGAGREEMENTDESCRIPTION0', $invoice->getTerms());

        if ($cc) $this->setCC($invoice, $cc);
        return $this;
    }
    
    function _setExpressAmounts(Invoice $invoice)
    {
        $this->addPostParameter('PAYMENTREQUEST_0_AMT', $invoice->first_total);
        $this->addPostParameter('PAYMENTREQUEST_0_CURRENCYCODE', 'USD'); // @todo
//        $this->addPostParameter('PAYMENTREQUEST_0_ITEMAMT', $invoice->first_subtotal);
//        $this->addPostParameter('PAYMENTREQUEST_0_SHIPPINGAMT', $invoice->first_shipping);
//        $this->addPostParameter('PAYMENTREQUEST_0_TAXAMT', $invoice->first_tax);
        $this->addPostParameter('PAYMENTREQUEST_0_INVNUM', $invoice->getSecureId('paypal'));
        $this->addPostParameter('PAYMENTREQUEST_0_NOTIFYURL', $this->plugin->getPluginUrl('ipn'));
        $this->addPostParameter('PAYMENTREQUEST_0_PAYMENTACTION', 'Sale');
        $i = 0;
        foreach ($invoice->getItems() as $item)
        {
            /* @var $item InvoiceItem */
            $this->addPostParameter('L_PAYMENTREQUEST_0_NAME'.$i, $item->item_title);
            $this->addPostParameter('L_PAYMENTREQUEST_0_AMT'.$i, $item->first_total);
//            $this->addPostParameter('L_PAYMENTREQUEST_0_ITEMAMT'.$i, $item->getFirstSubtotal());
//            $this->addPostParameter('L_PAYMENTREQUEST_0_NUMBER'.$i, $item->item_id);
            $this->addPostParameter('L_PAYMENTREQUEST_0_QTY'.$i, $item->qty);
//            $this->addPostParameter('L_PAYMENTREQUEST_0_TAXAMT'.$i, $item->first_tax);
            /// The unique non-changing identifier for the seller at the marketplace site. This ID is not displayed.
            //$this->addPostParameter('L_PAYMENTREQUEST_0_SELLERID'.$i, );
            // PAYMENTREQUEST_n_SELLERPAYPALACCOUNTID
            $i++;
        }
        if ($invoice->rebill_times)
        {
            $this->addPostParameter('L_BILLINGTYPE0', 'RecurringPayments');
            $this->addPostParameter('L_BILLINGAGREEMENTDESCRIPTION0', $invoice->getTerms());
        }
    }
    
    
    function setExpressCheckout(Invoice $invoice)
    {
        $this->addPostParameter('METHOD', 'SetExpressCheckout');
        $this->addPostParameter('RETURNURL', $this->plugin->getPluginUrl('express-checkout'));
        $this->addPostParameter('CANCELURL', $this->plugin->getCancelUrl());
        //$this->addPostParameter('REQCONFIRMSHIPPING', 0);
        if (!$invoice->hasShipping())
            $this->addPostParameter('NOSHIPPING', 1);
        //$this->addPostParameter('LOCALECODE', '');
        //$this->addPostParameter('PAGESTYLE', ''); // htmlvariable page_style
        // $this->addPostParameter('HDRIMG', ''); // 
        $this->addPostParameter('EMAIL', $invoice->getEmail());
        $this->addPostParameter('SOLUTIONTYPE', 'Sole');
        $this->addPostParameter('LANDINGPAGE', 'Billing');
        $this->_setExpressAmounts($invoice);
    }
    
    public function getExpressCheckoutDetails($token)
    {
        $this->addPostParameter('METHOD', 'GetExpressCheckoutDetails');
        $this->addPostParameter('TOKEN', $token);
    }
    public function doExpressCheckout(Invoice $invoice, $token, $payerId)
    {
        $this->addPostParameter('METHOD', 'DoExpressCheckoutPayment');
        $this->addPostParameter('TOKEN', $token);
        $this->addPostParameter('PAYERID', $payerId);
        $this->addPostParameter('PAYMENTREQUEST_0_NOTIFYURL', $this->plugin->getPluginUrl('ipn'));
        //$this->addPostParameter('PAYMENTACTION', 'Sale');
        $this->addPostParameter('PAYMENTREQUEST_0_PAYMENTACTION', 'Sale');
        $this->_setExpressAmounts($invoice);
    }
    
    /***
     * Add fields specific for PayPal API
     */
    static function initSetupForm(Am_Form_Setup $form)
    {
        $form->addText("business", array('size'=>40))->setLabel("Primary Paypal E-Mail Address");
        $form->addText("api_username", array('size'=>40))->setLabeL("API Username");
        $form->addPassword("api_password")->setLabel("API Password");
        $form->addText("api_signature", array('size'=>60))->setLabel("API Signature");
        $form->addAdvCheckbox("testing")->setLabel("Sandbox", "is test or live transaction");
    }
    /**
     * Send response handle failure, return parsed array
     * @return array 
     */
    public function sendRequest(InvoiceLog $log)
    {
        $log->paysys_id = $this->plugin->getId();
        $log->add($this);
        $response = $this->send();
        $log->add($response);
        if ($response->getStatus()!=200)
            throw new Am_Exception_InputError("Error communicating to PayPal, unable to finish transaction. Your account was not billed, please try again");
        parse_str($response->getBody(), $vars);
        if (!count($vars))
            throw new Am_Exception_InputError("Error communicating to PayPay, unable to parse response ");
        return $vars;
    }
}