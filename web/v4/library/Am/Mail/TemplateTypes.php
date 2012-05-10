<?php

/**
 * Registry of e-mail template types and its properties
 */
class Am_Mail_TemplateTypes extends ArrayObject
{
    protected $tagSets = array();
    
    static protected $instance;

    /** @return Am_Mail_TemplateTypes */
    static function getInstance()
    {
        if (!self::$instance)
            self::$instance = self::createInstance();
        return self::$instance;
    }

    public function find($id)
    {
        return $this->offsetExists($id) ? $this->offsetGet($id) : null;
    }
    
    /** @return Am_Mail_TemplateTypes */
    static function createInstance()
    {
        $o = new self;

        $o->tagSets = array(
            'user' => array(
                '%user.name_f%' => 'User First Name',
                '%user.name_l%' => 'User Last Name',
                '%user.login%' => 'Username',
                '%user.pass%' => 'Password (plain-text)',
                '%user.email%' => 'E-Mail',
                '%user.user_id%' => 'User Internal ID#',
                '%user.street%' => 'User Street',
                '%user.city%' => 'User City',
                '%user.state%' => 'User State',
                '%user.zip%' => 'User ZIP',
                '%user.country%' => 'User Country',
                '%user.status%' => 'User Status (0-pending, 1-active, 2-expired)',
            ),
        );
        
        $o->exchangeArray(array(
            'registration_mail' =>  array(
                'id' => 'registration_mail',
                'title' => 'Registration E-Mail',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user', 'password' => 'Plain-Text Password'),
            ),
            'send_signup_mail' =>  array(
                'id' => 'send_signup_mail',
                'title' => 'Send Signup Mail',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'send_pending_email' => array(
                'id' => 'send_pending_email',
                'title' => 'Send Pending Email',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'send_pending_admin' => array(
                'id' => 'send_pending_admin',
                'title' => 'Send Pending Admin',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'mail_payment_admin' => array(
                'id' => 'mail_payment_admin',
                'title' => 'Mail Payment Admin',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'aff_mail_sale_user' => array(
                'id' => 'aff_mail_sale_user',
                'title' => 'Aff Mail Sale User',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'aff_mail_sale_admin' => array(
                'id' => 'aff_mail_sale_admin',
                'title' => 'Aff Mail Sale Admin',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'send_payment_mail' => array(
                'id' => 'send_payment_mail',
                'title' => 'Send Payment Mail',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'send_payment_admin' => array(
                'id' => 'send_payment_admin',
                'title' => 'Send Payment Admin',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'manually_approve' => array(
                'id' => 'manually_approve',
                'title' => 'Manually Approve',
                'mailPeriodic' => Am_Mail::ADMIN_REQUESTED,
                'vars' => array('user'),
            ),
            'manually_approve_admin' => array(
                'id' => 'manually_approve_admin',
                'title' => 'Manually Approve Admin',
                'mailPeriodic' => Am_Mail::ADMIN_REQUESTED,
                'vars' => array('user'),
            ),
            'cc_rebill_failed' =>
            array(
                'id' => 'cc_rebill_failed',
                'title' => 'Cc Rebill Failed',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'cc_rebill_failed_admin' =>
            array(
                'id' => 'cc_rebill_failed_admin',
                'title' => 'Cc Rebill Failed Admin',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'cc_rebill_success' =>
            array(
                'id' => 'cc_rebill_success',
                'title' => 'Cc Rebill Success',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'card_expires' =>
            array(
                'id' => 'card_expires',
                'title' => 'Card Expires',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'send_security_code' =>
            array(
                'id' => 'send_security_code',
                'title' => 'Send Security Code',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' =>  array('user', 'code' => 'Security Code', 'url' => 'Click Url'),
            ),
            'notify_new_message' =>
            array(
                'id' => 'notify_new_message',
                'title' => 'Notify New Message',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'verify_email_signup' =>
            array(
                'id' => 'verify_email_signup',
                'title' => 'Verify Email Signup',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'verify_email_profile' =>
            array(
                'id' => 'verify_email_profile',
                'title' => 'Verify Email Profile',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'verify_guest' =>
            array(
                'id' => 'verify_guest',
                'title' => 'Verify Guest',
                'mailPeriodic' => Am_Mail::USER_REQUESTED,
                'vars' => array('user'),
            ),
            'autoresponder' => 
            array(
                'id' => 'autoresponder',
                'title' => 'Auto-Responder',
                'mailPeriodic' => Am_Mail::REGULAR,
                'vars' => array('user'),
            ),
            'expire' => 
            array(
                'id' => 'expire',
                'title' => 'Expiration E-Mail',
                'mailPeriodic' => Am_Mail::REGULAR,
                'vars' => array('user'),
            ),
        ));

        return $o;
    }
    
    /**
     * Return array - key => value of available options for template with given $id
     * @param type $id
     * @return array
     */
    public function getTagsOptions($id)
    {
        $record = @$this[$id];
        $ret = array(
            '%site_title%' => 'Site Title',
            '%root_url%' => 'aMember Root URL',
            '%admin_email%' => 'Admin E-Mail Address',
        );
        if (!$record || empty($record['vars']))
            return $ret;
        foreach ($record['vars'] as $k => $v)
        {
            if (is_int($k)) // tag set
                $ret = array_merge($ret, $this->tagSets[$v]);
            else // single variable
                $ret['%'.$k.'%'] = $v;
        }
        return $ret;
    }

    public function add($id, $title, $mailPeriodic, array $vars)
    {
        $this[$id] = array('id' => $id, 'title' => $title, 'mailPeriodic' => $mailPeriodic, 'vars' => $vars);
    }

}