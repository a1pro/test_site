<?php

/**
 * Use proxy to do not auto-load Am_Form
 * until first request received
 */
abstract class Am_Form_SetupProxy
{
    protected $class = 'Am_Form_Setup';

    protected $pageId;
    protected $title;
    protected $comment;
    /** @var Am_Form_Setup */
    protected $imp;
    public function __construct($id)
    {
        $this->setPageId($id);
    }
    public function setPageId($id)
    {
        $this->pageId = $id;
        return $this;
    }
    public function getPageId()
    {
        return $this->pageId;
    }
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    public function getTitle()
    {
        return $this->title ? $this->title : $this->pageId;
    }
    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }
    public function getComment()
    {
        return $this->comment;
    }
    /** @return Am_Form_Setup
     */
    protected function createImp(){
        $class = $this->getClass();
        $o = new $class($this->pageId);
        $o->setTitle($this->title);
        $o->setComment($this->comment);
        return $o;
    }
    protected function getClass()
    {
        return $this->class;
    }
    /** You can override this method to do your own elements initialization */
    public function prepare()
    {
        return $this->getImp()->prepare();
    }
    /** @return Am_Form_Setup */
    public function getImp()
    {
        if (!$this->imp)
            $this->imp = $this->createImp();
        return $this->imp;
    }
    public function __call($name,  $arguments)
    {
        $imp = $this->getImp();
        if (method_exists($imp, $name))
            return call_user_func_array(array($imp, $name), $arguments);
        else
            return $imp->__call($name, $arguments);
    }
}