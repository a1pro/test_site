<?php

/** Class represents a resource record with */
abstract class ResourceAbstract extends Am_Record
{
    /** @return string for exampe: 'folder', 'file', 'page' */
    abstract function getAccessType();
    public function delete()
    {
        parent::delete();
        $this->clearAccess();
    }
    public function clearAccess()
    {
        if (!$this->pk()) return;
        return $this->getDi()->resourceAccessTable->deleteBy(
            array('resource_type' => $this->getAccessType(), 
                  'resource_id' => $this->pk())
        );
    }
    public function getAccessList()
    {
        if (!$this->pk()) return array();
        return $this->getDi()->resourceAccessTable->findBy(
            array('resource_type' => $this->getAccessType(), 
                  'resource_id' => $this->pk())
        );
    }
    /**
     * Add a resource access record
     * @param int $itemId product# or category# or -1
     * @param string $startString 1d or 3m or 0d - for zero autoresponder
     * @param string $stopString
     * @param bool $isProduct is a product or category
     * @return ResourceAccess 
     */
    public function addAccessListItem($itemId, $startString, $stopString, $fn)
    {
        if (!$this->pk())
            throw new Am_Exception_InternalError("empty PK - could not execute " . __METHOD__);
        
        $fa = $this->getDi()->resourceAccessRecord;
        $fa->resource_type = $this->getAccessType();
        $fa->resource_id = $this->pk();
        
        $fa->fn = $fn;
        $fa->id = $itemId;
        $fa->start_days = null;
        $fa->stop_days = null;
        if (preg_match('/^(-?\d+)(\w+)$/', strtolower($startString), $regs))
        {
            $fa->start_days = $regs[1];
        }
        if (preg_match('/^(-?\d+)(\w+)$/', strtolower($stopString), $regs))
        {
            $fa->stop_days = $regs[1];
        }
        $fa->insert();
        return $fa;
    }
    /** Has the folder items in access list with not-default start/stop */
    public function hasCustomStartStop()
    {
        foreach ($this->getAccessList() as $access)
            if ($access->hasCustomStartStop()) return true;
    }
    public function hasAnyProducts()
    {
        foreach ($this->getAccessList() as $access)
            if ($access->isAnyProducts()) return true;
    }
    public function hasCategories()
    {
        foreach ($this->getAccessList() as $access)
            if ($access->getClass() != 'product') return true;
    }
    
    public function getUrl()
    {
        return null;
    }
    public function getLinkTitle()
    {
        return $this->title;
    }
    public function renderLink()
    {
        if (!empty($this->hide))
            return;
        $url = $this->getUrl();
        $title = $this->getLinkTitle();
        if (empty($title)) 
            return;
        if ($url)
        {
            return sprintf('<a href="%s">%s</a>', Am_Controller::escape($url), $title);
        } else {
            return $title;
        }
    }

    function hasAccess(User $user)
    {
        return $this->getDi()->resourceAccessTable->userHasAccess($user, $this->pk(), $this->getAccessType());
    }
    
    /**
     * 
     * @return array of int or ResourceAccess::ANY_PRODUCT
     */
    function findMatchingProductIds()
    {
        $ret = $this->getTable()->getAdapter()->selectCol("
            SELECT DISTINCT ?
            FROM 
                ?_resource_access ra 
            WHERE ra.fn = 'product_category_id' AND ra.resource_id=?d AND ra.id = ?
            
            UNION 

            SELECT DISTINCT `id`
            FROM ?_resource_access ra
            WHERE ra.fn = 'product_id' AND ra.resource_id=?d
            
            UNION 
            
            SELECT DISTINCT ppc.product_id 
            FROM 
                ?_product_product_category ppc 
                LEFT JOIN 
                ?_resource_access ra ON ppc.product_category_id = ra.id
            WHERE ra.fn = 'product_category_id' AND ra.resource_id=?d
        ", 
            ResourceAccess::ANY_PRODUCT, $this->pk(), ResourceAccess::ANY_PRODUCT, 
            $this->pk(), 
            $this->pk());
        
        if ($ret && ($ret[0] == ResourceAccess::ANY_PRODUCT)) return ResourceAccess::ANY_PRODUCT;
        return $ret;
    }
}

abstract class ResourceAbstractTable extends Am_Table
{
}