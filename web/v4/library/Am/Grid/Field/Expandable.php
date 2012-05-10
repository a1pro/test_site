<?php

class Am_Grid_Field_Expandable extends Am_Grid_Field
{
    protected $maxLength = 15;
    protected $placeholder = "Click to Expand";
    protected $isHtml = false;
    
    public function __construct($field, $title, $sortable = false, $align = null, $renderFunc = null, $width = null)
    {
        $this->setRenderFunction(array($this, 'renderExpandable'));
        $this->setGetFunction(array($this, 'expandableGet'));
        parent::__construct($field, $title, $sortable, $align, $renderFunc, $width);
    }

    public function setMaxLength($maxLength)
    {
        $this->maxLength = (int) $maxLength;
        return $this;
    }
    
    public function renderExpandable($obj, $field, $controller)
    {
        $val = $this->get($obj, $controller, $field);
        $isHtml = $this->isHtml;
        $out = '';
        if (strlen($val) <= $this->maxLength)
        {
            return $this->_defaultRender($obj, $field, $controller);
        } else
        {
            $align_class = $this->align ? ' align_' . $this->align : null;
            $placeholder = (is_null($this->placeholder)) ?
                htmlentities(substr($val, 0, $this->maxLength), null, 'UTF-8') . '...' :
                $this->placeholder;

            $out .= '<td class="expandable';
            $out .= $align_class;
            $out .= '" title="{$this->placeholder}">';
            $out .= '<div class="arrow"></div>';
            $out .= '<div class="placeholder">';
            $out .= $controller->escape($placeholder);
            $out .= '</div>'.PHP_EOL;
            $out .= '<input type="hidden" class="data';
            $out .= ( $isHtml ? ' isHtml' : '');
            $out .= '" value="' . $controller->escape($val) . '">'.PHP_EOL;
            $out .= '</td>';
        }
        return $out;
    }
    
    public function expandableGet($obj, $controller, $field)
    {
        $val = $obj->{$field};
        if (!$val) return $val;
        // try unserialize
        if (is_string($val))
        {
            if (($x = @unserialize($val)) !== false)
                $val = $x;
        }
        switch (true)
        {
            case is_array($val) : 
                $out = '';
                foreach ($val as $k => $v)
                    $out .= $k . ' = ' . ((is_array($v)) ? print_r($v, true) : (string) $v) . PHP_EOL;
                return $out;
            case is_object($val) : 
                return get_class($val) . "\n" . (string) $obj;
        }
        return $val;
    }
    public function setEscape($flag = null)
    {
        $ret = ! $this->isHtml;
        if ($flag !== null) $this->isHtml = ! $flag;
        return $ret;
    }
    public function renderStatic()
    {
        $url = htmlentities(REL_ROOT_URL . "/application/default/views/public/js/htmlreg.js", ENT_QUOTES, 'UTF-8');
        return parent::renderStatic() . 
        '    <script type="text/javascript" src="'.$url.'"></script>' . PHP_EOL;
    }
}
