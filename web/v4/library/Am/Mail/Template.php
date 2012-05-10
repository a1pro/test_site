<?php

/**
 * Email template class
 * 
 * @method Am_Mail_Template setUser(User $user) provides fluent interface
 */
class Am_Mail_Template extends ArrayObject
{
    const TO_ADMIN = '|TO-ADMIN|';
    
    /** @var array */
    protected $template = array();
    /** @var Am_Mail */
    protected $mail;
    
    protected $_mailPeriodic = Am_Mail::REGULAR;
    
    public function __construct($tplId = null, $lang = null)
    {
        $this->setFlags(self::ARRAY_AS_PROPS);
        $this->setArray(array(
            'site_title' => Am_Di::getInstance()->config->get('site_title'),
            'root_url'   => ROOT_URL, 
            'admin_email' => Am_Di::getInstance()->config->get('admin_email'),
        ));
    }
    public function __call($name, $arguments)
    {
        if (strpos($name, 'set')===0)
        {
            $var = lcfirst(substr($name, 3));
            $this[$var] = $arguments[0];
            return $this;
        }
        trigger_error("Method [$name] does not exists in " . __CLASS__, E_USER_ERROR);
    }
    public function setArray(array $vars)
    {
       foreach ($vars as $k => $v)
           $this->$k = $v;
       return $this;
    }
    function setTemplate($format, $subject, $bodyText, $bodyHtml, $attachments, $id)
    {
        // switch bodyText/bodyHtml based on format
        if (($format == 'text') && empty($bodyText))
        {
            $bodyText = $bodyHtml;
            $bodyHtml = null;
        } elseif (($format == 'html') && empty($bodyHtml)) {
            $bodyHtml = $bodyText;
            $bodyText = null;
        }
        
        $this->template = array(
            'format' => $format,
            'subject' => $subject,
            'bodyText' => $bodyText,
            'bodyHtml' => $bodyHtml,
            'attachments' => $attachments,
            'id' => $id,
        );
    }
    /** @return Am_Mail */
    function getMail()
    {
        if (!$this->mail)
            $this->mail = new Am_Mail;
        return $this->mail;
    }
    public function addTo($email, $name)
    {
        $this->getMail()->addTo($email, $name);
    }
    
    function parse()
    {
        if ($text = $this->template['bodyText'])
            $this->getMail()->setBodyText(  $this->_parse($text) , 'utf-8');
        if ($text = $this->template['bodyHtml'])
            $this->getMail()->setBodyHtml(  $this->_parse($text) , 'utf-8');

        if ($this->template['format'] == EmailTemplate::FORMAT_MULTIPART)
            $this->getMail()->setType(Zend_Mime::MULTIPART_ALTERNATIVE);        
        
        $this->getMail()->setSubject(   $this->_parse($this->template['subject']));
        
        $this->parseAttachments();
    }
    
    protected function parseAttachments()
    {
        $upload = new Am_Upload(Am_Di::getInstance(), EmailTemplate::ATTACHMENT_FILE_PREFIX);
        $upload->unserialize($this->template['attachments']);
        foreach ($upload->getUploads() as $file)
        {
            $f = @fopen($file->getFullPath(), 'r');
            if (!$f) {
                trigger_error("Could not open attachment [" . $file->getName() . "] for EmailTemplate#{$this->email_template_id}",
                    E_USER_WARNING);
                continue;
            }
            $this->getMail()->createAttachment($f, $file->getType(),
                    Zend_Mime::DISPOSITION_ATTACHMENT, Zend_Mime::ENCODING_BASE64, $file->getName());
        }
    }
    
    protected function _parse($text)
    {
        $tpl = new Am_SimpleTemplate();
        $tpl->assignStdVars();
        $tpl->assign($this->getArrayCopy());
        $tpl->assign(get_object_vars($this));
        return $tpl->render($text);
    }
    
    function send($recepient)
    {
        if (!$this->template)
            throw new Am_Exception_InternalError("Template was not set in " . __METHOD__);
        
        $this->parse();
        
        if ($recepient instanceof User)
        {
            $this->getMail()->addTo($email = $recepient->email, $recepient->getName());
        } elseif ($recepient instanceof Admin) {
            $this->getMail()->addTo($email = $recepient->email, $recepient->getName());
        } elseif ($recepient===self::TO_ADMIN) {
            $this->getMail()->toAdmin();
        } else {
            $this->getMail()->addTo($email = $recepient);
        }
        
        try {
            $this->getMail()->send();
        } catch (Zend_Mail_Exception $e) {
            Am_Di::getInstance()->errorLogTable->log($e);
            trigger_error("Could not send message to [$email] - error happened: " . $e->getMessage(), E_USER_WARNING);
        }
        
    }
    
    /**
     * Shortcut to email subscribed admins
     */
    function sendAdmin()
    {
        $this->send(self::TO_ADMIN);
    }
    
    function getMailPeriodic()
    {
        return $this->_mailPeriodic;
    }
    function setMailPeriodic($periodic)
    {
        $this->_mailPeriodic = $periodic;
    }
    
    /**
     * @return Am_Mail_Template|null null if no template found
     */
    static function load($id, $lang = null, $throwException = false)
    {
        $di = Am_Di::getInstance();
        if(is_null($lang)) $lang = $di->locale->getLanguage();
        $et = $di->emailTemplateTable->findFirstExact($id, $lang);
        if ($et)
        {
            return self::createFromEmailTemplate($et);
        } elseif ($throwException)
            throw new Am_Exception_Configuration("No e-mail template found for [$id,$lang]");
    }
    
    /** @return Am_Mail_Template */
    static function createFromEmailTemplate(EmailTemplate $et)
    {
        $t = new self;
        $t->setTemplate(
            $et->format, 
            $et->subject, 
            $et->plain_txt, 
            $et->txt,
            $et->attachments,
            $et->email_template_id . '-' . $et->name . '-' . $et->lang
        );

        $rec = Am_Mail_TemplateTypes::getInstance()->find($et->name);
        if ($rec)
            $t->setMailPeriodic($rec['mailPeriodic']);

        return $t;
    }
}
