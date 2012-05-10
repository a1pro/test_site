<?php

/**
 * @todo search for payments by current status
 * @todo search for payments by date
 */
class Am_Query_User extends Am_Query_Renderable {
    protected $template = 'admin/_user-search.phtml';
    function  __construct() {
        parent::__construct(Am_Di::getInstance()->userTable, 'u');
    }
    function initPossibleConditions(){
        if ($this->possibleConditions) return; // already initialized
        $t = new Am_View;
        $record = $this->table->createRecord();
        $baseFields = $record->getTable()->getFields();
        foreach ($baseFields as $field => $def){
            $title = ucwords(str_replace('_', ' ',$field));
            $f = new Am_Query_User_Condition_Field($field, $title, $def->type);
            $this->possibleConditions[] = $f;
        }
        $this->possibleConditions[] = new Am_Query_User_Condition_HaveSubscriptionTo(null, null, 'any-completed', "Subscribed to any of (including expired):");
        $this->possibleConditions[] = new Am_Query_User_Condition_HaveNoSubscriptionTo(null, 'none-completed', "Having no active subscription to:");
        $this->possibleConditions[] = new Am_Query_User_Condition_HaveSubscriptionTo(null, User::STATUS_ACTIVE, 'active', "Having active subscription to:");
        $this->possibleConditions[] = new Am_Query_User_Condition_HaveSubscriptionTo(null, User::STATUS_EXPIRED, 'expired', "Having expired subscription to:");
        // add payment search options
        $this->possibleConditions[] = new Am_Query_User_Condition_Filter;
        $this->possibleConditions[] = new Am_Query_User_Condition_UserId;

        $event = Am_Di::getInstance()->hook->call(Am_Event::USER_SEARCH_CONDITIONS);
        $this->possibleConditions = array_merge($this->possibleConditions, $event->getReturn());

    }

}

class Am_Query_User_Condition_Field extends Am_Query_Renderable_Condition_Field
{
    protected $fieldGroupTitle = 'User Base Fields';
    
    public function renderElement(HTML_QuickForm2_Container $form) {
        $knownSelects = array(
            'status' => array(0=>'Pending',1=>'Active','2'=>'Expired'),
            'is_affiliate' => array(0=>'Not Affiliate',1=>'Affiliate', 2=>'Only Affiliate,not a member'),
        );
        if (array_key_exists($this->field, $knownSelects)){
           $group = $this->addGroup($form);
           $group->addSelect('val')->loadOptions($knownSelects[$this->field]);
        } else
            return parent::renderElement($form);
    }
}

class Am_Query_User_Condition_HaveSubscriptionTo extends Am_Query_Condition
implements Am_Query_Renderable_Condition {
    protected $product_ids;
    protected $currentStatus = null;
    protected $alias = null;
    protected $title = null;
    protected $id;
    protected $empty = true;
    function __construct(array $product_ids=null, $currentStatus=null, $id=null, $title=null) {
        $this->product_ids = $product_ids ? $product_ids : array();
        if ($currentStatus !== null)
            $this->currentStatus = (int)$currentStatus;
        $this->id = $id;
        $this->title = $title;
    }
    function setAlias($alias = null)
    {
        $this->alias = $alias===null ? 'p'.substr(uniqid(), -4, 4) : $alias;
    }
    function getAlias(){
        if (!$this->alias)
            $this->setAlias();
        return $this->alias;
    }
    function getJoin(Am_Query $q){
        $alias = $this->getAlias();
        $ids = array_map('intval', $this->product_ids);
        $productsCond = $ids ? ' AND '.$alias.'.product_id IN (' . join(',',$ids) .')' : '';
        $statusCond = ($this->currentStatus !== null) ? " AND $alias.status=" . (int)$this->currentStatus : null;
        return "INNER JOIN ?_user_status $alias ON u.user_id=$alias.user_id{$productsCond}{$statusCond}";
    }
    /// for rendering
    public function setFromRequest(array $input) {
        $id = $this->getId();
        $this->product_ids = null;
        $this->empty = true;
        if (!empty($input[$id]['product_ids']))
        {
            $this->product_ids = array_map('intval', $input[$id]['product_ids']);
            $this->empty = false;
            return true;
        }
    }
    public function getId(){ return '-payments-'.$this->id; }
    public function renderElement(HTML_QuickForm2_Container $form) {
       $form->options['User Subscriptions Status'][$this->getId()] = $this->title;
       $group = $form->addGroup($this->getId())
           ->setLabel($this->title)
           ->setAttribute('id', $this->getId())
           ->setAttribute('class', 'searchField empty');
       $group->addSelect('product_ids', array('multiple'=>'multiple', 'size' => 5))
           ->loadOptions(Am_Di::getInstance()->productTable->getOptions());
    }
    public function isEmpty() {
        return $this->empty;
    }
    public function getDescription(){
        if ($this->currentStatus!==null) {
            if ($this->currentStatus == User::STATUS_ACTIVE) $completedCond = 'active';
            elseif ($this->currentStatus == User::STATUS_EXPIRED) $completedCond = 'expired';
            else $completedCond = 'pending';
        } else
            $completedCond = "any";
        $ids = $this->product_ids ? 'products # ' . join(',', $this->product_ids) : 'any product';
        return htmlentities("have {$completedCond} subscription to $ids");
    }
}

class Am_Query_User_Condition_HaveNoSubscriptionTo extends Am_Query_Condition
implements Am_Query_Renderable_Condition {
    protected $product_ids;
    protected $currentStatus = null;
    protected $alias = null;
    protected $title = null;
    protected $id;
    protected $empty = true;
    function __construct(array $product_ids=null, $id=null, $title=null) 
    {
        $this->product_ids = $product_ids ? $product_ids : array();
        $this->id = $id;
        $this->title = $title;
    }
    public function getId(){ return '-no-payments-'.$this->id; }
    function _getWhere(Am_Query $db)
    {
        $a = $db->getAlias();
        $ids = join(',', array_filter(array_map('intval', $this->product_ids)));
        if (!$ids) return null;
        return "NOT EXISTS 
            (SELECT * FROM ?_user_status ncmss 
            WHERE ncmss.user_id=$a.user_id AND ncmss.product_id IN ($ids) AND ncmss.status = 1)";
    }
    public function getDescription()
    {
        $ids = $this->product_ids ? 'products # ' . join(',', $this->product_ids) : 'any product';
        return htmlentities("have no active subscriptions to $ids");
    }
    public function renderElement(HTML_QuickForm2_Container $form) 
    {
       $form->options['User Subscriptions Status'][$this->getId()] = $this->title;
       $group = $form->addGroup($this->getId())
           ->setLabel($this->title)
           ->setAttribute('id', $this->getId())
           ->setAttribute('class', 'searchField empty');
       $group->addSelect('product_ids', array('multiple'=>'multiple', 'size' => 5))
           ->loadOptions(Am_Di::getInstance()->productTable->getOptions());
    }
    public function setFromRequest(array $input)
    {
        $id = $this->getId();
        $this->product_ids = null;
        $this->empty = true;
        if (!empty($input[$id]['product_ids']))
        {
            $this->product_ids = array_map('intval', $input[$id]['product_ids']);
            $this->empty = false;
            return true;
        } 
    }
    public function isEmpty()
    {
        return $this->empty;
    }
}

class Am_Query_User_Condition_Filter
extends Am_Query_Condition
implements Am_Query_Renderable_Condition
{
    protected $title = "Quick Filter";
    protected $filter;
    public function getId() {
        return 'filter';
    }
    public function isEmpty() {
        return $this->filter === null;
    }
    public function renderElement(HTML_QuickForm2_Container $form) {
       $form->options['Quick Filter'][$this->getId()] = $this->title;
       $group = $form->addGroup($this->getId())
           ->setLabel($this->title)
           ->setAttribute('id', $this->getId())
           ->setAttribute('class', 'searchField empty');
        $group->addText('val');
    }
    public function setFromRequest(array $input) {
        if (is_string($input)) {
            $this->filter = $input;
            return true;
        } elseif (@$input['filter']['val']!='') {
            $this->filter = $input['filter']['val'];
            return true;
        }
    }
    public function _getWhere(Am_Query $q){
        $a = $q->getAlias();
        $f = '%'.$this->filter.'%';
        return $q->escapeWithPlaceholders("($a.login LIKE ?) OR ($a.email LIKE ?) OR ($a.name_f LIKE ?) OR ($a.name_l LIKE ?) 
            OR ($a.user_id = (SELECT user_id FROM ?_invoice WHERE public_id=? OR invoice_id=? LIMIT 1))
            OR ($a.user_id = (SELECT user_id FROM ?_invoice_payment WHERE receipt_id=? LIMIT 1))
            ",
                $f, $f, $f, $f, $this->filter, $this->filter, $this->filter);
    ;}
    public function getDescription(){
        $f = htmlentities($this->filter);
        return "username, e-mail or name contains string [$f]";
    }
}

class Am_Query_User_Condition_UserId
extends Am_Query_Condition
implements Am_Query_Renderable_Condition
{
    protected $title = "UserId#";
    protected $ids = null;
    public function getId() {
        return 'member_id_filter';
    }
    public function isEmpty() {
        return !empty($this->ids);
    }
    public function renderElement(HTML_QuickForm2_Container $form) {
       //$form->options['Quick Filter'][$this->getId()] = $this->title;
       $group = $form->addGroup($this->getId())
           ->setLabel($this->title)
           ->setAttribute('id', $this->getId())
           ->setAttribute('class', 'searchField empty');
        $group->addText('val');
    }
    public function setIds($ids){
        if (!is_array($ids)) $ids = split(',', $ids);
        $this->ids = array_filter(array_map('intval', $ids));
    }
    public function setFromRequest(array $input) {
        if (@$input[$this->getId()]['val']!='') {
            $this->setIds($input[$this->getId()]['val']);
            return true;
        }
    }
    public function _getWhere(Am_Query $q){
        if (!$this->ids) return null;
        $a = $q->getAlias();
        $ids = join(',', $this->ids);
        return "$a.user_id IN ($ids)";
    ;}
    public function getDescription(){
        $ids = join(',', $this->ids);
        return "user_id IN ($ids)";
    }
}