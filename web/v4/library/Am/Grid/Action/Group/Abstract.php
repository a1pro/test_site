<?php

abstract class Am_Grid_Action_Group_Abstract extends Am_Grid_Action_Abstract
{
    protected $type = self::GROUP;
    
    const ALL = '[ALL]';
    
    protected $needConfirmation = true;
    protected $confirmationText;
    
    protected $batchCount = 20; // how many records to select and process between checkLimits() 
    protected $isDelete = false; // if that is delete operation, we must start any iteration from first page!
    
    public function __construct($id = null, $title = null)
    {
        $this->confirmationText = ___("Do you really want to %s %s %s records");
        parent::__construct($id, $title);
    }
    /**
     * @return array int 
     */
    public function getIds()
    {
        $ids = $this->grid->getRequest()->get(Am_Grid_Editable::GROUP_ID_KEY);
        $ids = explode(",", $ids);
        if (in_array(self::ALL, $ids)) return array(self::ALL);
        $ids = array_filter(array_map('intval', $ids));
        return $ids;
    }
    
    public function getConfirmationText()
    {
        return ___($this->confirmationText, 
                $this->getTitle(),
                $this->getTextCount(),
                $this->grid->getRecordTitle()
            ) . '?';
    }
    public function getDoneText(){
        return ___("DONE").". ";
    }
    public function getTextCount()
    {
        $ids = $this->getIds();
        if (in_array(self::ALL, $ids)) return $this->grid->getDataSource()->getFoundRows();
        return count($ids);
    }
    public function run()
    {
        // we do not accept GET requests by security reasons. so nobody can say give a link that deletes all users
        if ($this->needConfirmation && (!$this->grid->getRequest()->isPost() || !$this->grid->getRequest()->get('confirm')))
        {
            echo $this->renderConfirmation();
        } else {
            echo $this->renderRun($this->getIds());
        }
    }
    public function renderRun($ids)
    {
        echo ___("Running %s", $this->getTitle()) . '...';
        $this->doRun($ids);
    }
    public function renderDone()
    {
        return $this->getDoneText() . 
            sprintf('<a href="%s">%s</a>', 
                $this->grid->escape($this->grid->getBackUrl()), 
                ___("Return"));
        
    }
    public function doRun(array $ids)
    {
        if ($ids[0] == self::ALL)
            $this->handleAll();
        else {
            $ds = $this->grid->getDataSource();
            foreach ($ids as $id) {
                $record = $ds->getRecord($id);
                if (!$record) trigger_error ("Cannot load record [$id]", E_USER_WARNING);
                $this->handleRecord($id, $record);
            }
            echo $this->renderDone();
        }
        $this->log();
    }
    public function _handleAll(& $page, Am_BatchProcessor $batch)
    {
        $ds = $this->grid->getDataSource();
        $page = (int)$page;
        do {
            $done = 0;
            foreach ($ds->selectPageRecords($page++, $this->batchCount) as $record)
            {
                $id = $ds->getIdForRecord($record);
                $this->handleRecord($id, $record);
                $done++;
            }
            if (!$batch->checkLimits()) return ;
        } while ($done > 0);
        if ($done == 0) return true; // no more records
    }
    public function handleAll()
    {
        $batch = new Am_BatchProcessor(array($this, '_handleAll'), 10);
        $page = $this->grid->getRequest()->getInt('group_page');
        if ($this->isDelete) $page = 0;
        if ($batch->run($page))
        {
            echo $this->renderDone();
        } else {
            echo ($page*$this->batchCount)." records processed.";
            echo $this->renderConfirmationForm(___("Continue"), $page);
        }
    }
    abstract public function handleRecord($id, $record);
    public function log($message = null, $tablename = null, $record_id = null)
    {
        if ($record_id === null)
        {
            $ids = $this->getIds();
            if (empty($ids)) return;
            if ($ids[0] == self::ALL) 
                $record_id = 'several records';
            else
                $record_id = implode(',', $ids);
        }
        parent::log($message, $tablename, $record_id);
    }
}