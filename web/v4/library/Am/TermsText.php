<?php

/**
 * Render subscription terms to text
 */
class Am_TermsText
{
    protected $_source;
    function __construct($source)
    {
        $this->_source = $source;
    }
    public function __get($name)
    {
        if ($this->_source instanceof Invoice)
        {
            if ($name == 'start_date') return null;
            $name = preg_replace('/_price$/', '_total', $name);
        }
        if ($this->_source instanceof BillingPlan)
        {
            if (!in_array($name, $this->_source->getTable()->getFields(true)))
                return $this->_source->getProduct()->get($name);
        }
        return $this->_source->$name;
    }
    public function __call($name,  $arguments)
    {
        return call_user_func_array(array($this->_source, $name), $arguments);
    }
    public function __toString()
    {
        return $this->getString();
    }
    /**
     * Function returns product subscription terms as text
     * @return string
     */
    function getString(){
        if (is_null($this->first_price) || !strlen($this->first_period))
            return "";
        
        $price1 = $this->first_price;
        $price2 = $this->second_price;
        if ($price1 instanceof Am_Currency) $price1 = $price1->getValue();
        if ($price2 instanceof Am_Currency) $price2 = $price2->getValue();
        
        $c1 = ($price1 > 0) ? $this->getCurrency($this->first_price) : 'Free';
        $c2 = ($price2 > 0) ? $this->getCurrency($this->second_price) : 'free';
        
        $p1 = new Am_Period($this->first_period);
        $p2 = new Am_Period($this->second_period);
        $ret = (string)$c1;
        if (!$p1->isLifetime())
            if ($this->rebill_times)
                $ret .= $p1->getText(" for first %s", true);
            else
                $ret .= $p1->getText(" for %s");
        if ($this->rebill_times)
        {
            if (!$p1->equalsTo($p2) || ($price1 != $price2))
                $ret .= ", then ";
            else
                $ret = "";
            $ret .= (string)$c2 . $p2->getText(" for each %s");
            if ($this->rebill_times < IProduct::RECURRING_REBILLS)
                $ret .= sprintf(", for %d installments", $this->rebill_times);
        };
        return preg_replace('/[ ]+/', ' ', $ret);
    }
    
}