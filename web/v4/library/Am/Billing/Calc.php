<?php

/**
 * Base class for InvoiceItem amount calculations
 * @package Billing
 */
abstract class Am_Billing_Calc 
{
    /** @var Invoice */
    protected $invoiceBill;
    /** @var string */
    protected $currentPrefix; // to be retreived in calculatePiece
    
    // prefix of field names to calculate, order makes se
    static public $_prefixes = array('first_', 'second_', );
    
    // fields to pass into the function
    static public $_noPrefixFields = array(
        'qty',
        'no_tax',
        'is_tangible',
    );
    static public $_prefixFields = array(
        'price',
        'discount',
        'tax',
        'total',
        'shipping',
    );
    
    /**
     * Calculate piece of information
     * @param stdClass $fields to be calculated and modified 
     */
    public function calculatePiece(stdClass $fields) { }
    
    public function calculate(Invoice $invoiceBill)
    {
        $this->invoiceBill = $invoiceBill;
        foreach ($invoiceBill->getItems() as $item)
        {
            $this->item = $item;
            foreach (self::$_prefixes as $prefix)
            {
                $this->currentPrefix = $prefix;
                $fields = new stdClass;
                foreach (self::$_noPrefixFields as $k)
                    $fields->$k = @$item->$k;
                foreach (self::$_prefixFields as $k)
                {
                    $kk = $prefix ? $prefix .$k : $k;
                    if (isset($fields->$kk))
                        throw new Am_Exception_InternalError("Field is already defined [$k]");
                    $fields->$k = @$item->$kk;
                }
                $this->calculatePiece($fields);
                foreach (self::$_noPrefixFields as $k)
                    $item->$k = $fields->$k;
                foreach (self::$_prefixFields as $k)
                {
                    $kk = $prefix ? $prefix . $k : $k;
                    $item->$kk = $fields->$k;
                }
            }
            unset($this->item);
        }
    }
}

