<?php 

class ProfileController extends Am_Controller
{
    /** @var int */
    protected $user_id;
    /** @var User */
    protected $user;

    protected $saved = false;
    
    const SECURITY_CODE_STORE_PREFIX ='member-verify-email-profile-';
    const SECURITY_CODE_EXPIRE = 48; //hrs
    const EMAIL_CODE_LEN = 10;

    function savedAction()
    {
        $this->view->assign('saved', true);
        $this->saved = true;
        $this->indexAction();
    }

    function indexAction()
    {
        $this->form = new Am_Form_Profile();
        $record = $this->getDi()->savedFormTable->getByType(SavedForm::T_PROFILE);
        if (!$record)
            throw new Am_Exception_Configuration("No profile form configured");
        
        $this->form->initFromSavedForm($record);
        $this->form->setUser($this->user);
        
        $u = $this->user->toArray();
        $u = $this->getDi()->userTable->customFields()->valuesFromTable($u);
        $u['visible_in_profile'][3] = 3;
        
        unset($u['pass']);

        $dataSources = array(
            new HTML_QuickForm2_DataSource_Array($u)
        );

        if ($this->form->isSubmitted()) {
            array_unshift($dataSources, $this->_request);
        }

        $this->form->setDataSources($dataSources);

        if ($this->form->isSubmitted() && $this->form->validate())
        {
            $vars = $this->form->getValue();
            unset($vars['user_id']);
            if (!empty($vars['pass']))
                $this->user->setPass($vars['pass']);
            unset($vars['pass']);
            
            $ve = $this->handleEmail($record, $vars) ? 1 : 0;
            
            $vars = $this->getDi()->userTable->customFields()->valuesToTable($vars);
            $this->user->setForUpdate($vars)->update();
            return $this->redirectLocation($this->getFullUrl() . '?a=saved&ve='.$ve);
        }
        
        if ($this->getRequest()->getParam('ve')) {
            $this->view->message = ___('Verification email has been sent to your address. 
                    E-mail will be changed in your account after confirmation');
        }
        
        $this->view->form = $this->form;
        $this->view->display('member/profile.phtml');
    }
    
    public function confirmEmailAction() {
        /* @var $user User */
        $em = $this->getRequest()->getParam('em');
        list($user_id, $code) = explode(':', $em);
        
        $data = $this->getDi()->store->getBlob(self::SECURITY_CODE_STORE_PREFIX . $user_id);
        if (!$data) {
            throw new Am_Exception_FatalError(___('Security code is invalid'));
        }
        
        $data = unserialize($data);
        $user = $this->getDi()->userTable->load($user_id);
        
        if ($user && //user exist
            $data['security_code'] && //security code exist
            ($data['security_code'] == $code)) {//security code is valid

            $user->email = $data['email'];
            $user->save();
            
            $this->getDi()->store->delete(self::SECURITY_CODE_STORE_PREFIX . $user_id);
            
            $url = $this->getUrl('member', 'index');
            $this->redirectLocation($url, ___('Your email address has been changed'));
            exit;
            
        } else {
            throw new Am_Exception_FatalError(___('Security code is invalid'));
        }     
    }
    
    protected function handleEmail(SavedForm $form, & $vars) {
        /* @var $user User */
        $user = $this->user;
        $bricks = $form->getBricks();
        foreach ($bricks as $brick) {
            if ($brick->getClass() == 'email' 
                    && $brick->getConfig('validate')
                    && $vars['email'] != $user->email) {
                
                $code = $this->getDi()->app->generateRandomString(self::EMAIL_CODE_LEN);
                
                $data = array(
                    'security_code' => $code,
                    'email' => $vars['email']
                );
                
                $this->getDi()->store->setBlob(
                    self::SECURITY_CODE_STORE_PREFIX . $this->user_id, 
                    serialize($data), 
                    sqlTime(Am_Di::getInstance()->time + self::SECURITY_CODE_EXPIRE * 3600)
                );
                
                $tpl = Am_Mail_Template::load('verify_email_profile', get_first($user->lang, 
                    Am_Di::getInstance()->app->getDefaultLocale(false)), true);
                
                $cur_email = $user->email;          
                $user->email = $vars['email'];
                
                $tpl->setUser($user);
                $tpl->setCode($code);
                $tpl->setUrl($this->getDi()->config->get('root_surl') . '/profile/confirm-email?em=' . $user->pk() . ':' . $code);
                $tpl->send($user);
                
                $user->email = $cur_email;
                
                unset($vars['email']);
                return true;
            }
        }
        
        return false;
    }
    
    function preDispatch()
    {
        if ($this->getRequest()->getActionName() != 'confirm-email') {
            $this->getDi()->auth->requireLogin(ROOT_URL . '/profile');
            $this->user = $this->getDi()->user;
            $this->view->assign('user', $this->user->toArray());
            $this->user_id = $this->user->user_id;
        }
    }
}
