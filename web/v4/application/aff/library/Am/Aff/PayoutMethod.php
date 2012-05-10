<?php

abstract class Am_Aff_PayoutMethod
{
    static private $enabled = array();

    public function getId()
    {
        return lcfirst(str_ireplace('Am_Aff_PayoutMethod_', '', get_class($this)));
    }
    public function getTitle()
    {
        return ucfirst(str_ireplace('Am_Aff_PayoutMethod_', '', get_class($this)));
    }
    /**
     * Generate and send file or make actual payout if possible
     */
    abstract public function payCommissions(array $payoutRows);
    abstract function addFields(Am_CustomFieldsManager $m);
    protected function sendCsv($filename, array $rows)
    {
        header('Cache-Control: maxage=3600');
        header('Pragma: public');
        header("Content-type: application/csv");
        header("Content-Disposition: attachment; filename=$filename");
        
    }

    static function static_addFields()
    {
        $fieldsManager = Am_Di::getInstance()->userTable->customFields();
        foreach (self::getEnabled() as $o)
            $o->addFields($fieldsManager);
    }
    /** @return Am_Aff_PayoutMethod[] */
    static function getEnabled()
    {
        if (!self::$enabled)
        foreach (Am_Di::getInstance()->config->get('aff.payout_methods', array()) as $methodName)
        {
            $className = __CLASS__ . '_' . ucfirst($methodName);
            if (!class_exists($className)) continue;
            $o = new $className;
            self::$enabled[$o->getId()] = $o;
        }
        return self::$enabled;
    }
    static function getAvailableOptions()
    {
        $ret = array();
        foreach (get_declared_classes() as $className)
            if (strpos($className, __CLASS__ . '_')===0)
            {
                $o = new $className;
                $ret[$o->getId()] = $o->getTitle();
            }
        return $ret;
    }
    static function getEnabledOptions()
    {
        $ret = array();
        foreach (self::getEnabled() as $o)
            $ret[$o->getId()] = $o->getTitle();
        return $ret;
    }
}

class Am_Aff_PayoutMethod_Paypal extends Am_Aff_PayoutMethod
{
    public function payCommissions(array $payoutRows) {
        $tm = Am_Di::getInstance()->time;
        foreach ($rows as $r){
            $r['to_pay'] = sprintf('%.2f', $r['to_pay']);
            $rows[] = "{$r[data][aff_paypal_email]}\t$r[to_pay]\tUSD\t$r[user_id]\tAffiliate commission for $dat1 - $dat2\r\n";
        }
        $this->sendCsv("paypal-commission-$tm.txt", $rows);
    }
    public function addFields(Am_CustomFieldsManager $m) {
        $m->add(new Am_CustomFieldText('aff_paypal_email', 'Affiliate Payout - Paypal E-Mail address', 'for affiliate commission payouts'))->size = 40;
    }
}

class Am_Aff_PayoutMethod_Check extends Am_Aff_PayoutMethod
{
    public function getTitle() {
        return "Offline Check";
    }
    public function payCommissions(array $payoutRows) {
        $tm = Am_Di::getInstance()->time;
        header("Content-Disposition: attachment; filename=");
        $rows[] = "First Name;Last Name;Street Address;City;State;ZIP;Country;Check Amount\n";
        foreach ($rows as $r){
            $r['to_pay'] = sprintf('%.2f', $r['to_pay']);
            $rows[] = "$r[name_f];$r[name_l];$r[street];$r[city];$r[state];$r[zip];$r[country];$r[to_pay]\n";
        }
        $this->sendCsv("check-commission-$tm.csv", $rows);
    }
    public function addFields(Am_CustomFieldsManager $m) {
        $m->add(new Am_CustomFieldText('aff_check_payable_to', 'Affiliate Check - Payable To'))->size = 40;
        $m->add(new Am_CustomFieldText('aff_check_street', 'Affiliate Check - Street Address'))->size = 40;
        $m->add(new Am_CustomFieldText('aff_check_city', 'Affiliate Check - City'))->size = 40;
        $m->add(new Am_CustomFieldText('aff_check_country', 'Affiliate Check - Country'));
        $m->add(new Am_CustomFieldText('aff_check_state', 'Affiliate Check - State'));
        $m->add(new Am_CustomFieldText('aff_check_zip', 'Affiliate Check - ZIP Code'))->size = 10;
    }
}

class Am_Aff_PayoutMethod_Ikobo extends Am_Aff_PayoutMethod
{
    public function payCommissions(array $payoutRows) {
    }
    public function addFields(Am_CustomFieldsManager $m) {
        $m->add(new Am_CustomFieldText('aff_ikobo_email', 'Affiliate Payout - iKobo E-Mail Address'))->size = 40;
    }
}

class Am_Aff_PayoutMethod_Moneybookers extends Am_Aff_PayoutMethod
{
    public function payCommissions(array $payoutRows) {
    }
    public function addFields(Am_CustomFieldsManager $m) {
        $m->add(new Am_CustomFieldText('aff_moneybookers_email', 'Affiliate Payout - Moneybookers Account ID'))->size = 40;
    }
}

class Am_Aff_PayoutMethod_Safepay extends Am_Aff_PayoutMethod
{
    public function getTitle() {
        return "SafePaySolutions";
    }
    public function payCommissions(array $payoutRows) {
    }
    public function addFields(Am_CustomFieldsManager $m) {
        $m->add(new Am_CustomFieldText('aff_safepay_email', 'Affiliate Payout - SafePay account id'))->size = 40;
    }
}

