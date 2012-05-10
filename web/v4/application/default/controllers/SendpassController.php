<?php 
/*
*   Send lost password
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Send lost password page
*    FileName $RCSfile$
*    Release: 4.1.10 ($Revision$)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*    
*/


class SendpassController extends Am_Controller {
    const EXPIRATION_PERIOD = 8; //hrs
    const CODE_STATUS_VALID = 1;
    const CODE_STATUS_EXPIRED = -1;
    const CODE_STATUS_INVALID = 0;
    const STORE_PREFIX = 'sendpass-';
    
    /** @var User */
    protected $user;

    public function preDispatch()
    {
        $s = $this->_request->getFiltered('s');
        if ($s) {
            switch ($this->checkCode($s)) 
            {
                case self::CODE_STATUS_VALID :
                    $this->output = $this->changePassAction();
                    break;
                case self::CODE_STATUS_EXPIRED :
                    $this->output = $this->errorAction(___('Security code is expired'));
                    break;
                case self::CODE_STATUS_INVALID :
                default :
                    $this->output = $this->errorAction(___('User is not found in database'));

            }
        } else {
            $this->output = $this->sendAction();
        }

        $this->setProcessed(true);
    }

    public function errorAction($message=null)
    {
        $this->view->assign('message', $message);
        $this->view->assign('login_page', REL_ROOT_URL . "/member");
        $this->view->display('changepass_failed.phtml');
    }

    public function sendAction()
    {
        $login = trim($this->_request->get('login'));
        $user = $this->getDi()->userTable->findFirstByLogin($login);
        if (!$user) 
            $user = $this->getDi()->userTable->findFirstByEmail($login);
        if ($user) {
            $this->sendSecurityCode($user);
            $this->view->title =  ___('Lost Password Sent');
            $this->view->text =  ___('Your password has been e-mailed to you') . ".<br />\n" . ___('Please check your mailbox');
            if ($this->isAjax())
            {
                return $this->ajaxResponse(array('ok'=>true, 'error'=>array($this->view->text)));
            }
        } else { //we can not find such user
            $this->view->title =  ___('Lost Password Sending Error');
            $this->view->text = 
                ___('The information you have entered is incorrect') . ".<br />\n" .
                sprintf(___('Username [%s] does not exist in database', $this->_request->getEscaped('login'))
            );

            if ($this->isAjax())
            {
                return $this->ajaxResponse(array('ok'=>false, 'error'=>array($this->view->text)));
            }
        }
        $this->view->display('sendpass.phtml');
    }
    
    public function createForm()
    {
        $form = new Am_Form;
        $form->addElement('text', 'login', array('disabled'=>'disabled'))
                ->setLabel(___('Username'));

        $pass0 = $form->addElement('password', 'pass0')
            ->setLabel(___('New Password'));
        $pass0->addRule('minlength',
                ___('The password should be at least %d characters long', $this->getDi()->config->get('pass_min_length', 4)),
                $this->getDi()->config->get('pass_min_length', 4));
        $pass0->addRule('maxlength',
                ___('Your password is too long'),
                $this->getDi()->config->get('pass_max_length', 32));
        $pass0->addRule('required', 'This field is required');

        $pass1 = $form->addElement('password', 'pass1')
            ->setLabel(___('Confirm Password'));
            
        $pass1->addRule('eq', ___('Passwords do not match'), $pass0);
        
        $form->addElement('hidden', 's');
        $form->addElement('submit', '', array('value'=>___('Save')));
        return $form;
    }

    public function changePassAction()
    {
        
        $s = $this->_request->getFiltered('s');
        
        if (!$s || !$this->user) {
            throw new Am_Exception_FatalError('Trying to change User password without security code');
        }
        
        $form = $this->createForm();

        if ($form->isSubmitted()) {
            $form->setDataSources(array(
                $this->_request,
                new HTML_QuickForm2_DataSource_Array(array('login'=>$user->login))
            ));
        } else {
            $form->setDataSources(array(
                new HTML_QuickForm2_DataSource_Array(array(
                        's'=>$this->_request->get('s'),
                        'login'=>$this->user->login
                    ))
            ));
        }

        if ($form->isSubmitted() && $form->validate()) 
        {
            //allright let's change pass
            $this->user->setPass($this->_request->get('pass0'));
            $this->user->update();
            $this->getDi()->store->delete(self::STORE_PREFIX . 
                $this->_request->getFiltered('s'));
            $this->getDi()->auth->setUser($user);
            $this->redirectLocation(
                REL_ROOT_URL . '/member',
                ___('You will be redirected to protected area'),
                'Redirect'
            );
        } else {
            $this->view->form = $form;
            $this->view->display('changepass.phtml');
        }

    }

    private function checkCode($code)
    {
        $user_id = $this->getDi()->store->get(self::STORE_PREFIX . $code);
        if (!$user_id)
            return self::CODE_STATUS_EXPIRED;
        $this->user = $this->getDi()->userTable->load($user_id);
        return self::CODE_STATUS_VALID;
    }

    private function sendSecurityCode(User $user)
    {
        $security_code = $this->getDi()->app->generateRandomString(16);

        $et = Am_Mail_Template::load('send_security_code', $user->lang, true);
        $et->setUser($user);
        $et->setUrl(sprintf('%s/sendpass/?s=%s',
                ROOT_SURL,
                $security_code)
        );
        $et->setHours(self::EXPIRATION_PERIOD);
        $et->send($user);
        
        $this->getDi()->store->set(self::STORE_PREFIX . $security_code,
            $user->pk(), '+'.self::EXPIRATION_PERIOD.' hours');
    }
}
