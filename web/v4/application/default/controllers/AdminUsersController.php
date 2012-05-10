<?php

/**
 * This hook allows you to modify user form once it is created
 * you can add/remove elements as you wish
 * Please add additional fields with _ prefix so it does not interfere
 * with User table fields
 */
class Am_Form_Admin_User extends Am_Form_Admin {
    /** @var User */
    protected $record;

    function __construct($record)
    {
        $this->record = $record;
        parent::__construct('user');
    }
    
    function checkUniqLogin(array $group)
    {
        $login = $group['login'];
        if (!preg_match(Am_Di::getInstance()->userTable->getLoginRegex(), $login))
            return ___('Username contains invalid characters - please use digits and letters');
        if ($this->record->getTable()->checkUniqLogin($login, $this->record ? $this->record->pk() : null) === 0)
            return ___('Username %s is already taken. Please choose another username', Am_Controller::escape($login));
    }
    function checkUniqEmail(array $group)
    {
        $email = $group['email'];
        if (!Am_Validate::email($email))
            return ___('Please enter valid Email');
        if ($this->record->getTable()->checkUniqEmail($email, $this->record ? $this->record->pk() : null) === 0)
            return ___('An account with the same email already exists.');
    }

    function init()
    {
        /* General Settings */
        $fieldSet = $this->addElement('fieldset', 'general', array('id'=>'general', 'label' => ___('General')));

        $loginGroup = $fieldSet->addGroup('', array('id' => 'login',))->setLabel(___('Username'));
        $login = $loginGroup->addElement('text', 'login', array('size' => 20));
        $login->addRule('required');
        $loginGroup->addRule('callback2', '-error-', array($this, 'checkUniqLogin'));

        $comment = $fieldSet->addTextarea("comment", array('style'=>"width:90%", 'id' => 'comment'), array('label' => ___('Comment')));
        
        if ($this->record && $this->record->pk())
        {
            $url = Am_Controller::escape(Am_Controller::makeUrl('admin-users', 'login-as', null, array('id' => $this->record->pk())));
            $loginGroup->addStatic('_login_as')->setContent("&nbsp;<a href='$url' target='_blank'>".___("login as user")."</a>");
        }

        $pass = $fieldSet->addElement('password', '_pass', array('size' => 20, 'autocomplete'=>'off'))->setLabel(___('New Password'));
        //$pass0 = $gr->addElement('password', '_pass0', array('size' => 20));
        //$pass0->addRule('eq', 'Password confirmation must be equal to Password', $pass);
        if ($this->getAttribute('_a_') == 'insert')
            $pass->addRule('required');
        
        $nameField = $fieldSet->addGroup('', array('id' => 'name'), array('label' => ___('Name')));
        $nameField->addElement('text', 'name_f', array('size'=>20));
        $nameField->addElement('text', 'name_l', array('size'=>20));

        $gr = $fieldSet->addGroup()->setLabel(___('E-Mail Address'));
        $gr->addElement('text', 'email', array('size' => 40))->addRule('required');
        $gr->addRule('callback2', '-error-', array($this, 'checkUniqEmail'));
        
        $fieldSet->addElement('text', 'phone', array('size' => 20))->setLabel(___('Phone Number'));
        
        if ($this->record && $this->record->isLoaded())
        {
            $resendText = Am_Controller::escape(___("Resend Signup E-Mail"));
            $sending = Am_Controller::escape(___('sending'));
            $sent = Am_Controller::escape(___('sent successfully'));
            $id = $this->record->pk();
            $gr->addElement('static')->setContent(<<<CUT
<input type='button' value='$resendText' id='resend-signup-email' />
<script type='text/javascript'>
$(function(){
$("#resend-signup-email").click(function(){
    var btn = this;
    var txt = btn.value;
    btn.value += '...($sending)...';
    $.post(window.rootUrl + '/admin-users/resend-signup-email', {id: $id}, function(){
        btn.value = txt + '...($sent)';
        setTimeout(function(){ btn.value = txt; }, 600);
    });
});
});
</script>
CUT
            );
        }
        $isLocked = $fieldSet->addElement('advradio', 'is_locked', array('id' => 'is_locked', ))
            ->loadOptions(array(
                ''   => 'No',
                '1'  => '<font color=red><b>'.___("Yes, locked").'</b></font>',
                '-1' => '<i>'.___("Disable auto-locking for this customer").'</i>',
            ))->setLabel(___('Is Locked'));
        
        if (Am_Di::getInstance()->config->get('manually_approve'))
        {
            $fieldSet->addElement('advcheckbox', 'is_approved', array('id' => 'is_approved'))
                ->setLabel(___('Is Approved'));
        }

        $fieldSet->addElement('advradio', 'unsubscribed', array('id' => 'unsubscribed'))
            ->setLabel(___("Is Unsubscribed?
if enabled, this will
unsubscribe the customer from:
* messages that you send from aMember Cp, 
* autoresponder messages,
* subscription expiration notices"))
            ->loadOptions(array(
                ''   => ___('No'),
                '1'  => ___('Yes, do not e-mail this customer for any reasons'),
            ));

        if ($this->record->isLoaded()) {
            $fieldSet->addStatic('_signup_info', null, array('label' => ___('Signup Info')))->setContent(
                sprintf("<div>%s</div>", $this->record->added . ' / ' . $this->record->remote_addr)
            );
        }
        
        if (Am_Di::getInstance()->config->get('use_user_groups'))
        {
            $group = $this->addGroup('', array('id' => 'user_groups'))->setLabel(___('User Groups'));
            
            $groups = $group->addSelect('_groups', 
                array('multiple'=>'multiple', 'class'=>'magicselect'));
            $groups->loadOptions(Am_Di::getInstance()->userGroupTable->getSelectOptions());
            $group->addHtml()->setHtml(sprintf('<a href="%s" target="_blank">%s<a/>', 
                Am_Controller::escape(REL_ROOT_URL . '/admin-user-groups'),
                ___("Edit Groups")));
        }

        /* Address Info */
        $this->insertAddressFields();

        $this->insertAdditionalFields();

        $event = new Am_Event_UserForm(Am_Event_UserForm::INIT, $this, $this->record, array());
        $event->run();
    }
    function insertAddressFields()
    {
        $fieldSet = $this->addElement('advfieldset', 'address', array('id' => 'address_info'))
            ->setLabel(___('Address Info'));
        $fieldSet->addText('street')->setLabel(___('Street Address'));
        $fieldSet->addText('city')->setLabel(___('City'));
        $fieldSet->addText('zip')->setLabel(___('ZIP Code'));

        $fieldSet->addSelect('country')->setLabel(___('Country'))
            ->setId('f_country')
            ->loadOptions(Am_Di::getInstance()->countryTable->getOptions(true));

        $group = $fieldSet->addGroup()->setLabel(___('State'));
        $state =$group->addSelect('state')
            ->setId('f_state');
        if (!empty($this->record->country))
            $state->loadOptions(Am_Di::getInstance()->stateTable->getOptions($this->record->country, true));
        $group->addText('state')->setId('t_state')->setAttribute('disabled', 'disabled');
    }

    function insertAdditionalFields() {
        $fieldSet = $this->getElementById('general');
        $fields = Am_Di::getInstance()->userTable->customFields()->getAll();
        $exclude = array(
        );
        foreach ($fields as $k => $f)
            if (!in_array($f->name, $exclude) && strpos($f->name, 'aff_')!==0)
                $el = $f->addToQf2($fieldSet);
    }
    protected function renderClientRules(HTML_QuickForm2_JavascriptBuilder $builder)
    {
        $generate = ___("generate");
        $builder->addElementJavascript(<<<CUT
$(document).ready(function(){
    var pass0 = $("input#_pass-0").after("&nbsp;<a href='javascript:' id='generate-pass'>$generate</a>");
    $("a#generate-pass").click(function(){
        if (pass0.attr("type")!="text")
        {
            pass0.replaceWith("<input type='text' name='"+pass0.attr("name")
                    +"' id='"+pass0.attr("id")
                    +"' size='"+pass0.attr("size")
                    +"' />");
            pass0 = $("input#_pass-0");
        }
        var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz";
        var pass = "";
        length = 9;
        for(i=0;i<length;i++)
        {
            x = Math.floor(Math.random() * 62);
            pass += chars.charAt(x);
        }
        pass0.val(pass);
    });
});            
CUT
        );    
    }
}
    
class Am_Grid_Action_Group_EmailUsers extends Am_Grid_Action_Group_Abstract {
    protected $needConfirmation = false;
    
    public function __construct()
    {
        parent::__construct('email-users', ___('E-Mail Users'));
        $this->setTarget('_top');
    }
    public function handleRecord($id, $record)
    {
        ;
    }
    public function doRun(array $ids)
    {
        if ($ids[0] == self::ALL)
        {
            $search = urlencode($this->grid->getDataSource()->serialize());
        } else {
            $q = new Am_Query_User;
            $q->setPrefix('search');
            $vars['search']['member_id_filter']['val'] = join(',',$ids);
            $q->setFromRequest($vars);
            $search = urlencode($q->serialize());
        }
        $this->grid->redirect(REL_ROOT_URL . '/admin-email?search-type=advanced&search='.$search);
    }
}

class Am_Grid_Action_Group_MassSubscribe extends Am_Grid_Action_Group_Abstract 
{
    protected $needConfirmation = true;
    protected $form;
    protected $_vars, $_products;
    public function __construct()
    {
        parent::__construct('mass_subscribe', ___('Mass Subscribe'));
        $this->setTarget('_top');
    }
    public function _getProduct($id)
    {
        if (!$this->_products[$id]) 
        {
            $this->_products[$id] = Am_Di::getInstance()->productTable->load($id);
        }    
        return $this->_products[$id];
    }
    public function handleRecord($id, $record)
    {
        if (!$this->_vars['add_payment'])
        {
            $a = $this->grid->getDi()->accessRecord;
            $a->begin_date = $this->_vars['start_date'];
            $a->expire_date = $this->_vars['expire_date'];
            $a->product_id = $this->_vars['product_id'];
            $a->user_id = $id;
            $a->insert();
        } else {
            $invoice = $this->grid->getDi()->invoiceRecord;
            $invoice->user_id = $id;
            $invoice->add($this->_getProduct($this->_vars['product_id']));
            $invoice->paysys_id = 'free';
            $invoice->comment = 'mass-subscribe';
            $invoice->calculate();
            $invoice->save();
            
            $tr = new Am_Paysystem_Transaction_Manual($this->grid->getDi()->plugins_payment->loadGet('free'));
            $tr->setAmount($this->_vars['amount']);
            $tr->setTime(new DateTime($this->_vars['start_date']));
            $tr->setReceiptId('mass-subscribe-'.uniqid());
            $invoice->addPayment($tr);
        }
    }
    
    public function getForm()
    {
        if (!$this->form)
        {
            $id = $this->grid->getId();
            $this->form = new Am_Form_Admin;
            $sel = $this->form->addSelect($id . '_product_id')->setLabel(___('Product'));
            $sel->loadOptions(Am_Di::getInstance()->productTable->getOptions(false));
            $sel->addRule('required');
            $dates = $this->form->addGroup()->setLabel(___('Start and Expiration Dates'));
            $dates->addRule('required');
            $dates->addDate($id.'_start_date')->addRule('required');
            $dates->addDate($id.'_expire_date')->addRule('required');
            $pg = $this->form->addCheckboxedGroup($id.'_add_payment')->setLabel(___('Additionally to "Access", add "Invoice" and "Payment" record with given %s amount, like they have really made a payment', Am_Currency::getDefault()));
            $pg->addStatic()->setContent('Payment Record amount, ex.: 19.99');
            $pg->addText($id.'_amount')->addRule('regex', 'must be a number', '/^(\d+)(\.\d+)?$/');
            $this->form->addSaveButton(___('Mass Subscribe'));
        }
        return $this->form;
    }
    
    public function renderConfirmationForm($btn = null, $page = null, $addHtml = null)
    {
        $this->getForm();
        $vars = $this->grid->getCompleteRequest()->toArray();
        $vars[$this->grid->getId() . '_confirm'] = 'yes';
        if ($page !== null)
        {
            $vars[$this->grid->getId() . '_group_page'] = (int)$page;
        }
        foreach ($vars as $k => $v)
            if (!$this->form->getElementsByName($k))
                $this->form->addHidden($k)->setValue($v);
        $url_yes = $this->grid->makeUrl(null);
        $this->form->setAction($url_yes);;
        echo (string)$this->form;
    }
    public function run()
    {
        if (!$this->getForm()->validate())
        {
            echo $this->renderConfirmationForm();
        } else {
            $prefix = $this->grid->getId().'_';
            foreach ($this->getForm()->getValue() as $k => $v)
            {
                if (strpos($k, $prefix)===0)
                    $this->_vars[substr($k, strlen($prefix))] = $v;
            }
            // disable emailing
            Am_Mail::setDefaultTransport(new Am_Mail_Transport_Null);
            return parent::run();
        }
    }
}


class Am_Grid_Filter_User extends Am_Grid_Filter_Abstract
{
    protected $title;
    public function __construct()
    {
        $this->title = ___("Filter By Username/Name/E-Mail/Invoice#/Receipt#");
        parent::__construct();
    }
    public function getVariablesList()
    {
        $ret = parent::getVariablesList();
        $ret[] = 'search';
        $ret[] = 'search_load';
        return $ret;
    }
    protected function applyFilter()
    {
        // done in initFilter
    }
    protected function renderButton()
    {
        $title = Am_Controller::escape(___('Advanced Search'));
        return parent::renderButton().
          "<span style='margin-left: 1em;'></span>" . 
          "<input type='button' value='$title' onclick='toggleAdvancedSearch(this)'>";
    }
    function getTitle()
    {
        $query = $this->grid->getDataSource();
        $conditions = $query->getConditions();
        $title = "";
        if (count($conditions)>1 || (count($conditions)==1 && !$conditions[0] instanceof Am_Query_User_Condition_Filter))
        {
            $selfUrl = $this->grid->escape($this->grid->makeUrl(null));
            if ($name = Am_Controller::escape($query->getName())) {
                $desc = ___("Saved Search") . ": <b>$name</b>";
            } else {
                $desc  = "<a href='{$selfUrl}' class='red'><b>".___("Filtered").":</b></a>&nbsp;";
                $desc .= $query->getDescription();
                $desc .= "&nbsp;<a href='javascript:' onclick='saveAdvancedSearch(this)'>".___("Save This Search")."</a>";
            }
            $title = "<div style='text-align:left;float:left;'>&nbsp;"
                .$desc
                .'</div>' . PHP_EOL;
        }        
        return $title . parent::getTitle();
    }
    public function renderInputs()
    {
        return $this->renderInputText('filter');
    }
    public function initFilter(Am_Grid_ReadOnly $grid)
    {
        parent::initFilter($grid);
        $query = $grid->getDataSource();
        $query->setPrefix('_u_search');
        /* @var $query Am_Query_User */
        if ($id = $grid->getRequest()->getInt('search_load')){
            $query->load($id);
        } elseif (is_string($this->vars['filter']) && $this->vars['filter']){
            $cond = new Am_Query_User_Condition_Filter();
            $cond->setFromRequest(array('filter' => array('val' => $this->vars['filter'])));
            $query->add($cond);
        } else {
            $query->setFromRequest($grid->getCompleteRequest());
        }
    }
    public function isFiltered()
    {
        return (bool)$this->grid->getDataSource()->getConditions();
    }
}

class AdminUsersController extends Am_Controller_Grid
{
    public function preDispatch()
    {
        parent::preDispatch();
        $this->setActiveMenu($this->getParam('_u_a')=='insert' ? 'users-insert' : 'users-browse');
    }
    
    public function getNotConfirmedCount()
    {
        return $this->getDi()->db->selectCell("SELECT COUNT(*) FROM ?_store 
            WHERE name LIKE 'signup_record-%' AND CHAR_LENGTH(blob_value)>10");
    }
    
    public function notConfirmedAction()
    {
        $arr = array();
        foreach ($this->getDi()->db->select("SELECT `blob_value`, expires FROM ?_store 
            WHERE name LIKE 'signup_record-%' AND CHAR_LENGTH(blob_value)>10") as $row)
        {
            $v = unserialize($row['blob_value']);
            $rec = array();
            foreach ($v['values'] as $page)
            {
                $rec = array_merge($rec, $page);
            }
            $rec['expires'] = amDatetime($row['expires']);
            $link = Am_Controller::escape($v['opaque']['ConfirmUrl']);
            $rec['link'] = 'Give this link to customer if e-mail confirmation has not been received:'.
                '<br /><br /><pre>' . $link . '</pre><br />';
            if (empty($rec['login'])) $rec['login'] = null;
            if (empty($rec['name_f'])) $rec['name_f'] = null;
            if (empty($rec['name_l'])) $rec['name_l'] = null;
            $arr[] = (object)$rec;
        }
        
        $ds = new Am_Grid_DataSource_Array($arr);
        $grid = new Am_Grid_Editable('_usernc', ___("Not Confirmed Users"), 
            $ds, $this->_request, $this->view, $this->getDi());
        $grid->addField('login', ___('Username'));
        $grid->addField('email', ___('E-Mail'));
        $grid->addField('name_f', ___('First Name'));
        $grid->addField('name_l', ___('Last Name'));
        $grid->addField('expires', ___('Expires'));
        $grid->addField(new Am_Grid_Field_Expandable('link', ___('Link')))->setEscape(false);
        $grid->actionsClear();
        
        $this->view->content = $grid->runWithLayout('admin/layout.phtml');
    }
    
    public function autocompleteAction()
    {
        $term = '%' . $this->getFiltered('term') . '%';
        if (!$term) return null;
        $q = new Am_Query($this->getDi()->userTable);
        $q->addWhere('(t.login LIKE ?) OR (t.email LIKE ?) OR (t.name_f LIKE ?) OR (t.name_l LIKE ?)', 
            $term, $term, $term, $term);
        $qq = $q->query(0, 10);
        $ret = array();
        while ($r = $this->getDi()->db->fetchRow($qq))
        {
            $ret[] = array
            (
                'label' => sprintf('%s / "%s" <%s>', $r['login'], $r['name_f'] . ' ' . $r['name_l'], $r['email']), 
                'value' => $r['login']
            );
        }
        if ($q->getFoundRows() > 10)
            $ret[] = array(
                'label' => sprintf("... %d more rows found ...", $q->getFoundRows() - 10),
                'value' => null,
            );
        $this->ajaxResponse($ret);
    }
    
    public function indexAction()
    {
        if (in_array($this->grid->getCurrentAction(), array('edit','insert')))
            $this->layout = 'admin/user-layout.phtml';
        parent::indexAction();
    }
    
    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission('grid_u');
    }
    
    public function createGrid()
    {
        $ds = new Am_Query_User;
        $ds->addField("concat(name_f, ' ', name_l)", 'name')
          ->addField('(SELECT count(p.invoice_payment_id) FROM ?_invoice_payment p WHERE p.user_id = u.user_id)',  'payments_count')
          ->addField('IFNULL((SELECT sum(p.amount) FROM ?_invoice_payment p WHERE p.user_id = u.user_id),0)-' .
                     'IFNULL((SELECT sum(r.amount) FROM ?_invoice_refund r WHERE u.user_id=r.user_id),0)',  
              'payments_sum');
        $ds->setOrder("login");
        $grid = new Am_Grid_Editable('_u', ___("Browse Users"), $ds, $this->_request, $this->view);
        $grid->setRecordTitle(___('User'));
        $grid->addField(new Am_Grid_Field('login', ___('Username'), true))->setRenderFunction(array($this, 'renderLogin'));
        $grid->addField(new Am_Grid_Field('name', ___('Name'), true));
        $grid->addField(new Am_Grid_Field('email', ___('E-Mail Address'), true));
        $grid->addField(new Am_Grid_Field('payments_sum', ___('Payments'), true, null, array($this, 'renderPayments')));
        $grid->addField('status', ___('Status'), true)->setRenderFunction(array($this, 'renderStatus'));
        $grid->actionAdd($this->createActionExport());
        $grid->actionGet('edit')->setTarget('_top')->showFormAfterSave(true);
        $grid->actionGet('insert')->setTarget('_top')->showFormAfterSave(true);
        $grid->setForm(array($this, 'createForm'));
        $grid->addCallback(Am_Grid_Editable::CB_BEFORE_SAVE, array($this, 'beforeSave'));
        $grid->addCallback(Am_Grid_Editable::CB_AFTER_SAVE, array($this, 'afterSave'));
        $grid->addCallback(Am_Grid_Editable::CB_VALUES_TO_FORM, array($this, 'valuesToForm'));
        $grid->addCallback(Am_Grid_Editable::CB_VALUES_FROM_FORM, array($this, 'valuesFromForm'));
        $grid->addCallback(Am_Grid_Editable::CB_RENDER_STATIC, array($this, 'renderStatic'));
        $grid->addCallback(Am_Grid_ReadOnly::CB_TR_ATTRIBS, array($this, 'getTrAttribs'));
        
        $grid->actionAdd(new Am_Grid_Action_Group_Callback('lock', ___("Lock"), array($this, 'lockUser')));
        $grid->actionAdd(new Am_Grid_Action_Group_Callback('unlock', ___("Unlock"), array($this, 'unlockUser')));
        $grid->actionAdd(new Am_Grid_Action_Group_EmailUsers());
        $grid->actionAdd(new Am_Grid_Action_Group_MassSubscribe());
        
        $nc_count = $this->getDi()->cacheFunction->call(array($this, 'getNotConfirmedCount'), 
            array(), array(), 60);
        if ($nc_count)
        {
            $grid->actionAdd(new Am_Grid_Action_Url('not-confirmed', 
                    ___("Not Confirmed Users") . "($nc_count)", 
                    REL_ROOT_URL . '/admin-users/not-confirmed'))
                ->setType(Am_Grid_Action_Abstract::NORECORD)
                ->setTarget('_top');
        }
        $grid->setFilter(new Am_Grid_Filter_User());
//        $grid->addAction(new Am_Grid_Action_Group_Callback('email', ___("E-Mail"), array($this, 'email')));
        
        return $grid;
    }

    public function getTrAttribs(& $ret, $record)
    {
        if ($record->is_locked
            || (!$record->isApproved() && $this->getDi()->config->get('manually_approve')))
        {
            $ret['class'] = isset($ret['class']) ? $ret['class'] . ' disabled' : 'disabled';
        }
    }
    
    protected function createActionExport() {
        $action = new Am_Grid_Action_Export();
        $action->addField(new Am_Grid_Field('user_id', ___('User Id')))
                ->addField(new Am_Grid_Field('login', ___('Login')))
                ->addField(new Am_Grid_Field('email', ___('Email')))
                ->addField(new Am_Grid_Field('name_f', ___('First Name'))) 
                ->addField(new Am_Grid_Field('name_l', ___('Last Name')))       
                ->addField(new Am_Grid_Field('street', ___('Street')))
                ->addField(new Am_Grid_Field('city', ___('City')))
                ->addField(new Am_Grid_Field('state', ___('State')))
                ->addField(new Am_Grid_Field('zip', ___('ZIP Code')))
                ->addField(new Am_Grid_Field('country', ___('Country')))
                ->addField(new Am_Grid_Field('phone', ___('Phone')))
                ->addField(new Am_Grid_Field('added', ___('Added')))
                ->addField(new Am_Grid_Field('status', ___('Status')))
                ->addField(new Am_Grid_Field('unsubscribed', ___('Unsubscribed')))
                ->addField(new Am_Grid_Field('lang', ___('Language')))
                ->addField(new Am_Grid_Field('is_locked', ___('Is Locked')))
                ->addField(new Am_Grid_Field('comment', ___('Comment')))
                ->addField(new Am_Grid_Field('aff_id', ___('Affiliate Id#')));
        
        //Additional Fields
        foreach ($this->getDi()->userTable->customFields()->getAll() as $field) {
            if (isset($field->from_config) && $field->from_config) {
                if ($field->sql) {
                    $action->addField(new Am_Grid_Field($field->name, $field->title));
                } else {
                    //we use trailing __ to distinguish fields from data table
                    $action->addField(new Am_Grid_Field($field->name . '__', $field->title));
                }
            }
        }
        
        $action->setGetDataSourceFunc(array($this, 'getDS'));
        return $action;
    }

    public function getDS(Am_Query $ds, $fields) {
        $i = 0;
        //join only selected fields
        foreach ($fields as $field) {
            $fn = $field->getFieldName();
            if (substr($fn, -2) == '__') { //field from data table
                $i++;
                $field_name = substr($fn, 0, strlen($fn)-2);
                $ds = $ds->leftJoin("?_data", "d$i", "u.user_id = d$i.id AND d$i.table='user' AND d$i.key='$field_name'")
            ->addField("d$i.value", $fn);
            }
        }
        return $ds;
    }

    public function renderStatic(& $out, Am_Grid_Editable $grid)
    {
        $hidden = Am_Controller::renderArrayAsInputHiddens($grid->getFilter()->getAllButFilterVars());
        $out .= 
            "<!-- start of advanced search box -->\n" . 
            $grid->getDataSource()->renderForm($hidden) . "\n";
            "<!-- end of advanced search box -->\n"; 
    }
    public function lockUser($id, User $user)
    {
        $user->lock(true);
    }
    public function unlockUser($id, User $user)
    {
        $user->lock(false);
    }
    function renderLogin($record)
    {

        $icons = "";
        if ($record->isLocked())
            $icons .= $this->view->icon('user-locked', ___('User is locked'));
        if (!$record->isApproved() && $this->getDi()->config->get('manually_approve'))
            $icons .= $this->view->icon('user-not-approved', ___('User is not approved'));
        if ($icons) $icons = '<div style="float: right;">' . $icons . '</div>';

        return $this->renderTd(sprintf('%s<a target="_top" href="%s">%s</a>',
                $icons,
                $this->escape($this->grid->getActionUrl('edit', $record->user_id)),
                $this->escape($record->login)), false);
    }
    
    function renderStatus(User $record)
    {
        $text = "";
        switch ($record->status)
        {
            case User::STATUS_PENDING:
                if ($record->payments_count)
                    $text = '<i>'.___("Future").'</i>';
                else
                    $text = '<i>'.___("Pending").'</i>';
                break;
            case User::STATUS_ACTIVE:
                $text = '<b>'.___("Active").'</b>';
                break;
            case User::STATUS_EXPIRED:
                $text = sprintf('<span class="red">%s</span>', ___("Expired"));
                break;
        }
       return $this->renderTd($text, false);
    }
    function renderPayments(User $record)
    {
        if ($record->payments_count)
        {
            $curr = new Am_Currency();
            $curr->setValue($record->payments_sum);
            $text = $record->payments_count . ' - ' . $curr->toString();
        } else
            $text = ___('Never');
        $link = REL_ROOT_URL . "/admin-user-payments/index/user_id/{$record->user_id}";
        
        return sprintf('<td><a target="_top" href="%s#payments">%s</a></td>', $link, $text);
    }
    function createForm()
    {
        return new Am_Form_Admin_User($this->grid->getRecord());
    }
    
/*
    
    public function createRecord()
    {
        $record = parent::createRecord();
        $record->added = sqlTime('now');
        $record->remote_addr = $this->_request->getClientIp();
        $record->country = null;
        return $record;
    }

    function preDispatch()
    {
        if ($this->getParam('c')) {
            $pages = $this->getPages();
            $class = $pages[$this->_request->c];
            if ($class)
                $p = new $class($this->_request, $this->_response, $this->_invokeArgs);
            else
                throw new Am_Exception_InputError("[c] parameter is wrong - could not be handled");
            $p->dispatch($this->_request->getActionName() . 'Action');
            $this->setProcessed();
            return;
        }
        if ($id = $this->getParam('loadSearch')){
            $query = $this->getAdapter()->getQuery();
            $query->load($id);
        }
        parent::preDispatch();
        $amActiveMenuID = $this->getRequest()->getActionName() == 'insert' ? 'users-insert' : 'users-browse';
        $this->setActiveMenu($amActiveMenuID);
    }
    public function renderGrid($withWrap) {
        $ret = parent::renderGrid($withWrap);
        if ($withWrap) {
            $ret .= $this->getAdapter()->getQuery()->renderForm("");
        }
        return $ret;
    }
 * 
 */
    function saveSearchAction(){
        $q = new Am_Query_User();
        $search = $this->_request->get('search');
        $q->unserialize($search['serialized']);
        if (!$q->getConditions())
            throw new Am_Exception_InputError("Wrong parameters passed: no conditions : " . htmlentities($this->_request->search['serialized']));
        if (!strlen($this->getParam('name')))
            throw new Am_Exception_InputError(___("No search name passed"));
        $name = $this->getParam('name');
        $id = $q->setName($name)->save();
        $this->redirectLocation(REL_ROOT_URL . '/admin-users?_u_search_load=' . $id);
    }
    public function valuesFromForm(& $values, User $record)
    {
        $values = $this->getDi()->userTable->customFields()->valuesToTable($values);
        $event = new Am_Event_UserForm(Am_Event_UserForm::VALUES_FROM_FORM, $this->grid->getForm(), $record, $values);
        $event->run();
        $values = $event->getValues();
    }
    function valuesToForm(& $values, User $record)
    {
        $values['_groups'] = $record->getGroups();
        $values = $this->getDi()->userTable->customFields()->valuesFromTable($values);
        $event = new Am_Event_UserForm(Am_Event_UserForm::VALUES_TO_FORM, $this->grid->getForm(), $record, $values);
        $event->run();
        $values = $event->getValues();
    }
    function beforeSave(array &$values, User $record)
    {
        if (!empty($values['_pass']))
            $record->setPass($values['_pass']);
        $event = new Am_Event_UserForm(Am_Event_UserForm::BEFORE_SAVE, $this->grid->getForm(), $record, $values);
        $event->run();
        $values = $event->getValues();
    }
    function afterSave(array &$values, User $record)
    {
        $record->setGroups(array_filter((array)@$values['_groups']));
        $event = new Am_Event_UserForm(Am_Event_UserForm::AFTER_SAVE, $this->grid->getForm(), $record, $values);
        $event->run();
        $values = $event->getValues();
//        if ($this->grid->hasPermission(null, 'edit'))
//        {
//            $this->redirectLocation($this->getView()->userUrl($record->pk()));
//            exit();
//        }
    }
    function loginAsAction()
    {
        $id = $this->getInt('id');
        if (!$id) throw new Am_Exception_InputError("Empty or no id passed");

        $user = $this->getDi()->userTable->load($id);
        $this->getDi()->auth->setUser($user, $this->getRequest()->getClientIp())->onSuccess();
        $this->redirectLocation($this->getUrl('member', "index", null), ___("Logged-in as %s. Redirecting...", $user->login));
    }
    function accessLogAction()
    {
        require_once dirname(__FILE__) . '/AdminLogsController.php';
        $c = new AdminLogsController($this->getRequest(), $this->getResponse(), $this->getInvokeArgs());
        $grid = $c->createAccess();
        $grid->removeField('member_login');
        $grid->getDataSource()->addWhere('t.user_id=?d', (int)$this->getParam('user_id'));
        $grid->runWithLayout('admin/user-layout.phtml');
    }
    function notApprovedAction()
    {
        $this->_redirect('admin-users?_u_search[is_approved][val]=0');
    }
    function resendSignupEmailAction()
    {
        $id = $this->_request->getInt('id');
        if (!$id) throw new Am_Exception_InputError("Empty id");
        $user = $this->getDi()->userTable->load($id);
        $user->sendSignupEmail();
        $this->ajaxResponse(array('success' => true));
    }
}
