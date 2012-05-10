<?php
/**
 * Class represents records from table folders
 * {autogenerated}
 * @property int $folder_id 
 * @property string $title 
 * @property string $desc 
 * @property string $path 
 * @property string $url 
 * @property string $method 
 * @property datetime $dattm 
 * @see Am_Table
 */

class Folder extends ResourceAbstract {
    public function getAccessType()
    {
        return ResourceAccess::FOLDER;
    }
    public function getUrl()
    {
        return $this->url;
    }
    public function getLinkTitle()
    {
        return $this->title ? $this->title : ___("Link");
    }
}

class FolderTable extends ResourceAbstractTable {
    protected $_key = 'folder_id';
    protected $_table = '?_folder';
    protected $_recordClass = 'Folder';
}
