<?php

class Am_Upload
{
    protected $storeFolder;
    protected $prefix;
    protected $tempSeconds;
    // array of Am_Upload_File
    protected $files = array();
    private $_di;

    public function __construct(Am_Di $di, $prefix="tmp") {
        $this->_di = $di;
        $this->setStoreFolder(DATA_DIR);
        $this->setPrefix($prefix);
    }
    
    /** @return Am_Di */
    public function getDi()
    {
        return $this->_di;
    }

    public function loadFromStored()
    {
        $this->files = Am_Di::getInstance()->uploadTable->findByPrefix($this->prefix);
    }
    /**
     * Store submitted files to storeFolder
     */
    function processSubmit($fieldName){
        if (empty($_FILES[$fieldName])) return false;
        if (is_array($_FILES[$fieldName]['tmp_name']))
            $keys = array_keys($_FILES[$fieldName]['tmp_name']);
        else
            $keys = array(null);
        foreach ($keys as $k){
            $upload[$k] = $this->getDi()->uploadRecord;
            $upload[$k]->prefix = $this->prefix;
            $upload[$k]->admin_id = Am_Di::getInstance()->authAdmin->getUserId();
            $upload[$k]->setFrom_FILES($_FILES[$fieldName], $k);
        }
        foreach ($upload as $k => $file) {
            if ($f = $this->checkFileAndMove($file)) {
                $this->files[] = $f;
            }
        }
        return $this;
    }

    function checkFileAndMove(Upload $file)
    {
        if (!$file->name) return false; // no file uploaded
        $nm = htmlentities($file->getName());
        if (!$file->getSize()) {
            if ($nm)
                trigger_error("Uploaded file [$nm] has zero size", E_USER_WARNING);
        } elseif ($error=$file->_error) {
            trigger_error("File upload error reported for [$nm] : $error", E_USER_WARNING);
        } else { // ok file
            $file->moveUploaded($this->tempSeconds);
            return $file;
        }

        return false;
    }

    function processReSubmit($fieldName, $upload){
        if (empty($_FILES[$fieldName])) return false;
        if (is_array($_FILES[$fieldName]['tmp_name'])) return false;

        $upload->admin_id = Am_Di::getInstance()->authAdmin->getUserId();
        $upload->setFrom_FILES($_FILES[$fieldName]);
        $this->checkFileAndMove($upload);

        return $this;
    }


    function removeFiles(){
        foreach ($this->files as $file)
            unlink($file->getFullPath());
    }

    function setPrefix($prefix){
        $this->prefix = $prefix;
        return $this;
    }

    function setStoreFolder($storeFolder){
        $this->storeFolder = $storeFolder;
        if (!is_dir($this->storeFolder))
            throw new Am_Exception_InternalError("Store folder [$this->storeFolder] does not exists");
        if (!is_writeable($this->storeFolder))
            throw new Am_Exception_InternalError("Store folder [$this->storeFolder] is not writeable");
        return $this;
    }

    function getStoreFolder(){
        return $this->storeFolder;
    }
    /**
     * If you assign value to this, each uploaded file will have additional prefix
     * in end of filename equal to time() when it shall be deleted
     * @see UploadTable->cleanUp() for this
     * @param int $seconds
     */
    function setTemp($seconds){
        $this->tempSeconds = (int)$seconds;
        return $this;
    }
    
    function addUpload(Upload $u)
    {
        $this->files[] = $u;
    }
    function getUploads(){
        return $this->files;
    }
    function serialize(){
        $ret = array();
        foreach ($this->files as $f)
            $ret[] = $f->upload_id;
        return join(',', $ret);
    }
    function unserialize($string){
        $this->files = Am_Di::getInstance()->uploadTable->findByIds(explode(',', $string), $this->prefix);
    }
    function processDelete(array $deleteFilenames){
        if (!$deleteFilenames) return;
        foreach ($this->files as $k => $f)
            if (in_array($f->getFilename(), $deleteFilenames)) {
                $f->delete();
                unset($this->files[$k]);
            }
    }
    function delete($fn){
        return true;
    }
}