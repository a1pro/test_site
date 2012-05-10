<?php

class Am_Helpdesk_Grid_Member extends Am_Helpdesk_Grid {

    public function initGridFields() {
        $this->addField(new Am_Grid_Field('ticket_mask', 'Ticket#', true, '', null, '10%'));
        $this->addField(new Am_Grid_Field('subject', 'Subject', true, '', array($this, 'renderSubject'), '30%'));
        $this->addField(new Am_Grid_Field('updated', 'Updated', true, '', array($this, 'renderTime'), '20%'));
        $this->addField(new Am_Grid_Field('status', 'Status', true, '', array($this, 'renderStatus'), '10%'));
        $this->addField(new Am_Grid_Field('msg_cnt', 'Message Count', true, '', null, '10%'));
    }
    
    public function createDs() {
        $query = parent::createDS();
        $query->addWhere('t.user_id=?',
                Am_Di::getInstance()->auth->getUserId()
        );
        return $query;
    }

}

class Helpdesk_IndexController extends Am_Controller_Pages {
    protected $layout = 'member/layout.phtml';

    function preDispatch() {
        $this->getDi()->auth->requireLogin(ROOT_URL . '/helpdesk');
        $this->view->headLink()->appendStylesheet($this->view->_scriptCss('helpdesk-user.css'));
        parent::preDispatch();
    }

    public function initPages() {
        $this->addPage('Am_Helpdesk_Grid_Member', 'index', 'Tickets')
             ->addPage(array($this, 'createController'), 'view', 'Conversation');
    }

    public function renderTabs() {
        $intro = $this->getDi()->config->get('helpdesk.intro');
        return $intro ? sprintf('<div class="am-info">%s</div>', $this->escape($intro)) : '';
    }

    public function createController($id, $title, $grid) {
        return new Am_Helpdesk_Controller($grid->getRequest(), $grid->getResponse(), $this->_invokeArgs);
    }
}