<?php 
class Am_Form_Admin_Product extends Am_Form_Admin {
    protected $plans = array();

    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission('grid_product');
    }
    public function __construct($plans)
    {
        $this->plans = (array)$plans;
        parent::__construct('admin-product');
    }

    function addBillingPlans()
    {
        $plans = $this->plans;
        if (!$plans)
            $plans[] = array(
                'title' => ___('Default Billing Plan'),
                'plan_id'    => 0,
            );
        $plans[] = array(
            'title' => 'TEMPLATE',
            'plan_id'    => 'TPL',
        );
        foreach ($plans as $plan)
        {
            $fieldSet = $this->addElement('fieldset', '', array('id'=>'plan-'.$plan['plan_id'], 'class'=>'billing-plan'))
                    ->setLabel('<span class="plan-title-text">' . ($plan['title'] ? $plan['title'] : "title - click to edit") .  '</span>' .
                        sprintf(' <input type="text" class="plan-title-edit" name="_plan[%s][title]" value="%s" size="30" style="display: none" />',
                        $plan['plan_id'], Am_Controller::escape($plan['title'])));
            $this->addBillingPlanElements($fieldSet, $plan['plan_id']);
        }
    }
    function addBillingPlanElements(HTML_QuickForm2_Container $fieldSet, $plan)
    {
        $prefix = '_plan['.$plan.'][';
        $suffix = ']';
        $firstPrice = $fieldSet->addElement('text', $prefix.'first_price'.$suffix)
                ->setLabel(___("First Price\n".
                    "price of first period of subscription"));
        $firstPrice->addRule('gte', ___('must be equal or greather than 0'), 0.0)
                    ->addRule('regex', ___('must be a number in format 99 or 99.99'), '/^(\d+(\.\d+)?|)$/');

        $firstPeriod = $fieldSet->addElement('period', $prefix.'first_period'.$suffix)
                ->setLabel(___('First Period'));

        $group = $fieldSet->addGroup()->setLabel(
            ___("Rebill Times\n".
            "This is the number of payments which\n". 
            "will occur at the Second Price"));

        $sel = $group->addElement('select', $prefix.'_rebill_times'.$suffix)->setId('s_rebill_times');
        $sel->addOption(___('No more charges'), 0);
        $sel->addOption(___('Charge Second Price Once'), 1);
        $sel->addOption(___('Charge Second Price x Times'), 'x');
        $sel->addOption(___('Rebill Second Price until cancelled'), IProduct::RECURRING_REBILLS);
        $txt = $group->addElement('text', $prefix.'rebill_times'.$suffix, array('size'=>5, 'maxlength'=>6))->setId('t_rebill_times');

        $secondPrice = $fieldSet->addElement('text', $prefix.'second_price'.$suffix)
                ->setLabel(
                    ___("Second Price\n".
                    "price that must be billed for second and\n".
                    "the following periods of subscription"));
        $secondPrice->addRule('gte', ___('must be equal or greather than 0.0'), 0.0)
                ->addRule('regex', ___('must be a number in format 99 or 99.99'), '/^\d+(\.\d+)?$/');

        $secondPeriod = $fieldSet->addElement('period', $prefix.'second_period'.$suffix)
                ->setLabel(___('Second Period'));

        $secondPeriod = $fieldSet->addElement('text', $prefix.'terms'.$suffix, array('size' => 40, 'class'=>'translate'))
                ->setLabel(___("Terms Text\nautomatically calculated if empty"));

        foreach (Am_Di::getInstance()->billingPlanTable->customFields()->getAll() as $k => $f)
        {
            $el = $f->addToQf2($fieldSet);
            $el->setName($prefix.$el->getName().$suffix);
        }
    }
    
    function checkBillingPlanExists(array $vals)
    {
        foreach ($vals['_plan'] as $k => $v)
        {
            if ($k === 'TPL') continue;
            if (strlen($v['first_price']) && strlen($v['first_period']))
                return true;
        }
        return false;
    }

    function init() {
        $this->addElement('hidden', 'product_id');
        
        $this->addRule('callback', ___('At least one billing plan must be added'), array($this, 'checkBillingPlanExists'));
        
        /* General Settings */
        $fieldSet = $this->addElement('fieldset', 'general')
                ->setLabel(___('General Settings'));

        $fieldSet->addElement('text', 'title', array('size' => 40, 'class'=>'translate'))
                ->setLabel(___('Title'))
                ->addRule('required');

        $fieldSet->addElement('textarea', 'description', array('class'=>'translate'))
                ->setLabel(___(
                    "Description\n".
                    "displayed to visitors on order page below the title"))
                ->setAttribute('cols', 40)->setAttribute('rows', 2);

        $fieldSet->addElement('textarea', 'comment')
                ->setLabel(___("Comment\nfor admin reference"))
                ->setAttribute('cols', 40)->setAttribute('rows', 2);

        $gr = $fieldSet->addGroup()->setSeparator(' ')
                ->setLabel(___('Product Categories'));
        $gr->addMagicSelect('_categories')
            ->loadOptions(Am_Di::getInstance()->productCategoryTable->getAdminSelectOptions());
        $gr->addStatic()->setContent('<a href="'.Am_Controller::escape(REL_ROOT_URL).'/admin-product-categories">'.___('Edit Groups').'</a>');
        
        /* Billing Settings */
        $fieldSet = $this->addElement('fieldset', 'billing')
                ->setLabel(___('Billing'));

        if (Am_Di::getInstance()->config->get('use_tax'))
            $fieldSet->addElement('advcheckbox', 'no_tax')
               ->setLabel(___('Do not Apply Tax?'));

        $fieldSet->addElement('select', 'currency')
                ->setLabel(array(___("Currency"), ___("you can choose from list of currencies supported by paysystems")))
                ->loadOptions(Am_Currency::getSupportedCurrencies('ru_RU'));

//        if (Am_Di::getInstance()->config->get('product_paysystem')) {
//            $fieldSet->addElement('select', 'paysys_id')
//                ->setLabel(array('Payment System',
//                "choose a payment system to be used exclusively with this product"))
//                ->loadOptions(array('' => '* Choose a paysystem *') + Am_Di::getInstance()->paysystemList->getOptions());
//        };
//
        $this->addBillingPlans();

        /* Advanced Settings */
//        $fieldSet = $this->addElement('fieldset', 'advanced')
//                ->setId('advanced')
//                ->setLabel(___('Advanced'));
        
//        $fieldSet->addElement('text', 'trial_group', array('size' => 20))
//                ->setLabel(___("Trial Group\n".
//                'If this field is filled-in, user will be unable to order the product
//                    twice. It is extermelly useful for any trial product. This field
//                    can have different values for different products, then "trial history"
//                    will be separate for these groups of products.
//                    If your site offers only one level of membership,
//                    just enter "1" to this field. '));

        /* Product availability */
        $fieldSet = $this->addElement('fieldset', 'avail')
                ->setLabel(___('Product Availability'));
        
        $this->insertAvailablilityFields($fieldSet);

        $sdGroup = $fieldSet->addGroup()->setLabel(___(
            "Start Date Calculation\n". 
            "rules for subscription start date calculation.\n".
            "MAX date from alternatives will be chosen.\n".
            "This settings has no effect for recurring subscriptions"));
        
        $sd = $sdGroup->addSelect('start_date', 
            array('multiple'=>'mutliple', 'id'=>'start-date-edit',));
        $sd->loadOptions(array(
            Product::SD_PRODUCT => ___('Last existing subscription date of this product'),
            Product::SD_GROUP   => ___('Last expiration date in the renewal group: '),
            Product::SD_FIXED    => ___('Fixed date'),
            Product::SD_PAYMENT => ___('Payment date'),
        ));
        $sdGroup->addSelect('renewal_group', array('style'=>'display:none; font-size: xx-small'), 
            array('intrinsic_validation' => false, 'options' => Am_Di::getInstance()->productTable->getRenewalGroups()));
        $sdGroup->addDate('start_date_fixed', array('style'=>'display:none; font-size: xx-small'));
        
        $fieldSet->addElement('text', 'sort_order')
                ->setLabel(___(
                    "Sorting order\n" .
                    "This is a numeric field. Products are sorted\n".
                    "according to this field value, then alphabetically"));


        /* Additional Options */
        $fieldSet = $this->addElement('fieldset', 'additional')
                ->setId('additional')
                ->setLabel(___('Additional'));

        $this->insertAdditionalFields();
        
        Am_Di::getInstance()->hook->call(Am_Event::PRODUCT_FORM, array('form' => $this));
        
    }


    function insertAvailablilityFields($fieldSet) {
        
        $fieldSet->addAdvCheckbox('is_disabled')->setLabel(___("Is Disabled?\n".
            "disable product ordering, hide it from signup and renewal forms"));
        // add require another subscription field
        
        $require_options = array(/*''  => "Don't require anything (default)"*/);
        $prevent_options = array(/*''  => "Don't prevent anything (default)"*/);
        foreach (Am_Di::getInstance()->productTable->getOptions() as $id => $title) {
            $title = Am_Controller::escape($title);
            $require_options['ACTIVE-'. $id] = ___('ACTIVE subscription for %s', '"'.$title.'"');
            $require_options['EXPIRED-'.$id] = ___('EXPIRED subscription for %s', '"'.$title.'"');
            $prevent_options['ACTIVE-'. $id] = ___('ACTIVE subscription for %s', '"'.$title.'"');
            $prevent_options['EXPIRED-'.$id] = ___('EXPIRED subscription for %s', '"'.$title.'"');
        }
        $fieldSet->addMagicSelect('require_other', array('multiple'=>'multiple', 'class'=>'magicselect'))
                ->setLabel(___("To order this product user must have an\n".
                "when user orders this subscription, it will be checked\n".
                "that user has one from the following subscriptions"
                ))
                ->loadOptions($require_options);

        $fieldSet->addMagicSelect('prevent_if_other', array('multiple'=>'multiple', 'class'=>'magicselect'))
                ->setLabel(___("Disallow ordering of this product if user has\n".
                "when user orders this subscription, it will be checked\n".
                "that he has no any from the following subscriptions"
                ))
                ->loadOptions($prevent_options);
    }

    function insertAdditionalFields() {
        $fieldSet = $this->getElementById('additional');
        $fields = Am_Di::getInstance()->productTable->customFields()->getAll();
        $exclude = array();
        foreach ($fields as $k => $f)
            if (!in_array($f->name, $exclude))
                $el = $f->addToQf2($fieldSet);
    }

}

class Am_Grid_Filter_Product extends Am_Grid_Filter_Abstract
{
    
    protected function applyFilter()
    {
        $query = $this->grid->getDataSource();
        if ($s = @$this->vars['filter']['text'])
        {
            $query->add(new Am_Query_Condition_Field('title', 'LIKE', '%'.$s.'%'))
                ->_or(new Am_Query_Condition_Field('product_id', '=', $s));
        }
        if ($category_id = @$this->vars['filter']['category_id'])
        {
            $query->leftJoin("?_product_product_category", "ppc");
            $query->addWhere("ppc.product_category_id=?d", $category_id);
        }
    }
    public function renderInputs()
    {
        $options = array('' => '-' . ___('Filter by Category') . '-');
        $options = $options + 
            Am_Di::getInstance()->productCategoryTable->getAdminSelectOptions(
                array(ProductCategoryTable::COUNT=>true)); 
        $options = Am_Controller::renderOptions(
            $options,
            @$this->vars['filter']['category_id']
        );
        $out = sprintf('<select onchange="this.form.submit()" name="_product_filter[category_id]">%s</select>&nbsp;'.PHP_EOL, $options);
        $this->attributes['value'] = (string)$this->vars['filter']['text'];
        $out .= $this->renderInputText('filter[text]');
        return $out;
    }
}

class Am_Grid_Action_Group_ProductAssignCategory extends Am_Grid_Action_Group_Abstract
{
    protected $needConfirmation = true;
    protected $remove = false;
    public function __construct($removeCategory = false)
    {
        $this->remove = (bool)$removeCategory;
        parent::__construct(
            !$removeCategory ? "product-assign-category":"product-remove-category", 
            !$removeCategory ? ___("Assign Category"):___("Remove Category")
            );
    }
    
    public function renderConfirmationForm($btn = "Yes, assign", $page = null, $addHtml = null) 
    {
        $select = sprintf('
            <select name="%s_category_id">
            %s
            </select><br /><br />'.PHP_EOL,
            $this->grid->getId(),
            Am_Controller::renderOptions(Am_Di::getInstance()->productCategoryTable->getAdminSelectOptions())
            );
        return parent::renderConfirmationForm($this->remove ? ___("Yes, remove category") :  ___("Yes, assign category"), null, $select);
    } 
    
    /**
     * @param int $id
     * @param Product $record
     */
    public function handleRecord($id, $record)
    {
        $category_id = $this->grid->getRequest()->getInt('category_id');
        if (!$category_id) throw new Am_Exception_InternalError("category_id empty");
        $categories = $record->getCategories();
        if ($this->remove)
        {
            if (!in_array($category_id, $categories)) return;
            foreach ($categories as $k => $id)
                if ($id == $category_id) unset($categories[$k]);
        } else {
            if (in_array($category_id, $categories)) return;
            $categories[] = $category_id;
        }
        $record->setCategories($categories);
    }
}

class AdminProductsController extends Am_Controller_Grid
{
    
    public function preDispatch()
    {
        parent::preDispatch();
        $this->getDi()->billingPlanTable->toggleProductCache(false);
        $this->getDi()->productTable->toggleCache(false);
    }
    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission('grid_product');
    }
    
    public function createGrid()
    {
        $ds = new Am_Query($this->getDi()->productTable);
        $ds->setOrderRaw("0+IFNULL(sort_order,0),title");
        $grid = new Am_Grid_Editable('_product', ___("Products"), $ds, $this->_request, $this->view);
        $grid->setRecordTitle(___('Product'));
        $grid->actionAdd(new Am_Grid_Action_Group_ProductAssignCategory(false));
        $grid->actionAdd(new Am_Grid_Action_Group_ProductAssignCategory(true));
        $grid->actionAdd(new Am_Grid_Action_Group_Delete);
        $grid->addGridField(new Am_Grid_Field('product_id', '#', true, '', null, '5%'));
        $grid->addGridField(new Am_Grid_Field('title', ___('Title'), true, '', null, '50%'))->setRenderFunction(array($this, 'renderTitle'));
        $grid->addGridField(new Am_Grid_Field('pgroup', ___('Product Categories'), false))->setRenderFunction(array($this, 'renderPGroup'));
        $grid->addGridField(new Am_Grid_Field('terms', ___('Default Billing Terms')))->setRenderFunction(array($this, 'renderTerms'));
        $grid->addGridField(new Am_Grid_Field('sort_order', ___('Sort Order')));
        $grid->actionGet('edit')->setTarget('_top');
        $grid->actionAdd(new Am_Grid_Action_LiveEdit('sort_order'));

        $grid->setFormValueCallback('start_date', array('RECORD', 'getStartDate'),  array('RECORD', 'setStartDate'));
        $grid->setFormValueCallback('require_other', array('RECORD', 'unserializeList'),  array('RECORD', 'serializeList'));
        $grid->setFormValueCallback('prevent_if_other', array('RECORD', 'unserializeList'),  array('RECORD', 'serializeList'));
        
        $grid->addCallback(Am_Grid_Editable::CB_AFTER_SAVE, array($this, 'afterSave'));
        $grid->addCallback(Am_Grid_Editable::CB_VALUES_TO_FORM, array($this, 'valuesToForm'));
//            ->setInputSize(3)->setGetFunction(array($this, 'getSortOrder'));
///    protected $liveEditFields = array('title', 'sort_order');
        
        $grid->setForm(array($this, 'createForm'));
        $grid->setFilter(new Am_Grid_Filter_Product);
        
        $grid->actionAdd(new Am_Grid_Action_Url('categories', ___('Edit Groups'), 
                REL_ROOT_URL . '/admin-product-categories'))
            ->setType(Am_Grid_Action_Abstract::NORECORD)
            ->setTarget('_top');

// product upgrades are hidden until fully implemented
//        $grid->addCallback(Am_Grid_Editable::CB_RENDER_STATIC, array($this, 'renderProductStatic'));
        return $grid;
    }

    function renderPGroup(Product $p)
    {
        $res = array();
        $options = $this->getDi()->productCategoryTable->getAdminSelectOptions();
        foreach ($p->getCategories() as $pc_id)
        {
            $res[] = $options[$pc_id];
        }
        return $this->renderTd(implode(", ", $res));
    }
    
    function renderTitle(Product $p)
    {
        $v = Am_Controller::escape($p->title);
        if ($p->is_disabled)
        {
            $v = '<strike>' . $v . '</strike>';
        }
        return $this->renderTd($v, false);
    }
    
    function renderProductStatic(& $out, Am_Grid_Editable $grid)
    {
        $url = $this->escape(REL_ROOT_URL . '/admin-products/upgrades');
        $txt = ___("Manage Product Upgrade Paths");
        $out .= "<a href='$url'>$txt</a>";
    }
    
    function renderTerms(Product $record)
    {
        if (!$record->getBillingPlan(false)) return;
        $t = $this->escape($record->getBillingPlan()->getTerms());
        return $this->renderTd($t);
    }

    function createForm() {
        $record = $this->grid->getRecord();
        $plans = array();
        foreach ($record->getBillingPlans() as $plan)
            $plans[$plan->pk()] = $plan->toArray();
        $form = new Am_Form_Admin_Product($plans);
        return $form;
    }

    function valuesToForm(& $ret, Product $record){
        $ret['_categories'] = $record->getCategories();
        $ret['_plan'] = array();
        foreach ($record->getBillingPlans() as $plan)
        {
            $arr = $plan->toArray();
            if (!empty($arr['rebill_times']))
            {
                $arr['_rebill_times'] = $arr['rebill_times'];
                if (!in_array($arr['rebill_times'], array(0,1,IProduct::RECURRING_REBILLS)))
                    $arr['_rebill_times'] = 'x';
            };
            foreach (array('first_period', 'second_period') as $f)
                if (array_key_exists($f, $arr)) {
                    $arr[$f] = new Am_Period($arr[$f]);
                }
            $ret['_plan'][$plan->pk()] = $arr;
        }
    }
    
    public function afterSave(array &$values, Product $product) {
        $this->updatePlansFromRequest($product, $values, $product->getBillingPlans());
        $product->setCategories(empty($values['_categories']) ? array() : $values['_categories']);
    }
    /** @return array BillingPlan including existing, $toDelete - but existing not found in request */
    public function updatePlansFromRequest(Product $record, $values, $existing = array())
    {
        // we access "POST" directly here as there is no access to new added
        // fields from the form!
        $plans = $_POST['_plan'];
        unset($plans['TPL']);
        foreach ($plans as $k => & $arr)
        {
            if ($arr['_rebill_times']!='x')
                $arr['rebill_times'] = $arr['_rebill_times'];
            try {
                $p = new Am_Period($arr['first_period']['c'], $arr['first_period']['u']);
                $arr['first_period'] = (string)$p;
            } catch (Am_Exception_InternalError $e) {
                unset($plans[$k]);
                continue;
            }
            try {
                $p = new Am_Period($arr['second_period']['c'], $arr['second_period']['u']);
                $arr['second_period'] = (string)$p;
            } catch (Am_Exception_InternalError $e) {
                $arr['second_period'] = '';
            }
        }
        foreach ($existing as $k => $plan)
            if (empty($plans[$plan->pk()]))
            {
                $plan->delete();
            } else {
                $plan->setForUpdate($plans[$plan->pk()]);
                $plan->update();
                unset($plans[$plan->pk()]);
            }
        foreach ($plans as $id => $a)
        {
            $plan = $this->getDi()->billingPlanRecord;
            $plan->setForInsert($a);
            $plan->product_id = $record->pk();
            $plan->insert();
        }
        // temp. stub
        $record->updateQuick('default_billing_plan_id', $this->getDi()->db->selectCell(
            "SELECT MIN(plan_id) FROM ?_billing_plan WHERE product_id=?d
                AND (disabled IS NULL OR disabled = 0)",
            $record->product_id));
    }
    public function upgradesAction()
    {
        $planOptions = $this->getDi()->db->selectCol("SELECT concat(b.plan_id) AS ARRAY_KEY, concat(p.title, '/',b.title)
            FROM ?_billing_plan b RIGHT JOIN ?_product p USING (product_id)
            ORDER BY b.product_id");

        $ds = new Am_Query($this->getDi()->productUpgradeTable);
        $grid = new Am_Grid_Editable('_upgrades', ___("Product Upgrades"), $ds, $this->_request, $this->view);
        $grid->addField(new Am_Grid_Field_Enum('from_billing_plan_id', ___('Upgrade From')))->setTranslations($planOptions);
        $grid->addField(new Am_Grid_Field_Enum('to_billing_plan_id', ___('Upgrade To')))->setTranslations($planOptions);
        $grid->addField('surcharge', ___('Surcharge'));
        $grid->setForm(array($this, 'createUpgradesForm'));
        $grid->runWithLayout('admin/layout.phtml');
    }
    public function createUpgradesForm()
    {
        $form = new Am_Form_Admin;
        $options = $this->getDi()->db->selectCol("SELECT concat(b.plan_id) AS ARRAY_KEY, concat(p.title, '/',b.title)
            FROM ?_billing_plan b RIGHT JOIN ?_product p USING (product_id)
            ORDER BY b.product_id");
        $from = $form->addSelect('from_billing_plan_id', null, array('options'=>$options))->setLabel(___('Upgrade From'));
        $to = $form->addSelect('to_billing_plan_id', null, array('options' => $options))->setLabel(___('Upgrade To'));
        $to->addRule('neq', ___('[From] and [To] billing plans must not be equal'), $from);
        $form->addText('surcharge', array('placeholder' => '0.0'))->setLabel(___('Surcharge'));
        return $form;
    }
    public function init()
    {
        parent::init();
        $this->view->headStyle()->appendStyle("
.billing-plan.collapsed .row { display: none; }
.billing-plan.collapsed .row.terms-text-row { display: block; }
.billing-plan .terms-text-row { font-decoration: italic; font-size: 120%; padding: 4px;}
#plan-TPL { display: none; }
        ");
        $this->view->headScript()->appendFile(REL_ROOT_URL . "/application/default/views/public/js/adminproduct.js");
        $this->getDi()->plugins_payment->loadEnabled()->getAllEnabled();
    }
}