<?php

class Am_Form_Setup_Helpdesk extends Am_Form_Setup
{
    function __construct()
    {
        parent::__construct('helpdesk');
        $this->setTitle(___('Helpdesk'));
    }
    function initElements()
    {
         $this->addElement('textarea', 'helpdesk.intro', array('style'=>"width:90%"))
                 ->setLabel(___('Intro Text on Helpdesk Page'));
         $this->setDefault('helpdesk.intro', 'We answer customer tickets Mon-Fri, 10am - 5pm EST. You can also call us by phone if you have an urgent question.');
         
          $this->addElement('email_checkbox', 'helpdesk.notify_new_message')
                 ->setLabel(___("Send Notification about New Messages to Customer\n". 
                     "aMember will email a notification to user\n".
                     "each time admin responds to a user ticket")); 
          $this->addElement('email_checkbox', 'helpdesk.notify_new_message_admin')
                 ->setLabel(___("Send Notification about New Messages to Admin\n". 
                     "aMember will email a notification to admin\n".
                     "each time user responds to a ticket")); 
          $this->setDefault('helpdesk.notify_new_message', 1);
          $this->setDefault('helpdesk.notify_new_message_admin', 1);
    }
}
