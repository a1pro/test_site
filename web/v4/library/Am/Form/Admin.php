<?php

/**
 * Admin Form - adds the following:
 * - addSaveButton() method
 * - initRenderer() - special init for default renderer
 * 
 * @package Forms
 * @subpackage Admin
 */
class Am_Form_Admin extends Am_Form {
    protected $epilog = null;
    
    /** @var HTML_QuickForm2_Renderer_Default */
    protected $renderer;
    
    public function __construct($id = null, $attributes = null) {
        parent::__construct($id, $attributes);
        //$this->addCsrf();
    }

    function addSaveButton($title = null)
    {
        if ($title === null) $title = ___("Save");
        return $this->addSubmit('save', array('value'=>$title));
    }
    public function render(HTML_QuickForm2_Renderer $renderer) {
        if (method_exists($renderer->getJavascriptBuilder(), 'addValidateJs'))
            $renderer->getJavascriptBuilder()->addValidateJs('errorElement: "span"');
        return parent::render($renderer);
    }
    public function __toString()
    {
        try {
            $t = new Am_View;
            $t->form = $this;
            return $t->render('admin/_form.phtml');
        } catch (Exception $e) {
            user_error('Exception catched: ' . get_class($e) . ':' . $e->getMessage(), E_USER_ERROR);
        }
    }
    public function renderEpilog() {
        return $this->epilog;
    }
    public function addEpilog($code) {
        $this->epilog .= $code;
    }
    public function addCsrf()
    {
        $csrf = new Am_Form_Element_Csrf('_csrf');
        $csrf->setUniqId($this->getAttribute('id') ? $this->getAttribute('id') : $this->getAttribute('name'));
        $this->addElement($csrf)->setId('_csrf');
        return $csrf;
    }
}
