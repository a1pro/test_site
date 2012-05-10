<?php

abstract class Am_Helpdesk_Strategy_Abstract {

    private static $cacheMembers = array();
    private static $cacheAdmins = array();

    abstract public function isMessageAvalable($message);
    abstract public function isMessageForReply($message);
    abstract public function fillUpMessageIdentity($message);
    abstract public function fillUpTicketIdentity($ticket, $request);
    abstract public function getAdminName($message);
    abstract public function getTemplatePath();
    abstract public function getIdentity();
    abstract public function canViewTicket($ticket);
    abstract public function canViewMessage($message);
    abstract public function canEditTicket($ticket);
    abstract public function canEditMessage($message);
    abstract public function getTicketStatusAfterReply($message);
    abstract public function onAfterInsertMessage($message);
    abstract protected function createForm();
    abstract protected function getControllerName();

    public function assembleUrl($params, $route = 'default'){
        $router = Zend_Controller_Front::getInstance()->getRouter();
        return $router->assemble(array(
            'module' => 'helpdesk',
            'controller' => $this->getControllerName(),
        )+$params, $route, true);
    }

    /**
     * @return Am_Helpdesk_Strategy_Abstract
     */
    public static function create()
    {
        return defined('AM_ADMIN') ?
            (Zend_Controller_Front::getInstance()->getRequest()->getControllerName() == 'admin-user' ?
                new Am_Helpdesk_Strategy_Admin_User() :
                new Am_Helpdesk_Strategy_Admin() ) :
            new Am_Helpdesk_Strategy_Member();
    }

    /**
     *
     * @return Am_Form
     *
     */
    public function createNewTicketForm()
    {
        $form = $this->createForm();

        $subject = $form->addElement('text', 'subject')
            ->setLabel(___('Subject'));
        $subject->addRule('maxlength', ___('Your subject is too verbose'), 255);
        $subject->addRule('nonempty', ___('Subject can not be empty'));

        $content = $form->addElement('textarea', 'content', array('rows'=>12))
            ->setLabel(___('Message'));
        $content->addRule('maxlength', ___('Your message is too verbose'), 1000);
        $content->addRule('nonempty', ___('Message can not be empty'));

        return $form;
    }


    public function getMemberName($message){
        $member = $this->getMember($message->getTicket()->user_id);
        return sprintf('%s (%s %s)',
            $member->login,
            $member->name_f,
            $member->name_l
        );
    }

    protected function getAdmin($admin_id)
    {
        if (!isset(self::$cacheAdmins[$admin_id])) {
           self::$cacheAdmins[$admin_id] = Am_Di::getInstance()->adminTable->load($admin_id);
        }

        return self::$cacheAdmins[$admin_id];
    }

    protected function getMember($user_id)
    {
        if (!isset(self::$cacheMembers[$user_id])) {
           self::$cacheMembers[$user_id] = Am_Di::getInstance()->userTable->load($user_id);
        }

        return self::$cacheMembers[$user_id];
    }
}

