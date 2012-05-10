<?php

class AdminLogsController extends Am_Controller_Pages
{
    public function initPages()
    {
        $this->addPage(array($this,'createErrors'), 'errors', ___('Errors'))
             ->addPage(array($this, 'createAccess'), 'access', ___('Access'))
             ->addPage(array($this, 'createInvoice'), 'invoice', ___('Invoice'))
             ->addPage(array($this, 'createMailQueue'), 'mailqueue', ___('Mail Queue'))
             ->addPage(array($this, 'createAdminLog'), 'adminlog', ___('Admin Log'));
    }
    /// 
    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission(Am_Auth_Admin::PERM_LOGS);
    }
    public function createErrors()
    {
        $q = new Am_Query($this->getDi()->errorLogTable);
        $q->setOrder('time', 'desc');
        $g = new Am_Grid_ReadOnly('_error', ___('Error/Debug Log'), $q, $this->getRequest(), $this->view);
        $g->addField(new Am_Grid_Field_Date('time', ___('Time'), true, '', null, '10%'));
        $g->addField(new Am_Grid_Field_Expandable('url', ___('URL'), true, '', null, '20%'));
        $g->addField(new Am_Grid_Field('remote_addr', ___('IP'), true, '', null, '10%'));
        $g->addField(new Am_Grid_Field('error', ___('Message'), true, '', null, '45%'));
        $f = $g->addField(new Am_Grid_Field_Expandable('trace', ___('Trace'), false, '', null, '15%'))
             ->setGetFunction(array($this, 'escapeTrace'));
        $f->setEscape(false);
        $g->setFilter(new Am_Grid_Filter_Text(___('Filter'), array(
            'url' => 'LIKE', 
            'remote_addr' => 'LIKE',
            'referrer' => 'LIKE',
            'error' => 'LIKE',
        )));
        return $g;
    }
    public function escapeTrace(ErrorLog $l)
    {
        return highlight_string($l->trace, true);
    }
    public function createAccess()
    {
        $query = new Am_Query($this->getDi()->accessLogTable);
        $query->leftJoin('?_user', 'm', 't.user_id=m.user_id')
            ->addField("m.login", 'member_login')
            ->addField("CONCAT(m.name_f, ' ', m.name_l)", 'member_name');        
        $query->setOrder('time', 'desc');
        $g = new Am_Grid_ReadOnly('_access', ___('Access Log'), $query, $this->getRequest(), $this->view);
        $g->setPermissionId(Am_Auth_Admin::PERM_LOGS);
        $g->addGridField(new Am_Grid_Field_Date('time', ___('Time'), true, '', null, '10%'));
        $g->addGridField(new Am_Grid_Field('member_login', ___('User'), true, '', array($this, 'renderAccessMember'), '10%'));
        $g->addGridField(new Am_Grid_Field_Expandable('url', ___('URL'), true, '', null, '20%'));
        $g->addGridField(new Am_Grid_Field('remote_addr', ___('IP'), true, '', null, '10%'));
        $g->addGridField(new Am_Grid_Field_Expandable('referrer', ___('Referrer'), true, '', null, '15%'));
        $g->setFilter(new Am_Grid_Filter_Text(___('Filter by IP or Referrer or URL'), array(
            'remote_addr' => 'LIKE',
            'referrer' => 'LIKE', 
            'url' => 'LIKE',
        )));
        return $g;
    }
    public function createInvoice()
    {
        $query  = new Am_Query(new InvoiceLogTable);
        $query->addField("m.login", "login");
        $query->leftJoin("?_user", "m", "t.user_id=m.user_id");
        $query->setOrder('tm', 'desc');
        $g = new Am_Grid_Editable('_invoice', ___('Invoice Log'), $query, $this->getRequest(), $this->view);
        $g->addField(new Am_Grid_Field('tm', ___('Time'), true, '', null, '10%'));
        $g->addField(new Am_Grid_Field('invoice_id', ___('Invoice'), true, '', null, '5%'));
        $g->addField(new Am_Grid_Field('login', ___('User'), true, '', null, '5%'));
        $g->addField(new Am_Grid_Field('remote_addr', ___('IP'), true, '', null, '5%'));
        $g->addField(new Am_Grid_Field('paysys_id', ___('Paysystem'), true, '', null, '10%'));
        $g->addField(new Am_Grid_Field('title', ___('Title'), true, '', null, '25%'));
        $g->addField(new Am_Grid_Field_Expandable('details', ___('Details'), false, '', null, '25%'))
            ->setGetFunction(array($this, 'renderInvoiceDetails'));
        $g->actionsClear();
        $g->actionAdd(new Am_Grid_Action_InvoiceRetry);
        $g->setFilter(new Am_Grid_Filter_InvoiceLog);
        return $g;
    }
    public function renderAccessMember($record)
    {
        return sprintf('<td><a target="_top" href="%s">%s (%s)</a></td>', 
            $this->getView()->userUrl($record->user_id), $record->member_login, $record->member_name);
    }
    public function renderTime($record)
    {
        return amDateTime($record->time);
    }
    
    public function renderAdminTime($record)
    {
        return sprintf("<td>%s</td>", amDateTime($record->dattm));
    }
    
    public function renderAdmin($record)
    {
        return sprintf('<td><a href="%s" target="_top">%s</a></td>', 
            $this->escape(REL_ROOT_URL . "/admin-admins?_admin_a=edit&_admin_id=" . (int)$record->admin_id),
            $this->escape($record->admin_login)
        );
    }
    
    public function renderRec(AdminLog $record)
    {
        $text = "";
        if ($record->tablename || $record->record_id)
            $text = $this->escape($record->tablename . ":" . $record->record_id);
        // @todo - add links here to edit pages
        return sprintf('<td>%s</td>', $text);
    }
    
    public function renderInvoiceDetails(InvoiceLog $obj, $field, $controller, $fieldObj)
    {
        $fieldObj->setEscape(true);
        $ret = "";
        $ret .= "<div class='collapsible'>\n";
        $rows = $obj->getRenderedDetails();
        $open = count($rows) == 1 ? 'open' : '';
        foreach ($rows as $row)
        {
            $popup = @$row[2];
            if ($popup) $popup = "<br /><br />ENCODED DETAILS:<br />" . nl2br($row[2]);
            $ret .= "\t<div class='item $open'>\n";
            $ret .= "\t\t<div class='head'>$row[0]</div>\n";
            $ret .= "\t\t<div class='more'>$row[1]$popup</div>\n";
            $ret .= "\t</div>\n";
        }
        $ret .= "</div>\n\n";
        return $ret;
    }
    public function createMailQueue()
    {
        $ds = new Am_Query($this->getDi()->mailQueueTable);
        $ds->setOrder('added', true);
        
        $g = new Am_Grid_ReadOnly('_mail', ___("E-Mail Queue"), $ds, $this->getRequest(), $this->view);
        $g->addGridField(new Am_Grid_Field('recipients', ___('Recipients'), true, '', null, '20%'));
        $g->addGridField(new Am_Grid_Field('added', ___('Added'), true, '', array($this, 'renderTimestamp'), '15%'));
        $g->addGridField(new Am_Grid_Field('sent', ___('Sent'), true, '', array($this, 'renderTimestamp'), '15%'));
        $g->addGridField(new Am_Grid_Field('subject', ___('Subject'), true, '', null, '30%'))
            ->setRenderFunction(array($this, 'renderSubject'));
        
        $body = new Am_Grid_Field_Expandable('body', ___('Mail'), true, '', null, '20%');
        $body->setEscape(true);
        $body->setGetFunction(array($this, 'renderMail'));
        $g->addGridField($body);
        
        $g->setFilter(new Am_Grid_Filter_Text(___("Filter by subject or recepient"), array(
            'subject' => 'LIKE',
            'recipients' => 'LIKE',
        )));
        return $g;
    }
    
    function renderMail($obj, $controller, $field, Am_Grid_Field_Expandable $fieldObj)
    {
        $_body = $obj->body;
        $_headers = unserialize($obj->headers);
        $atRendered = null;

        $val = '';
        $headers = array();
        foreach ($_headers as $k => $v)
        {
            $headers[$k] = $v[0];
        }

        if (isset($headers['Content-Transfer-Encoding']) &&
            $headers['Content-Transfer-Encoding'] == 'quoted-printable')
        {
            $body = quoted_printable_decode($_body);
            if (strpos($headers['Subject'], '=?') === 0)
                $headers['Subject'] = mb_decode_mimeheader($headers['Subject']);
        } else
        {
            $body = base64_decode($_body);
        }
        if ($body) $body = nl2br($body);

        foreach ($headers as $headerName => $headerVal)
        {
            $val .= '<b>' . $headerName . '</b> : <i>' . Am_Controller::escape($headerVal) . '</i><br />';
        }

        if (isset($headers['Content-Type']) &&
            strstr($headers['Content-Type'], 'multipart/mixed'))
        {

            preg_match('/boundary="(.*)"/', $headers['Content-Type'], $matches);
            $boundary = $matches[1];

            $message = @Zend_Mime_Message::createFromMessage($body, $boundary);
            $parts = $message->getParts();
            $part = @$parts[0];
            if ($part)
            {
                $body = $part->getContent();
                if ($part->encoding == 'quoted-printable')
                {
                    $body = quoted_printable_decode($body);
                } else
                {
                    $body = base64_decode($body);
                }
                }
            $attachments = array_slice($parts, 1);
            $atRendered = '';
            foreach ($attachments as $at)
            {
                preg_match('/filename="(.*)"/', $at->disposition, $matches);
                $filename = @$matches[1];
                $atRendered .= sprintf("&mdash %s (%s)", $filename, $at->type) . '<br />';
            }
        }
        $val .= '<br />' . $body . ($atRendered ? '<br /><strong>Attachments:</strong><br />' . $atRendered : '');
        return $val;
    }

    function renderSubject(MailQueue $m)
    {
        $s = $m->subject;
        if (strpos($s, '=?') === 0)
            $s = mb_decode_mimeheader($s);
        return "<td>". Am_Controller::escape($s) . "</td>";
    }

    function renderTimestamp($record, $field)
    {
        $val = $record->$field;
        if ($val)
        {
            $val = amDatetime($val);
        } else
        {
            $val = 'Not Sent';
        }
        return sprintf('<td>%s</td>', $val);
    }

    public function createAdminLog()
    {
        $ds = new Am_Query($this->getDi()->adminLogTable);
        $ds->setOrder('dattm', 'desc');
        
        $g = new Am_Grid_ReadOnly('_admin', ___("Admin Log"), $ds, $this->getRequest(), $this->view);
        $g->addGridField(new Am_Grid_Field('dattm', ___('Time'), true, '', array($this, 'renderAdminTime'), '10%'));
        $g->addGridField(new Am_Grid_Field('admin_login', ___('Admin'), true, '', array($this, 'renderAdmin'), '10%'));
        $g->addGridField(new Am_Grid_Field('ip', ___('IP'), true, '', null, '10%'));
        $g->addGridField(new Am_Grid_Field('message', ___('Message')));
        $g->addGridField(new Am_Grid_Field('record', ___('Record')))->setRenderFunction(array($this, 'renderRec'));
        
        $g->setFilter(new Am_Grid_Filter_Text(___('Filter'), array(
            'admin_login' => 'LIKE',
        )));
        return $g;
    }
}

class Am_Grid_Filter_InvoiceLog extends Am_Grid_Filter_Abstract
{
    public function __construct()
    {
        $this->title = ___("Filter by string or by invoice#/member#");
    }
    protected function applyFilter()
    {
        $query = $this->grid->getDataSource();
        $filter = $this->vars['filter'];
        $condition = $query->add(new Am_Query_Condition_Field('paysys_id', 'LIKE', '%' . $filter . '%'))
            ->_or(new Am_Query_Condition_Field('title', 'LIKE', '%' . $filter . '%'))
            ->_or(new Am_Query_Condition_Field('type', 'LIKE', '%' . $filter . '%'))
            ->_or(new Am_Query_Condition_Field('details', 'LIKE', '%' . $filter . '%'));
        if ($filter > 0)
        {
            $condition->_or(new Am_Query_Condition_Field('invoice_id', '=', (int)$filter));
            $condition->_or(new Am_Query_Condition_Field('user_id', '=', (int)$filter));
        }
    }
    public function renderInputs()
    {
        return $this->renderInputText();
    }
}

class Am_Grid_Action_InvoiceRetry extends Am_Grid_Action_Abstract
{
    protected $type = self::SINGLE;
    
    public function __construct($id = null, $title = null)
    {
        $this->title = ___("Repeat Action Handling");
        parent::__construct($id, $title);
        $this->setTarget('_top');
    }
    
    public function isAvailable($record)
    {
        return (strpos($record->details, 'type="incoming-request"') !== false);
    }

    public function run()
    {
        echo $this->renderTitle();
        $invoiceLog = Am_Di::getInstance()->invoiceLogTable->load($this->getRecordId());

        $response = array();
        try
        {
            Am_Di::getInstance()->plugins_payment->load($invoiceLog->paysys_id);
            $paymentPlugin = Am_Di::getInstance()->plugins_payment->get($invoiceLog->paysys_id);
            /* @var $paymentPlugin Am_Paysystem_Abstract */
            try
            {
                $request = $invoiceLog->getFirstRequest();
                if (!$request instanceof Am_Request)
                    throw new Am_Exception_InputError("Am_Request is not saved for this record, this action cannot be repeated");
                $resp = new Zend_Controller_Response_Http();

                Zend_Controller_Front::getInstance()->getRouter()->route($request);

                $paymentPlugin->toggleDisablePostbackLog(true);
                $paymentPlugin->directAction($request, $resp, array('di' => Am_Di::getInstance()));

                $response['status'] = 'OK';
                $response['msg'] = 'The action has been repeated, ipn script response [' . $resp->getBody() . "]";
            } catch (Exception $e)
            {
                $response['status'] = 'ERROR';
                $response['msg'] = sprintf("Exception %s : %s", get_class($e), $e->getMessage());
            }
        } catch (Exception $e)
        {
            Am_Di::getInstance()->errorLogTable->log($e);
            $response['status'] = 'ERROR';
            $response['msg'] = $e->getMessage();
        }

        echo "<b>RESULT: $response[status]</b><br />";
        echo $response['msg'];
        echo "<br /><br />\n";
        echo $this->renderBackUrl();
    }
}




