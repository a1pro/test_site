<?php

class Newsletter_UnsubscribeController extends Am_Controller
{
    function guestAction()
    {
        return $this->indexAction();
    }
    /**
     * display signup page
     */
    function indexAction()
    {
        $e = $this->getParam('e');
        if (!$e)
            throw new Am_Exception_InputError("Empty e-mail parameter passed - wrong url");
        
        $s = $this->getFiltered('s');
        if (!Am_Mail::validateUnsubscribeLink($e, $s, Am_Mail::LINK_GUEST))
            throw new Am_Exception_InputError(___('Wrongly signed URL, please contact site admin'));

        $this->view->guest = $this->getDi()->newsletterGuestTable->findFirstByEmail($e);
        if (!$this->view->guest)
            throw new Am_Exception_InputError(___("Your e-mail address was not found in database"));

        $this->view->e = $e;
        $this->view->s = $s;
        
        $this->lists = $this->getDi()->newsletterListTable->findGuests();
        if (!$this->lists)
            throw new Am_Exception_InputError("Guest subscriptions disabled - no lists available");

        $this->view->form = $this->createForm();
        
        if ($this->view->form->isSubmitted() && $this->view->form->validate())
        {
            $this->changeSubscriptions($this->view->guest, $this->view->form->getValue());
            $this->_redirect('index');
        }

        $this->view->display('newsletter/unsubscribe.phtml');
    }
    function changeSubscriptions(NewsletterGuest $guest, array $vars)
    {
        if (!empty($vars['unsubscribe']))
            $vars['newsletter'] = array();
        $ids = array_filter(array_map('intval', array_keys(@array_filter($vars['newsletter']))));
        if (!$ids)
            $guest->delete();
        else
            $guest->setLists($ids);
    }
    function createForm()
    {
        $f = new Am_Form;
        $f->addHidden('s')->setValue($this->getFiltered('s'));
        $f->addHidden('e')->setValue($this->getParam('e'));
        $g = $f->addGroup('newsletter', array('id' => 'newsletter-group'))
            ->setLabel(___('Untick checkbox to cancel subscription'));
        $g->setSeparator("<br />\n");
        $ids = $this->view->guest->getLists();
        foreach ($this->lists as $l)
        {
            $title = $l->title;
            if ($l->desc) $title .= " - " . $l->desc;
            $el = $g->addCheckbox($l->pk())->setContent($title);
            if (in_array($l->list_id, $ids) && !$this->_request->isPost())
                $el->setAttribute('checked'); // checked by default
        }
        if (count($this->lists) > 1)
        {
            $f->addCheckbox('unsubscribe')->setLabel(___('Cancel all Subscriptions'));
            $f->addScript()->setScript(<<<CUT
jQuery(document).ready(function($) {
    $("input#unsubscribe-0").change(function(){
        $("#row-newsletter-group").toggle(!this.checked);
    });
});
CUT
            );
        }
        $f->addSubmit('do', array('value' => ___('Change Subscription')));
        return $f;
    }
}