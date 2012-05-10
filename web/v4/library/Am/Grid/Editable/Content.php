<?php

abstract class Am_Grid_Editable_Content extends Am_Grid_Editable
{
    public function __construct(Am_Request $request, Am_View $view)
    {
        $id = explode('_', get_class($this));
        $id = strtolower(array_pop($id));
        parent::__construct('_'.$id, ___(ucfirst($id)), $this->createAdapter(), $request, $view);
        
        $this->addCallback(self::CB_AFTER_INSERT, array($this, 'afterInsert'));
        $this->addCallback(self::CB_AFTER_UPDATE, array($this, 'afterInsert'));
        
        $this->addCallback(self::CB_VALUES_TO_FORM, array($this, '_valuesToForm'));
        foreach ($this->getActions() as $action)
            $action->setTarget('_top');
    }
    
    function renderAccessTitle(ResourceAbstract $r)
    {
        $title = Am_Controller::escape($r->title);
        if (!empty($r->hide))
            $title = "<span class='disabled-text'>$title</span>";
        return $this->renderTd($title, false);
    }
    
    public function getPermissionId()
    {
        return 'grid_content';
    }
    
    protected function initGridFields()
    {
        $this->addGridField('_access', ___('Products'), false)->setRenderFunction(array($this, 'renderProducts'));
        $this->addGridField('_link', ___('Link'), false)->setRenderFunction(array($this, 'renderLink'));
        parent::initGridFields();
    }

    public function renderLink(ResourceAbstract $resource)
    {
        $html = "";
        $url = $resource->getUrl();
        if (!empty($url)) 
            $html = sprintf('<a href="%s" target="_blank">%s</a>', 
                Am_Controller::escape($url), ___('link'));
        return $this->renderTd($html, false);
    }
    public function renderProducts(ResourceAbstract $resource)
    {
        $access_list = $resource->getAccessList();
        if (count($access_list) > 6)
            $s = ___('%d access records...', count($access_list));
        else
        {
            $s = "";
            foreach ($access_list as $access)
            {
                $l = "";
                if ($access->getStart())
                    $l .= " from " . $access->getStart();
                if ($access->getStop())
                    $l .= " to " . $access->getStop();
                $s .= sprintf("%s <b>%s</b> %s<br />\n", $access->getClassTitle(), $access->getTitle(), $l);
            }
        }
        return $this->renderTd($s, false);
    }

    public function afterInsert(array & $values, ResourceAbstract $record)
    {
        $record->clearAccess();
        if (!empty($values['_access']['free'])) {
            $record->addAccessListItem(0, null, null, ResourceAccess::FN_FREE);
        } else {
            if (!empty($values['_access']['product_category_id']))
                foreach ($values['_access']['product_category_id'] as $cat_id => $params)
                {
                    if (!is_array($params))
                        $params = json_decode($params, true);
                    $record->addAccessListItem($cat_id, $params['start'], $params['stop'], ResourceAccess::FN_CATEGORY);
                }
            if (!empty($values['_access']['product_id']))
                foreach ($values['_access']['product_id'] as $product_id => $params)
                {
                    if (!is_array($params))
                        $params = json_decode($params, true);
                    $record->addAccessListItem($product_id, $params['start'], $params['stop'], ResourceAccess::FN_PRODUCT);
                }
        }
    }

    public function _valuesToForm(array & $values)
    {
        $values['_access'] = $this->getRecord()->getAccessList();
    }
}

