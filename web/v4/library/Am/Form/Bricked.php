<?php

/**
 * Class containing Am_Form_Brick objects from saved config
 * used for signup and user profile forms
 */

interface Am_Form_Bricked
{
    /** @return array Am_Form_Brick */
    public function getDefaultBricks();
    public function getAvailableBricks();
    public function isMultiPage();
}


//class Am_Form_Bricked extends Am_Form
//{
//    /** @var SavedForm */
//    protected $record;
//
//    static function getDefaultBricks()
//    {
//        throw new Am_Exception_InternalError("Must be overriden");
//    }
//    function load(SavedForm $record)
//    {
//        $this->record = $record;
//        class_exists('Am_Form_Brick', true);
//        $this->loadBricks();
//        $this->addSubmit('_submit_', array('value'=>'   '.___('Continue').'   '));
//    }
//    function loadBricks()
//    {
//        foreach ($pages as $page)
//        {
//            $this->addPage($page);
//            foreach ($page['config'] as $row)
//            {
//                $id = $row['id'];
//                $config = empty($row['config']) ? array() : $row['config'];
//                $brick = Am_Form_Brick::createBrick($id, $config);
//                $brick->insertBrick($this);
//            }
//        }
//    }
//    function getAvailableBricks()
//    {
//        return Am_Form_Brick_Abstract::getAvailableBricks($this);
//    }
//
//}