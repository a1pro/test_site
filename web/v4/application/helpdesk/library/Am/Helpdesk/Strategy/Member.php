<?php

class Am_Helpdesk_Strategy_Member extends Am_Helpdesk_Strategy_Abstract {

    protected $_identity = null;

    public function  __construct($user_id = null) {
        $this->_identity = $user_id ? $user_id : Am_Di::getInstance()->auth->getUserId();
    }


    public function isMessageAvalable($message)
    {
        return !($message->type == 'comment' && $message->admin_id);
    }
    public function isMessageForReply($message)
    {
        if ($message->type=='comment') {
            return false;
        } else {
            return (boolean)$message->admin_id;
        }
    }
    public function fillUpMessageIdentity($message)
    {
        return $message;
    }

    public function fillUpTicketIdentity($ticket, $request)
    {
        $ticket->user_id = $this->getIdentity();
        return $ticket;
    }

    public function getTicketStatusAfterReply($message)
    {
        if ($message->type == 'comment') {
            return $message->getTicket()->status;
        } else {
            return 'awaiting_admin_response';
        }
    }

    public function onAfterInsertMessage($message)
    {
        if (Am_Di::getInstance()->config->get('helpdesk.notify_new_message_admin', 1)) {
            
            $et = Am_Mail_Template::load('helpdesk.notify_new_message_admin');
            if ($et)
            {
                $et->setUrl(sprintf('%s/helpdesk/admin/p/view/view/ticket/%s',
                        Am_Di::getInstance()->config->get('root_surl'),
                        $message->getTicket()->ticket_mask)
                );
                $et->send(Am_Mail_Template::TO_ADMIN);
            }
        }
    }

    public function getAdminName($message)
    {
        return ___('Administrator');
    }

    public function getTemplatePath()
    {
        return 'helpdesk';
    }

    public function getIdentity()
    {
        return $this->_identity;
    }

    public function canViewTicket($ticket)
    {
        return $ticket->user_id == $this->getIdentity();
    }

    public function canViewMessage($message)
    {
        return $message->getTicket()->user_id == $this->getIdentity();
    }

    public function canEditTicket($ticket)
    {
        return $ticket->user_id == $this->getIdentity();
    }
    
    public function canEditMessage($message)
    {
        return $message->type == 'comment' &&
            ($message->getTicket()->user_id == $this->getIdentity());
    }

    protected function createForm()
    {
        $form = new Am_Form();
        $form->setAttribute('class', 'am-form-helpdesk');
        return $form;
    }

    protected function getControllerName()
    {
        return 'index';
    }

}

