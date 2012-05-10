<?php

class Am_Billing_Calc_Shipping extends Am_Billing_Calc
{
    public function calculatePiece(stdClass $fields)
    {
        $fields->shipping = 0;
    }
}
