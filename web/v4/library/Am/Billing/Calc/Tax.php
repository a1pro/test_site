<?php

// @todo 2 different taxes
class Am_Billing_Calc_Tax extends Am_Billing_Calc
{
    /** @var float */
    protected $tax_rate = 0.0;
    
    public function getTaxRate(User $user){
        if (!Am_Di::getInstance()->config->get('use_tax'))
            return 0;
        if (Am_Di::getInstance()->config->get('tax_type') == 1)
        { // global tax
            return Am_Di::getInstance()->config->get('tax_value');
        } elseif ($user && (Am_Di::getInstance()->config->get('tax_type') == 2)) { //regional tax
            foreach (Am_Di::getInstance()->config->get('regional_taxes') as $t){
                if ($t['state'] && ($t['state'] == $user->get('state')) && ($t['country'] == $user->get('country')))
                    return $t['tax_value'];
                if (!$t['state'] && $t['country'] && ($t['country'] == $user->get('country')))
                    return $t['tax_value'];
            }
        }
        return 0;
    }
    public function calculate(Invoice $invoiceBill) {
        $user = $invoiceBill->getUser();
        if (!$user) $user = Am_Di::getInstance()->userRecord;
        $this->tax_rate = $this->getTaxRate($user);
        $invoiceBill->tax_rate = $this->tax_rate;
        parent::calculate($invoiceBill);
    }
    public function calculatePiece(stdClass $fields)
    {
        if (!$fields->no_tax)
            $fields->tax = moneyRound($fields->total * $this->tax_rate / 100);
        else
            $fields->tax = 0.0;
        $fields->total += $fields->tax;
    }
}
