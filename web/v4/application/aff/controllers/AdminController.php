<?php

class Aff_AdminController extends Am_Controller
{
    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission('affiliates');
    }
    function infoTabAction()
    {
        require_once APPLICATION_PATH . '/default/controllers/AdminUsersController.php';
        require_once 'Am/Report.php';
        require_once 'Am/Report/Standard.php';
        include_once APPLICATION_PATH . '/aff/library/Reports.php';
        $this->setActiveMenu('users-browse');

        $rs = new Am_Report_AffStats();
        $rs->setAffId($this->user_id);
        $rc = new Am_Report_AffClicks();
        $rc->setAffId($this->user_id);
        
        $form = $rs->getForm();
        if ($form->isSubmitted() && $form->validate())
            $rs->applyConfigForm($this->_request);
        else 
        {
            $rs->setInterval('-1 month', 'now')->setQuantity(new Am_Report_Quant_Day());
            $form->addDataSource(new Am_Request(array('start' => $rs->getStart(), 'stop' => $rs->getStop())));
        }
        $rc->setInterval($rs->getStart(), $rs->getStop())->setQuantity(clone $rs->getQuantity());
            
        $result = $rs->getReport();
        $rc->getReport($result);
        
        $this->view->form = $form;
        $this->view->form->setAction($this->_request->getRequestUri());
        
        $output = new Am_Report_Graph_Line($result);
        $output->setSize(600, 300);
        $this->view->report = $output->render();

        $this->view->result = $result;
        
        $this->view->display('admin/aff/info-tab.phtml');
    }
    function infoTabDetailAction()
    {
        $date = $this->getFiltered('date');
        
        $this->view->date = $date;
        $this->view->commissions = $this->getDi()->affCommissionTable->fetchByDate($date, $this->user_id);
        $this->view->clicks = $this->getDi()->affClickTable->fetchByDate($date, $this->user_id);
        $this->view->display('admin/aff/info-tab-detail.phtml');
    }
    function preDispatch()
    {
        $this->user_id = $this->getInt('user_id');
        if (!$this->user_id)
            throw new Am_Exception_InputError("Wrong URL specified: no member# passed");
        $this->view->user_id = $this->user_id;
    }
}