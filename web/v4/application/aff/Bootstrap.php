<?php

class Bootstrap_Aff extends Am_Module
{
    const AFF_COMMISSION_AFTER_INSERT = 'affCommissionAfterInsert';
    const COOKIE_NAME = 'amember_aff_id';
    
    function init()
    {
        parent::init();
        $this->getDi()->userTable->customFields()->addCallback(array('Am_Aff_PayoutMethod', 'static_addFields'));
    }
    function onGetMemberLinks(Am_Event $e)
    {
        $u = $e->getUser();
        if (!$u->is_affiliate && !$this->getDi()->config->get('aff.signup_type'))
            $e->addReturn(___('Advertise our website to your friends and earn money'), 
                ROOT_URL . '/aff/aff/enable-aff');
    }
    function onGetUploadPrefixList(Am_Event_GetUploadPrefixList $event)
    {
        $event->addReturn(array(
            Am_Upload_Acl::IDENTITY_TYPE_ADMIN => array(
                'affiliates' => Am_Upload_Acl::ACCESS_ALL
            ),
            Am_Upload_Acl::IDENTITY_TYPE_USER => Am_Upload_Acl::ACCESS_READ,
            Am_Upload_Acl::IDENTITY_TYPE_ANONYMOUS => Am_Upload_Acl::ACCESS_READ
        ), "banners");
        
        $event->addReturn(array(
            Am_Upload_Acl::IDENTITY_TYPE_ADMIN => array(
                'affiliates' => Am_Upload_Acl::ACCESS_ALL
            ),
            Am_Upload_Acl::IDENTITY_TYPE_AFFILIATE => Am_Upload_Acl::ACCESS_READ
        ), "affiliate");
    }
    function onGetPermissionsList(Am_Event $event)
    {
        $event->addReturn("Can see affiliate info/make payouts", "affiliates");
    }
    function onUserMenu(Am_Event $event)
    {
        if (!$event->getUser()->is_affiliate) return;
        $event->getMenu()->addPage(
            array(
                'id'    => 'aff',
                'controller'   => 'aff',
                'module' => 'aff',
                'label' => ___('Affiliate Info'),
                'order' => 300,
                'pages' => array(
                    array(
                        'id'    => 'aff-links',
                        'controller' => 'aff',
                        'module' => 'aff',
                        'label' => ___('Get affiliate banners and links'),
                    ),
                    array(
                        'id'    => 'aff-stats',
                        'controller' => 'member',
                        'module' => 'aff',
                        'action' => 'stats',
                        'label' => ___('Review your affiliate statistics'),
                    ),
                    array(
                        'id'    => 'aff-payout-info',
                        'controller' => 'member',
                        'module' => 'aff',
                        'action' => 'payout-info',
                        'label' => ___('Update your commissions payout info'),
                    ),
                ),
            )
        );

    }
    function onAdminMenu(Am_Event $event)
    {
        $menu = $event->getMenu();
        $menu->addPage(array(
            'id' => 'affiliates',
            'uri' => '#',
            'label' => ___('Affiliates'),
            'resource' => "affiliates",
            'pages' => array(
                array(
                    'id' => 'affiliates-payout',
                    'controller' => 'admin-payout',
                    'module' => 'aff',
                    'label' => ___("Review/Pay Affiliate Commission"),
                    'resource' => "affiliates",
                ),
                array(
                    'id' => 'affiliates-commission',
                    'controller' => 'admin-commission',
                    'module' => 'aff',
                    'label' => ___('Affiliate Clicks/Sales Statistics'),
                    'resource' => "affiliates",
                ),
                array(
                    'id' => 'affiliates-banners',
                    'controller' => 'admin-banners',
                    'module' => 'aff',
                    'label' => ___('Manage Banners and Text Links'),
                    'resource' => "affiliates",
                ),
            )
        ));
    }

    public function addPayoutInputs(HTML_QuickForm2_Container $fieldSet)
    {
        $el = $fieldSet->addSelect('aff_payout_type')
            ->setLabel(___('Affiliate Payout Type'))
            ->loadOptions(array_merge(array(''=>___('Not Selected'))));
        foreach (Am_Aff_PayoutMethod::getEnabled() as $method)
            $el->addOption($method->getTitle(), $method->getId());

        $fieldSet->addScript()->setScript('
/**** show only options for selected payout method */
$(function(){
$("#'.$el->getId().'").change(function()
{
    for (i in this.options)
    {
        var v = this.options[i].value;
        (i == this.selectedIndex) ?
            $(":input[name^=aff_"+v+"_]").parents(".row").show() :
            $(":input[name^=aff_"+v+"_]").parents(".row").hide();
    }
}).change();
});
/**** end of payout method options */
');

        foreach ($this->getDi()->userTable->customFields()->getAll() as $f)
            if (strpos($f->name, 'aff_')===0)
                $f->addToQf2($fieldSet);
    }

    public function onUserForm(Am_Event_UserForm $event)
    {
        if ($event->getAction() == Am_Event_UserForm::BEFORE_SAVE)
        {
            $input = $event->getForm()->getValue();
            if (!empty($input['_aff']))
            {
                $aff = $this->getDi()->userTable->findFirstByLogin($input['_aff'], false);
                if ($aff)
                {
                    if ($aff->pk() == $event->getUser()->pk())
                    {
                        throw new Am_Exception_InputError("Cannot assign affiliate to himself");
                    }
                    $event->getUser()->aff_id = $aff->pk();
                } else {
                    throw new Am_Exception_InputError("Affiliate not found, username specified: " . Am_Controller::escape($input['_aff']));
                }
            }
        }
        
        if ($event->getAction() != Am_Event_UserForm::INIT) return;
        
        $fieldSet = $event->getForm()->addFieldset('affiliate')->setLabel(___('Affiliate Program'));

        $user = $event->getUser();
        $affHtml = "";
        if (!empty($user->aff_id))
        {
            try {
                $aff = $this->getDi()->userTable->load($user->aff_id);
                $url = new Am_View_Helper_UserUrl;
                $affHtml = sprintf('<a target="_blank" href="%s">"%s %s" &lt;%s&gt;</a>', 
                    Am_Controller::escape($url->userUrl($user->aff_id)),
                    $aff->name_f, $aff->name_l, $aff->email
                    );
                $fieldSet->addElement('static', '_aff')
                    ->setLabel(___('Referred Affiliate'))
                    ->setContent($affHtml);
            } catch (Am_Exception $e) {
                // ignore if affiliate was deleted
            }
        } else {
            $fieldSet->addElement('text', '_aff', array('placeholder' => 'Type username or e-mail'))
                ->setLabel(___('Referred Affiliate'));
            $fieldSet->addScript()->setScript(<<<CUT
    $("input#_aff-0").autocomplete({
        minLength: 2,
        source: window.rootUrl + "/admin-users/autocomplete"
    });
CUT
            );
        }

        $fieldSet->addElement('advradio', 'is_affiliate')
            ->setLabel(array(___('Is Affiliate?'), ___('customer / affiliate status')))
            ->loadOptions(array(
                '0'  => ___('No'),
                '1'  => ___('Both Affiliate and member'),
                '2'  => ___('Only Affiliate %s(rarely used)%s', '<i>', '</i>'),
             ));
        
        $this->addPayoutInputs($fieldSet);
    }
    function onUserTabs(Am_Event_UserTabs $event)
    {
        if ($event->getUserId() > 0)
            $event->getTabs()->addPage(array(
                'id' => 'aff',
                'module' => 'aff',
                'controller' => 'admin',
                'action' => 'info-tab',
                'params' => array(
                    'user_id' => $event->getUserId(),
                ),
                'label' => ___('Affiliate Info'),
                'order' => 1000,
                'resource' => 'affiliates',
            ));
    }
    /**
     * if $_COOKIE is empty, find matches for user by IP address in aff_clicks table
     * @param Am_Event_UserBeforeInsert $event 
     */
    function onUserBeforeInsert(Am_Event_UserBeforeInsert $event)
    {
        // skip this code if running from aMember CP
        if (defined('AM_ADMIN') && AM_ADMIN) return;
        $aff_id = @$_COOKIE[self::COOKIE_NAME];
        if (empty($aff_id))
        {
            $aff_id = $this->getDi()->affClickTable->findAffIdByIp($_SERVER['REMOTE_ADDR']);
        }
        // remember for usage in onUserAfterInsert
        $this->last_aff_id = $aff_id;
        if ($aff_id > 0)
            $event->getUser()->aff_id = intval($aff_id);
        if (empty($event->getUser()->is_affiliate))
            $event->getUser()->is_affiliate = $this->getDi()->config->get('aff.signup_type') == 1 ? 1 : 0;
    }
    function onUserAfterInsert(Am_Event_UserAfterInsert $event)
    {
        // skip this code if running from aMember CP
        if (preg_match('/^(\d+)-(\d+)-(\d+)$/', $this->last_aff_id, $regs))
        {
            $this->getDi()->affLeadTable->log($regs[1], $regs[2], $event->getUser()->pk(), $this->decodeClickId($regs[3]));
        }
    }
    function onUserAfterDelete(Am_Event_UserAfterDelete $event) 
    {
        foreach (array('?_aff_click', '?_aff_commission', '?_aff_lead') as $table)
            $this->getDi()->db->query("DELETE FROM $table WHERE aff_id=?", $event->getUser()->user_id);
    }

    /**
     * Handle free signups
     * @todo handle free signups
     */
    function onInvoiceStarted(Am_Event_InvoiceStarted $event) 
    {
        return;//
//        if ($event->getInvoice()->first_total === 0) 
//        {
//            $this->getDi()->affCommissionRuleTable->processInvoice($event->getInvoice(), null);
//        }
    }
    
    /**
     * Handle payments
     */
    function onPaymentAfterInsert(Am_Event_PaymentAfterInsert $event)
    {
        $this->getDi()->affCommissionRuleTable->processPayment($event->getInvoice(), $event->getPayment());
    }
    
    /**
     * Handle refunds
     */
    function onRefundAfterInsert(Am_Event $event)
    {
        $this->getDi()->affCommissionRuleTable->processRefund($event->getInvoice(), $event->getRefund());
    }
    
    function onAffCommissionAfterInsert(Am_Event $event)
    {
        /* @var $commission AffCommission */
        $commission = $event->getCommission();
        if ($commission->record_type == AffCommission::VOID) return; // void
        if ($this->getConfig('mail_sale_admin'))
        {
            if ($et = Am_Mail_Template::load('aff.mail_sale_admin'))
                $et->setPayment($commission->getPayment())
                   ->setInvoice($invoice=$commission->getInvoice())
                   ->setAffiliate($commission->getAff())
                   ->setUser($invoice->getUser())
                   ->setCommission($commission->amount)
                   ->setTier($commission->tier + 1)
                   ->setProduct($this->getDi()->productTable->load($commission->product_id, false))
                   ->sendAdmin();
        }
        if ($this->getConfig('mail_sale_user'))
            if ($et = Am_Mail_Template::load('aff.mail_sale_user'))
                $et->setPayment($commission->getPayment())
                   ->setInvoice($invoice=$commission->getInvoice())
                   ->setAffiliate($commission->getAff())
                   ->setUser($invoice->getUser())
                   ->setCommission($commission->amount)
                   ->setTier($commission->tier + 1)
                   ->setProduct($this->getDi()->productTable->load($commission->product_id, false))
                   ->send($commission->getAff());
    }
    // utility functions
    function setCookie(User $aff, /* AffBanner */ $banner, $aff_click_id = null)
    {
        $tm = $this->getDi()->time + $this->getDi()->config->get('aff.cookie_lifetime', 30) * 3600*24;
        $val = $aff->pk();
        $val .= '-' . ($banner?$banner->pk():"0");
        if ($aff_click_id)
            $val .= '-' . $this->encodeClickId($aff_click_id);
        Am_Controller::setCookie(self::COOKIE_NAME, $val, $tm, '/', $_SERVER['HTTP_HOST']);
    }
    function encodeClickId($id)
    {
        // we use only part of key to don't give attacker enough results to guess key
        $key = crc32(substr($this->getDi()->app->getSiteKey(), 1, 9)) % 100000;
        return $id + $key;
    }
    function decodeClickId($id)
    {
        $key = crc32(substr($this->getDi()->app->getSiteKey(), 1, 9)) % 100000;
        return $id - $key;
    }
    /**
     * run payouts when scheduled
     */
    function onDaily(Am_Event $event)
    {
        $delay = $this->getConfig('payout_day');
        if (!$delay) return;
        list($count, $unit) = preg_split('/(\D)/', $delay, 2, PREG_SPLIT_DELIM_CAPTURE);
        switch ($unit)
        {
            case 'd': 
                if ($count != (int)date('d', amstrtotime($event->getDatetime())))
                    return;
                break;
            case 'w':
                $w = date('w', amstrtotime($event->getDatetime()));
                if ($count != $w)
                    return;
                break;
            default : return; // wtf?
        }
        $this->getDi()->affCommissionTable->runPayout(sqlDate($event->getDatetime()));
    }
    
    function onBuildDemo(Am_Event $event)
    {
        $user = $event->getUser();
        $user->is_affiliate = 1;
        $user->aff_payout_type = 'check';
        if (rand(0,10)<4)
        {
            $user->aff_id = $this->getDi()->db->selectCell("SELECT `id` 
                FROM ?_data 
                WHERE `table`='user' AND `key`='demo-id' AND `value`=?
                LIMIT ?d, 1", 
                $event->getDemoId(), rand(0, $event->getUsersCreated()));
        }
    }
    function onSavedFormTypes(Am_Event $event)
    {
        $event->getTable()->addTypeDef(array(
            'type' => 'aff',
            'class' => 'Am_Form_Signup_Aff',
            'title' => ___('Affiliate Signup Form'),
            'defaultTitle' => ___('Affiliate Signup Form'),
            'defaultComment' => '',
            'generateCode' => false,
            'urlTemplate'  => 'aff/signup',
            'isSingle' => true,
            'noDelete' => true,
        ));
    }
    
    function onLoadReports()
    {
        include_once APPLICATION_PATH . '/aff/library/Reports.php';
    }
}