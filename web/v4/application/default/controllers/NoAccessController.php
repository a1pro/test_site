<?php

class NoAccessController extends Am_Controller
{
    /**
     * Use the following params from request
     * id, title
     */
    function liteAction() {
        $this->view->accessObjectTitle = $this->getParam('title', ___('protected area'));
        $this->view->orderUrl = REL_ROOT_URL . '/signup';
        $this->view->display('no-access.phtml');
    }
    
    function folderAction()
    {
        $id = $this->_request->getInt('id');
        if (!$id) throw new Am_Exception_InputError("Empty folder#");
        $folder = $this->getDi()->folderTable->load($id);
        if(empty($folder)) throw new Am_Exception_InputError("Folder not found");
        $this->view->accessObjectTitle = ___("Folder %s (%s)", $folder->title, $folder->url);
        $this->view->orderUrl = REL_ROOT_URL . '/signup';
        $this->view->display('no-access.phtml');
    }
    function contentAction()
    {
        $id = $this->_request->getInt('id');
        if (!$id) throw new Am_Exception_InputError("Empty folder#");
        $this->view->accessObjectTitle = ___("Protected Content #%d", $id);
        $this->view->orderUrl = REL_ROOT_URL . '/signup';
        $this->view->display('no-access.phtml');
    }
}