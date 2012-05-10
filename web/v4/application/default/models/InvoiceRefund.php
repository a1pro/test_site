<?php
/**
 * Class represents records from table invoice_refund
 * {autogenerated}
 * @property int $invoice_refund_id 
 * @property int $invoice_id 
 * @property int $invoice_payment_id 
 * @property int $user_id 
 * @property string $paysys_id 
 * @property string $receipt_id 
 * @property string $transaction_id 
 * @property datetime $dattm 
 * @property string $currency 
 * @property double $amount 
 * @property int $refund_type 
 * @property double $base_currency_multi 
 * @see Am_Table
 */
class InvoiceRefund extends Am_Record 
{
    /** @var Invoice */
    protected $_invoice;
    /** by customer request - ACCESS does not stopped */
    const REFUND = 0; 
    /** chargeback */
    const CHARGEBACK = 1;
    /** quickly after the order - ACCESS will be revoked */
    const VOID = 2; 

    public function setFromTransaction(Invoice $invoice, Am_Paysystem_Transaction_Abstract $transaction, $refundType)
    {
        $this->dattm  = $transaction->getTime()->format('Y-m-d H:i:s');
        $this->invoice_id       = $invoice->invoice_id;
        $this->user_id        = $invoice->user_id;
        $this->currency         = $invoice->currency;
        $this->amount           = $transaction->getAmount();
        $this->paysys_id        = $transaction->getPlugin()->getId();
        $this->receipt_id       = $transaction->getReceiptId();
        $this->transaction_id   = $transaction->getUniqId();
        $this->refund_type      = $refundType;
        return $this;
    }
    
    public function insert($reload = true)
    {
        if ($this->currency == Am_Currency::getDefault())
            $this->base_currency_multi = 1.0;
        else
            $this->base_currency_multi = $this->getDi()->currencyExchangeTable->getRate($this->currency, sqlDate($this->dattm));
        $ret = parent::insert($reload);
        $this->getDi()->hook->call('refundAfterInsert', array(
            'invoice' => $this->getInvoice(),
            'refund'  => $this,
            'user'    => $this->getInvoice()->getUser(),
        ));
        return $ret;
    }
    /**
     * @return Invoice
     */
    public function getInvoice()
    {
        if (empty($this->_invoice))
            $this->_invoice = $this->getDi()->invoiceTable->load($this->invoice_id);
        return $this->_invoice;
    }
}

class InvoiceRefundTable extends Am_Table {
    protected $_key = 'invoice_refund_id';
    protected $_table = '?_invoice_refund';
    protected $_recordClass = 'InvoiceRefund';

    public function insert(array $values, $returnInserted = false)
    {
        if (empty($values['dattm']))
            $values['dattm'] = $this->getDi()->sqlDateTime;
        return parent::insert($values, $returnInserted);
    }
}
