<?php

class Am_Billing_Calc_Coupon extends Am_Billing_Calc
{
    /** @var Coupon */
    protected $coupon;
    protected $user;
    
    public function calculate(Invoice $invoiceBill)
    {
        $this->coupon = $invoiceBill->getCoupon();
        $this->user = $invoiceBill->getUser();
        $isFirstPayment = $invoiceBill->isFirstPayment();
        foreach ($invoiceBill->getItems() as $item) {
            $item->first_discount = $item->second_discount = 0;
            $item->_calculateTotal();
        }
        if (!$this->coupon) return;
        if ($this->coupon->getBatch()->discount_type == Coupon::DISCOUNT_PERCENT){
            foreach ($invoiceBill->getItems() as $item) {
                if ($this->coupon->isApplicable($item->item_type, $item->item_id, $isFirstPayment))
                    $item->first_discount = moneyRound($item->first_total * $this->coupon->getBatch()->discount / 100 );
                if ($this->coupon->isApplicable($item->item_type, $item->item_id, false))
                    $item->second_discount = moneyRound($item->second_total * $this->coupon->getBatch()->discount / 100 );
            }
        } else { // absolute discount
            $discountFirst = $this->coupon->getBatch()->discount;
            $discountSecond = $this->coupon->getBatch()->discount;

            $first_discountable = $second_discountable = array();
            $first_total = $second_total = 0;
            $second_total = array_reduce($second_discountable, create_function('$s,$item', 'return $s+=$item->second_total;'), 0);
            foreach ($invoiceBill->getItems() as $item) {
                if ($this->coupon->isApplicable($item->item_type, $item->item_id, $isFirstPayment)) {
                    $first_total += $item->first_total;
                    $first_discountable[] = $item;
                }
                if ($this->coupon->isApplicable($item->item_type, $item->item_id, false)) {
                    $second_total += $item->second_total;
                    $second_discountable[] = $item;
                }
            }
            if ($first_total) {
                $k = max(0,min($discountFirst / $first_total, 1)); // between 0 and 1!
                foreach ($first_discountable as $item) {
                    $item->first_discount = moneyRound($item->first_total * $k);
                }
            }
            if ($second_total) {
                $k = max(0,min($discountSecond / $second_total, 1)); // between 0 and 1!
                foreach ($second_discountable as $item) {
                    $item->second_discount = moneyRound($item->second_total * $k);
                }
            }
        }
        foreach ($invoiceBill->getItems() as $item) {
            $item->_calculateTotal();
        }
    }
}
