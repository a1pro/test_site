<?php

class Am_Grid_Editable_Newsletter extends Am_Grid_Editable_Content
{
    protected function initGridFields()
    {
        $this->addGridField('title', ___('Title'));
        $this->addGridField(new Am_Grid_Field_Enum('access', ___('Access')))
            ->translate(NewsletterList::ACCESS_RESTRICTED, ___('Restricted'))
            ->translate(NewsletterList::ACCESS_USERS, ___('All Users'))
            ->translate(NewsletterList::ACCESS_GUESTS_AND_USERS, ___('All Users and Guests'));
        $this->addGridField('subscribed_users', ___('Subscribers'))
            ->addDecorator(new Am_Grid_Field_Decorator_Link('admin-users/index?_u_search[-newsletters][val][]={list_id}'));
        parent::initGridFields();
    }

    protected function createAdapter()
    {
        $q = new Am_Query(Am_Di::getInstance()->newsletterListTable);
        $q->leftJoin('?_newsletter_user_subscription', 's', 's.list_id = t.list_id AND s.is_active > 0');
        $q->addField('COUNT(s.list_id)', 'subscribed_users');
        return $q;
    }

    function createForm()
    {
        $form = new Am_Form_Admin;
        $form->addText('title', array('size'=>80))->setLabel(___('Title'))->addRule('required');
        $form->addText('desc', array('size'=>80))->setLabel(___('Description'));
        $sel = $form->addSelect('access', array('size' => 1, 'id' => 'newsletter-access'))->setLabel(___('Access'));
        $sel->loadOptions(array(
            NewsletterList::ACCESS_RESTRICTED => ___('Restricted Access'),
            NewsletterList::ACCESS_USERS => ___('Access allowed for all Users'),
            NewsletterList::ACCESS_GUESTS_AND_USERS => ___('Access allowed for all Users and Guests'),
        ));
        
        $form->addScript()->setScript(<<<CUT
jQuery(document).ready(function($) {
    $("#newsletter-access").change(function(){
        $("select.category").closest(".row").toggle($(this).val() == 0);
    }).change();
});
CUT
            );
        
        $form->addElement(new Am_Form_Element_ResourceAccess)->setName('_access')
            ->setLabel(___('Access Permissions'))
            ->setAttribute('without_period', 'true');
            
        return $form;
    }
    
    public function renderTable()
    {
            return '<a href="'.
                Am_Controller::escape(REL_ROOT_URL).
                '/newsletter/admin-guest" target="_top">'.___('Browse Guests Subscribers').'</a><br /><br />' . parent::renderTable();
    }
}