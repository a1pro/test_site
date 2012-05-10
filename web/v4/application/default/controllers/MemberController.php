<?php

/*
 *   Members page. Used to renew subscription.
 *
 *
 *     Author: Alex Scott
 *      Email: alex@cgi-central.net
 *        Web: http://www.cgi-central.net
 *    Details: Member display page
 *    FileName $RCSfile$
 *    Release: 4.1.10 ($Revision$)
 *
 * Please direct bug reports,suggestions or feedback to the cgi-central forums.
 * http://www.cgi-central.net/forum/
 *
 * aMember PRO is a commercial software. Any distribution is strictly prohibited.
 *
 */

class MemberController extends Am_Controller
{

    function preDispatch()
    {
        $this->getDi()->auth->requireLogin(ROOT_URL . '/member');
        $this->user = $this->getDi()->user;
        $this->view->assign('user', $this->user);
        $this->user_id = $this->user->pk();
        $this->checkEmailVerified();
    }

    /** @var User */
    protected $user;
    /** @var int */
    protected $user_id;

    static function getCancelLink(Payment $payment)
    {
        $paysys = $this->getDi()->paysystemList->get($payment->paysys_id);
        if ($paysys && $paysys->isRecurring()
            && ($pay_plugin = &instantiate_plugin('payment', $v['paysys_id']))
            && method_exists($pay_plugin, 'get_cancel_link')
            && $product = $payment->getProduct(false) && $product->isRecurring())
            return $pay_plugin->get_cancel_link($v['payment_id']);
    }

    function paymentHistoryAction()
    {
        $psList = $this->getDi()->paysystemList;
        
        $this->view->activeInvoices = $this->getDi()->invoiceTable->findBy(
            array('user_id'=>$this->user_id, 'status'=>array(Invoice::RECURRING_ACTIVE, Invoice::RECURRING_CANCELLED)), 
            null, null, 'tm_added DESC');
        
        foreach ($this->view->activeInvoices as $invoice)
        {
            $invoice->_paysysName = $psList->getTitle($invoice->paysys_id);
            // if there is only 1 item, offer upgrade path
            $invoice->_upgrades = array();
            
            if ($invoice->getStatus() == Invoice::RECURRING_ACTIVE)
            {
                if (count($invoice->getItems()) == 1)
                {
                    $invoice->_upgrades = $this->getDi()->productUpgradeTable->findUpgrades($invoice);
                }                
                $invoice->_cancelUrl = null;
                try { 
                    $ps = $this->getDi()->plugins_payment->loadGet($invoice->paysys_id, false);
                    if ($ps)
                        $invoice->_cancelUrl = $ps->getUserCancelUrl($invoice);
                } catch (Exception $e){}
            }
        }
        
        $this->view->payments = $this->getDi()->invoicePaymentTable->findByUserId($this->user_id, null, null, 'dattm DESC');
        foreach ($this->view->payments as $payment)
        {
            $payment->_paysysName = $psList->getTitle($payment->paysys_id);
        }
        
        $this->view->display('member/payment-history.phtml');
    }

    function setError($error)
    {
        $this->view->assign('error', $error);
        return false;
    }

    function addRenewAction()
    {
        $this->_redirect('signup');
    }

    function indexAction()
    {

        $this->view->assign('member_products', $this->getDi()->user->getActiveProducts());

        $this->view->assign('member_links', 
            $this->getDi()->hook->call(Am_Event::GET_MEMBER_LINKS, array('user' => $this->user))
                ->getReturn());
        $this->view->assign('left_member_links', 
            $this->getDi()->hook->call(Am_Event::GET_LEFT_MEMBER_LINKS, array('user' => $this->user))
                ->getReturn());

        $this->view->assign('resources', 
            $this->getDi()->resourceAccessTable->getAllowedResources($this->getDi()->user, 
            ResourceAccess::USER_VISIBLE_TYPES));
        
        $this->view->display('member/main.phtml');
    }

    function checkEmailVerified()
    {
        if ($this->user->email_verified < 0 && $this->getDi()->config->get('verify_email'))
        {
            $v = md5($this->user->user_id . $this->user->login . $this->user->email);
            throw new Am_Exception_InputError(___('You have not verified your e-mail address, so login is
                not%sallowed. Please check your mailbox for e-mail verification message and%sclick link to be verified.
                If you have not receive verification message,%splease click %sthis link%s and we will resend you verification message.'),
                "<br />", "<br />", "<br />", "<a href='".REL_ROOT_URL."/resend?v=$v'>",
                "</a>");
        }
    }

    function getInvoiceAction()
    {
        $id = $this->getFiltered('id');
        if (!$id)
            throw new Am_Exception_InputError("Wrong invoice# passed");
        $invoice = $this->getDi()->invoiceTable->findFirstByPublicId($id);
        if (!$invoice)
            throw new Am_Exception(___("Invoice not found"));
        if ($invoice->user_id != $this->user->user_id)
            throw new Am_Exception_Security("Foreign invoice requested : [$id] for {$this->user->user_id}");

        $pdfInvoice = new Am_Pdf_Invoice($invoice);
        $pdfInvoice->setDi($this->getDi());
        $pdfInvoiceRendered = $pdfInvoice->render();

        $this->noCache();
        header("Content-type: application/pdf");
        header("Content-Length: " . strlen($pdfInvoiceRendered));
        header("Content-Disposition: attachment; filename={$pdfInvoice->getFileName()}");

        echo $pdfInvoiceRendered;
    }
    
    function upgradeAction()
    {
        // load invoice to work with
        $id = $this->getFiltered('invoice_id');
        if (!$id)
            throw new Am_Exception_InputError("Wrong invoice# passed");
        $invoice = $this->getDi()->invoiceTable->findFirstByPublicId($id);
        if (!$invoice)
            throw new Am_Exception(___("Invoice not found"));
        if ($invoice->user_id != $this->user->user_id)
            throw new Am_Exception_Security("Foreign invoice requested : [$id] for {$this->user->user_id}");
        // right now we only can handle first item
        $item = current($invoice->getItems());
        //
        $upgrade = $this->getDi()->productUpgradeTable->load($this->_request->getInt('upgrade'));
        if ($upgrade->from_billing_plan_id != $item->billing_plan_id)
            throw Am_Exception_Security(___("Wrong Upgrade Path selected"));
        
        $ps = $this->getDi()->plugins_payment->loadGet($invoice->paysys_id);
        $ps->changeSubscription($invoice, $item, $upgrade->to_billing_plan_id);
    }
}
