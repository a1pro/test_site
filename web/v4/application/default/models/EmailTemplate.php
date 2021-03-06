<?php
/**
 * Class represents records from table email_templates
 * {autogenerated}
 * @property int $email_template_id 
 * @property string $name 
 * @property string $lang 
 * @property string $format enum('text','html','multipart')
 * @property string $subject 
 * @property string $txt 
 * @property string $plain_txt 
 * @property string $attachments 
 * @property int $product_id 
 * @property int $day 
 * @see Am_Table
 */
class EmailTemplate extends ResourceAbstract 
{
    const AUTORESPONDER  = 'autoresponder';
    const EXPIRE = 'expire';
    const NOT_COMPLETED = 'mail_not_completed';
    
    const ATTACHMENT_FILE_PREFIX = 'emailtemplate';
    
    const FORMAT_TEXT = 'text';
    const FORMAT_HTML = 'html';
    const FORMAT_MULTIPART = 'multipart';
    
    public function toRow() {
        $ret = parent::toRow();
        if (@$ret['day']=='') $ret['day'] = null;
        if (@$ret['product_id']=='') $ret['product_id'] = null;
        return $ret;
    }

    public function getAccessType()
    {
        return ResourceAccess::EMAILTEMPLATE;
    }
    public function getLinkTitle()
    {
        return null;
    }
    
}

class EmailTemplateTable extends ResourceAbstractTable
{
    protected $_key = 'email_template_id';
    protected $_table = '?_email_template';
    protected $_recordClass = 'EmailTemplate';
    public $_checkUnique = array(
        'name', 'lang', 'product_id', 'day'
    );
    
    /**
     *
     * @param type $name
     * @param type $lang
     * @return EmailTemplate
     */
    public function findFirstExact($name, $lang = null)
    {
        $row = $this->getDi()->db->selectRow("SELECT * FROM ?_email_template
            WHERE name=?
            {ORDER BY lang=? DESC, lang='en' DESC}",
                $name,
                is_null($lang) ? DBSIMPLE_SKIP : $lang);
        return $row ? $this->createRecord($row) : null;
    }
    
    // --------- ------------- -------------- -------------- ---------------/
    function deleteByFields($name, $productId=null, $day=null){
        $this->_db->query("DELETE FROM ?_email_template
            WHERE name=? {AND product_id=?} {AND day=?}",
                $name,
                $productId!="" ? $productId : DBSIMPLE_SKIP,
                $day!="" ? $day : DBSIMPLE_SKIP
                );
    }
    /**
     * Find exact template by criteria
     * @return EmailTemplate
     */
    function getExact($name, $lang=null, $product_id=null, $day=null)
    {
        $row = $this->getDi()->db->selectRow("SELECT * FROM ?_email_template
            WHERE name=?
            {AND lang=?} {AND product_id=?} {AND day=?}",
                $name,
                is_null($lang) ? DBSIMPLE_SKIP : $lang,
                is_null($product_id) ? DBSIMPLE_SKIP : $product_id,
                is_null($day) ? DBSIMPLE_SKIP : $day);
        return $row ? $this->createRecord($row) : null;
    }

    /**
     * Search available days options by criteria
     * @return array of int (available days)
     */
    function getDays($name, $product_id=null, $exclude=null){
        return $this->getDi()->db->selectCol("SELECT DISTINCT day
            FROM ?_email_template
            WHERE name=? AND day IS NOT NULL AND IFNULL(product_id,0)=?d
            {AND day<>?}
            ORDER BY day",
                $name,
                is_null($product_id) ? 0 : $product_id,
                is_null($exclude) ? 0 : $exclude
            );
    }

    public function getLanguages($name, $product_id=null, $day=null, $exclude=null){
        return $this->_db->selectCol("SELECT DISTINCT lang as ARRAY_KEY, lang
            FROM ?_email_template
            WHERE name=? { AND product_id=? } {AND day=?} {AND lang<>?}",
                $name, 
                is_null($product_id) ? DBSIMPLE_SKIP : $product_id,
                is_null($day) ? DBSIMPLE_SKIP : $day,
                is_null($exclude) ? DBSIMPLE_SKIP : $exclude
            );
    }

    
    /**
     * @link Invoice->start
     */
    public function sendZeroAutoresponders(User $user)
    {
        foreach ($this->getDi()->resourceAccessTable->
            getAllowedResources($user, ResourceAccess::EMAILTEMPLATE) 
                as $et)
        {
            if (($et->name != EmailTemplate::AUTORESPONDER) || ($et->day != 1)) continue;
            // don't send same e-mail twice
            $sent = (array)$user->data()->get('zero-autoresponder-sent');
            if (in_array($et->pk(), $sent)) continue;
            $sent[] = $et->pk();
            ///
            $tpl = Am_Mail_Template::createFromEmailTemplate($et);
            $tpl->user = $user;
            $tpl->send($user);
            // store sent emails
            $user->data()->set('zero-autoresponder-sent', $sent)->update();
        }
    }
    
    static function onInvoiceStarted(Am_Event_InvoiceStarted $event)
    {
        $event->getDi()->emailTemplateTable->sendZeroAutoresponders($event->getInvoice()->getUser());
    }
    
    static function onPaymentAfterInsert(Am_Event_PaymentAfterInsert $event)
    {
        /**
         * This e-mail is sent for first payment in invoice only
         * another template must be used for the following payments
         */
        if ($event->getInvoice()->getPaymentsCount() != 1) return;
        if ($event->getDi()->config->get('send_payment_mail'))
        {
            $et = Am_Mail_Template::load('send_payment_mail', $event->getUser()->lang);
            if ($et)
            {
                $et->setUser($event->getUser())
                   ->setInvoice($event->getInvoice())
                   ->setPayment($event->getPayment())
                   ->setInvoice_text($event->getInvoice()->render());
                
                if (Am_Di::getInstance()->config->get('send_pdf_invoice', false)) {
                    try{
                        $invoice = new Am_Pdf_Invoice($event->getInvoice());
                        $et->getMail()->createAttachment(
                                    $invoice->render(), 
                                    'application/pdf', 
                                    Zend_Mime::DISPOSITION_ATTACHMENT, 
                                    Zend_Mime::ENCODING_BASE64, 
                                    $invoice->getFileName()
                                );
                    }
                    catch(Exception $e)
                    {
                        $this->getDi()->errorLogTable->logException($e);
                    }
                }
                
                $et->send($event->getUser());
            }
        }
        
        if ($event->getDi()->config->get('send_payment_admin'))
        {
            $et = Am_Mail_Template::load('send_payment_admin', $event->getUser()->lang);
            if ($et)
            {
                $et->setUser($event->getUser())
                   ->setInvoice($event->getInvoice())
                   ->setPayment($event->getPayment())
                   ->setInvoice_text($event->getInvoice()->render());
                $et->send(Am_Mail_Template::TO_ADMIN);
            }
        }
    }
    
    public function sendCronAutoresponders()
    {
        $userTable = $this->getDi()->userTable;
        $etCache = array();
        $db = $this->getDi()->db;
        $q = $this->getDi()->resourceAccessTable->getResourcesForMembers(ResourceAccess::EMAILTEMPLATE)->query();
        while ($res = $db->fetchRow($q))
        {
            $user = $userTable->load($res['user_id'], false);
            if (!$user) continue; // no user found
            if (!array_key_exists($res['resource_id'], $etCache))
                $etCache[$res['resource_id']] = $this->load($res['resource_id'], false);
            if (!$etCache[$res['resource_id']]) continue; // no template found
            if (($etCache[$res['resource_id']]->name != EmailTemplate::AUTORESPONDER)) continue;
            $tpl = Am_Mail_Template::createFromEmailTemplate($etCache[$res['resource_id']]);
            $tpl->user = $user;
            $tpl->send($user);
        }
    }
    
    public function sendCronExpires()
    {
        $mails = $this->findBy(array('name' => EmailTemplate::EXPIRE));
        if (!$mails) return; // nothing to send
        $byDate = array(); // templates by expiration date
        foreach ($mails as $et)
        {
            $et->_productIds = $et->findMatchingProductIds();
            ///
            $day = - $et->day;
            $string = $day . ' days';
            if ($day >= 0) $string = "+$string";
            if ($day == 0) $string = 'today';
            $date = date('Y-m-d', strtotime($string));
            
            $byDate[$date][] = $et;
        }
        
        $userTable = $this->getDi()->userTable;
        // now query expirations
        $q = $this->getDi()->accessTable->queryExpirations(array_keys($byDate));
        $sent = array(); // user_id => array('tpl_id')
        while ($row = $this->_db->fetchRow($q))
        {
            $user = $userTable->createRecord($row);
            foreach ($byDate[$row['_expire_date']] as $et)
            {
                // do not send same template agian to the same user
                if (array_search($et->pk(), (array)@$sent[$user->user_id]) !== false) continue; 
                
                if ($et->_productIds == ResourceAccess::ANY_PRODUCT ||
                    (in_array($row['_product_id'], $et->_productIds))) 
                {
                    $tpl = Am_Mail_Template::createFromEmailTemplate($et);
                    $tpl->user = $user;
                    $tpl->send($user);
                    $sent[$user->user_id][] = $et->pk();
                }
            }
        }
    }
    
    function sendCronMailNotCompleted()
    {
        return;
        $mails = $this->findBy(array('name' => EmailTemplate::NOT_COMPLETED));
        if (!$mails) return; // nothing to send
        $byDate = array(); // templates by expiration date
        foreach ($mails as $et)
        {
            $day = - $et->day;
            if (!$day) continue; // must be an error
            $string = $day . ' days';
            $date = date('Y-m-d', strtotime($string));
            $byDate[$date] = $et;
            $whereDates[] = "(i.tm_added BETWEEN '$date 00:00:00' AND '$date 23:59:59')";
        }
        
        $whereDates = implode(' OR ', $whereDates);
        $q = $this->_db->queryResultOnly("SELECT u.*, 
                i.invoice_id AS _invoice_id,
                DATE(i.tm_added) AS _invoice_date_added
            FROM ?_invoice i 
                LEFT JOIN ?_user u USING (user_id)
            WHERE 
                i.status = ?d 
                AND ($whereDates)
                AND u.status = ? 
            ",  Invoice::PENDING, 
                User::STATUS_PENDING
            );
        
        $sent = array();
        $userTable = $this->getDi()->userTable;
        while ($row = $this->_db->fetchRow($q))
        {
            if (array_key_exists($row['user_id'], $sent)) continue;
            
            $et = $byDate[ $row['_invoice_date_added'] ];
            if (!$et) continue;
            
            $user = $userTable->createRecord($row);
            
            $tpl = Am_Mail_Template::createFromEmailTemplate($et);
            $tpl->user = $user;
            $tpl->send($user);
            
            $sent[$row['user_id']] = true;
        }
    }
}
