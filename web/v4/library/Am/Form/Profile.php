<?php

class Am_Form_Profile extends Am_Form implements Am_Form_Bricked 
{
    /** @var SavedForm */
    protected $savedForm;
    protected $user;
    
    public function initFromSavedForm(SavedForm $record)
    {
        foreach ($record->getBricks() as $brick)
            $brick->insertBrick($this);
        $this->addSubmit('_submit_', array('value'=>'   '.___('Save Profile').'   '));
    }
    
    public function isMultiPage()
    {
        return false;
    }

    public function getAvailableBricks()
    {
        return Am_Form_Brick::getAvailableBricks($this);
    }
    public function getRecord()
    {
        return $this->savedForm;
    }
    public function setRecord(SavedForm $record)
    {
        $this->savedForm = $record;
    }

    public function __construct()
    {
        parent::__construct('profile');
    }
    public function setUser(User $user)
    {
        $this->user = $user;
    }
    public function getDefaultBricks()
    {
        return array(
            new Am_Form_Brick_Name,
            new Am_Form_Brick_Email,
            new Am_Form_Brick_NewPassword,
        );
    }
}