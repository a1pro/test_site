<?php

abstract class Am_Paysystem_CreditCard extends Am_Paysystem_Abstract
{
    const ACTION_CC = 'cc';
    // form fields contants @see getFormOptions
    const CC_COMPANY                = 'cc_company';
    const CC_TYPE_OPTIONS           = 'cc_type_options';
    const CC_CODE                   = 'cc_code';
    const CC_MAESTRO_SOLO_SWITCH    = 'cc_maestro_solo_switch';
    const CC_INPUT_BIN              = 'cc_input_bin';
    const CC_HOUSENUMBER            = 'cc_housenumber';
    const CC_PROVINCE_OUTSIDE_OF_US = 'cc_province_outside_of_us';
    const CC_PHONE                  = 'cc_phone';

    /** invoice data field name */
    const FIRST_REBILL_FAILURE = 'first_rebill_failure';

    /** @var CcRecord set during bill processing */
    protected $cc;

    public function getRecurringType()
    {
        return self::REPORTS_REBILL;
    }
    /** @return bool if plugin needs to store CC info */
    public function storesCcInfo()
    {
        return true;
    }
    public function _process(Invoice $invoice, Am_Request $request, Am_Paysystem_Result $result)
    {
        $action = new Am_Paysystem_Action_Redirect( $this->getPluginUrl(self::ACTION_CC) );
        $action->id = $invoice->getSecureId($this->getId());
        $result->setAction($action);
    }
    public function createTransaction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs){}
    
    public function directAction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        if ($request->getActionName() == self::ACTION_IPN)
        {
            return parent::directAction($request, $response, $invokeArgs);
        } else {
            $request->setActionName($request->getActionName());
            $p = new Am_Controller_CreditCard($request, $response, $invokeArgs);
            $p->setPlugin($this);
            $p->run();
        }
    }
    /**
     * Method must return array of self::CC_xxx constants to control which
     * additional fields will be displayed in the form
     * @return array
     */
    public function getFormOptions(){
        $ret = array(self::CC_PHONE, self::CC_CODE);
        if ($this->getCreditCardTypeOptions()) $ret[] = self::CC_TYPE_OPTIONS;
        return $ret;
    }
    /**
     * 
     */
    public function getCreditCardTypeOptions(){
        return array();
    }


    /**
     * You can do form customization necessary for the plugin
     * here
     */
    public function onFormInit(Am_Form_CreditCard $form)
    {
    }

    /**
     * You can do custom form validation here. If errors found,
     * call $form->getElementById('xx-0')->setError('xxx') and
     * return false
     * @return bool
     */
    public function onFormValidate(Am_Form_CreditCard $form)
    {
        return true;
    }
    
    /**
     * Filter and validate cc#
     * @return null|string null if ok, error message if error
     */
    public function validateCreditCardNumber($cc){
        require_once 'ccvs.php';
        $validator = new CreditCardValidationSolution;
        if (!$validator->validateCreditCard($cc)) 
            return $validator->CCVSError;
        /** @todo translate error messages from ccvs.php */
        return null;
    }

    final public function doBill(Invoice $invoice, $doFirst, CcRecord $cc)
    {
        $this->invoice = $invoice;
        $this->cc = $cc;
        $result = new Am_Paysystem_Result();
        $this->_doBill($invoice, $doFirst, $cc, $result);
        return $result;
    }
    abstract public function _doBill(Invoice $invoice, $doFirst, CcRecord $cc, Am_Paysystem_Result $result);

    /**
     * Function can be overrided to change behaviour
     */
    public function storeCreditCard(CcRecord $cc, Am_Paysystem_Result $result)
    {
        if ($this->storesCcInfo())
        {
            $cc->replace();
            $result->setSuccess();
        }
        return $this;
    }


    /**
     * Method defined for overriding in child classes where CC info is not stored locally
     * @return CcRecord
     * @param Invoice $invoice
     * @throws Am_Exception
     */
    public function loadCreditCard(Invoice $invoice)
    {
        if ($this->storesCcInfo())
            return $this->getDi()->ccRecordTable->findFirstByUserId($invoice->user_id);
    }

    public function prorateInvoice(Invoice $invoice, CcRecord $cc, Am_Paysystem_Result $result, $date)
    {
        /** @todo use "reattempt" config **/
        $reattempt = array_filter($this->getConfig('reattempt'));
        sort($reattempt);
        if (!$reattempt) return;

        $first_failure = $invoice->data()->get(self::FIRST_REBILL_FAILURE);
        if (!$first_failure)
        {
            $invoice->data()->set(self::FIRST_REBILL_FAILURE, $date)->update();
            $first_failure = $date;
        }
        $days_diff = (strtotime($date) - strtotime($first_failure)) / (24*3600);
        foreach ($reattempt as $day)
             if ($day > $days_diff) break; // we have found next rebill date to jump
        if (!$day) return;
        
        $invoice->updateQuick('rebill_date', date('Y-m-d', strtotime($first_failure) + $day * 24*3600));

        $tr = new Am_Paysystem_Transaction_Manual($this);
        if ($invoice->getAccessExpire() < $invoice->rebill_date)
            $invoice->extendAccessPeriod($invoice->rebill_date);
            
    }
    
    public function onRebillFailure(Invoice $invoice, CcRecord $cc, Am_Paysystem_Result $result, $date)
    {
        $this->prorateInvoice($invoice, $cc, $result, $date);
    }
    public function onRebillSuccess(Invoice $invoice, CcRecord $cc, Am_Paysystem_Result $result, $date)
    {
        if ($invoice->data()->get(self::FIRST_REBILL_FAILURE))
            $invoice->data()->set(self::FIRST_REBILL_FAILURE, null)->update();
    }

    public function ccRebill($date = null)
    {
        $rebillTable = $this->getDi()->ccRebillTable;
        foreach ($this->getDi()->invoiceTable->findForRebill($date) as $invoice)
        {
            /* @var $invoice Invoice */
            try {
                $rebill = $rebillTable->createRecord(array(
                    'paysys_id'     => $this->getId(),
                    'invoice_id'    => $invoice->invoice_id,
                    'rebill_date'   => $date,
                    'status'        => CcRebill::STARTED,
                    'status_msg'    => "Not Processed",
                ));
                $rebill->insert();
                $cc = null;
                if ($this->storesCcInfo())
                {
                    $cc = $this->loadCreditCard($invoice);
                    if (!$cc)
                    {
                        $rebill->setStatus(CcRebill::NO_CC, "No credit card saved, cannot rebill");
                        continue;
                    }
                }
                $result = $this->doBill($invoice, false, $cc);
                if (!$result->isSuccess())
                    $this->onRebillFailure($invoice, $cc, $result, $date);
                else
                    $this->onRebillSuccess($invoice, $cc, $result, $date);
                $rebill->setStatus($result->isSuccess() ? CcRebill::SUCCESS : CcRebill::ERROR, 
                    current($result->getErrorMessages()));
            } catch (Exception $e) {
                if (stripos(get_class($e), 'PHPUnit_')===0) throw $e;
                $rebill->setStatus(CcRebill::EXCEPTION, 
                    "Exception " . get_class($e) . " : " . $e->getMessage());
                // if there was an exception in billing (say internal error),
                // we set rebill_date to tomorrow
                $invoice->updateQuick('rebill_date', date('Y-m-d', strtotime($invoice->rebill_date . ' +1 day')));
                $this->getDi()->errorLogTable->logException($e);
                
                $this->logError("Exception on rebilling", $e, $invoice);
                
                unset($this->invoice);
            }
        }
    }

    public function onSetupForms(Am_Event_SetupForms $event) 
    {
        // insert title, description fields
        $form = parent::onSetupForms($event);
        $form->setTitle(ucfirst(toCamelCase($this->getId())));
        $el = $form->addSelect('payment.'.$this->getId().'.reattempt', array('multiple'=>'multiple', 'class'=>'magicselect'));
        $options = array();
        for ($i=1;$i<60;$i++) $options[$i] = ___("on %d-th day", $i);
        $el->loadOptions($options);
        $el->setLabel(___("Retry On Failure\n".
                 "if the recurring billing has failed,\n".
                 "aMember can repeat it after several days,\n".
                 "and extend customer subscription for that period\n".
                 "enter number of days to repeat billing attempt"));
        
        $text = "<p><font color='red'>WARNING!</font> Every application processing credit card information, must be certified\n" .
                "as PA-DSS compliant, and every website processing credit cards must\n" .
                "be certified as PCI-DSS compliant.</p>";
        $text.= "<p>aMember Pro is not yet certified as PA-DSS compliant. We will start certification process\n".
                "once we get 4.2.0 branch released and stable. This plugins is provided solely for TESTING purproses\n".
                "Use it for anything else but testing at your own risk.</p>";
            
        $form->addProlog(<<<CUT
<div class="warning_box">
    $text
</div>   
CUT
);
        
        $keyFile = defined('AM_KEYFILE') ? AM_KEYFILE : APPLICATION_PATH . '/configs/key.php';
        if (!is_readable($keyFile))
        {
            $random = $this->getDi()->app->generateRandomString(78);
            $text = "<p>To use credit card plugins, you need to create a key file that contains unique\n";
            $text .= "encryption key for your website. It is necessary even if the plugin does not\n";
            $text .= "store sensitive information.</p>";
            $text .= "<p>In a text editor, create file with the following content (one-line, no spaces before opening &lt;?php):\n";
            $text .= "<br /><br /><pre style='background-color: #e0e0e0;'>&lt;?php return '$random';</pre>\n";
            $text .= "<br />save the file as <b>key.php</b>, and upload to <i>amember/application/configs/</i> folder.\n";
            $text .= "This warning will disappear once you do it correctly.</p>";
            $text .= "<p>KEEP A BACKUP COPY OF THE key.php FILE (!)</p>";
            
            $form->addProlog(<<<CUT
<div class="warning_box">
    $text
</div>
CUT
            );
        }
    }
    
    function getAdminCancelUrl(Invoice $invoice)
    {
        return REL_ROOT_URL . '/admin-user-payments/stop-recurring/user_id/'.$invoice->user_id.'?invoice_id=' . $invoice->pk();
    }
    
    function getUserCancelUrl(Invoice $invoice)
    {
        return REL_ROOT_URL . '/payment/'.$this->getId().'/cancel?id=' . $invoice->getSecureId('STOP'.$this->getId());
    }
    
}