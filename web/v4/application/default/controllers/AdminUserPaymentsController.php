<?php

class AdminUserPaymentsController extends Am_Controller 
{
    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission('grid_payment');
    }
    function preDispatch()
    {
        $this->user_id = intval($this->_request->user_id);
        if ($this->_request->getActionName() != 'log') 
        {
            if ($this->user_id <= 0)
                throw new Am_Exception_InputError("user_id is empty in " . get_class($this));
        }
        return parent::preDispatch();
    }
    public function createAdapter() {
        $adapter =  $this->_createAdapter();
        
        $query = $adapter->getQuery();
        $query->addWhere('t.user_id=?d', $this->user_id);

        return $adapter;
    }
    public function getAddForm()
    {
        $form = new Am_Form_Admin;
        $form->setAction($url = $this->getUrl(null, 'c', null, 'payments','addpayment', 'user_id',$this->user_id));
        $form->addText("receipt_id", array('tabindex' => 2))
             ->setLabel("Receipt#")
             ->addRule('required');
        $amt = $form->addSelect("amount", array('tabindex' => 3), array('intrinsic_validation' => false))
             ->setLabel("Amount");
        $amt->addRule('required', 'This field is required');
        if ($this->_request->getInt('invoice_id'))
        {
            $invoice = $this->getDi()->invoiceTable->load($this->_request->getInt('invoice_id'));
            if (!$invoice->first_total || $invoice->getPaymentsCount())
                $amt->addOption($invoice->second_total, $invoice->second_total);
            else
                $amt->addOption($invoice->first_total, $invoice->first_total);
        }
        $form->addSelect("paysys_id", array('tabindex' => 1))
             ->setLabel("Payment System")
             ->loadOptions($this->getDi()->paysystemList->getOptions());
        $date = $form->addDate("dattm", array('tabindex' => 4))
             ->setLabel("Date Of Transaction");
        $date->addRule('required', 'This field is required');
        $date->setValue(sqlDate('now'));
        
        $form->addHidden("invoice_id");
        $form->addSaveButton();
        return $form;
    }
    function getAccessRecords()
    {
        return $this->getDi()->accessTable->selectObjects("SELECT a.*, p.title as product_title
            FROM ?_access a LEFT JOIN ?_product p USING (product_id)
            WHERE a.user_id = ?d
            ORDER BY begin_date, expire_date, product_title
            ", $this->user_id);
    }
    public function createAccessForm()
    {
        static $form;
        if (!$form)
        {
            $form = new Am_Form_Admin;
            $form->setAction($url = $this->getUrl(null, 'c', null, 'payments','addaccess', 'user_id',$this->user_id));
            $sel = $form->addSelect('product_id');
            $options = $this->getDi()->productTable->getOptions();
            $sel->addOption(___('Please select an item...'), '');
            foreach ($options as $k => $v)
                $sel->addOption($v, $k);
            $sel->addRule('required', 'this field is required');
            $form->addDate('begin_date')->addRule('required', 'this field is required');
            $form->addDate('expire_date')->addRule('required', 'this field is required');
            $form->addSaveButton('Add Access Manually');
        }
        return $form;
    }
    public function indexAction()
    {
        $this->getDi()->plugins_payment->loadEnabled();
        $this->view->invoices = $this->getDi()->invoiceTable->findByUserId($this->user_id);
        
        foreach ($this->view->invoices as $invoice)
        {
            if ($invoice->getStatus() == Invoice::RECURRING_ACTIVE)
            {
                $invoice->_cancelUrl = null;
                $ps = $this->getDi()->plugins_payment->loadGet($invoice->paysys_id, false);
                if ($ps)
                    $invoice->_cancelUrl = $ps->getAdminCancelUrl($invoice);
            }
        }
        
        $this->view->user_id = $this->user_id;
        $this->view->addForm = $this->getAddForm();
        $this->view->accessRecords = $this->getAccessRecords();
        $this->view->accessForm = $this->createAccessForm()->toObject();
        $this->view->display('admin/user-invoices.phtml');
    }
    
    public function refundAction()
    {
        $this->invoice_payment_id = $this->getInt('invoice_payment_id');
        if (!$this->invoice_payment_id)
            throw new Am_Exception_InputError("Not payment# submitted");
        $p = $this->getDi()->invoicePaymentTable->load($this->invoice_payment_id);
        /* @var $p InvoicePayment */
        if (!$p)
            throw new Am_Exception_InputError("No payment found");
        if ($this->user_id != $p->user_id)
            throw new Am_Exception_InputError("Payment belongs to another customer");
        if ($p->isRefunded())
            throw new Am_Exception_InputError("Payment is already refunded");
        $amount = sprintf('%.2f', $this->_request->get('amount'));
        if ($p->amount < $amount)
            throw new Am_Exception_InputError("Refund amount cannot exceed payment amount");
        if ($this->_request->getInt('manual'))
        {
            switch ($type = $this->_request->getFiltered('type'))
            {
                case 'refund':
                case 'chargeback':
                    $pl = $this->getDi()->plugins_payment->loadEnabled()->get($p->paysys_id);
                    if (!$pl)
                        throw new Am_Exception_InputError("Could not load payment plugin [$pl]");
                    $invoice = $p->getInvoice();
                    $transaction = new Am_Paysystem_Transaction_Manual($pl);
                    $transaction->setAmount($amount);
                    $transaction->setReceiptId($p->receipt_id . '-manual-'.$type);
                    $transaction->setTime($this->getDi()->dateTime);
                    if ($type == 'refund')
                        $invoice->addRefund($transaction, $p->receipt_id);
                    else
                        $invoice->addChargeback($transaction, $p->receipt_id);
                    break;
                case 'correction':
                    $this->getDi()->accessTable->deleteBy(array('invoice_payment_id' => $this->invoice_payment_id));
                    $invoice = $p->getInvoice();
                    $p->delete();
                    $invoice->updateStatus();
                    break;
                default:
                    throw new Am_Exception_InputError("Incorrect refund [type] passed:" . $type );
            }
            $res = array(
                'success' => true,
                'text'    => ___("Payment has been successfully refunded"),
            );
        } else { // automatic 
            /// ok, now we have validated $p here
            $pl = $this->getDi()->plugins_payment->loadEnabled()->get($p->paysys_id);
            if (!$pl)
                throw new Am_Exception_InputError("Could not load payment plugin [$pl]");
            /* @var $pl Am_Paysystem_Abstract */
            $result = new Am_Paysystem_Result;
            $pl->processRefund($p, $result, $amount);

            if ($result->isSuccess())
            {
                $p->getInvoice()->addRefund($result->getTransaction(), $p->receipt_id, $amount);

                $res = array(
                    'success' => true,
                    'text'    => ___("Payment has been successfully refunded"),
                );
            } elseif ($result->isAction()) {
                $action = $result->getAction();
                if ($action instanceof Am_Paysystem_Action_Redirect)
                {
                    $res = array(
                        'success' => 'redirect',
                        'url'     => $result->getUrl(),
                    );
                } else {// todo handle other actions if necessary
                    throw new Am_Exception_NotImplemented("Could not handle refund action " . get_class($action));
                }
            } elseif ($result->isFailure()) {
                $res = array(
                    'success' => false,
                    'text' => join(";", $result->getErrorMessages()),
                );
            }
        }
        $this->_response->setHeader("Content-type", "application/json");
        echo $this->getJson($res);
    }
    
    function addaccessAction()
    {
        $form = $this->createAccessForm();
        if ($form->validate())
        {
            $access = $this->getDi()->accessRecord;
            $access->setForInsert($form->getValue());
            unset($access->save);
            $access->user_id = $this->user_id;
            $access->insert();
            // send 1-day autoresponders if supposed to
            $user = $this->getDi()->userTable->load($this->user_id);
            $this->getDi()->emailTemplateTable->sendZeroAutoresponders($user);
            //
            $form->setDataSources(array(new Am_Request(array())));
            $form->getElementById('begin_date-0')->setValue('');
            $form->getElementById('expire_date-0')->setValue('');
        } else {
            
        }
        return $this->indexAction();
    }
    function delaccessAction()
    {
        $access = $this->getDi()->accessTable->load($this->getInt('id'));
        if ($access->user_id != $this->user_id)
            throw new Am_Exception_InternalError("Wrong access record to delete - member# does not match");
        $access->delete();
        return $this->indexAction();
    }
    function addpaymentAction()
    {
        $invoice = $this->getDi()->invoiceTable->load($this->_request->getInt('invoice_id'));
        if (!$invoice || $invoice->user_id != $this->user_id)
            throw new Am_Exception_InputError("Invoice not found");

        $form = $this->getAddForm();
        if (!$form->validate())
        {
            echo $form;
            return;
        }
        
        $vars = $form->getValue();
        $transaction = new Am_Paysystem_Transaction_Manual($this->getDi()->plugins_payment->get($vars['paysys_id']));
        $transaction->setAmount($vars['amount'])->setReceiptId($vars['receipt_id'])->setTime(new DateTime($vars['dattm']));
        $invoice->addPayment($transaction);

        $form->setDataSources(array(new Am_Request(array())));
        $form->addHidden('saved-ok');
        echo $form;
    }
    
    function stopRecurringAction()
    {
        $invoiceId = $this->_request->getInt('invoice_id');
        if (!$invoiceId)
            throw new Am_Exception_InputError("No invoice# provided");
        $invoice = $this->getDi()->invoiceTable->load($invoiceId);
        $invoice->setCancelled();
        
        $this->_redirect('admin-user-payments/index/user_id/'.$invoice->user_id.'#invoice-'.$invoiceId);
            
    }
    
    function logAction()
    {
        $this->getDi()->authAdmin->getUser()->checkPermission(Am_Auth_Admin::PERM_LOGS);
        $invoice = $this->getDi()->invoiceTable->load($this->_request->getInt('invoice_id'));
        $this->getResponse()->setHeader('Content-type', 'text/xml');
        echo $invoice->exportXmlLog();
    }
    
    function replaceProductAction()
    {
        $this->getDi()->authAdmin->getUser()->checkPermission('_payment',  'edit');
        
        $item = $this->getDi()->invoiceItemTable->load($this->_request->getInt('id'));
        $pr = $this->getDi()->productTable->load($item->item_id);

        $form = new Am_Form_Admin('replace-product-form');
        $form->setDataSources(array($this->_request));
        $form->method = 'post';
        $form->addHidden('id');
        $form->addHidden('user_id');
        $form->addStatic()
            ->setLabel(___('Replace Product'))
            ->setContent("#{$pr->product_id} [$pr->title]");
        $sel = $form->addSelect('product_id')->setLabel('To Product');    
        $options = array('' => '-- ' . ___('Please select') . ' --');
        foreach ($this->getDi()->billingPlanTable->getProductPlanOptions() as $k => $v)
            if (strpos($k, $pr->pk().'-')!==0)
                $options[$k] = $v;
        $sel->loadOptions($options);
        $sel->addRule('required');
        $form->addSubmit('_save', array('value' => ___('Save')));
        if ($form->isSubmitted() && $form->validate())
        {
            try {
                list($p,$b) = explode("-", $sel->getValue(), 2);
                $item->replaceProduct(intval($p), intval($b));
                $this->getDi()->adminLogTable->log("Inside invoice: product #{$item->item_id} replaced to product #$p (plan #$b)", 'invoice', $item->invoice_id);
                return $this->ajaxResponse(array('ok'=>true));
            } catch (Am_Exception $e) {
                $sel->setError($e->getMessage());
            }
        }
        echo $form->__toString();
    }
}
