<?php
class Am_Form_Element_CreditCardExpire extends HTML_QuickForm2_Container_Group
{
    public function __construct($name = null, $attributes = null, $data = null)
    {
        parent::__construct($name, $attributes, $data);
        $require = !$data['dont_require'];
        $years = @$data['years'];
        if (!$years) $years = 10;
        $m = $this->addSelect('m')->loadOptions($this->getMonthOptions());
        if ($require)
            $m->addRule('required', ___('Invalid Expiration Date - Month'));
        $y = $this->addSelect('y')->loadOptions($this->getYearOptions($years));
        if ($require)
            $y->addRule('required', ___('Invalid Expiration Date - Year'));
    }
    public function getMonthOptions()
    {
        $locale = Zend_Registry::get('Am_Locale');
        $months = $locale->getMonthNames();
        
        foreach ($months as $k=>$v) $months[$k] = sprintf('(%02d) %s', $k, $v);
        $months[''] = '';
        ksort($months);
        return $months;
    }
    public function getYearOptions($add){
        $years = range(date('Y'), date('Y')+$add);
        array_unshift($years, '');
        return array_combine($years, $years);
    }
    public function setValue($value)
    {
        if (is_string($value) && preg_match('/^\d{4}$/', $value))
        {
            $value = array(
                'm' => (int)substr($value, 0, 2),
                'y' => '20' . substr($value, 2, 2),
            );
        }
        return parent::setValue($value);
    }
    protected function updateValue()
    {
        $name = $this->getName();
        foreach ($this->getDataSources() as $ds) {
            if (null !== ($value = $ds->getValue($name))) {
                $this->setValue($value);
                return;
            }
        }
        return parent::updateValue();
    }
}

class Am_Form_CreditCard extends Am_Form
{
    const PAYFORM = 'payform';
    const USER_UPDATE = 'user-update';
    const ADMIN_UPDATE = 'admin-update';
    const ADMIN_INSERT = 'admin-insert';
    
    protected $payButtons = array(
        self::PAYFORM => "Subscribe And Pay",
        self::ADMIN_UPDATE => "Update Credit Card Info",
        self::USER_UPDATE => "Update Credit Card Info",
        self::ADMIN_INSERT => "Update Credit Card Info",
    );
    
    /** @var Am_Paysystem_CreditCard */
    protected $plugin;
    protected $formType = self::PAYFORM;
    
    public function __construct(Am_Paysystem_CreditCard $plugin, $formType = self::PAYFORM) {
        $this->plugin = $plugin;
        $this->formType = $formType;
        
        parent::__construct('cc');
    }
    public function init() {
        parent::init();

        $name = $this->addGroup()->setLabel(array(___('Cardholder Name'), sprintf(___('cardholder first and last name, exactly as%son the card'), '<br/>')));
        $name->addRule('required', ___('Please enter credit card holder name'));
        $name_f = $name->addText('cc_name_f', array('size'=>15));
        $name_f->addRule('required', ___('Please enter credit card holder first name'))->addRule('regex', ___('Please enter credit card holder first name'), '|^[a-zA-Z_\' -]+$|');
        $name_l = $name->addText('cc_name_l', array('size'=>15));
        $name_l->addRule('required', ___('Please enter credit card holder last name'))->addRule('regex', ___('Please enter credit card holder last name'), '|^[a-zA-Z_\' -]+$|');

        $options = $this->plugin->getFormOptions();

        if (in_array(Am_Paysystem_CreditCard::CC_COMPANY, $options))
            $company = $this->addText('cc_company')->setLabel(array(___('Company Name'), sprintf(___('the company name associated with the%sbilling address for the transaction'), '<br/>')));
        
        if (in_array(Am_Paysystem_CreditCard::CC_TYPE_OPTIONS, $options))
        {
            $type = $this->addSelect('cc_type')->setLabel(array(___('Credit Card Type'), ___('please select one')))
                ->loadOptions(array_merge(array(''=>'-- ' . ___('Please choose') . ' --'), 
                    $this->plugin->getCreditCardTypeOptions()));
            $type->addRule('required', ___('Please choose a Credit Card Type'));
        }

        if ($this->formType == self::ADMIN_UPDATE)
        {
            $group = $this->addGroup()->setLabel(___('Credit Card Number'), ___('for example: 1111-2222-3333-4444'));
            $group->addStatic('cc');
            $cc = $group->addText('cc_number', array('autocomplete'=>'off', 'size'=>22, 'maxlength'=>22, 'style'=>'display:none'));
            $cc->addRule('regex', ___('Invalid Credit Card Number'), '/^[0-9 -]+$/')
                ->addRule('callback2', 'Invalid CC#', array($this->plugin, 'validateCreditCardNumber'));
            $group->addScript("")->setScript(<<<CUT
$(function(){
    $("input#cc_number-0").closest(".element").click(function(){
        var input = $("input#cc_number-0").detach();
        $(this).empty().append(input.show());
    });
});
CUT
);
        } else {
            $cc = $this->addText('cc_number', array('autocomplete'=>'off', 'size'=>22, 'maxlength'=>22))
                    ->setLabel(___('Credit Card Number'), ___('for example: 1111-2222-3333-4444'));
            $cc->addRule('required', ___('Please enter Credit Card Number'))
                ->addRule('regex', ___('Invalid Credit Card Number'), '/^[0-9 -]+$/')
                ->addRule('callback2', 'Invalid CC#', array($this->plugin, 'validateCreditCardNumber'));
        }

        $expire = $this->addElement(new Am_Form_Element_CreditCardExpire('cc_expire'))
            ->setLabel(array(___('Card Expire'), ___('Select card expiration date - month and year')));

        
        if (in_array(Am_Paysystem_CreditCard::CC_CODE, $options))
        {
            $code = $this->addPassword('cc_code', array('autocomplete'=>'off', 'size'=>4, 'maxlength'=>4))
                    ->setLabel(___('Credit Card Code'), sprintf(___('The "Card Code" is a three- or four-digit security code%sthat is printed on the back of credit cards in the card\'s%ssignature panel (or on the front for American Express cards).'),'<br>','<br>'));
            $code->addRule('required', ___('Please enter Credit Card Code'))
                 ->addRule('regex', ___('Please enter Credit Card Code'), '/^\s*\d{3,4}\s*$/');
        }
        if (in_array(Am_Paysystem_CreditCard::CC_MAESTRO_SOLO_SWITCH, $options))
        {
            $issue = $this->addText('cc_issuenum', array('autocomplete'=>'off', 'size'=>20, 'maxlength'=>22))
                    ->setLabel(___('Card Issue #'), ___('is required for Maestro/Solo/Switch credit cards only'))
                    ->addRule('regex', ___('Invalid Issue Number'), '/^\d+$/');
            $this->addElement(new Am_Form_Element_CreditCardExpire('cc_startdate', null, array('dont_require'=>true, 'years'=>-10)))
                ->setLabel(array(___('Card Start Date'), ___('is required for Maestro/Solo/Switch credit cards only')));
        }
        if (in_array(Am_Paysystem_CreditCard::CC_INPUT_BIN, $options))
        {
            $fieldSet = $this->addFieldset()->setLabel(___('Bank Identification'));
            $fieldSet->addText('_cc_bin_name', array())
               ->setLabel(array(___('Bank Name'), ___('name of the bank which issued the credit card')));
            $fieldSet->addText('_cc_bin_phone', array())
               ->setLabel(array(___('Bank Phone'), ___('customer service phone number listed on back of your credit card')));
        }
        
        $fieldSet = $this->addFieldset(___('Address Info'))->setLabel(array(___('Address Info'), ___('(must match your credit card statement delivery address)')));
        $street = $fieldSet->addText('cc_street')->setLabel(___('Street Address'))
                           ->addRule('required', ___('Please enter Street Address'));
        if (in_array(Am_Paysystem_CreditCard::CC_HOUSENUMBER, $options))
        {
            $house = $fieldSet->addText('cc_housenumber', array('size'=>15))->setLabel(___('Housenumber'))
                    ->addRule('required', ___('Please enter housenumber'));
        }
        $city = $fieldSet->addText('cc_city')->setLabel(___('City'))
                         ->addRule('required', ___('Please enter City'));
        if (in_array(Am_Paysystem_CreditCard::CC_PROVINCE_OUTSIDE_OF_US, $options))
        {
            $province = $fieldSet->addText('cc_province', array('size'=>15))->setLabel(array(___('Billing International Province'), ___('for international provinces outside of US & Canada include the province name here')))
                    ->addRule('required', ___('Please choose state'));
        }

        $zip = $fieldSet->addText('cc_zip')->setLabel(___('ZIP'))
                        ->addRule('required', ___('Please enter ZIP code'));
        $country = $fieldSet->addSelect('cc_country')->setLabel(___('Country'))
            ->setId('f_cc_country')
            ->loadOptions(Am_Di::getInstance()->countryTable->getOptions(true));
        $country->addRule('required', ___('Please enter Country'));

        $group = $fieldSet->addGroup()->setLabel(___('State'));
        $group->addRule('required', ___('Please enter State'));
        /** @todo load correct states */
        $stateSelect = $group->addSelect('cc_state')
            ->setId('f_cc_state')
            ->loadOptions($stateOptions = Am_Di::getInstance()->stateTable->getOptions(@$_REQUEST['cc_country'], true));
        $stateText = $group->addText('cc_state')->setId('t_cc_state');
        $disableObj = $stateOptions ? $stateText : $stateSelect;
        $disableObj->setAttribute('disabled', 'disabled')->setAttribute('style', 'display: none');

        if (in_array(Am_Paysystem_CreditCard::CC_PHONE, $options))
        {
            $phone = $fieldSet->addText('cc_phone', array('size'=>14))->setLabel(___('Phone'))
                    ->addRule('required', ___('Please enter phone number'))
                    ->addRule('regex', ___('Please enter phone number'), '|^[\d() +-]+$|');
        }
        // if free trial set _TPL_CC_INFO_SUBMIT_BUT2
        $buttons = $this->addGroup();
        $buttons->addSubmit('_cc_', array('value'=>
                 '    '
                . $this->payButtons[ $this->formType ]
                .'    '));
        if ($this->formType == self::USER_UPDATE)
        {
            $buttons->addInputButton('_cc_', array('value'=>
                 '    '
                . ___("Back")
                .'    ',
                'onclick'=>'goBackToMember()'));
            $this->addScript("")->setScript("function goBackToMember(){ window.location = window.rootUrl + '/member'; }");
        }
        $this->plugin->onFormInit($this);
    }
    /**
     * Return array of default values based on $user record
     * @param User $user
     */
    public function getDefaultValues(User $user){
        return array(
            'cc_name_f'  => $user->name_f,
            'cc_name_l'  => $user->name_l,
            'cc_street'  => $user->street,
            'cc_city'    => $user->city,
            'cc_state'   => $user->state,
            'cc_country' => $user->country,
            'cc_zip'     => $user->zip,
            'cc_phone'   => $user->phone,
        );
    }
    public function validate() {
        return parent::validate() && $this->plugin->onFormValidate($this);
    }
    public function getValue() {
        $ret = parent::getValue();
        array_walk_recursive($ret, create_function('&$v,$k', '$v=trim($v);'));
        if (!empty($ret['cc_number']))
            $ret['cc_number'] = preg_replace('/\D/', '', $ret['cc_number']);
        return $ret;
    }
    public function toCcRecord(CcRecord $cc){
        $values = $this->getValue();
        foreach ($values as $k=>$v)
            if (is_array($v) && !empty($v['m']))
                $values[$k] = sprintf('%02d%02d', $v['m'], substr($v['y'], -2));
        unset($values['_cc_bin_name']);
        unset($values['_cc_bin_phone']);
        unset($values['a']);
        unset($values['id']);
        if( !empty($values['cc_code']))
            $cc->setCvv($values['cc_code']);
        unset($values['cc_code']);
        unset($values['action']);
        $cc->setForInsert($values);
    }
}
