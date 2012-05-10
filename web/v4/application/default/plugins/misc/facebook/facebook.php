<?php

class Am_Plugin_Facebook extends Am_Plugin
{
    const PLUGIN_STATUS = self::STATUS_BETA;
    const PLUGIN_REVISION = '4.1.10';
    const FACEBOOK_UID = 'facebook-uid';
    
    const NOT_LOGGED_IN = 0;
    const LOGGED_IN = 1;
    const LOGGED_AND_LINKED = 2;
    
    protected $status = null; //self::NOT_LOGGED_IN;
    /** @var User */
    protected $linkedUser;
    /** @var array */
    protected $fbProfile = array();
    
    public function isConfigured()
    {
        return $this->getConfig('app_id') && $this->getConfig('app_secret');
    }
    function onSetupForms(Am_Event_SetupForms $event)
    {
        $form = new Am_Form_Setup('facebook');
        $form->setTitle('Facebook');
        
        $fs = $form->addFieldset()->setLabel(___('FaceBook Application'));
        $fs->addText('app_id')->setLabel(___('FaceBook App ID'));
        $fs->addText('app_secret', array('size' => 40))->setLabel(___('Facebook App Secret'));
        
        $fs = $form->addFieldset()->setLabel(___('Features'));
        $gr = $fs->addCheckboxedGroup('like')->setLabel(___('Add "Like" button'));
        $gr->addStatic()->setContent(___('Like Url'));
        $gr->addText('likeurl', array('size' => 40));
        $form->setDefault('likeurl', ROOT_URL);
        
        $fs->addAdvCheckbox('no_signup')->setLabel(___('Do not add to Signup Form'));
        $fs->addAdvCheckbox('no_login')->setLabel(___('Do not add to Login Form'));
        
        $form->addFieldsPrefix('misc.facebook.');
        $event->addForm($form);
    }
    function onInitFinished(Am_Event $event)
    {
        $blocks = $this->getDi()->blocks;
        if (!$this->getConfig('no_login'))
            $blocks->add(
                new Am_Block('login/form/after', null, 'fb-login', $this, 'fb-login.phtml')
            );
        if (!$this->getConfig('no_signup'))
            $blocks->add(
                new Am_Block('signup/form/before', null, 'fb-signup', $this, 'fb-signup.phtml')
            );
        if ($this->getConfig('like'))
            $blocks->add(
                new Am_Block('member/main/right/top', null, 'fb-like', $this, 'fb-like.phtml')
            );
    }
    function onSignupUserAdded(Am_Event $event)
    {
        $user = $event->getUser();
        // validate if user is logged-in to Facebook
        $api = $this->getApi();
        if ($api->getSignedRequest() && ($fbuid = $api->getUser()))
        {
            $user->data()->set(self::FACEBOOK_UID, $fbuid)->update();
        }
    }
    /** @return Facebook|null */
    function getApi()
    {
        if (!$this->getConfig('app_id'))
        {
            throw new Am_Exception_Configuration("Facebook plugins is not configured");
        }
        require_once dirname(__FILE__) . '/facebook-sdk.php';
        return new Am_Facebook(array(
            'appId'  => $this->getConfig('app_id'),
            'secret' => $this->getConfig('app_secret'),
            'cookie' => true,
        ), $this->getSession());
    }
    function getSession()
    {
        static $session;
        if (empty($session))
            $session = new Zend_Session_Namespace('am_facebook');
        return $session;
    }
    
    function getReadme()
    {
    }
    
    function onAuthCheckLoggedIn(Am_Event_AuthCheckLoggedIn $event)
    {
        $status = $this->getStatus();
        if ($status == self::LOGGED_AND_LINKED)
            $event->setSuccessAndStop($this->linkedUser);
    }
    function onAuthAfterLogout(Am_Event_AuthAfterLogout $event)
    {
        Am_Controller::setCookie('fbsr_'.$this->getConfig('app_id'), null, time() - 3600*24, "/");
        Am_Controller::setCookie('fbs_'.$this->getConfig('app_id'), null, time() - 3600*24, "/");
        $this->getSession()->unsetAll();
    }
    function onAuthAfterLogin(Am_Event_AuthAfterLogin $event)
    {
        if (($this->getStatus() == self::LOGGED_IN) && $this->getFbUid())
        {
            $event->getUser()->data()->set(self::FACEBOOK_UID, $this->getFbUid())->update();
        }
    }
    
    function getStatus()
    {
        if ($this->status !== null) return $this->status;
        $this->linkedUser = null;
        if ($id = $this->getApi()->getUser())
        {
            $user = $this->getDi()->userTable->findFirstByData(self::FACEBOOK_UID, $id);
            if ($user)
            {
                $this->linkedUser = $user;
                $this->status = self::LOGGED_AND_LINKED;
            } else {
                $this->status = self::LOGGED_IN;
            }
        } else {
            $this->status = self::NOT_LOGGED_IN;
        }
        return $this->status;
    }
    
    /** @return User */
    function getLinkedUser()
    {
        return $this->linkedUser;
    }
    /** @return int FbUid */
    function getFbUid()
    {
        return $this->getApi()->getUser();
    }
    /** @return facebook info */
    function getFbProfile($fieldName)
    {
        if (($this->fbProfile !== null) && $this->getFbUid())
        {
            $this->fbProfile = $this->getApi()->api('/me');
        }
        return !empty($this->fbProfile[$fieldName]) ? $this->fbProfile[$fieldName] : null;
    }
    
    function renderConnect()
    {
        return sprintf('<img src="%s" width="%d" height="%d" alt="%s"/>',
            Am_Controller::escape(REL_ROOT_URL . '/misc/facebook/connect-btn'),
            202, 26, ___("Connect with Facebook")
        );
        //return ___('Connect with Facebook');
    }
    function renderLogin()
    {
        return sprintf('<img src="%s" width="%d" height="%d" alt="%s"/>',
            Am_Controller::escape(REL_ROOT_URL . '/misc/facebook/login-btn'),
            107, 25, ___("Login using Facebook")
        );
        //return ___('Login using Facebook');
    }
    public function directAction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        switch ($action = $request->getActionName())
        {
            case 'connect-btn':
            case 'login-btn':
                $response->setHeader('Content-Type', 'image/png', true);
                $response->setHeader('Expires', gmdate('D, d M Y H:i:s', time()+3600*24).' GMT', true);
                readfile(dirname(__FILE__) . '/facebook-connect.png');
                break;
            default:
                throw new Am_Exception_InputError("Wrong request: [$action]");
        }
    }
}
