<?php

class Am_Helpdesk_Strategy_Admin extends Am_Helpdesk_Strategy_Abstract{
    public function isMessageAvalable($message)
    {
        return !($message->type == 'comment' && !$message->admin_id);
    }
    public function isMessageForReply($message)
    {
        if ($message->type=='comment') {
            return false;
        } else {
            return !$message->admin_id;
        }
    }
    public function fillUpMessageIdentity($message)
    {
        $message->admin_id = $this->getIdentity();
        return $message;
    }

    public function fillUpTicketIdentity($ticket, $request)
    {
        //loginOrEmail was already validated in form 
        //and we must find user with such login or email
        //in any case
        $user = Am_Di::getInstance()->userTable->findFirstByLogin($request->get('loginOrEmail'));
        if (!$user) {
            $user = Am_Di::getInstance()->userTable->findFirstByEmail($request->get('loginOrEmail'));
        }

        $ticket->user_id = $user->user_id;
        return $ticket;
    }

    public function getTicketStatusAfterReply($message)
    {
        if ($message->type == 'comment') {
            return $message->getTicket()->status;
        } else {
            return 'awaiting_user_response';
        }
    }

    public function onAfterInsertMessage($message)
    {
        if ($message->type == 'message'
            && Am_Di::getInstance()->config->get('helpdesk.notify_new_message', 1))
        {
            $user = $this->getMember($message->getTicket()->user_id);

            $et = Am_Mail_Template::load('helpdesk.notify_new_message');
            if ($et)
            {
                $et->setUser($user);
                $et->setUrl(sprintf('%s/helpdesk/index/p/view/view/ticket/%s',
                        Am_Di::getInstance()->config->get('root_surl'),
                        $message->getTicket()->ticket_mask)
                );
                $et->send($user);
            }
        }
    }

    /**
     *
     * @return Am_Form
     *
     */
    public function createNewTicketForm()
    {
        $form = parent::createNewTicketForm();

        $element = HTML_QuickForm2_Factory::createElement('text', 'loginOrEmail');
        $element->setId('loginOrEmail')
            ->setLabel(___('E-Mail Address or Username'))
            ->addRule('callback', ___('Can not find user with such username or email'), array($this, 'checkUser'));

        //prepend element to form
        $formElements = $form->getElements();
        $form->insertBefore($element, $formElements[0]);

        $from = HTML_QuickForm2_Factory::createElement('select', 'from');
        $from->setLabel(___('Create ticket as'));
        $from->loadOptions(array(
            'admin' => ___('Admin'),
            'user' => ___('Customer')
        ));

        $form->insertBefore($from, $element);

        $script = <<<CUT
        $("input#loginOrEmail").autocomplete({
                minLength: 2,
                source: window.rootUrl + "/admin-users/autocomplete"
        });
CUT;

        $form->addScript('script')->setScript($script);

        return $form;
    }

    public function getAdminName($message)
    {
        $admin = $this->getAdmin($message->admin_id);
        return $admin->login;
    }

    public function getTemplatePath()
    {
        return 'admin/helpdesk';
    }
    public function getIdentity()
    {
        $admin = Am_Di::getInstance()->authAdmin->getSessionVar();
        return $admin['admin_id'];
    }

    public function canViewTicket($ticket)
    {
        return true;
    }

    public function canViewMessage($message)
    {
        return true;
    }

    public function canEditTicket($ticket)
    {
        return true;
    }

    public function canEditMessage($message)
    {
        return $message->type == 'comment' && (boolean)$message->admin_id;
    }

    public function checkUser($loginOrEmail)
    {
        $user = Am_Di::getInstance()->userTable->findFirstByLogin($loginOrEmail);
        if (!$user) {
            $user = Am_Di::getInstance()->userTable->findFirstByEmail($loginOrEmail);
        }
        return (boolean)$user;
    }

    protected function createForm()
    {
        $form = new Am_Form_Admin();
        $form->setAttribute('class', 'am-form-helpdesk');
        return $form;
    }

    protected function getControllerName()
    {
        return 'admin';
    }

}

