<?php

class Bootstrap_Newsletter extends Am_Module
{
    const NEWSLETTER_SIGNUP_DATA = 'newsletter_signup_data';
    function init()
    {
        $this->getDi()->blocks->add(
            new Am_Block(array('member/main/left', 'unsubscribe'), ___("Newsletter Subscriptions"), 
                'member-main-newsletter', $this, 'member-main-newsletter.phtml', Am_Block::MIDDLE)
        );
    }
    function onInitControllerPages(Am_Event $event)
    {
        if ($event->getController() instanceof AdminContentController)
        {
            $event->getController()->addPage('Am_Grid_Editable_Newsletter', 'newsletters', 'Newsletters');
        }
    }
    function onLoadBricks()
    {
        require_once 'Am/Form/Brick/Newsletter.php';
    }
    function onUserSearchConditions(Am_Event $event)
    {
        $event->addReturn(new Am_Query_User_Condition_SubscribedToNewsletter);
    }
    function onSignupUserAdded(Am_Event $event)
    {
        $vars = $event->getVars();
        if (empty($vars['_newsletter'])) 
            return;
        $event->getUser()->data()->set(self::NEWSLETTER_SIGNUP_DATA, $vars['_newsletter'])->update();
    }
    function onSignupUserUpdated(Am_Event $event)
    {
        $this->onSignupUserAdded($event);
    }
    function onSubscriptionChanged(Am_Event_SubscriptionChanged $event)
    {
        $this->getDi()->newsletterUserSubscriptionTable->checkSubscriptions($event->getUser());
    }
    function onUserUnsubscribedChanged(Am_Event $event)
    {
        $this->getDi()->newsletterUserSubscriptionTable->checkSubscriptions($event->getUser());
    }
    function onUserForm(Am_Event_UserForm $event)
    {
        switch ($event->getAction())
        {
            case Am_Event_UserForm::INIT:
                $form = $event->getForm()->getElementById('general');
                $el = $form->addMagicSelect('_newsletter')->setLabel(___('Newsletter Subscriptions'));
                $el->loadOptions($this->getDi()->newsletterListTable->getAdminOptions());
                if ($event->getUser()->isLoaded())
                   $el->setValue($ids = $this->getDi()->newsletterUserSubscriptionTable->getSubscribedIds($event->getUser()->pk()));
                $form->addHidden('_newsletter_hidden')->setValue(1);
                break;
            case Am_Event_UserForm::VALUES_TO_FORM:
                // todo 
                if ($event->getUser()->isLoaded())
                    $event->_newsletter = $this->getDi()->newsletterUserSubscriptionTable->getSubscribedIds($event->getUser()->pk());
                else
                    $event->_newsletter = array();
                break;
            case Am_Event_UserForm::AFTER_SAVE:
                if ($event->_newsletter_hidden) // if was submitted
                {
                    $vals = $event->_newsletter;
                    $this->getDi()->newsletterUserSubscriptionTable->adminSetIds($event->getUser()->pk(), (array)$event->_newsletter);
                }
                break;
        }
    }
    function onUserAfterDelete(Am_Event_UserAfterDelete $event)
    {
        $this->getDi()->newsletterUserSubscriptionTable->deleteByUserId($event->getUser()->pk());
    }
    function onPaymentAfterInsert(Am_Event_PaymentAfterInsert $event)
    {
        $guest = $this->getDi()->newsletterGuestTable->findByEmail($event->getUser()->email);
        if ($guest) $guest->delete();        
    }
    function onRebuild(Am_Event_Rebuild $event)
    {
        $batch = new Am_BatchProcessor(array($this, 'batchProcess'), 5);
        $context = $event->getDoneString();
        $this->_batchStoreId = 'rebuild-' . $this->getId() . '-' . Zend_Session::getId();
        if ($batch->run($context))
        {
            $event->setDone();
        } else
        {
            $event->setDoneString($context);
        }
    }
    function batchProcess(& $context, Am_BatchProcessor $batch)
    {
        $db = $this->getDi()->db;
        $q = $db->queryResultOnly("SELECT * FROM ?_user WHERE user_id > ?d", (int)$context);
        $userTable = $this->getDi()->userTable;
        $newsletterUserSubscriptionTable = $this->getDi()->newsletterUserSubscriptionTable;
        while ($r = $db->fetchRow($q))
        {
            $u = $userTable->createRecord($r);
            $context = $r['user_id'];
            $newsletterUserSubscriptionTable->checkSubscriptions($u);
            if (!$batch->checkLimits()) return;
        }
        return true;
    }
    function onGetPermissionsList(Am_Event $event)
    {
        $event->addReturn(___("Manage Newsletters"), "newsletter");
    } 
}