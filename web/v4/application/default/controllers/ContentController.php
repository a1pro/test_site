<?php

class ContentController extends Am_Controller
{
    
    /**
     * Serve file download
     */
    function fAction()
    {
        $f = $this->loadWithAccessCheck($this->getDi()->fileTable, $id = $this->getInt('id'));

        if (!$f->isExists())
            throw new Am_Exception_InternalError("File [$id] was not found on disk. Path [$f->path]");

        @ini_set('zlib.output_compression', 'Off'); // for IE

        $this->_helper->sendFile($f->getFullPath(), $f->getType(), 
            array(
                //'cache'=>array('max-age'=>3600),
                'filename' => $f->getName(),
        ));
    }
    
    /**
     * Display saved page
     */
    function pAction()
    {
        $p = $this->loadWithAccessCheck($this->getDi()->pageTable, $this->getInt('id'));
        if ($p->use_layout)
        {
            $this->view->content = '<div class="am-content-page">' . $p->html . '</div>';
            $this->view->title = $p->title;
            $this->view->display('layout.phtml');
        } else 
            echo $p->html;
    }
    function loadWithAccessCheck(ResourceAbstractTable $table, $id)
    {
        $id = $this->getInt('id');
        if ($id<=0)
            throw new Am_Exception_InputError(___("Wrong link - no id passed"));
        
        if (!$this->getDi()->auth->getUserId())
            $this->_redirect('login?amember_redirect_url=' . $this->getFullUrl());
        
        $p = $table->load($id);
        if (!$p->hasAccess($this->getDi()->user))
            $this->_redirect('no-access/content/'.sprintf('?id=%d&type=%s', 
                $id, $table->getName(true)));
        
        return $p;
    }
    
}
