<?php

class Am_Controller_CreditCard extends Am_Controller
{
    /** @var Invoice */
    public $invoice;
    /** @var Am_Paysystem_CreditCard */
    public $plugin;
    /** @var Am_Form_CreditCard */
    public $form;

    public function setPlugin(Am_Paysystem_CreditCard $plugin)
    {
        $this->plugin = $plugin;
    }

    public function validateInvoiceAndKey()
    {
        $invoiceId = $this->getFiltered('id');
        if (!$invoiceId)
            throw new Am_Exception_InputError("invoice_id is empty - seems you have followed wrong url, please return back to continue");
        
        $this->invoice = $this->getDi()->invoiceTable->findBySecureId($invoiceId, $this->plugin->getId());
        if (!$this->invoice)
            throw new Am_Exception_InputError('You have used wrong link for payment page, please return back and try again');

        if ($this->invoice->isCompleted())
            throw new Am_Exception_InputError(sprintf(___('Payment is already processed, please go to %sMembership page%s'), "<a href='".htmlentities($this->getDi()->config->get('root_url'))."/member'>","</a>"));

        if ($this->invoice->paysys_id != $this->plugin->getId())
            throw new Am_Exception_InputError("You have used wrong link for payment page, please return back and try again");

        if ($this->invoice->tm_added < sqlTime('-1 days'))
            throw new Am_Exception_InputError("Invoice expired - you cannot open invoice after 24 hours elapsed");
    }

    /**
     * Process the validated form and if ok, display thanks page,
     * if not ok, return false
     */
    public function processCc(){
        $cc = $this->getDi()->ccRecordRecord;
        $this->form->toCcRecord($cc);
        $cc->user_id = $this->invoice->user_id;

        $result = $this->plugin->doBill($this->invoice, true, $cc);
        if ($result->isSuccess()) {
            if (($this->invoice->rebill_times > 0) && !$cc->pk())
                $this->plugin->storeCreditCard($cc, new Am_Paysystem_Result);
            $this->redirectLocation($this->plugin->getReturnUrl());
            return true;
        } else {
            $this->view->error = $result->getErrorMessages();
        }
    }

    public function ccAction(){
        $this->validateInvoiceAndKey();
        $this->form = $this->createForm();

        if ($this->form->isSubmitted() && $this->form->validate()) {
            if ($this->processCc()) return;
        }
        $this->view->form = $this->form;
        $this->view->invoice = $this->invoice;
        $this->view->display_receipt = true;
        $this->view->display('cc/info.phtml');
    }
    public function createForm(){
        $form = new Am_Form_CreditCard($this->plugin);
        $form->setDataSources(array(
            $this->_request,
            new HTML_QuickForm2_DataSource_Array($form->getDefaultValues($this->invoice->getUser()))
        ));

        $form->addHidden(Am_Controller::ACTION_KEY)->setValue($this->_request->getActionName());
        $form->addHidden('id')->setValue($this->getFiltered('id'));

        return $form;
    }
    public function preDispatch() {
        if (!$this->plugin)
            throw new Am_Exception_InternalError("Payment plugin is not passed to " . __CLASS__);
    }

    public function createUpdateForm()
    {
        $form = new Am_Form_CreditCard($this->plugin, Am_Form_CreditCard::USER_UPDATE);
        $user = $this->getDi()->auth->getUser(true);
        if (!$user)
            throw new Am_Exception_InputError("You are not logged-in");
        $cc = $this->getDi()->ccRecordTable->findFirstByUserId($user->user_id);
        if (!$cc) $this->getDi()->ccRecordRecord;
        $arr = $cc->toArray();
        unset($arr['cc_number']);
        $form->setDataSources(array(
            $this->_request,
            new HTML_QuickForm2_DataSource_Array($arr)
        ));
        return $form;
    }
    
    public function updateAction()
    {
        $this->form = $this->createUpdateForm();
        if ($this->form->isSubmitted() && $this->form->validate()) 
        {
            $cc = $this->getDi()->ccRecordRecord;
            $this->form->toCcRecord($cc);
            $cc->user_id = $this->getDi()->auth->getUserId();
            $result = new Am_Paysystem_Result();
            $this->plugin->storeCreditCard($cc, $result);
            if ($result->isSuccess())
            {
                return $this->redirectLocation(REL_ROOT_URL . '/member');
            } else {
                $this->form->getElementById('cc-0')->setError($result->getLastError());
            }
        }
        $this->view->form = $this->form;
        $this->view->invoice = null;
        $this->view->display_receipt = false;
        $this->view->display('cc/info.phtml');
    }
    
    public function cancelAction()
    {
        $id = $this->_request->getFiltered('id');
        $invoice = $this->getDi()->invoiceTable->findBySecureId($id, 'STOP'.$this->plugin->getId());
        if (!$invoice) 
            throw new Am_Exception_InputError("No invoice found [$invoiceId]");
        if ($invoice->user_id != $this->getDi()->auth->getUserId())
            throw new Am_Exception_InternalError("User tried to access foreign invoice: [$id]");
        $invoice->setCancelled();
        $this->_redirect('member/payment-history');
    }
}
