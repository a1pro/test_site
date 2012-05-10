<?php

class Am_Helpdesk_Controller extends Am_Controller {
    /** @var Am_Helpdesk_Strategy */
    protected $strategy;

    public function checkAdminPermissions(Admin $admin) {
        return $admin->hasPermission('helpdesk');
    }
    public function init() {
        $this->strategy = $this->getDi()->helpdeskStrategy;
        $type = defined('AM_ADMIN') ? 'admin' : 'user';
        $this->getView()->headLink()->appendStylesheet($this->getView()->_scriptCss('helpdesk-'.$type.'.css'));
        parent::init();
    }

    public function viewAction() 
    {
        $ticketIdentity = $this->_request->get('ticket');
        $ticket = $this->getDi()->helpdeskTicketTable->load($ticketIdentity);

        if (!$this->strategy->canViewTicket($ticket)) {
            throw new Am_Exception_AccessDenied(___('Access Denied'));
        }
        
        $grid = new Am_Helpdesk_Grid($this->getRequest(), $this->getView());
        $grid->getDataSource()->getDataSourceQuery()->addWhere('m.user_id=?d', $ticket->user_id);
        $grid->actionsClear();

        $t = new Am_View();
        $t->assign('ticket', $ticket);
        $t->assign('user', $ticket->getUser());
        $t->assign('strategy', $this->strategy);
        $t->assign('historyGrid', $grid->render());
        $content = $t->render($this->strategy->getTemplatePath() . '/ticket.phtml');

        if ($this->isAjax()) {
            header('Content-type: text/html; charset=UTF-8');
            echo $content;
        } else {
            $this->view->assign('content', $content);
            $this->view->display($this->strategy->getTemplatePath() . '/index.phtml');
        }
    }

    public function replyAction() {
        $ticket = $this->getDi()->helpdeskTicketTable->load($this->_request->getInt('ticket_id'));

        if (!$this->strategy->canEditTicket($ticket)) {
            throw new Am_Exception_AccessDenied(___('Access Denied'));
        }

        if ($this->_request->isPost()) {
            $this->reply($ticket, $this->_request->get('message_id', null));
            $this->_request->set('ticket', $ticket->ticket_mask);
            return $this->redirect($ticket);
        }

        $message = null;
        $type = $this->_request->get('type', 'message');
        if ($this->_request->get('message_id')) {
            $message = $this->getDi()->helpdeskMessageTable->load($this->_request->get('message_id'));

            switch ($type) {
                case 'message' :
                    if (!$this->strategy->canViewMessage($message)) {
                        throw new Am_Exception_AccessDenied(___('Access Denied'));
                    }
                    break;
                case 'comment' :
                    if (!$this->strategy->canEditMessage($message)) {
                        throw new Am_Exception_AccessDenied(___('Access Denied'));
                    }
                    break;
                default :
                    throw new Am_Exception_InputError('Unknown message type : ' . $type);
            }

        }

        $content = $this->getReplyForm(
                $this->_request->getInt('ticket_id'),
                $message,
                $type
        );

        if ($this->isAjax()) {
            header('Content-type: text/html; charset=UTF-8');
            echo $content;
        } else {
            $this->view->assign('content', $content);
            $this->view->display($this->strategy->getTemplatePath() . '/index.phtml');
        }

    }

    public function changestatusAction() {
        $ticketIdentity = $this->_request->get('ticket');
        $ticket = $this->getDi()->helpdeskTicketTable->load($ticketIdentity);

        if (!$this->strategy->canEditTicket($ticket)) {
            throw new Am_Exception_AccessDenied(___('Access Denied'));
        }

        $ticket->status = $this->_request->get('status');
        $ticket->save();
        return $this->redirect($ticket);
    }

    protected function redirect($ticket) {
        $url = $this->strategy->assembleUrl(array(
                'page_id' => 'view',
                'action' => 'view',
                'ticket' => $ticket->ticket_mask,
            ), 'inside-pages');
        $this->redirectLocation($url);
        exit;
    }

    private function editMessage($message_id, $content) {
        $message = $this->getDi()->helpdeskMessageTable->load($message_id);
        if (!$this->strategy->canEditMessage($message)) {
            throw new Am_Exception_AccessDenied(___('Access Denied'));
        }
        $message->content = $content;
        $message->save();
    }

    private function addMessage($ticket, $content, $type) {
        $message = $this->getDi()->helpdeskMessageRecord;
        $message->content = $content;
        $message->ticket_id = $ticket->ticket_id;
        $message->type = $type;
        $message = $this->strategy->fillUpMessageIdentity($message);
        $message->save();

        $this->strategy->onAfterInsertMessage($message);

        $ticket->status = $this->strategy->getTicketStatusAfterReply($message);
        $ticket->updated = $this->getDi()->sqlDateTime;
        $ticket->save();
    }

    private function reply($ticket, $message_id = null) {
        if ($message_id) {
            $this->editMessage($message_id, $this->_request->get('content'));
        } else {
            $this->addMessage(
                    $ticket,
                    $this->_request->get('content'),
                    $this->_request->get('type')
            );
        }
    }

    private function getReplyForm($ticket_id, $message = null, $type = 'message') {
        $content = '';
        $hiddens ='';

        if (!is_null($message) && $type == 'message') {
            $content = explode("\n", $message->content);
            $content = array_map(create_function('$v', 'return \'>\'.$v;'), $content);
            $content = "\n\n" . implode("\n", $content);
        } elseif (!is_null($message) && $type == 'comment') {
            $content = $message->content;
            $hiddens .= sprintf(
                    '<input type="hidden" name="message_id" value="%d" />',
                    $message->message_id
            );
        }

        $t = new Am_View();
        $t->assign('content', $content);
        $t->assign('type', $type);
        $t->assign('hiddens', $hiddens);
        $t->assign('ticket_id', $ticket_id);

        return $t->display($this->strategy->getTemplatePath() . '/_reply-form.phtml');
    }
}

