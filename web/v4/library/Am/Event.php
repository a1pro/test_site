<?php

/**
 * All possible event objects are defined in this file
 */

/**
 * Class defines basic Am_Event class
 * it must contain all information regarding event
 * and returing information from hooks if required
 * the Am_Event object will be returned as result of Am_Di::getInstance()->hook->call(...)
 *
 * BY AGREEMENT ALL SUBCLASSES OF Am_Event must have name starting with "Am_Event..." !!!
 *
 * @package Am_Events
 */
class Am_Event
{
    const HOURLY = 'hourly';
    const DAILY  = 'daily';
    const INIT_FINISHED  = 'initFinished';
    const INVOICE_STARTED = 'invoiceStarted';

    const USER_BEFORE_DELETE = 'userBeforeDelete';
    const USER_AFTER_DELETE = 'userAfterDelete';
    const USER_BEFORE_INSERT = 'userBeforeInsert';
    const USER_AFTER_INSERT = 'userAfterInsert';
    const USER_BEFORE_UPDATE = 'userBeforeUpdate';
    const USER_AFTER_UPDATE = 'userAfterUpdate';
    const USER_UNSUBSCRIBED_CHANGED = 'userUnsubscribedChanged';
    
    const SET_PASSWORD = 'setPassword';
    /** User (or affiliate) record is added after submitting signup form - before payment */
    const SIGNUP_USER_ADDED = 'signupUserAdded';
    /** User record is added after submitting signup form - before payment */
    const SIGNUP_AFF_ADDED = 'signupAffAdded';
    /** User record is updated after submitting signup form - before payment */
    const SIGNUP_USER_UPDATED  = 'signupUserUpdated';
    /** Payment record insered into database. Is not called for free subscriptions */
    const PAYMENT_AFTER_INSERT = 'paymentAfterInsert';
    
    /** Return array of objects to calculate invoice. @link Invoice::getCalculators() 
     * use @link Am_Event::getReturn() , @link Am_Event::setReturn()
     * @var array array of calculators (passed by reference)
     */
    const INVOICE_GET_CALCULATORS = 'invoiceGetCalculators';
    /** 
     * Called when invoice calculation is finished
     * @var Invoice invoice
     */
    const INVOICE_CALCULATE = 'invoiceCalculate';
    
    const AUTH_CHECK_LOGGED_IN = 'authCheckLoggedIn';
    const AUTH_AFTER_LOGIN = 'authAfterLogin';
    const AUTH_TRY_LOGIN = 'authTryLogin';
    const AUTH_AFTER_LOGOUT = 'authAfterLogout';
    
    const GET_MEMBER_LINKS = 'getMemberLinks';
    const GET_LEFT_MEMBER_LINKS = 'getLeftMemberLinks';
    
    const SUBSCRIPTION_ADDED = 'subscriptionAdded';
    const SUBSCRIPTION_DELETED = 'subscriptionDeleted';
    const SUBSCRIPTION_CHANGED = 'subscriptionChanged';
    const SUBSCRIPTION_UPDATED = 'subscriptionUpdated';
    const SUBSCRIPTION_REMOVED = 'subscriptionRemoved';
    
    /**
     * Access record inserted 
     * NOTE - record may be in not-active state - check dates
     * @param Access access
     */
    const ACCESS_AFTER_INSERT = 'accessAfterInsert';
    /**
     * Access record updated
     * @param Access access
     * @param Access old - record before changes
     */
    const ACCESS_AFTER_UPDATE = 'accessAfterUpdate';
    /**
     * Access record deleted
     * NOTE - record may be in not-active state - check dates
     * @param Access access
     */
    const ACCESS_AFTER_DELETE = 'accessAfterDelete';
    
    const CHECK_UNIQ_LOGIN = 'checkUniqLogin';
    const CHECK_UNIQ_EMAIL = 'checkUniqEmail';
    
    const VALIDATE_SAVED_FORM = 'validateSavedForm';
    const GLOBAL_INCLUDES = 'globalIncludes';
    const GLOBAL_INCLUDES_FINISHED = 'globalIncludesFinished';

    const REBUILD = 'rebuild';
    /** if your plugin has a table that must not be backed up, 
     *  call $event->addReturn('tablewithoutprefix') on this hook */
    const SKIP_BACKUP = 'skipBackup';
    const PRODUCT_FORM = 'productForm';
    const SETUP_FORMS = 'setupForms';
    const USER_FORM = 'userForm';
    const THANKS_PAGE = 'thanksPage';
    const GET_PERMISSIONS_LIST = 'getPermissionsList';
    const GET_UPLOAD_PREFIX_LIST = 'getUploadPrefixList';
    
    const SAVED_FORM_TYPES = 'savedFormTypes';
    /**
     * Add new pages into existing "pageable" controllers
     * like AdminContentController
     */
    const INIT_CONTROLLER_PAGES = 'initControllerPages';
    const LOAD_BRICKS = 'loadBricks';
    const ADMIN_MENU = 'adminMenu';
    const ADMIN_WARNINGS = 'adminWarnings';
    const USER_MENU = 'userMenu';
    const USER_TABS = 'userTabs';
    const USER_SEARCH_CONDITIONS = 'userSearchConditions';
    const LOAD_REPORTS = 'loadReports';
    const BEFORE_RENDER = 'beforeRender';
    const AFTER_RENDER = 'afterRender';    
    
    const INIT_CONTENT_PAGES = 'initContentPages';
    
    /**
     * Add sample data to database (@link AdminBuildController)
     * $user->save() will be called after hook finished
     * @param User $user
     * @param string $demoId
     * @param int $usersCreated
     * @param int $usersTotal
     */
    const BUILD_DEMO = 'buildDemo';
    
    /** @var id - if empty, will be detected automatically */
    protected $id;
    /** @var array event-specific variables */
    protected $vars = array();
    /** @var array of raised exceptions in format classname::method->exception */
    protected $raisedExceptions = array();
    /** @var bool must stop processing of ->handle(...)? */
    protected $mustStop = false;
    /** @var array collected return values from hooks */
    protected $return = array();
    /** @var Am_Di */
    private $_di;

    function __construct($id = null, array $vars = array())
    {
        $this->id = $id;
        $this->vars = $vars;
    }
    
    /** @access private */
    public function _setDi(Am_Di $di)
    {
        $this->_di = $di;
    }
    
    /** @return Am_Di */
    public function getDi()
    {
        return $this->_di;
    }

    /**
     * Do call of a single callback, it is an internal functions
     * @access protected
     */
    public function call(Am_HookCallback $callback)
    {
        try
        {
            $ret = call_user_func($callback->getCallback(), $this);
        }
        catch (Exception $e)
        {
            $this->addRaisedException($callback->getSignature(), $e);
            $e = $this->onException($e);
            if ($e)
                throw $e;
            return;
        }
        return $ret;
    }

    /**
     * Do call of passed array of callbacks here
     * @param array $hooks
     */
    public function handle(array $hooks)
    {
        foreach ($hooks as $h)
        {
            if ($h->getFile())
                include_once ROOT_DIR . DIRECTORY_SEPARATOR . $h->getFile();
            $this->call($h);
            if ($this->mustStop())
                break;
        }
    }

    /**
     * Will be called when exception raised during callback
     * @return Exception|null if Exception returned, it will be re-raised, and following handling stopped
     */
    public function onException(Exception $e)
    {
        return $e;
    }

    public function addRaisedException($sig, Exception $e)
    {
        $this->raisedExceptions[$sig] = $e;
        return $this;
    }

    public function getRaisedExceptions()
    {
        return $this->raisedExceptions;
    }

    /**
     * Stop handling $this->handle(..) cycle
     * It can be called by a callback to notify app
     * that following callbacks must not be called
     */
    public function stop()
    {
        $this->mustStop = true;
    }

    /**
     * Shall the handle(..) cycle be interrupted?
     * @see Am_Event::stop()
     * @return bool
     */
    public function mustStop()
    {
        return (bool) $this->mustStop;
    }

    public function getId()
    {
        if ($this->id === null)
        {
            $this->id = lcfirst(preg_replace('/^Am_Event_/i', '', get_class($this)));
            if ($this->id === null && get_class($this) === 'Am_Event')
                throw new Am_Exception_InternalError("Am_Event requires id");
        }
        return $this->id;
    }

    /**
     * Run the event agains default Am_Di::getInstance()->hook
     * @return Am_Event
     */
    public function run()
    {
        Am_Di::getInstance()->hook->call($this->getId(), $this);
        return $this;
    }
    
    public function __call($name, $arguments)
    {
        if (strpos($name, 'get')===0)
        {
            $var = lcfirst(substr($name, 3));
            if (!array_key_exists($var, $this->vars))
            {
                $id = $this->getId();
                trigger_error("Event variable [$var] is not set for [$id]", E_USER_WARNING);
                return null;
            }
            return $this->vars[$var];
        }
        trigger_error("Method [$name] does not exists in " . __CLASS__, E_USER_ERROR);
    }
    
    /**
     * Add return value to be used by main program
     * @param type $val
     * @param type $key (optional)
     */
    public function addReturn($val, $key = null)
    {
        if ($key === null)
            $this->return[] = $val;
        else
            $this->return[$key] = $val;
    }
    /**
     * Set entire return values array 
     */
    public function setReturn(array $return) 
    {
        $this->return = $return;
    }
    /**
     * Get values returned by hooks
     * @return array
     */
    public function getReturn()
    {
        return $this->return;
    }
}

////////////////// Abstract classes ///////////////////////////////////////////
/** @method User getUser() */
abstract class Am_Event_User extends Am_Event
{
    function __construct(User $user)
    {
        parent::__construct(null, array('user' => $user));
    }
}

abstract class Am_Event_ValidateRequest extends Am_Event
{

    protected $form;
    protected $errors = array();
    protected $param = null; // var 'scope'
    /** @var array */
    protected $request;

    function __construct(array $request, HTML_QuickForm2 $form = null)
    {
        $this->request = $request;
        $this->form = $form;
    }
    function addError($msg)
    {
        $this->errors[] = (string) $msg;
    }

    function getErrors()
    {
        return $this->errors;
    }
    /**
     * may return null if form was not set
     * @return HTML_QuickForm2|null
     */
    function getForm()
    {
        return $this->form;
    }
}

abstract class Am_Event_UserProduct extends Am_Event
{

    protected $user;
    protected $product;

    function __construct(User $user, Product $product)
    {
        $this->user = $user;
        $this->product = $product;
    }

    /** @return User */
    function getUser()
    {
        return $this->user;
    }

    /** @return Product */
    function getProduct()
    {
        return $this->product;
    }

}

abstract class Am_Event_AbstractUserUpdate extends Am_Event
{

    /** @var User after saving changes */
    protected $user;
    /** @var User before any changes */
    protected $oldUser;

    function __construct(User $user, User $oldUser)
    {
        $this->user = $user;
        $this->oldUser = $oldUser;
    }

    /** @return User */
    function getUser()
    {
        return $this->user;
    }

    /** @return User */
    function getOldUser()
    {
        return $this->oldUser;
    }

}

//////////// Real Am_Event classes that can be used for hooking //////////////////

/** @method InvoicePayment getPayment() 
 *  @method Invoice getInvoice()
 *  @method User getUser()
 */
class Am_Event_PaymentAfterInsert extends Am_Event  { }

/** Called when first access for invoice added
 *  @method Invoice getInvoice
 *  @method User getUser
 */
class Am_Event_InvoiceStarted extends Am_Event { }

class Am_Event_UserBeforeInsert extends Am_Event_User {}
class Am_Event_UserAfterInsert extends Am_Event_User {}
class Am_Event_UserBeforeUpdate extends Am_Event_AbstractUserUpdate {}
class Am_Event_UserAfterUpdate extends Am_Event_AbstractUserUpdate {}
class Am_Event_UserAfterDelete extends Am_Event_User {}

class Am_Event_AuthCheckLoggedIn extends Am_Event
{

    protected $user;
    /**
     * This function must be called in a hook
     * if we have found correct auth credentials
     */
    function setSuccessAndStop(User $user)
    {
        $this->user = $user;
        $this->stop(); // no following hooks will be called
    }
    function validateSuccess($login, $pass)
    {
        $code = null;
        return $this->getDi()->userTable->getAuthenticatedRow($login, $pass, $code);
    }
    function isSuccess()
    {
        return (bool) $this->user;
    }
    /** @return User|null */
    function getUser()
    {
        return $this->user;
    }
}

class Am_Event_AuthAfterLogin extends Am_Event_User
{
    protected $plaintextPass = null;

    public function __construct(User $user, $plaintextPass = null)
    {
        parent::__construct($user);
        $this->plaintextPass = $plaintextPass;
    }

    public function getPassword()
    {
        return $this->plaintextPass;
    }

    public function setPassword($plaintextPass)
    {
        $this->plaintextPass = $plaintextPass;
        return $this;
    }

}

/**
 * After login attempt failed, plugins can try to
 * login into third-party app with the same credentials
 * If that is possible, plugin can :
 *   - create corresponding user in aMember
 *   - login user into third-party app
 *   - return status to let amember know that is ok
 * Then Am_Controller_AuthUser will login user to aMember, too
 */
class Am_Event_AuthTryLogin extends Am_Event
{

    protected $login, $pass;
    protected $user;

    public function __construct($login, $pass)
    {
        parent::__construct();
        $this->login = $login;
        $this->pass = $pass;
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function getPassword()
    {
        return $this->pass;
    }

    public function setCreated(User $user)
    {
        $this->user = $user;
    }

    public function isCreated()
    {
        return (bool) $this->user;
    }

    /** @return User|null */
    public function getCreated()
    {
        return $this->user;
    }

}

class Am_Event_AuthAfterLogout extends Am_Event_User { }
class Am_Event_AuthSessionRefresh extends Am_Event_User{ }
class Am_Event_SubscriptionAdded extends Am_Event_UserProduct {}
class Am_Event_SubscriptionDeleted extends Am_Event_UserProduct {}
/**
 * This hook is called when subscription list is changed
 * @method User getUser() user record
 * @method array getAdded() array of product# that were added to user access
 * @method array getDeleted() array of product# that were deleted from user access
 */
class Am_Event_SubscriptionChanged extends Am_Event
{
    public function __construct(User $user, array $added, array $deleted)
    {
        parent::__construct(null, array('user' => $user, 'added' => $added, 'deleted' => $deleted));
    }
}

class Am_Event_SubscriptionUpdated extends Am_Event_AbstractUserUpdate { }

class Am_Event_SubscriptionRemoved extends Am_Event_User { }

/**
 * @method string getLogin()
 */
class Am_Event_CheckUniqLogin extends Am_Event
{
    protected $failed = false;
    /**
     * Report conflicting login found
     */
    function setFailureAndStop()
    {
        $this->failed = true;
        $this->stop();
    }
    /** Return true if login is unique (no problems reported by hooks) */
    function isUnique() { return!$this->failed;  }
}

/**
 * @method string getEmail()
 * @method int|null getUserId() if null, we are checking in signup
 */
class Am_Event_CheckUniqEmail extends Am_Event
{
    protected $failed = false;
    /**
     * Report conflicting login found
     */
    function setFailureAndStop()
    {
        $this->failed = true;
        $this->stop();
    }
    /** Return true if login is unique (no problems reported by hooks) */
    function isUnique() { return!$this->failed;  }
}

class Am_Event_ValidateSavedForm extends Am_Event_ValidateRequest
{
    protected $param = 'signup';
}

class Am_Event_SetPassword extends Am_Event_User
{

    protected $plaintextPass;
    protected $saved = array();

    public function __construct(User $user, $plaintextPass)
    {
        parent::__construct($user);
        $this->plaintextPass = $plaintextPass;
    }

    /**
     *
     * @return string new plain-text password
     */
    public function getPassword()
    {
        return $this->plaintextPass;
    }

    public function addSaved(SavedPass $saved)
    {
        $this->saved[$saved->format] = $saved;
    }

    /** @return SavedPass|null */
    public function getSaved($format)
    {
        return empty($this->saved[$format]) ? null : $this->saved[$format];
    }

}

class Am_Event_GlobalIncludes extends Am_Event
{
    protected $includes = array();
    function add($fn) { $this->includes[] = $fn; }
    function get()  {   return $this->includes;   }
}

class Am_Event_Rebuild extends Am_Event 
{
    protected $doneString;
    /** if plugin called this function with a status string, 
     *  next iteration of the rebuild will be runned for this plugin
     */
    function setDoneString($doneString)
    {
        $this->doneString = $doneString;
    }
    function setDone()
    {
        $this->doneString = null;
    }
    function getDoneString()
    {
        return $this->doneString;
    }
    /** @return bool if another iteration is necessary */
    function needContinue() { return strlen($this->doneString); }
}

class Am_Event_SetupForms extends Am_Event
{
    /** @var AdminSetupController */
    protected $setup;
    public function __construct($setup)
    {
        parent::__construct();
        $this->setup = $setup;
    }
    public function addForm(Am_Form_Setup $form)
    {
        return $this->setup->addForm($form);
    }
    /** @return Am_Form_Setup */
    public function getForm($id)
    {
        return $this->setup->getForm($id, false);
    }
}
class Am_Event_UserForm extends Am_Event
{
    const INIT = 'init';
    const VALUES_TO_FORM = 'valuesToForm';
    const VALUES_FROM_FORM = 'valuesFromForm';
    const BEFORE_SAVE = 'beforeSave';
    const AFTER_SAVE = 'afterSave';
    protected $form;
    protected $values;
    protected $user;
    protected $action;
    public function __construct($action, Am_Form_Admin_User $form, User $user, $values)
    {
        parent::__construct();
        $this->form = $form;
        $this->action = $action;
        $this->values = $values;
        $this->user = $user;
    }
    /** @return Am_Form_Admin_User */
    public function getForm()
    {
        return $this->form;
    }
    public function __set($k, $v)
    {
        $this->values[$k] = $v;
    }
    public function __get($k)
    {
        return isset($this->values[$k]) ? $this->values[$k] : null;
    }
    public function __isset($k)
    {
        return isset($this->values[$k]);
    }
    /** @return User */
    public function getUser() { return $this->user; }
    public function getValues() { return $this->values; }
    public function setValues(array $values) { $this->values = $values; }
    public function getAction() { return $this->action; }
}

class Am_Event_UserTabs extends Am_Event
{
    protected $tabs;
    
    /** @var bool */
    protected $insert;
    protected $userId;

    public function __construct(Am_Navigation_UserTabs $tabs, $isInsert, $userId)
    {
        $this->tabs = $tabs;
        $this->insert = (bool)$isInsert;
        $this->userId = $userId;
    }
    /** @return Am_Navigation_UserTabs */
    public function getTabs()
    {
        return $this->tabs;
    }
    /** @return bool */
    public function isInsert()
    {
        return (bool)$this->insert;
    }
    public function getUserId()
    {
        return $this->userId;
    }
}

class Am_Event_AfterRender extends Am_Event
{
    /** @return int count of replaced patterns */
    public function replace($pattern, $replacement, $limit = -1)
    {
        $this->vars['output'] = preg_replace($pattern, $replacement, $this->vars['output'], $limit, $count);
        return (int) $count;
    }
    public function setOutput($output)
    {
        $this->vars['output'] = $output;
    }
}

