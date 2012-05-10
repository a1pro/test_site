<?php

class Am_Query implements Am_Grid_DataSource_Interface_Editable
{
    /** @var DbSimple_Mysql */
    protected $db;
    protected $conditions = array();
    protected $order = array();
    protected $start;
    protected $count;
    protected $fields = array('*');
    protected $joins = array();
    protected $having = array();
    protected $where = array();
    protected $unions = array();
    protected $groupBy = array();
    protected $foundRows;
    /** @var Am_Table */
    protected $table;
    protected $alias;
    protected $tableName;
    protected $keyField;
    function  __construct(Am_Table $table, $alias = 't') {
        $this->db = $table->getAdapter();
        $this->table = $table;
        $this->alias = $alias;
        $this->tableName = $table->getName();
        $this->keyField = $table->getKeyField();
    }
    function setFromRequest($vars){
        return;
    }
    /** @return PDOStatement */
    function query($start=null, $count=null){
        $sql = $this->getSql($start, $count);
        $sql = preg_replace('/^SELECT /i', 'SELECT SQL_CALC_FOUND_ROWS ', $sql);
        $ret = $this->db->queryResultOnly($sql);
        $this->foundRows = $this->db->selectCell("SELECT FOUND_ROWS()");
        return $ret;
    }
    function getFoundRows()
    {
        if ($this->foundRows === null)
            $this->query(0,1);
        return (int)$this->foundRows;
    }
    function getSql($start=null, $count=null){
        $fields = "";
        foreach ($this->fields as $f){
            if (is_string($f))
                $f = array($f, null);
            if (preg_match('/^[a-zA-Z0-9*]+$/', $f[0]))
                $f[0] = $this->alias . '.' . $f[0];
            $fields .= $f[0];
            if ($f[1] !== null)
                $fields .= " AS " . $this->db->escape($f[1], true);
            $fields .= ",";
        }
        $fields = trim($fields, ',');

        $joins = array();
        foreach ($this->joins as $join) {
            if (empty($join[4])) // add condition
            {
                $key = $this->keyField;
                $join[4] = sprintf('%s.%s=%s.%s',
                    $this->alias, $key,
                    $join[2], $key);
            }
            $joins[] = join(' ', $join);
        }
        $ret = "SELECT {$fields} FROM {$this->tableName} {$this->alias}";
        $where = $this->where;
        $having = $this->having;
        foreach ($this->conditions as $c)
        {
            if ($j = $c->getJoin($this))    $joins[]  = $j;
            if ($w = $c->getWhere($this))   $where[] = '('.$w.')';
            if ($h = $c->getHaving($this))  $having[] = '('.$h.')';
        }
        if ($joins) $ret .= " " . join(' ', $joins);
        if ($where) $ret .= " WHERE " . join(' AND ', $where);
        if ($joins && !$this->groupBy)
        {
            $this->addAutoGroupBy();
            $ret .= $this->getGroupBy();
        } elseif ($this->groupBy) {
            $ret .= $this->getGroupBy();
        }
        if ($having) $ret .= " HAVING " . join(' AND ', $having);
        if ($this->unions)
        {
            $ret  = "($ret)";
            foreach ($this->unions as $q) {
                $ret .= " UNION (" . $q->getSql() . ")";
            }
        }
        $ret .= $this->getSqlOrder();
        if ($count > 0) {
            $count = (int)$count;
            $start = (int)$start;
            $ret .= " LIMIT {$start},{$count}";
        }
        return $ret;
    }
    protected function getSqlOrder(){
        if (!$this->order) return "";
        $ret = " ORDER BY ";
        foreach ($this->order as $o)
        {
            if ($o[1] == 'RAW')
            {
                $ret .= $o[0];
            } else {
                $ret .= $this->db->escape($o[0],true);
                if ($o[1] == 'DESC') $ret .= " DESC";
            }
            $ret .= ",";
        }
        return trim($ret, ',');
    }
    /**
     * Select records on given "page"
     * @param int $pageNum page# to display, starting from 0
     * @param int $count records per page
     * @return array of Am_Record
     */
    function selectPageRecords($pageNum, $count){
        if ($count <= 0)
            throw new Am_Exception_InternalError("count could not be empty in " . __METHOD__);
        $q = $this->query($pageNum * $count, $count);
        $ret = array();
        while ($row = $this->db->fetchRow($q))
            $ret[] = $this->table->createRecord($row);
        return $ret;
    }
    function selectRows($pageNum, $count)
    {
        if ($count <= 0)
            throw new Am_Exception_InternalError("count could not be empty in " . __METHOD__);
        $q = $this->query($pageNum * $count, $count);
        $ret = array();
        while ($row = $this->db->fetchRow($q))
            $ret[] = $row;
        return $ret;
    }
    function add(Am_Query_Condition $c) {
        $this->conditions[] = $c;
        return $c;
    }
    function getConditions(){
        return $this->conditions;
    }
    function clearConditions(){
        $this->conditions = array();
    }
    ///// order /////
    function clearOrder(){
        $this->order = array();
        return $this;
    }
    function addOrder($field, $desc=false) {
        $this->order[] = array($field, $desc ? 'DESC' : null);
        return $this;
    }
    function addOrderRaw($orderExpr) {
        $this->order[] = array($orderExpr, 'RAW');
        return $this;
    }
    function setOrder($field, $dir=null) {
        $this->clearOrder();
        return $this->addOrder($field, $dir);
    }
    function setOrderRaw($string){
        $this->clearOrder();
        $this->addOrderRaw($string);
        return $this;
    }
    /////////////////// fields /////////////////////
    function addField($expr, $alias=null){
        $this->fields[] = array($expr, $alias);
        return $this;
    }
    function clearFields(){
        $this->fields = array();
        return $this;
    }
    ///////////////// where /////////////////////
    function clearWhere(){
        $this->where = array();
        return $this;
    }
    function addWhere($expr, $_=null){
        $this->where[] = $this->db->expandPlaceholders(func_get_args());
        return $this;
    }
    ///////////////// joins /////////////////////
    function clearJoins(){
        $this->joins = array();
        return $this;
    }
    function leftJoin($table, $alias, $onCondition = null) {
        $this->joins[] = array('LEFT JOIN', $table, $alias, 'ON', $onCondition);
        return $this;
    }
    function innerJoin($table, $alias, $onCondition = null) {
        $this->joins[] = array('INNER JOIN', $table, $alias, 'ON', $onCondition);
        return $this;
    }
    function fullOuterJoin($table, $alias, $onCondition = null) {
        $this->joins[] = array('FULL OUTER JOIN', $table, $alias, 'ON', $onCondition);
        return $this;
    }
    function crossJoin($table, $alias)
    {
        $this->joins[] = array('CROSS JOIN', $table, $alias, null, " ");
        return $this;
    }
    ///////////////// unions ////////////////////
    function addUnion(Am_Query $q){
        $this->unions[] = $q;
    }
    function clearUnions(){
        $this->unions = array();
    }
    /////////////// groups ////////////////////////
    function clearGroupBy() {
        $this->groupBy = array();
        return $this;
    }

    public function addAutoGroupBy()
    {
        $this->groupBy($this->keyField, null, true);
    }

    function groupBy($field, $tableAlias=null, $auto = false) {
        $this->groupBy[] = array('fieldName' => $field, 'tableAlias' => $tableAlias, 'auto' => (bool)$auto);
        return $this;
    }

    function getGroupBy() {
        if (!count($this->groupBy)) return '';

        $ret = array();
        foreach ($this->groupBy as $g) {
            if ($g['tableAlias'] === '')
                $a = '';
            else
                $a = ($g['tableAlias'] ? $g['tableAlias'] : $this->alias) . '.';
            $ret[] = $a . $g['fieldName'];
        }
        return " GROUP BY " . implode(',', $ret);
    }
    ///
    //
    //
    function clearHaving()
    {
        $this->having = array();
        return $this;
    }
    function addHaving($expr, $_=null){
        $queryAndArgs = func_get_args();
        if (count($queryAndArgs )>1)
            $this->db->_expandPlaceholders($queryAndArgs);
        $this->having[] = $queryAndArgs[0];
        return $this;
        
    }
    /**
     * Proxies this request to @see $db
     */
    function escape($s, $isIndent=false){
        return $this->db->escape($s, $isIndent);
    }
    function escapeWithPlaceholders($s, $_){
        $args = func_get_args();
        return call_user_func_array(array($this->db,'escapeWithPlaceholders'), $args);
    }
    /** @return Am_Table */
    function getTable()
    {
        return $this->table;
    }
    function getTableName(){
        return $this->tableName;
    }
    function getAlias(){
        return $this->alias;
    }
    public function getDataSourceQuery()
    {
        return $this;
    }

    public function createRecord()
    {
        return $this->getTable()->createRecord();
    }

    public function deleteRecord($id, $record)
    {
        $record->delete();
    }

    public function getIdForRecord($record)
    {
        return $record->pk();
    }

    public function getRecord($id)
    {
        return $this->getTable()->load($id);
    }

    public function insertRecord($record, $valuesFromForm)
    {
        $record->setForInsert($valuesFromForm)->insert();
    }

    public function updateRecord($record, $valuesFromForm)
    {
        $record->setForUpdate($valuesFromForm)->update();
    }
    
    public function exportXml(XMLWriter $xml, $options = array())
    {
        $xml->startElement('table_data');
        $xml->writeAttribute('name', $this->getTable()->getName(true));
        $q = $this->query();
        while ($row = $this->db->fetchRow($q))
        {
            $this->getTable()->createRecord($row)
                ->exportXml($xml, $options);
        }
        $xml->endElement();
    }
}
abstract class Am_Query_Condition {
    protected $_or = array();
    
    /** @return string to be included into query WHERE part (without brackets), must be escaped using $db */
    function getJoin(Am_Query $db){}
    final function getWhere(Am_Query $db){
        $cond = array($this->_getWhere($db));
        foreach ($this->_or as $or)
            $cond[] = $or->getWhere($db);
        if (count($cond) == 1) return $cond[0];
        $cond = array_filter($cond, 'strlen');
        foreach ($cond as $k=>$v) $cond[$k] = "($v)";
        return join(' OR ', $cond);
    }
    /**
     * This function can be overriden
     * @return string escaped where string
     */
    function _getWhere(Am_Query $db) {}
    function getHaving(Am_Query $db){}
    function _or(Am_Query_Condition $cond) {
        $this->_or[] = $cond;
        return $this;
    }
}

class Am_Query_Condition_Field extends Am_Query_Condition {
    static protected $validOperations = array('<','<>','=','>','<=','>=','<=>','IS NULL', 'IS NOT NULL', 'LIKE', 'NOT LIKE');
    protected $field;
    protected $op;
    protected $value;
    protected $tableAlias;
    /**
     * Construct a query object
     * @param string $field
     * @param string $op any valid SQL operator from the list above
     * @param string $value to compare with
     */
    function  __construct($field, $op, $value=null, $tableAlias = null) {
        $this->field = $field;
        $this->tableAlias = $tableAlias;
        $this->init($op, $value);
    }
    protected function init($op, $value){
        if (!in_array($op, self::$validOperations))
            throw new Am_Exception_InternalError("Invalid operator provided: " . htmlentities($op) . " in ".__METHOD__);
        $this->op = $op;
        $this->value = $value;
    }
    function _getWhere(Am_Query $q) {
        $ret = $this->getAlias($q) . '.' . $q->escape($this->field, true) . ' ' . $this->op ;
        if ($this->op != 'IS NULL' && $this->op != 'IS NOT NULL')
            $ret .= ' ' . $q->escape($this->value);
        return $ret;
    }

    private function getAlias(Am_Query $q) {
        return (is_null($this->tableAlias) ? $q->getAlias() : $this->tableAlias);
    }
}
