<?php

class Aff_SignupController extends Am_Controller
{
    /** @var Am_Form_Signup */
    protected $form;
    /** @var array */
    protected $vars;
    /** @var SavedForm */
    protected $record;
    
    function indexAction()
    {
        if ($this->getDi()->auth->getUserId())
            $this->_redirect('aff/aff'); // there are no reasons to use this form if logged-in
        $this->record = $this->getDi()->savedFormTable->getByType(SavedForm::D_AFF);
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
        $this->view->display('signup/signup.phtml');
    }
    function process(array $vars, $name, HTML_QuickForm2_Controller_Page $page)
    {
        $this->vars = $vars;
        $em = $page->getController()->getSessionContainer()->getOpaque('EmailCode');
        // do actions here
        $this->user = $this->getDi()->userRecord;
        $this->user->setForInsert($this->vars); // vars are filtered by the form !
        $this->user->is_affiliate = 1;
        
        if (empty($this->user->login))
            $this->user->generateLogin();

        if (empty($this->vars['pass']))
            $this->user->generatePassword();
        else {
            $this->user->setPass($this->vars['pass']);
        }
        $this->user->insert();
        // remove verification record
        if (!empty($em))
            $this->getDi()->store->delete(Am_Form_Signup_Action_SendEmailCode::STORE_PREFIX . $em);
        $page->getController()->destroySessionContainer();
        $this->getDi()->hook->call(Am_Event::SIGNUP_USER_ADDED, array(
            'vars' => $this->vars,
            'user' => $this->user,
            'form' => $this->form,
        ));
        $this->getDi()->hook->call(Am_Event::SIGNUP_AFF_ADDED, array(
            'vars' => $this->vars,
            'user' => $this->user,
            'form' => $this->form,
        ));
        $this->getDi()->auth->setUser($this->user, $_SERVER['REMOTE_ADDR']);

        if ($this->getDi()->config->get('aff.registration_mail'))
        {
            if ($et = Am_Mail_Template::load('aff.registration_mail', $this->user->lang))
            {
                $et->setUser($this->user);
                $et->password = $this->user->getPlaintextPass();
                $et->send($this->user);
            }                        
        }
        $this->_redirect('aff/aff');
        return true;
   }

   function getCurrentUrl()
   {
       $c = $this->getFiltered('c');
       return $this->_request->getScheme() . '://' .
              $this->_request->getHttpHost() .
              $this->_request->getBaseUrl() . '/' .
              $this->_request->getModuleName() . '/' .
              $this->_request->getControllerName();
   }
    
}