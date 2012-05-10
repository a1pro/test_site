<?php

class SignupController extends Am_Controller
{
    /** @var Am_Form_Signup */
    protected $form;
    /** @var array */
    protected $vars;
    
    
    function loadForm()
    {
        if ($c = $this->getFiltered('c'))
        {
            if ($c == 'cart'){
                if($this->getDi()->auth->getUser() != null)
                    $this->_redirect('cart/');
                else
                    $this->record = $this->getDi()->savedFormTable->getByType(SavedForm::T_CART);
            }else
                $this->record = $this->getDi()->savedFormTable->findFirstBy(array(
                    'code' => $c, 
                    'type' => SavedForm::T_SIGNUP,
                ));
        }
        else 
        {
            $this->record = $this->getDi()->savedFormTable->getDefault
            (
                $this->getDi()->auth->getUserId() ? 
                    SavedForm::D_MEMBER : SavedForm::D_SIGNUP 
            );
        }
        
        if (!$this->record)
            throw new Am_Exception_InputError("Wrong signup form code - the form does not exists");
        /* @var $this->record SavedForm */
        if (!$this->record->isSignup())
            throw new Am_Exception_InputError("Wrong signup form loaded [$this->record->saved_form_id] - it is not a signup form!");
    }
    
    function indexAction()
    {
        /*==TRIAL_SPLASH==*/
        $this->loadForm();
        $this->view->title = $this->record->title;
        $this->form = new Am_Form_Signup();
        $this->form->setParentController($this);
        $this->form->initFromSavedForm($this->record);
        $this->form->run();
    }
    function display(Am_Form $form, $pageTitle)
    {
        $this->view->form = $form;
        $this->view->title = $this->record->title;
        if ($pageTitle) $this->view->title = $pageTitle;
        $this->view->display($this->record->tpl ? ('signup/' . basename($this->record->tpl)) : 'signup/signup.phtml');
    }
    function autoLoginIfNecessary()
    {
        if ($this->getConfig('auto_login_after_signup') || ($this->record->type == SavedForm::T_CART))
        {
            $this->user->refresh();
            $this->getDi()->auth
                 ->setUser($this->user, $_SERVER['REMOTE_ADDR'])
                 ->onSuccess(); // run hook
        }
    }
    function process(array $vars, $name, HTML_QuickForm2_Controller_Page $page)
    {
        $this->vars = $vars;
        // do actions here
        $this->user = $this->getDi()->auth->getUser();
        if ($this->getSession()->signup_member_id && $this->getSession()->signup_member_login)
        {
            $user = $this->getDi()->userTable->load((int)$this->getSession()->signup_member_id, false);
            if ($user && ((($this->getDi()->time - strtotime($user->added)) < 24*3600) && ($user->status == User::STATUS_PENDING)))
            {
                // prevent attacks as if someone has got ability to set signup_member_id to session
                if ($this->getSession()->signup_member_login == $user->login)
                    /// there is a potential problem
                    /// because user password is not updated second time - @todo
                    $this->user = $user;
                else {
                    $this->getSession()->signup_member_id = null;
                    $this->getSession()->signup_member_login = null;
                }
            } else {
                $this->getSession()->signup_member_id = null;
            }
        }

        if (!$this->user)
        {
            $this->user = $this->getDi()->userRecord;
            $this->user->setForInsert($this->vars); // vars are filtered by the form !
            
            if (empty($this->user->login))
                $this->user->generateLogin();
                            
            if (empty($this->vars['pass']))
                $this->user->generatePassword();
            else {
                $this->user->setPass($this->vars['pass']);
            }
            $this->user->insert();
            $this->getSession()->signup_member_id = $this->user->pk();
            $this->getSession()->signup_member_login = $this->user->login;
            $this->autoLoginIfNecessary();
            // user inserted
            $this->getDi()->hook->call(Am_Event::SIGNUP_USER_ADDED, array(
                'vars' => $this->vars,
                'user' => $this->user,
                'form' => $this->form,
            ));
            if ($this->getDi()->config->get('registration_mail'))
                $this->user->sendRegistrationEmail();
        } else {
            if ($this->record->isCart())
                $this->_redirect('cart/');
            unset($this->vars['pass']);
            unset($this->vars['login']);
            unset($this->vars['email']);
            unset($this->vars['name_f']);
            unset($this->vars['name_l']);
            $this->user->setForUpdate($this->vars)->update();
            // user updated
            $this->getDi()->hook->call(Am_Event::SIGNUP_USER_UPDATED, array(
                'vars' => $this->vars,
                'user' => $this->user,
                'form' => $this->form,
            ));
        }
        
        // keep reference to e-mail confirmation link so it still working after signup
        if (!empty($this->vars['code']))
        {
            $this->getDi()->store->setBlob(Am_Form_Signup_Action_SendEmailCode::STORE_PREFIX . $this->vars['code'], 
                $this->user->pk(), '+7 days');
        }
        
        if ($this->record->isCart())
        {
            $this->_redirect('cart/');
            return true;
        }

        /// now the ordering process
        $invoice = $this->getDi()->invoiceRecord;
        $invoice->setUser($this->user);
        foreach ($this->vars as $k => $v)
            if (strpos($k, 'product_id')===0)
                foreach ((array)$this->vars[$k] as $product_id)
                {
                    @list($product_id, $plan_id) = explode('-', $product_id, 2);
                    $p = $this->getDi()->productTable->load($product_id);
                    if ($plan_id > 0) $p->setBillingPlan(intval($plan_id));
                    $invoice->add($p, 1);
                }
        if (!empty($this->vars['coupon']))
        {
            $invoice->setCouponCode($this->vars['coupon']);
            $invoice->validateCoupon();
        }
        $invoice->calculate();
        $invoice->setPaysystem($this->vars['paysys_id']);
        
        $payProcess = new Am_Paysystem_PayProcessMediator($this, $invoice);
        try {
            $result = $payProcess->process();
        } catch (Am_Exception_Redirect $e) {
            $invoice->refresh();
            if ($invoice->isCompleted())
            { // relogin customer if free subscription was ok
                $this->autoLoginIfNecessary();
            }
            throw $e;
        }
        // if we got back here, there was an error in payment!
        /** @todo offer payment method if previous failed */
        
        $page = $this->form->findPageByElementName('paysys_id');
        if (!$page) $page = $this->form->getFirstPage(); // just display first page
        foreach ($page->getForm()->getElementsByName('paysys_id') as $el)
            $el->setValue(null)->setError(current($result->getErrorMessages()));
        $page->handle('display');
        return false;
   }

   function getCurrentUrl()
   {
       $c = $this->getFiltered('c');
       return $this->_request->getScheme() . '://' .
              $this->_request->getHttpHost() .
              $this->_request->getBaseUrl() . '/' .
              $this->_request->getControllerName() . '/' .
              $this->_request->getActionName() .  '/' .
              ($c ? "c/$c/" : '');
   }
}
