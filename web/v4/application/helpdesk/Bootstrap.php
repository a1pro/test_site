<?php

class Bootstrap_Helpdesk extends Am_Module
{
    function init()
    {
    }
    function onAdminMenu(Am_Event $event)
    {
        $event->getMenu()->addPage(array(
            'label' => ___('Helpdesk'),
            'controller' => 'admin',
            'action' => 'index',
            'module' => 'helpdesk',
            'id' => 'helpdesk',
            'resource' => "helpdesk",
            'params' => array (
                'page_id' => 'index'
            ),
            'route' => 'inside-pages'
        ));
    }
    function onUserMenu(Am_Event $event)
    {
        $event->getMenu()->addPage(
            array(
                'id' => 'helpdesk',
                'label' => ___('Helpdesk'),
                'controller' => 'index',
                'action' => 'index',
                'params' => array('page_id' => 'index'),
                'module' => 'helpdesk',
                'order' => 600,
                'route' => 'inside-pages',
                'resource' => 'helpdesk',
            )
        );
    }
    function onUserTabs(Am_Event_UserTabs $event)
    {
        $event->getTabs()->addPage(array(
            'id' => 'helpdesk',
            'module' => 'helpdesk',
            'controller' => 'admin-user',
            'action' => 'index',
            'params' => array (
                            'user_id' => $event->getUserId()
                        ),
            'label' => ___('Tickets'),
            'order' => 1000,
            'resource' => 'helpdesk',
        ));
    }
    function onGetPermissionsList(Am_Event $event)
    {
        $event->addReturn(___("Can open and answer helpdesk tickets"), "helpdesk");
    }    
    
    function onUserAfterDelete(Am_Event_UserAfterDelete $event) 
    {
        $this->getDi()->db->query("DELETE FROM ?_helpdesk_message WHERE 
            ticket_id IN (SELECT ticket_id FROM ?_helpdesk_ticket
            WHERE user_id=?)", $event->getUser()->user_id);
        $this->getDi()->db->query("DELETE FROM ?_helpdesk_ticket
            WHERE user_id=?", $event->getUser()->user_id);
    }
    
    function onInitFinished() {
       $this->getDi()->register('helpdeskStrategy', 'Am_Helpdesk_Strategy_Abstract')
            ->setConstructor('create');
       
    }
    
    function onBuildDemo(Am_Event $event)
    {
        $subjects = array(
            'Please help',
            'Urgent question',
            'I have a problem',
            'Important question',
            'Pre-sale inquiry',
        );
        $questions = array(
            "My website is now working. Can you help?",
            "I have a problem with website script.\nWhere can I find documentation?",
            "I am unable to place an order, my credit card is not accepted.",
        );
        $answers = array(
            "Please call us to phone# 1-800-222-3334",
            "We are looking to your problem, and it will be resolved within 4 hours",
        );
        $user = $event->getUser();
        /* @var $user User */
        while (rand(0,10)<4)
        {
            $ticket = $this->getDi()->helpdeskTicketRecord;
            $ticket->status = HelpdeskTicket::STATUS_AWAITING_ADMIN_RESPONSE;
            $ticket->subject = $subjects[ rand(0, count($subjects)-1) ];
            $ticket->user_id = $user->pk();
            $ticket->created = sqlTime('now');;
            $ticket->insert();
            //
            $msg = $this->getDi()->helpdeskMessageRecord;
            $msg->content = $questions[ rand(0, count($questions)-1) ];
            $msg->type = 'message';
            $msg->ticket_id = $ticket->pk();
            $msg->dattm = $tm = sqlTime(time() - rand(3600, 3600*24*180));
            $msg->insert();
            //
            if (rand(0, 10)<6)
            {
                $msg = $this->getDi()->helpdeskMessageRecord;
                $msg->content = $answers[ rand(0, count($answers)-1) ];
                $msg->type = 'message';
                $msg->ticket_id = $ticket->pk();
                $msg->dattm = sqlTime(strtotime($tm) + rand(180, 3600*24));
                $msg->admin_id = $this->getDi()->adminTable->findFirstBy()->pk();
                $msg->insert();
                if (rand(0, 10)<6)
                    $ticket->status = HelpdeskTicket::STATUS_AWAITING_USER_RESPONSE;
                else
                    $ticket->status = HelpdeskTicket::STATUS_CLOSED;
                $ticket->update();
            }
        }
    }
}