<?php

/**
 * Class represents records from table newsletter_list
 * {autogenerated}
 * @property int $list_id 
 * @property string $title 
 * @property string $desc 
 * @property int $disabled 
 * @property int $auto_subscribe 
 * @property int $access 
 * @see Am_Table
 */
class NewsletterList extends ResourceAbstract 
{
    const NEWSLETTER_LIST = 'newsletterlist';

    /** access only by ResourceAccess */
    const ACCESS_RESTRICTED = 0; 
    /** allows free users access */
    const ACCESS_USERS = 2;
    /** allows access for both users and guests */
    const ACCESS_GUESTS_AND_USERS = 3;
    
    public function getAccessType()
    {
        return self::NEWSLETTER_LIST;
    }
}

class NewsletterListTable extends ResourceAbstractTable
{
    protected $_key = 'list_id';
    protected $_table = '?_newsletter_list';
    
    function getAdminOptions()
    {
        return $this->_db->selectCol("SELECT list_id AS ARRAY_KEY, title FROM $this->_table");
    }
    function getUserOptions()
    {
        return $this->_db->selectCol("SELECT list_id AS ARRAY_KEY, title FROM $this->_table");
    }
    
    function getAllowed(User $user)
    {
        $ids = array(-99); // to avoid empty array errors
        foreach ($this->getDi()->resourceAccessTable->selectAllowedResources($user, NewsletterList::NEWSLETTER_LIST) as $r)
            $ids[] = $r['resource_id'];
        return $this->selectObjects("SELECT * FROM $this->_table WHERE list_id IN (?a) OR access IN (?a)", $ids, array(
            NewsletterList::ACCESS_GUESTS_AND_USERS, NewsletterList::ACCESS_USERS
        ));
    }
    function findGuests()
    {
        return $this->findBy(array('access' => NewsletterList::ACCESS_GUESTS_AND_USERS));
    }
}