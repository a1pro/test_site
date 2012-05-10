<?php

class Am_Navigation_UserTabs extends Zend_Navigation
{
    public function addDefaultPages()
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $id = $request->getInt('id', $request->getInt('user_id'));
        if (!$id && $request->getInt('_u_id'))
            $id = $request->getInt('_u_id');
        if (!$id && $request->getParam('_u_a') == 'insert')
            $id = 'insert';
        if (!$id) throw new Am_Exception_InputError("Could not find out [id]");
        
        $userUrl = REL_ROOT_URL . '/admin-users?';
        if ($action = $request->getFiltered('_u_a', 'edit'))
            $userUrl .= "_u_a=$action&";
        if ($a = $request->getFiltered('_u_id', $id))
            $userUrl .= "_u_id=$a";
        
        $this
           ->addPage(array(
                'id' => 'users',
                'uri' => $userUrl,
                'label' => ___('User Info'),
                'order' => 0,
                'disabled' => $id <= 0,
                'active' => $request->getFiltered('_u_id', false)
          ))->addPage(array(
                'id' => 'payments',
                'label' => ___('Payments'),
                'controller' => 'admin-user-payments',
                'params' => array(
                    'user_id' => $id,
                ),
                'order' => 100,
                'resource' => 'grid_payment',
          ))->addPage(array(
                'id' => 'access-log',
                'label' => ___('Access Log'),
                'controller' => 'admin-users',
                'action' => 'access-log',
                'params' => array(
                    'user_id' => $id,
                ),
                'order' => 200,
                'resource' => Am_Auth_Admin::PERM_LOGS,
          ));
        $event = new Am_Event_UserTabs($this, $id<=0, (int)$id);
        $event->run();

        /// workaround against using the current route for generating urls
        foreach (new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST) as $child)
        {
            if ($child instanceof Zend_Navigation_Page_Mvc && $child->getRoute()===null)
                $child->setRoute('default');
            if ($id<=0) $child->set('disabled', true);
        }
    }
    public function setActive($id)
    {
        foreach($this->getPages() as $page) {
            $page->setActive($page->getId() == $id);
        }
    }
}