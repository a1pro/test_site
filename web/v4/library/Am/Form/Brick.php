<?php

abstract class Am_Form_Brick
{
    const HIDE = 'hide';

    const HIDE_DONT = 0;
    const HIDE_DESIRED = 1;
    const HIDE_ALWAYS = 2;

    protected $config = array();
    protected $hideIfLoggedInPossible = self::HIDE_DESIRED;
    protected $hideIfLoggedIn = false;
    protected $id, $name;
    
    protected $labels = array();
    protected $customLabels = array();
    
    abstract public function insertBrick(HTML_QuickForm2_Container $form);
    
    public function __construct($id = null, $config = null)
    {
        // transform labels to array with similar key->values
        if ($this->labels && is_int(key($this->labels)))
        {
            $ll = array_values($this->labels);
            $this->labels = array_combine($ll, $ll);
        }
        if ($id !== null)
            $this->setId($id);
        if ($config !== null)
            $this->setConfigArray($config);
        if ($this->hideIfLoggedInPossible() == self::HIDE_ALWAYS)
            $this->hideIfLoggedIn = true;
        // format labels
    }
    
    function getClass()
    {
        return fromCamelCase(str_replace('Am_Form_Brick_', '', get_class($this)), '-');
    }
    function getName() 
    { 
        if (!$this->name)
            $this->name = str_replace('Am_Form_Brick_', '', get_class($this));
        return $this->name;
    }
    function getId()  
    {
        if (!$this->id) 
        {
            $this->id = $this->getClass();
            if ($this->isMultiple())
                $this->id .= '-0';
        }
        return $this->id;
    }
    function setId($id)  {  $this->id = (string)$id; }
    
    function getConfigArray() { return $this->config; }
    function setConfigArray(array $config) { $this->config = $config; }
    function getConfig($k, $default = null)
    {
        return array_key_exists($k, $this->config) ?
            $this->config[$k] : $default;
    }
    function getStdLabels()
    {
        return $this->labels;
    }
    function getCustomLabels()
    {
        return $this->customLabels;
    }
    function setCustomLabels(array $labels)
    {
        $this->customLabels = $labels;
    }
    function ___($id)
    {
        $args = func_get_args();
        $args[0] = array_key_exists($id, $this->customLabels) ? 
            $this->customLabels[$id] :
            $this->labels[$id];
        return call_user_func_array('___', $args);
    }
    
    function initConfigForm(Am_Form $form) {  }
    /** @return bool true if initConfigForm is overriden */
    function haveConfigForm() 
    {
        $r = new ReflectionMethod(get_class($this), 'initConfigForm');
        return $r->getDeclaringClass()->getName() != __CLASS__;
    }
    
    function setFromRecord(array $brickConfig)
    {
        if ($brickConfig['id'])
            $this->id = $brickConfig['id'];
        $this->setConfigArray(empty($brickConfig['config']) ? array() : $brickConfig['config']);
        if (isset($brickConfig[self::HIDE]))
            $this->hideIfLoggedIn = $brickConfig[self::HIDE];
        if (isset($brickConfig['labels']))
            $this->customLabels = $brickConfig['labels'];
        return $this;
    }
    
    /** @return array */
    function getRecord()
    {
        $ret = array(
            'id' => $this->getId(),
            'class' => $this->getClass(),
        );
        if ($this->hideIfLoggedIn)
            $ret[self::HIDE] = $this->hideIfLoggedIn;
        if ($this->config)
            $ret['config'] = $this->config;
        if ($this->customLabels)
            $ret['labels'] = $this->customLabels;
        return $ret;
    }

    function  isAcceptableForForm(Am_Form_Bricked $form) { return true; }
    
    public function hideIfLoggedIn() { return $this->hideIfLoggedIn; }
    public function hideIfLoggedInPossible() { return $this->hideIfLoggedInPossible; }
    /** if user can add many instances of brick right in the editor */
    public function isMultiple() { return false; }

    static function createAvailableBricks($className)
    {
        return new $className;
    }
    
    /** 
     * @param array $brickConfig - must have keys: 'id', 'class', may have 'hide', 'config'
     * 
     * @return Am_Form_Brick */
    static function createFromRecord(array $brickConfig)
    {
        if (empty($brickConfig['class']))
            throw new Am_Exception_InternalError("Error in " . __METHOD__ . " - cannot create record without [class]");
        if (empty($brickConfig['id']))
            throw new Am_Exception_InternalError("Error in " . __METHOD__ . " - cannot create record without [id]");
        $className = 'Am_Form_Brick_' . ucfirst(toCamelCase($brickConfig['class']));
        if (!class_exists($className, true))
            throw new Am_Exception_InternalError("Error in " . __METHOD__ . " cannot create class [$className] - not defined");
        
        $b = new $className($brickConfig['id'], empty($brickConfig['config']) ? array() : $brickConfig['config']);
        if (array_key_exists(self::HIDE, $brickConfig))
            $b->hideIfLoggedIn = (bool)@$brickConfig[self::HIDE];
        if (!empty($brickConfig['labels']))
            $b->setCustomLabels($brickConfig['labels']);
        return $b;
    }
    
    static function getAvailableBricks(Am_Form_Bricked $form)
    {
        $ret = array();
        
        Am_Di::getInstance()->hook->call(Am_Event::LOAD_BRICKS);
        
        foreach (get_declared_classes() as $className)
        {
            if (is_subclass_of($className, 'Am_Form_Brick'))
            {
                $class = new ReflectionClass($className);
                if ($class->isAbstract()) continue;
                $obj = call_user_func(array($className, 'createAvailableBricks'), $className);
                if (!is_array($obj) ) {
                    $obj = array($obj);
                }
                foreach ($obj as $k => $o)
                    if (!$o->isAcceptableForForm($form))
                        unset($obj[$k]);
                $ret = array_merge($ret, $obj);
            }
        }
        return $ret;
    }
}

class Am_Form_Brick_Name extends Am_Form_Brick
{
    protected $labels = array(
        'First & Last Name',
        'Please enter your First Name', 
        'Please enter your Last Name', 
    );
    
    public function __construct($id = null, $config = null)
    {
        $this->name = ___('Name');
        parent::__construct($id, $config);
    }
    
    protected $hideIfLoggedInPossible = self::HIDE_ALWAYS;
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        $nameField = $form->addGroup('', array('id' => 'name-0'))->setLabel($this->___('First & Last Name'));
        $nameField->addRule('required');
        $nameField->addElement('text', 'name_f', array('size'=>15))
                ->addRule('required', $this->___('Please enter your First Name'))
                ->addRule('regex', $this->___('Please enter your First Name'), '/^[^=:<>{}()"]+$/D');
        $nameField->addElement('text', 'name_l', array('size'=>15))
                ->addRule('required', $this->___('Please enter your Last Name'))
                ->addRule('regex', $this->___('Please enter your Last Name'), '/^[^=:<>{}()"]+$/D');
    }
}

class Am_Form_Brick_Email extends Am_Form_Brick
{
    protected $labels = array(
        "Your E-Mail Address\na confirmation email will be sent\nto you at this address",
        'Please enter valid Email',
    );
    
    public function __construct($id = null, $config = null)
    {
        $this->name = ___('E-Mail');
        parent::__construct($id, $config);
    }
    
    protected $hideIfLoggedInPossible = self::HIDE_ALWAYS;
    
    public function initConfigForm(Am_Form $form) {
        $form->addAdvCheckbox('validate')->setLabel(___('Validate E-Mail Address by sending e-mail message with code'));
    }
    public function check($email)
    {
        $user_id = Am_Di::getInstance()->auth->getUserId();
        if (!$user_id)
            $user_id = Am_Di::getInstance()->session->signup_member_id;
        
        if (!Am_Di::getInstance()->userTable->checkUniqEmail($email, $user_id))
            return ___('An account with the same email already exists.').'<br />'.
                    sprintf(___('Please %slogin%s to your existing account.%sIf you have not completed payment, you will be able to complete it after login'),'<a href="'.Am_Controller::escape(REL_ROOT_URL . '/member').'">','</a>','<br />');
        return Am_Di::getInstance()->banTable->checkBan(array('email'=>$email));
    }
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        $email = $form->addText('email', array('size'=>30))
            ->setLabel($this->___("Your E-Mail Address\na confirmation email will be sent\nto you at this address"));
        $email->addRule('required', $this->___('Please enter valid Email'))
              ->addRule('callback', $this->___('Please enter valid Email'), array('Am_Validate', 'email'));
        $email->addRule('callback2', '--wrong email--', array($this, 'check'));
    }
}

class Am_Form_Brick_Login extends Am_Form_Brick
{
    protected $labels = array(
"Choose a Username\nit must be %d or more characters in length\nmay only contain letters, numbers, and underscores",
'Please enter valid Username. It must contain at least %d characters',        
'Username contains invalid characters - please use digits, letters or spaces',
'Username contains invalid characters - please use digits and letters',
'Username %s is already taken. Please choose another username',
    );
    protected $hideIfLoggedInPossible = self::HIDE_ALWAYS;
    
    public function __construct($id = null, $config = null)
    {
        $this->name = ___("Username");
        parent::__construct($id, $config);
    }
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        $len = Am_Di::getInstance()->config->get('login_min_length', 6);
        $login = $form->addText('login', array('size' => 15, 'maxlength' => Am_Di::getInstance()->config->get('login_max_length', 64)))
           ->setLabel($this->___("Choose a Username\nit must be %d or more characters in length\nmay only contain letters, numbers, and underscores", $len)
           );
        $login->addRule('required', sprintf($this->___('Please enter valid Username. It must contain at least %d characters'), $len))
            ->addRule('length', sprintf($this->___('Please enter valid Username. It must contain at least %d characters'), $len), array($len, Am_Di::getInstance()->config->get('login_max_length', 64)))
            ->addRule('regex', !Am_Di::getInstance()->config->get('login_disallow_spaces') ? 
                $this->___('Username contains invalid characters - please use digits, letters or spaces') : 
                $this->___('Username contains invalid characters - please use digits and letters'), 
                Am_Di::getInstance()->userTable->getLoginRegex())
            ->addRule('callback2', "--wrong login--", array($this, 'check'));

        if (!Am_Di::getInstance()->config->get('login_dont_lowercase'))
            $login->addFilter('strtolower');
        
        $this->form = $form;
    }
    public function check($login)
    {
        if (!Am_Di::getInstance()->userTable->checkUniqLogin($login, Am_Di::getInstance()->session->signup_member_id))
            return sprintf($this->___('Username %s is already taken. Please choose another username'), Am_Controller::escape($login));
        return Am_Di::getInstance()->banTable->checkBan(array('login'=>$login));
    }
    public function isAcceptableForForm(Am_Form_Bricked $form) {
        return $form instanceof Am_Form_Signup;
    }
}
class Am_Form_Brick_NewLogin extends Am_Form_Brick
{
    protected $labels = array(
"Username\nyou can choose new username here or keep it unchanged.\nUsername must be %d or more characters in length and may\nonly contain small letters, numbers, and underscore",
"Please enter valid Username. It must contain at least %d characters",
"Username contains invalid characters - please use digits, letters or spaces",
"Username contains invalid characters - please use digits and letters",
'Username %s is already taken. Please choose another username',
    );
    public function __construct($id = null, $config = null)
    {
        $this->name = ___("Change Username");
        parent::__construct($id, $config);
    }
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        $len = Am_Di::getInstance()->config->get('login_min_length', 6);
        $login = $form->addText('login', array('size' => 15, 'maxlength' => Am_Di::getInstance()->config->get('login_max_length', 64)))
           ->setLabel(sprintf($this->___("Username\nyou can choose new username here or keep it unchanged.\nUsername must be %d or more characters in length and may\nonly contain small letters, numbers, and underscore"), $len)
            );
        $login
            ->addRule('length', sprintf($this->___("Please enter valid Username. It must contain at least %d characters"), $len), array($len, Am_Di::getInstance()->config->get('login_max_length', 64)))
            ->addRule('regex', !Am_Di::getInstance()->config->get('login_disallow_spaces') ? 
                $this->___("Username contains invalid characters - please use digits, letters or spaces") : 
                $this->___("Username contains invalid characters - please use digits and letters"), 
                Am_Di::getInstance()->userTable->getLoginRegex())
            ->addRule('callback2', $this->___('Username %s is already taken. Please choose another username'), array($this, 'checkNewUniqLogin'));
        
    }
    function checkNewUniqLogin($login)
    {
        $u = Am_Di::getInstance()->userTable->findFirstByEmail($login);
        if (!$u || $u->user_id == Am_Di::getInstance()->auth->getUserId())
            return null;
        else
            return sprintf($this->___('Username %s is already taken. Please choose another username'), Am_Controller::escape($login));
    }
    public function isAcceptableForForm(Am_Form_Bricked $form) {
        return $form instanceof Am_Form_Profile;
    }
}
class Am_Form_Brick_Password extends Am_Form_Brick
{
    protected $labels = array(
        "Choose a Password\nmust be %d or more characters",
        'Confirm Your Password',
        'Please enter Password',
        'Password must contain at least %d letters or digits',
        'Password and Password Confirmation are different. Please reenter both',
    );
    
    protected $hideIfLoggedInPossible = self::HIDE_ALWAYS;
    
    public function __construct($id = null, $config = null)
    {
        $this->name = ___("Password");
        parent::__construct($id, $config);
    }
    
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        $len = Am_Di::getInstance()->config->get('pass_min_length', 6);
        $pass = $form->addPassword('pass', array('size'=>15, 'maxlength' => Am_Di::getInstance()->config->get('pass_max_length', 64)))
           ->setLabel($this->___("Choose a Password\nmust be %d or more characters", $len));
        $pass0 = $form->addPassword('_pass', array('size'=>15))
            ->setLabel(array($this->___('Confirm Your Password')));
        
        $pass->addRule('required', $this->___('Please enter Password'));
        $pass->addRule('length', sprintf($this->___('Password must contain at least %d letters or digits'), $len), 
            array($len, Am_Di::getInstance()->config->get('pass_max_length', 64)));
        $pass0->addRule('required');
        $pass0->addRule('eq', $this->___('Password and Password Confirmation are different. Please reenter both'), $pass);
        return array($pass, $pass0);
    }
    public function isAcceptableForForm(Am_Form_Bricked $form) {
        return $form instanceof Am_Form_Signup;
    }
}
class Am_Form_Brick_NewPassword extends Am_Form_Brick
{
    protected $labels = array(
        "Your Current Password\nif you are changing password, please\n enter your current password for validation",
        "New Password\nyou can choose new password here or keep it unchanged\nmust be %d or more characters",
        'Confirm New Password',
        'Please enter Password',
        'Password must contain at least %d letters or digits',
        'Password and Password Confirmation are different. Please reenter both',
        'Please enter your current password for validation',
        'Current password entered incorrectly, please try again',
    );
    public function __construct($id = null, $config = null)
    {
        $this->name = ___("Change Password");
        parent::__construct($id, $config);
    }
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        $forSignup = true;
        $len = Am_Di::getInstance()->config->get('pass_min_length', 6);
        $oldPass = $form->addPassword('_oldpass', array('size' => 15))
            ->setLabel($this->___("Your Current Password\nif you are changing password, please\n enter your current password for validation"));
        $oldPass->addRule('callback2', 'wrong', array($this, 'validateOldPass'));
        $pass = $form->addPassword('pass', array('size'=>15, 'maxlength' => Am_Di::getInstance()->config->get('pass_max_length', 64)))
           ->setLabel($this->___("New Password\nyou can choose new password here or keep it unchanged\nmust be %d or more characters", $len));
        $pass0 = $form->addPassword('_pass', array('size'=>15))
            ->setLabel($this->___('Confirm New Password'));
        $pass->addRule('length', sprintf($this->___('Password must contain at least %d letters or digits'), $len), 
            array($len, Am_Di::getInstance()->config->get('pass_max_length', 64)));
        $pass0->addRule('eq', $this->___('Password and Password Confirmation are different. Please reenter both'), $pass);

        return array($pass, $pass0);
    }
    public function validateOldPass($vars, HTML_QuickForm2_Element_InputPassword $el)
    {
        $vars = $el->getContainer()->getValue();
        if ($vars['pass'] != '') {
            if ($vars['_oldpass'] == '') return $this->___('Please enter your current password for validation');
            if (!Am_Di::getInstance()->user->checkPassword($vars['_oldpass'])) 
                return $this->___('Current password entered incorrectly, please try again');
        }
    }
    public function isAcceptableForForm(Am_Form_Bricked $form) {
        return $form instanceof Am_Form_Profile;
    }
}
class Am_Form_Brick_Address extends Am_Form_Brick
{
    protected $labels = array(
        'Address Information' => 'Address Information',
        'Street' => 'Street',
        'City' => 'City',
        'State' => 'State',
        'ZIP Code' => 'ZIP Code',
        'Country' => 'Country',
    );
    public function __construct($id = null, $config = null)
    {
        $this->name = ___('Address Information');
        parent::__construct($id, $config);
    }
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        $fieldSet = $form->addElement('fieldset', 'address', array('id' => 'row-address-0'))->setLabel($this->___('Address Information'));
        $street = $fieldSet->addText('street')->setLabel($this->___('Street'));
        $city = $fieldSet->addText('city')->setLabel($this->___('City'));
        $zip = $fieldSet->addText('zip')->setLabel($this->___('ZIP Code'));

        $country = $fieldSet->addSelect('country')->setLabel($this->___('Country'))
            ->setId('f_country')
            ->loadOptions(Am_Di::getInstance()->countryTable->getOptions(true));

        $group = $fieldSet->addGroup()->setLabel($this->___('State'));
        $stateSelect = $group->addSelect('state')
            ->setId('f_state')
            ->loadOptions($stateOptions = Am_Di::getInstance()->stateTable->getOptions(@$_REQUEST['country'], true));
        $stateText = $group->addText('state')->setId('t_state');
        $disableObj = $stateOptions ? $stateText : $stateSelect;
        $disableObj->setAttribute('disabled', 'disabled')->setAttribute('style', 'display: none');
        if ($this->getConfig('required')){
            $street->addRule('required', ___('Please enter %s', $this->___('Street')));
            $city->addRule('required', ___('Please enter %s', $this->___('City')));
            $zip->addRule('required', ___('Please enter %s', $this->___('ZIP Code')));
            $country->addRule('required', ___('Please enter %s', $this->___('Country')));
            $group->addRule('required', ___('Please enter %s', $this->___('State')));
        }
    }
    public function initConfigForm(Am_Form $form)
    {
        $form->addAdvCheckbox('required')->setLabel(___('Require Address Info'));
    }
}

class Am_Form_Brick_Phone extends Am_Form_Brick
{
    protected $labels = array(
        'Phone Number' => 'Phone Number',
    );
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        $phone = $form->addText('phone')->setLabel($this->___('Phone Number'));
        if ($this->getConfig('required')){
            $phone->addRule('required', ___('Please enter %s', $this->___('Phone Number')));
        }
    }
    public function initConfigForm(Am_Form $form)
    {
        $form->addAdvCheckbox('required')->setLabel(___('Required'));
    }
}


class Am_Form_Brick_Product extends Am_Form_Brick
{
    protected $labels = array(
        'Membership Type',
        'Please choose a membership type',
    );
    
    protected $hideIfLoggedInPossible = self::HIDE_DONT;
    
    protected static $bricksAdded = 0;
    
    public function __construct($id = null, $config = null)
    {
        $this->name = ___("Product");
        parent::__construct($id, $config);
    }

    function shortRender(Product $p, BillingPlan $plan = null)
    {
        return $p->getTitle() . ' - ' . $plan->getTerms();
    }
    
    function renderProduct(Product $p, BillingPlan $plan = null, $short = false){
        return $p->defaultRender($plan, $short);
    }
    function getProducts()
    {
        switch ($this->getConfig('type', 0))
        {
            case 1:
                return Am_Di::getInstance()->productTable->getVisible($this->getConfig('groups', array())); break;
            case 2:
                $ret = Am_Di::getInstance()->productTable->loadIds($this->getConfig('products', array())); 
                foreach ($ret as $k => $p)
                    if ($p->is_disabled) unset($ret[$k]);
                return $ret;
                break;
            default:
                return Am_Di::getInstance()->productTable->getVisible(null);
        }
    }
    
    function getProductsFiltered(){
        $products = $this->getProducts();
        if($this->getConfig('display-type', 'hide') == 'display') return $products;
        
        $user = Am_Di::getInstance()->auth->getUser();
        $haveActive = $haveExpired = array();
        if(!is_null($user)){
            $haveActive = $user->getActiveProductIds();
            $haveExpired = $user->getExpiredProductIds();
        }
        return Am_Di::getInstance()->productTable
                ->filterProducts($products, $haveActive, $haveExpired, $this->getConfig('input-type') == 'checkbox' ? true : false);
    }
    
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        $name = self::$bricksAdded ? 'product_id_' . self::$bricksAdded : 'product_id';
        self::$bricksAdded++;
        
        $products = $this->getProductsFiltered();
        if (!$products)
        {
            throw new Am_Exception_InputError(___("There are no products available for purchase. Please come back later."));
        }
        $options = $shortOptions = array();
        foreach ($products as $p)
        {
            foreach ($p->getBillingPlans(true) as $plan)
            {
                $options[$p->product_id . '-' . $plan->plan_id] = $this->renderProduct($p, $plan);
                $shortOptions[$p->product_id . '-' . $plan->plan_id] = $this->shortRender($p, $plan);
                $attrs[$p->product_id . '-' . $plan->plan_id] = array(
                    'data-first_price' => $plan->first_price,
                    'data-second_price' => $plan->second_price,
                );
            }
        }
        if(count($options) == 1){
            $el  = $form->addAdvRadio($name);
            list($pid, $label) = each($options);
            $el->addOption($label, $pid, $attrs[$pid]);
            $el->setValue($pid)->toggleFrozen(true);
        }else switch ($this->getConfig('input-type'))
        {
            case 'checkbox':
                $el = $form->addGroup($name);
                foreach ($products as $p)
                {
                    // @todo add select for billing plans
                    // right now default billing plan wil be used
                    $el->addCheckbox($p->product_id, array(
                        'value' => $p->product_id, 
                        'data-first_price' => $p->getBillingPlan()->first_price,
                        'data-second_price' => $p->getBillingPlan()->second_price
                    ))
                        ->setContent($this->renderProduct($p, $p->getBillingPlan()));
                }
                break;
            case 'select':
                $el = $form->addSelect($name);
                foreach ($shortOptions as $pid => $label)
                    $el->addOption($label, $pid, $attrs[$pid]);
                break;
            case 'advradio':
            default:
                $el = $form->addAdvRadio($name);
                $first = 0;
                foreach ($options as $pid => $label)
                {
                    if (!$first++ && Am_Di::getInstance()->request->isGet()) // pre-check first option
                        $attrs[$pid]['checked'] = 'checked';
                    $el->addOption($label, $pid, $attrs[$pid]);
                }
                break;
        }
        // only the first brick is requried
        if (self::$bricksAdded <= 1)
        {
            $el->addRule('required', $this->___('Please choose a membership type'));
        }
        $el->setLabel($this->___('Membership Type'));
    }
    public function initConfigForm(Am_Form $form)
    {
        $radio = $form->addSelect('type')->setLabel(array('What to Display'));
        $radio->loadOptions(array(
            0 => 'Display All Products',
            1 => 'Products from selected Categories',
            2 => 'Only Products selected below',
        ));

        $groups = $form->addMagicSelect('groups', array('data-type' => 1,))->setLabel('Product Gategories');
        $groups->loadOptions(Am_Di::getInstance()->productCategoryTable->getAdminSelectOptions(array(ProductCategoryTable::COUNT => 1)));
        
        $products = $form->addMagicSelect('products', array('data-type' => 2,))->setLabel(___('Product(s) to display'));
        $products->loadOptions(Am_Di::getInstance()->productTable->getOptions(true));
        
        $inputType = $form->addSelect('input-type')->setLabel('Input Type');
        $inputType->loadOptions(array(
            'advradio' => 'Radio-buttons (one product can be selected)',
            'select' =>   'Select-box (one product can be selected)',
            'checkbox' => 'Checkboxes (multiple products can be selected)',
        ));
        
        $form->addSelect('display-type')->setLabel('If product is not available because of require/disallow settings')
            ->loadOptions(array(
                'hide'      =>  'Remove It From Signup Form',
                'display'   =>  'Display It Anyway'
            ));
        
        $formId = $form->getId();
        $script = <<<EOF
        jQuery(document).ready(function($) {
            // there can be multiple bricks like that :)
            if (!window.product_brick_hook_set)
            {
                window.product_brick_hook_set = true;
                $("select[name='type']").live('change', function (event){
                    var val = $(event.target).val();
                    var frm = $(event.target).closest("form");
                    $("[data-type]", frm).closest(".row").hide();
                    $("[data-type='"+val+"']", frm).closest(".row").show();
                }).change();
            }
        });
EOF;
        $form->addScript()->setScript($script);
    }
    public function isAcceptableForForm(Am_Form_Bricked $form) {
        return $form instanceof Am_Form_Signup;
    }
    public function isMultiple()
    {
        return true;
    }
}

class Am_Form_Brick_Paysystem extends Am_Form_Brick
{
    protected $labels = array(
        'Payment System',
        'Please choose a payment system',
    );
    protected $hide_if_one = false;
    protected $hideIfLoggedInPossible = self::HIDE_DONT;
    
    public function __construct($id = null, $config = null)
    {
        $this->name = ___("Payment System");
        parent::__construct($id, $config);
    }
    
    function renderPaysys(Am_Paysystem_Description $p){
        return sprintf('&nbsp;<b>%s</b><br /><span class="small">%s</span>',
            $p->getTitle(), $p->getDescription());
    }
    public function getPaysystems()
    {
        $psList = Am_Di::getInstance()->paysystemList->getAllPublic();
        if ($psEnabled = $this->getConfig('paysystems', array()))
        {
            foreach ($psList as $k => $ps)
            {
                if (!in_array($ps->getId(), $psEnabled))
                    unset($psList[$k]);
            }
        }
        return $psList;
    }
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        $paysystems = $this->getPaysystems();
        if ((count($paysystems)==1) && $this->getConfig('hide_if_one'))
        {
            reset($paysystems);
            $form->addHidden('paysys_id')->setValue(current($paysystems)->getId())->toggleFrozen(true);
            return;
        }
        $psOptions = $psHide = array();
        foreach ($paysystems as $ps)
        {
            $psOptions[ $ps->getId() ] = $this->renderPaysys($ps);
            $psHide[ $ps->getId() ] = Am_Di::getInstance()->plugins_payment->loadGet($ps->getId())->hideBricks(); 
        }
        $psHide = Am_Controller::getJson($psHide);
        if (count($paysystems) != 1) {
            $attrs = array('id' => 'paysys_id');
            $el0 = $el = $form->addAdvRadio('paysys_id', array('id' => 'paysys_id'), 
                array('intrinsic_validation'=>false));
            $first = 0;
            foreach ($psOptions as $k => $v)
            {
                $attrs = array();
                if (!$first++ && Am_Di::getInstance()->request->isGet())
                    $attrs['checked'] = 'checked';
                $el->addOption($v, $k, $attrs);
            }
        } else {
            /** @todo display html here */
            reset($psOptions);
            $el = $form->addStatic('_paysys_id', array('id' => 'paysys_id'))->setContent(current($psOptions));
            $el->toggleFrozen(true);
            $el0 = $form->addHidden('paysys_id')->setValue(key($psOptions));
        }
        $el0->addRule('required', $this->___('Please choose a payment system'),
            // the following is added to avoid client validation if select is hidden
            null, HTML_QuickForm2_Rule::SERVER);
        $el0->addFilter('filterId');
        $el->setLabel($this->___('Payment System'));
        
        $form->addScript()->setScript(<<<CUT
jQuery(document).ready(function($) {
    /// hide payment system selection if:
    //   - there are only free products in the form
    //   - there are selected products, and all of them are free
    $(":checkbox[name^='product_id'], select[name^='product_id'], :radio[name^='product_id']").change(function(){
        var count_free = 0, count_paid = 0, total_count_free = 0, total_count_paid = 0;
        $(":checkbox[name^='product_id']:checked, select[name^='product_id'] option:selected, :radio[name^='product_id']:checked").each(function(){
            if ($(this).data('first_price') || $(this).data('second_price')) 
                count_paid++; 
            else
                count_free++;
        });
        
        $(":checkbox[name^='product_id'], select[name^='product_id'] option, :radio[name^='product_id']").each(function(){
            if ($(this).data('first_price') || $(this).data('second_price')) 
                total_count_paid++; 
            else
                total_count_free++;
        });
        if ( (count_free && !count_paid) || (!total_count_paid && total_count_free))
        { // hide select
            $("#row-paysys_id").hide().after("<input type='hidden' name='paysys_id' value='free' class='hidden-paysys_id' />");
        } else { // show select
            $("#row-paysys_id").show();
            $(".hidden-paysys_id").remove();
        }
    }).change();
    window.psHiddenBricks = [];
    $("input[name='paysys_id']").change(function(){
        if (!this.checked) return;
        var val = $(this).val();
        var hideBricks = $psHide;
        $.each(window.psHiddenBricks, function(k,v){ $('#row-'+v+'-0').show(); });
        window.psHiddenBricks = hideBricks[val];
        if (window.psHiddenBricks)
        {
            $.each(window.psHiddenBricks, function(k,v){ $('#row-'+v+'-0').hide(); });
        }
    }).change();
});
CUT
        ); 
    }
    public function isAcceptableForForm(Am_Form_Bricked $form) {
        return $form instanceof Am_Form_Signup;
    }
    public function initConfigForm(Am_Form $form)
    {
        Am_Di::getInstance()->plugins_payment->loadEnabled();
        $ps = $form->addMagicSelect('paysystems')->setLabel(array('Payment Options', 
                        'if none selected, all enabled will be displayed'))
            ->loadOptions(Am_Di::getInstance()->paysystemList->getOptionsPublic());
        $form->addAdvCheckbox('hide_if_one')->setLabel(array('Hide Select', 'if there is only one choice'));
    }
}

class Am_Form_Brick_Recaptcha extends Am_Form_Brick
{
    protected $labels = array(
        "Enter Verification Text\nplease type text from image",
        'Text has been entered incorrectly' => 'Text has been entered incorrectly',
    );
    protected $theme_options = array('clean'=>'clean', 'red'=>'red', 'white'=>'white', 'blackglass'=>'blackglass');
    /** @var HTML_QuickForm2_Element_Static */
    protected $static;
    
    public function initConfigForm(Am_Form $form) {
        $form->addSelect('theme')
              ->setLabel(array('reCaptcha Theme', '<a target="_blank" href="http://code.google.com/apis/recaptcha/docs/customization.html">examples<a/>'))
              ->loadOptions($this->theme_options);
    }
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        $captcha = $form->addGroup()
            ->setLabel($this->___("Enter Verification Text\nplease type text from image"));
        $captcha->addRule('callback', $this->___('Text has been entered incorrectly'), array($this, 'validate'));
        $this->static = $captcha->addStatic('captcha')->setContent(Am_Di::getInstance()->recaptcha->render($this->getConfig('theme')));
    }
    
    public static function createAvailableBricks($className)
    {
        return Am_Recaptcha::isConfigured() ? 
            parent::createAvailableBricks($className) : 
            array();
    }
    public function validate()
    {
        $form = $this->static;
        while ($np = $form->getContainer()) $form = $np;
        
        $challenge = $response = null;
        foreach ($form->getDataSources() as $ds)
        {
            $challenge = $ds->getValue('recaptcha_challenge_field');
            $resp = $ds->getValue('recaptcha_response_field');
            if ($challenge) break;
        }
        
        $status = false;
        if ($resp) 
            $status = Am_Di::getInstance()->recaptcha->validate($challenge, $resp);
        if (!$status)
            $this->static->setContent(Am_Di::getInstance()->recaptcha->render($this->config['theme']));
        return $status;
    }
}

class Am_Form_Brick_Coupon extends Am_Form_Brick
{
    protected $labels = array(
        "Enter coupon code\n(optional)",
        'No coupons found with such coupon code',
    );
    protected $hideIfLoggedInPossible = self::HIDE_DONT;
    
    public function __construct($id = null, $config = null)
    {
        $this->name = ___("Coupon");
        parent::__construct($id, $config);
    }
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        $fieldSet = $form->addFieldset()->setId('fieldset-coupons')->setLabel(___('COUPONS'));
        $coupon = $fieldSet->addText('coupon', array('size'=>15))
            ->setLabel($this->___("Enter coupon code\n(optional)"));
        $coupon->addRule('callback2', 'error', array($this, 'validateCoupon'));
    }
    function validateCoupon($value)
    {
        if ($value == "") return null;
        $coupon = htmlentities($value);
        $coupon = Am_Di::getInstance()->couponTable->findFirstByCode($coupon);
        $msg = $coupon ? $coupon->validate() : $this->___('No coupons found with such coupon code');
        return $msg === null ? null : $msg;
    }
    public function isAcceptableForForm(Am_Form_Bricked $form) {
        return $form instanceof Am_Form_Signup;
    }
}

class Am_Form_Brick_Field extends Am_Form_Brick 
{
    protected $field = null;
    
    static function createAvailableBricks($className)
    {
        $res = array();
        foreach(Am_Di::getInstance()->userTable->customFields()->getAll() as $field)
        {
            if (strpos($field->name, 'aff_') === 0) continue;
            $res[] = new self('field-'.$field->getName());
        }
        return $res;
    }
    public function __construct($id = null, $config = null)
    {
        parent::__construct($id, $config);
        $fieldName =  str_replace('field-', '', $id);
        $this->field = Am_Di::getInstance()->userTable->customFields()->get($fieldName);
        // to make it fault-tolerant when customfield is deleted 
        if (!$this->field)
            $this->field = new Am_CustomFieldText($fieldName, $fieldName);
    }
    function getName()
    {
        return $this->field->title;
    }
    function insertBrick(HTML_QuickForm2_Container $form)
    {
        $this->field->addToQF2($form);
    }
    function getFieldName() { return $this->field->name; }
}

class Am_Form_Brick_Agreement extends Am_Form_Brick
{
    protected $labels = array(
        'User Agreement',
        'I Agree',
        'Please agree to User Agreement',
    );
    protected $text = "";
    protected $isHtml = false;
    public function __construct($id = null, $config = null)
    {
        $this->name = ___("User Agreement");
        parent::__construct($id, $config);
    }
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        
        $fieldSet = $form->addFieldset()->setId('fieldset-agreement')->setLabel($this->___('User Agreement'));
        $agreement = $fieldSet->addStatic('_agreement');
        $agreement->setContent('<div class="agreement">'.$this->getText().'</div>');
        $checkbox = $fieldSet->addCheckbox('i_agree')->setLabel($this->___('I Agree'));
        $checkbox->addRule('required', $this->___('Please agree to User Agreement'));
    }
    public function getText()
    {
        return empty($this->config['isHtml']) ? 
            Am_Controller::escape(@$this->config['text']) :
            @$this->config['text'];
    }
    public function isAcceptableForForm(Am_Form_Bricked $form) 
    {
        return $form instanceof Am_Form_Signup;
    }
    public function initConfigForm(Am_Form $form)
    {
        $form->addAdvCheckbox("isHtml")->setLabel("Is Html?");
        $form->addTextarea("text", array('cols'=>80, 'rows'=>20))->setLabel("Agreement text");
    }
}

class Am_Form_Brick_PageSeparator extends Am_Form_Brick
{
    protected $labels = array(
        '',
        'Back', 
        'Next',
    );
    protected $hideIfLoggedInPossible = self::HIDE_DONT;
    
    public function __construct($id = null, $config = null)
    {
        $this->name = ___("Form Page Break");
        parent::__construct($id, $config);
    }
    public function insertBrick(HTML_QuickForm2_Container $form)
    {
        // nop;
    }
    public function isAcceptableForForm(Am_Form_Bricked $form) 
    {
        return (bool)$form->isMultiPage();
    }
    public function isMultiple()
    {
        return true;
    }
}
