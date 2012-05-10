<?php

class Am_Paysystem_Action_HtmlTemplate implements Am_Paysystem_Action
{
    protected $_template;
    public function  __construct($template) {
        $this->_template = $template;
    }
    public function process(Am_Controller $action = null)
    {
        $action->view->assign($this->getVars());
        $action->renderScript($this->_template);
    }
    function getVars()
    {
        $ret = array();
        foreach ($this as $k => $v)
            if ($k[0] != '_')
                $ret[$k] = $v;
        return $ret;
    }

    public function toXml(XmlWriter $x)
    {
        $x->startElement('template');$x->text($this->_template);$x->endElement();
        $x->startElement('params');
        foreach ($this->getVars() as $k => $v)
        {
            $x->startElement('param');
            $x->writeAttribute('name', $k);
            $x->text($v);
            $x->endElement();
        }
        $x->endElement();
    }

}