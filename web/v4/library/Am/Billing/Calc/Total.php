<?php

class Am_Billing_Calc_Total extends Am_Billing_Calc
{
    public function calculate(Invoice $invoiceBill) {
        foreach ($invoiceBill->getItems() as $item)
            $item->_calculateTotal();
    }
}