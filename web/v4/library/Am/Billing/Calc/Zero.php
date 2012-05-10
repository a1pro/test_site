<?php

class Am_Billing_Calc_Zero extends Am_Billing_Calc
{
    public function calculatePiece(stdClass $fields)
    {
        $fields->tax = $fields->discount = $fields->shipping = 0.0;
        $fields->total = moneyRound($fields->price * $fields->qty);
    }
}