<?php

class Newsletter_AdminGuestController extends Am_Controller_Grid
{
    protected $lists = array();
    
    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission('newsletter');
    }
    public function createGrid()
    {
        $ds = new Am_Query($this->getDi()->newsletterGuestTable);
        $g = new Am_Grid_Editable('_guest', ___("Newsletter Guest Subscribers"), $ds, $this->_request,
            $this->view);
        $g->setPermissionId('newsletter');
        $g->setForm(array($this, 'createForm'));
        $g->setFilter(new Am_Grid_Filter_Text(___('Filter by e-mail or name'), array('name_f'=>'LIKE', 'name_l'=>'LIKE', 'email'=>'LIKE')));
        $g->addGridField('name_f', ___('First Name'));
        $g->addGridField('name_l', ___('Last name'));
        $g->addGridField('email', ___('E-Mail'));
        $g->addGridField('subscriptions', ___('Subscriptions'))->setGetFunction(array($this, 'getGuestSubscriptions'));
        $g->setFormValueCallback('_s', array('RECORD', 'getLists'), array('RECORD', 'setLists'));
        return $g;
    }
    public function createForm()
    {
        $f = new Am_Form_Admin;
        $g = $f->addGroup()->setLabel('Name');
        $g->addText('name_f', array('size'=>40));
        $g->addText('name_l', array('size'=>40));
        $f->addText('email', array('size'=>40))->setLabel('E-Mail Address')
            ->addRule('required')
            ->addRule('callback', ___("Please enter valid e-mail address"), array('Am_Validate', 'email'));
        $f->addMagicSelect('_s')->setLabel('Lists')->loadOptions($this->lists);
        return $f;
    }
    function getGuestSubscriptions(NewsletterGuest $g)
    {
        $ret = array();
        foreach ($g->getLists() as $list_id)
        {
            $s = @$this->lists[ $list_id ];
            if (strlen($s) > 20) $s = substr($s, 0, 20) . '..';
            $ret[] = $s;
        }
        return join(", ", $ret);
    }
    public function preDispatch()
    {
        parent::preDispatch();
        $this->lists = $this->getDi()->newsletterListTable->getAdminOptions();
    }
}