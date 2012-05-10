<?php

/**
 * Class makes necessary calls to process payment for invoice and do all necessary
 * actions in controller
 */
class Am_Paysystem_PayProcessMediator 
{
    /** @var Am_Controller */
    protected $controller;
    /** @var Invoice */
    protected $invoice;
    /** @var Am_Paysystem_Result */
    protected $result;
    
    protected $onNormalExit;
    protected $onSuccess;
    protected $onFailure;
    protected $onAction;
    
    public function __construct(Am_Controller $controller, Invoice $invoice)
    {
        $this->controller = $controller;
        $this->invoice = $invoice;
    }
    
    public function setOnAction($callback)
    {
        $this->onAction = $callback;
        return $this;
    }
    public function setOnSuccess($callback)
    {
        $this->onSuccess = $callback;
        return $this;
    }
    public function setOnFailure($callback)
    {
        $this->onFailure = $callback;
        return $this;
    }
    /**
     * This function is likely never returns
     * but anyway handle result and exceptions
     * @return Am_Paysystem_Result
     */
    function process()
    {
        $err = $this->invoice->validate();
        if ($err)
            throw new Am_Exception_InputError($err[0]);
        $this->invoice->save();
        
        $plugin = Am_Di::getInstance()->plugins_payment->loadGet($this->invoice->paysys_id);
        
        $this->result = new Am_Paysystem_Result();
        $plugin->processInvoice($this->invoice, $this->controller->getRequest(), $this->result);
        
        if ($this->result->isSuccess() || $this->result->isFailure())
            if ($transaction = $this->result->getTransaction())
            {
                $transaction->setInvoice($this->invoice);
                $transaction->process();
            }
        
        if ($this->result->isSuccess()) {
            $url = REL_ROOT_URL . "/thanks?id=" . $this->invoice->getSecureId('THANKS');
            $this->callback($this->onSuccess);
            $this->controller->redirectLocation($url, ___("Invoice processed"), ___("Invoice processed successfully"));
            // no return Am_Exception_Redirect
        } elseif ($this->result->isAction()) {
            $this->callback($this->onAction);
            $this->result->getAction()->process($this->controller);
            // no return Am_Exception_Redirect
        } else {//  ($result->isFailure()) {
            $this->callback($this->onFailure);
        }
        return $this->result;
    }
    
    protected function callback($callback)
    {
        if ($callback)
            call_user_func($callback, $invoice, $controller, $this, $this->result);
    }
}