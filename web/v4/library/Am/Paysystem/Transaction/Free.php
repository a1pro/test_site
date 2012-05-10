<?php

class Am_Paysystem_Transaction_Free extends Am_Paysystem_Transaction_Abstract
{
    public function __construct($plugin)
    {
        parent::__construct($plugin, Am_Request::createEmpty());
    }
    public function setInvoice(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }
    public function processValidated()
    {
        $this->invoice->addAccessPeriod($this);
        $this->invoice->updateRebillDate(); // todo - ???
    }
    public function getUniqId()
    {
        return $_SERVER['REMOTE_ADDR'] . '-' . $this->plugin->getDi()->time;
    }
}
