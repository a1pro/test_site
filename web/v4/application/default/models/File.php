<?php
/**
 * Class represents records from table files
 * "path" field may contain numeric id - from the uploads table
 * {autogenerated}
 * @property int $file_id 
 * @property string $title 
 * @property string $desc 
 * @property string $path 
 * @property string $mime 
 * @property int $size 
 * @property string $display_type 
 * @property datetime $dattm 
 * @see Am_Table
 */
class File extends ResourceAbstract {
    /** @var Upload */
    private $_upload;
    public function getAccessType()
    {
        return ResourceAccess::FILE;
    }
    function getDisplayFilename()
    {
        return ($this->path>0 && $u=$this->getUpload()) ?
            $u->getName() :
            $this->path;
    }
    function getFullPath()
    {
        return ($this->path>0 && $u=$this->getUpload()) ?
            $u->getFullPath() :
            $this->path;
    }
    /** @return Upload|null */
    function getUpload()
    {
        if ($this->_upload && $this->_upload->upload_id == $this->path)
            return $this->_upload;
        return ($this->path > 0) ? $this->_upload = $this->getDi()->uploadTable->load($this->path) : null;
    }
    public function getUrl()
    {
        return REL_ROOT_URL . "/content/f/id/" . $this->file_id. '/';
    }
    function isExists()
    {
        return file_exists($this->getFullPath());
    }
    function getType()
    {
        return $this->mime ? $this->mime : 'application/octet-stream';
    }
    function getSize()
    {
        return filesize($this->getFullPath());
    }
    function getName()
    {
        $upload = $this->getUpload();
        return $upload ? $upload->getName() : $this->title;
    }
    /**
     * Read file starting from $start bytes to $stop bytes
     * or completely by default and output it to standart
     * output
     * @param int|null $start
     * @param int|null $stop
     */
    function readFile($start = null, $stop = null)
    {
        if (!$start && !$stop)
        {
            readfile($this->getFullPath());
            return;
        } else {
            $done = $start;
            if ($file = fopen($this->getFullPath(), 'r'))
            {
                /// ???? $done = $start; // ????
                fseek($file, $start);
                while(!feof($file) && !connection_aborted() &&($done<$stop))
                {
                    echo $buffer = fread($file, 1048576);
                    flush();
                    $done += strlen($buffer);
                }
                fclose($file);
            } else
                throw new Am_Exception_InternalError("Could not open file [path=".$this->getFullPath());
        } 
    }
}

class FileTable extends ResourceAbstractTable {
    protected $_key = 'file_id';
    protected $_table = '?_file';
    protected $_recordClass = 'File';
}