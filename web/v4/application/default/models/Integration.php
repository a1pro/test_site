<?php
/**
 * Class represents records from table integrations
 * {autogenerated}
 * @property int $integration_id 
 * @property string $comment 
 * @property string $plugin 
 * @property string $vars 
 * @see Am_Table
 */
class Integration extends ResourceAbstract {
    public function getAccessType()
    {
        return ResourceAccess::INTEGRATION;
    }
    public function getLinkTitle()
    {
        return null;
    }
}

class IntegrationTable extends ResourceAbstractTable {
    protected $_key = 'integration_id';
    protected $_table = '?_integration';
    protected $_recordClass = 'Integration';

    /** @return array of Integration */
    public function getAllowedResources(User $user, $pluginId)
    {
        $ret = array();
        foreach ($this->getDi()->resourceAccessTable
                    ->getAllowedResources($user, ResourceAccess::INTEGRATION) as $r)
            if ($r->plugin == $pluginId) $ret[] = $r;
        return $ret;
    }
}
