<?php

class Am_Paysystem_CcDemo extends Am_Paysystem_CreditCard
{
    const PLUGIN_STATUS = self::STATUS_PRODUCTION;
    const PLUGIN_DATE = '$Date$';
    const PLUGIN_REVISION = '$Revision$';

    public function __construct(Am_Di $di, array $config)
    {
        $this->defaultTitle = ___("CC Demo");
        $this->defaultDescription = ___("use 4111-1111-1111-1111 for successful transaction");
        parent::__construct($di, $config);
    }
    public function getSupportedCurrencies()
    {
        return array_keys(Am_Currency::getFullList()); // support any
    }
    public function getCreditCardTypeOptions()
    {
        return array('visa' => 'Visa', 'mastercard' => 'MasterCard');
    }
    public function getFormOptions()
    {
        return array_merge(parent::getFormOptions(), array(self::CC_CODE));
    }
    public function _doBill(Invoice $invoice, $doFirst, CcRecord $cc, Am_Paysystem_Result $result) {
        if ($cc->cc_number != '4111111111111111')
            $result->setFailed("Please use CC# 4111-1111-1111-1111 for successful payments with demo plugin");
        else {
            $tr = new Am_Paysystem_Transaction_CcDemo($this, $invoice, null, $doFirst);
            $result->setSuccess($tr);
            $tr->processValidated();
        }
    }
    public function isRefundable(InvoicePayment $payment)
    {
        return true;
    }
    public function processRefund(InvoicePayment $payment, Am_Paysystem_Result $result, $amount) {
        $transaction = new Am_Paysystem_Transaction_CcDemo_Refund($this, $payment->getInvoice(), new Am_Request(array('receipt_id'=>'rr')), false);
        $transaction->setAmount($amount);
        $result->setSuccess($transaction);
    }

    public function _initSetupForm(Am_Form_Setup $form) {
        ;
    }
}

class Am_Paysystem_Transaction_CcDemo extends Am_Paysystem_Transaction_CreditCard
{
    protected $_id;
    protected static $_tm;
    public function getUniqId()
    {
        if (!$this->_id)
            $this->_id = 'cc-demo-'.microtime(true);
        return $this->_id;
    }
    public function parseResponse()
    {
    }
    public function getTime()
    {
        if (self::$_tm) return self::$_tm;
        return parent::getTime();
    }
    static function _setTime(DateTime $tm)
    {
        self::$_tm = $tm;
    }
}

class Am_Paysystem_Transaction_CcDemo_Refund extends Am_Paysystem_Transaction_CcDemo
{
    protected $_amount = 0.0;
    
    public function setAmount($amount)
    {
        $this->_amount = $amount;
    }
    public function getAmount()
    {
        return $this->_amount;
    }
}