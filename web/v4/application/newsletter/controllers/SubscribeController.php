<?php

class Newsletter_SubscribeController extends Am_Controller
{
    const STORE_KEY = 'newsletter-guest-';
    /**
     * display signup page
     */
    function indexAction()
    {
        $this->lists = $this->getDi()->newsletterListTable->findGuests();
        if (!$this->lists)
            throw new Am_Exception_InputError("Guest subscriptions disabled - no lists available");
        $form = $this->createForm();
        if ($form->isSubmitted() && $form->validate())
        {
            $vars = $form->getValue();
            $vars['newsletter'] = array_filter(array_map('intval', array_keys(@array_filter($vars['newsletter']))));
            return $this->doSignup($vars);
        }
        $this->view->form = $form;
        $this->view->display('newsletter/subscribe.phtml');
    }
    function createForm()
    {
        $f = new Am_Form;
        $g = $f->addGroup('newsletter')
            ->setLabel(___('Choose Newsletters'));
        $g->addRule('required', 'This field is required');
        $g->setSeparator("<br />\n");
        foreach ($this->lists as $l)
        {
            $title = $l->title;
            if ($l->desc) $title .= " - " . $l->desc;
            $el = $g->addCheckbox($l->pk())->setContent($title);
            if ($_SERVER['REQUEST_METHOD'] == 'GET')
                $el->setAttribute('checked'); // checked by default
        }
        $f->addText('name', array('size' => 40))->setLabel(___('Name'))
            ->addRule('required', 'This field is required');
        $f->addText('email', array('size' => 40))->setLabel(___('E-Mail Address'))
            ->addRule('required', 'This field is required')
            ->addRule('callback', "Please enter valid e-mail address", array('Am_Validate', 'email'))
            ->addRule('callback', "There is a user registered with this e-mail address. Please login to manage your subscriptions",
                    array($this, 'checkUserEmail'));
        $f->addSubmit('do', array('value' => ___('Subscribe')));
        return $f;
    }
    public function checkUserEmail($email)
    {
        return ! $this->getDi()->userTable->findFirstByEmail($email);
    }
    public function preDispatch()
    {
        parent::preDispatch();
        if ($this->getDi()->auth->getUserId())
            $this->_redirect('member');
    }
    public function doSignup(array $vars)
    {
        $vars['name'] = preg_replace('/[^\w\s]+/u', '', $vars['name']);
        $rand = substr(sha1(mt_rand()), 0, 10);
        $link = ROOT_SURL . "/newsletter/subscribe/confirm/k/" . $rand;
        $this->getDi()->store->setBlob(self::STORE_KEY . $rand, serialize($vars), sqlTime('+48 hours'));
        /// send confirmation e-mail
        $et = Am_Mail_Template::load('verify_guest');
        if (!$et)
            throw new Am_Exception_Configuration("No e-mail template found for [verify_guest]");
        $et
            ->setName($vars['name'])
            ->setLink($link)
            ->setCode($rand);
        $et->send($vars['email']);
        $this->_redirect('newsletter/subscribe/confirm');
    }
    
    public function tryConfirm($k)
    {
        $x = $this->getDi()->store->getBlob(self::STORE_KEY . $k);
        if (!$x) return;
        $vars = unserialize($x);
        if (empty($vars['email'])) return;
        list($name_f, $name_l) = explode(' ', $vars['name'], 2);
        // allowed ids
        $allowed = array();
        foreach ($this->getDi()->newsletterListTable->findGuests() as $l)
            $allowed[] = $l->pk();
        $ids = array_intersect($vars['newsletter'], $allowed);
        if (!$ids) return;
        $guest = $this->getDi()->newsletterGuestTable->create($vars['email'], $name_f, $name_l, $ids);
        $this->getDi()->store->delete(self::STORE_KEY . $k);
        return true;
    }
    
    public function confirmAction()
    {
        if ($k = $this->getFiltered('k'))
        {
            if ($this->tryConfirm($k))
                return $this->_redirect('newsletter/subscribe/confirmed');
            else
                $this->view->error = ___("Wrong code entered or confirmation link expired");
        }
        $this->view->display('newsletter/confirm.phtml');
    }
    public function confirmedAction()
    {
        $this->view->display('newsletter/confirmed.phtml');
    }
}