<?php

abstract class Am_Grid_Action_Abstract
{
    const NORECORD = 'norecord';
    const SINGLE = 'single';
    const GROUP  = 'group';
    const HIDDEN = 'hidden';
    
    protected $type = self::SINGLE;
    protected $id, $title;
    protected $attributes = array();
    /** @var Am_Grid_Editable */
    protected $grid;
    protected $isAvailableCallback = null;
    /** permission (default null====grid.grid_id), and priviledge to request */
    protected $permission=null, $privilege = 'edit';
    
    public function __construct($id = null, $title = null)
    {
        if ($id !== null) $this->id = $id;
        if ($title !== null) $this->title = $title;
    }
    
    function getType()
    {
        return $this->type;
    }
    function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    function getId()
    {
        if ($this->id === null)
        {
            $a = explode('_', get_class($this));
            $this->id = fromCamelCase(array_pop($a), '-');
        } 
        return $this->id;
    }
    function getUrl($record = null, $id = null)
    {
        return $this->grid->getActionUrl($this, $id);
    }
    function getTitle() {
        if (!$this->title)
            $this->title = ucfirst($this->getId());
        return $this->title;
    }
    function setTitle($title) {
        $this->title = $title;
        return $this;
    }
    function getAttributes()
    {
        return $this->attributes;
    }
    function setAttribute($k, $v)
    {
        $this->attributes[$k] = $v;
        return $this;
    }
    function setTarget($target)
    {
        $this->attributes['target'] = (string)$target;
        return $this;
    }
    function getTarget()
    {
        return empty($this->attributes['target']) ? null : $this->attributes['target'];
    }
    /**
     * @param type $record 
     * @return bool
     */
    public function isAvailable($record)
    {
        if ($this->isAvailableCallback)
            return (bool)call_user_func($this->isAvailableCallback, $record);
        return true;
    }
    
    public function setIsAvailableCallback($callback)
    {
        $this->isAvailableCallback = $callback;
    }
    /**
     * This function will be called before @link run()
     */
    function setGrid(Am_Grid_Editable $grid)
    {
        $this->grid = $grid;
    }
    
    abstract function run();
    public function renderTitle()
    {
        if ($this->getType() != self::GROUP)
            $title = $this->grid->renderTitle();
        else
            $title = ___($this->getTitle());
        return "<h1>" . $title . '</h1>' . PHP_EOL;
    }
    public function renderBackUrl()
    {
        $url = $this->grid->getBackUrl();
        return sprintf('<a href="%s"%s>%s</a>',
            $this->grid->escape($url),
            $this->getTarget() ? (' target="'.$this->getTarget().'"') : '',
            ___("Return")
            );
    }
    public function getRecordId()
    {
        return $this->grid->getRecordId();
    }
    /**
     * This function can be problematic as it cleans up all variables
     * but the known ones
     */
    public function redirectSelf()
    {
        $url = $this->grid->makeUrl(array(
            Am_Grid_Editable::ACTION_KEY => $this->getId(),
            Am_Grid_Editable::ID_KEY => $this->grid->getRequest()->get(Am_Grid_Editable::ID_KEY),
            Am_Grid_Editable::BACK_KEY => $this->grid->getRequest()->get(Am_Grid_Editable::BACK_KEY),
            Am_Grid_Editable::GROUP_ID_KEY => $this->grid->getRequest()->get(Am_Grid_Editable::GROUP_ID_KEY),
        ), false);
        $this->grid->redirect($url);
    }
    
    protected function _runFormAction($action)
    {
        if ($this->grid->doFormActions($action))
        {
            return true;
        } else {
            echo $this->renderTitle();
            echo $this->grid->getForm();
        }
        return false;
    }

    
    protected function getConfirmationText()
    {
        return ___("Do you really want to %s?", 
            $this->grid->getRecordTitle($this->getTitle()));
    }
    
    public function renderConfirmation()
    {
        $message = $this->getConfirmationText();
        
        $form = $this->renderConfirmationForm();
        $back = $this->renderBackButton(___('No, cancel'));
        return <<<CUT
<div class="group-action-confirm">
<p>$message</p>
<div class="buttons">
$form $back
</div>
</div>
CUT;
    }
    public function renderBackButton($text)
    {
        $url_no  = $this->grid->escape($this->grid->getBackUrl());
        $target = $this->getTarget();
        $returnCode = !$target ? 
            "data-url='$url_no' data-target='$target'" :
            "onclick='window.location=".$this->grid->escape(json_encode($this->grid->getBackUrl()))."'";
        return sprintf('<input type="button" value="%s" %s />'.PHP_EOL,
            htmlentities($text, ENT_QUOTES, 'UTF-8'),
            $returnCode);
    }
    public function renderConfirmationForm($btn=null, $page = null, $addHtml = null)
    {
        if (empty($btn))
            $btn = ___("Yes, continue");
        $vars = $this->grid->getCompleteRequest()->toArray();
        $vars[$this->grid->getId() . '_confirm'] = 'yes';
        if ($page !== null)
            $vars[$this->grid->getId() . '_group_page'] = (int)$page;
        $hidden = Am_Controller::renderArrayAsInputHiddens($vars);
        $btn = $this->grid->escape($btn);
        $url_yes = $this->grid->makeUrl(null);
        return <<<CUT
<form method="post" action="$url_yes" style="margin: 10px; display: inline;">
    $hidden
    $addHtml
    <input type="submit" value="$btn" />
</form>
CUT;
    }
    public function checkPermissions()
    {
        if (!$this->hasPermissions())
            $this->grid->throwPermission($this->permission, $this->privilege);
    }
    public function hasPermissions()
    {
        return $this->grid->hasPermission($this->permission, $this->privilege);
    }
    public function log($message = null, $tablename = null, $record_id = null)
    {
        if ($message === null)
            $message = $this->grid->getRecordTitle($this->getTitle());
        if ($tablename === null)
            $tablename = 'grid'.$this->grid->getId();
        if ($record_id === null)
            try {
                $record_id = $this->grid->getRecordId();
            } catch (Exception $e ){
            }
        if (!defined('AM_ADMIN') || !AM_ADMIN) return;
        $this->grid->getDi()->adminLogTable->log($message, $tablename, $record_id);
    }
}