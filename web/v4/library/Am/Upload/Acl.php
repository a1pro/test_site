<?php

class Am_Upload_Acl {
    //read-own read write list
    const ACCESS_READ_OWN = 0x8;
    const ACCESS_READ = 0x4;
    const ACCESS_WRITE = 0x2;
    const ACCESS_LIST = 0x1;
    const ACCESS_ALL = 0x7;
    const ACCESS_NONE = 0x0;
    
    const IDENTITY_TYPE_ADMIN = 'admin';
    const IDENTITY_TYPE_USER = 'user';
    const IDENTITY_TYPE_AFFILIATE = 'affiliate';
    const IDENTITY_TYPE_ANONYMOUS = 'anonymous';
    
    /*
     * Array of prefixes with Access Control
     * it should be fill in by hook Am_Event::GET_UPLOAD_PREFIX_LIST
     * <example>
     * 'emailtemplates' => array(
     *     UploadTable::IDENTITY_TYPE_ADMIN => array(
     *        'setup' => UploadTable::ACCESS_ALL
     *     )
     * ),
     * 'public' => array(
     *     UploadTable::IDENTITY_TYPE_ADMIN => UploadTable::ACCESS_ALL,
     *     UploadTable::IDENTITY_TYPE_USER => UploadTable::ACCESS_READ | UploadTable::ACCESS_LIST,
     *     UploadTable::IDENTITY_TYPE_AFFILIATE => UploadTable::ACCESS_ALL,
     *     UploadTable::IDENTITY_TYPE_ANONYMOUS => UploadTable::ACCESS_READ | UploadTable::ACCESS_LIST
     * ),
     * 'helpdesk' => array(
     *     UploadTable::IDENTITY_TYPE_USER => UploadTable::ACCESS_WRITE | UploadTable::ACCESS_READ_OWN,
     *     UploadTable::IDENTITY_TYPE_ADMIN => array(
     *          'helpdesk' => UploadTable::ACCESS_ALL
     *     )
     * )
     * </example>
     * 
     * 
     */
    protected $_prefix_list = null;
    
    /**
     *
     * @param Upload|string $uploadOrPrefix
     * @param int $access access constant
     * @param null|Admin|User $identity
     * @return type 
     */
    public function checkPermission($uploadOrPrefix, $access, $identity) {
        if ($uploadOrPrefix instanceof Upload) 
        {
            $prefix = $uploadOrPrefix->getPrefix();
            $isOwner = $this->isOwner($uploadOrPrefix, $identity);
        } 
        else 
        {
            $prefix = $uploadOrPrefix;
            $isOwner = false;
        }
        
        $perm = $this->preparePermission($this->getPermission($identity, $prefix), $isOwner);
        return (bool)($perm & $access);
    }
    
    /**
     * Retrieve array of all defined prefixes 
     * @return array 
     */
    static public function getPrefixList() {
        return array_keys($this->getPrefixACList());
    }

    protected function getPermission($identity, $prefix) {
        if ($identity instanceof Admin) {
            return $this->getAdminPermission($identity, $prefix);
        } elseif ($identity instanceof User) {
            return $this->getUserPermission($identity, $prefix);
        } else {
            return $this->getAnonymousPermission($identity, $prefix);
        }
    }
    
    protected function getAdminPermission(Admin $identity, $prefix) {
        if ($identity->isSuper()) return self::ACCESS_ALL;
        
        $prefixList = $this->getPrefixACList();    
        $prefixAC = $prefixList[$prefix];
        
        $perm = self::ACCESS_NONE;
        if (isset($prefixAC[self::IDENTITY_TYPE_ADMIN])) {
            if (is_array($prefixAC[self::IDENTITY_TYPE_ADMIN])) {
                foreach ($prefixAC[self::IDENTITY_TYPE_ADMIN] as $globPerm => $uploadPerm) {
                    if ($identity->hasPermission($globPerm)) {
                        $perm |= $uploadPerm; 
                    }
                }
            } else {
                $perm = $prefixAC[self::IDENTITY_TYPE_ADMIN];
            }
        }
        
        return $perm;
    }
    
    protected function getUserPermission(User $identity, $prefix) {
        $prefixList = $this->getPrefixACList();    
        $prefixAC = $prefixList[$prefix];
        
        $involvedIdentityTypes = array(self::IDENTITY_TYPE_USER);
        if ($identity->is_affiliate) {
            $involvedIdentityTypes[] = self::IDENTITY_TYPE_AFFILIATE;
        }
        
        $perm = self::ACCESS_NONE;
        foreach ($prefixAC as $identityType => $uploadPerm) {
            if (in_array($identityType, $involvedIdentityTypes)) {
                $perm |= $uploadPerm; 
            }
        }
        
        return $perm;       
    }
    
    protected function getAnonymousPermission($identity, $prefix) {
        $prefixList = $this->getPrefixACList();    
        $prefixAC = $prefixList[$prefix];
        return isset($prefixAC[self::IDENTITY_TYPE_ANONYMOUS]) ? 
                $prefixAC[self::IDENTITY_TYPE_ANONYMOUS] : 
                self::ACCESS_NONE;     
    }
    
    protected function isOwner(Upload $upload, $identity) {
        if ($identity instanceof Admin) {
            return $identity->pk() == $upload->admin_id;
        } elseif ($identity instanceof User) {
            return $identity->pk() == $upload->user_id;
        } else {
            return false;
        }
    }
    
    protected function preparePermission($perm, $isOwner) {
        //set read byte in case of user is owner of upload and has permission ACCESS_READ_OWN
        //truncate up to 3 byte
        return ($perm | (($isOwner && ($perm & self::ACCESS_READ_OWN)) ? self::ACCESS_READ : self::ACCESS_NONE)) & ~0x8;
    } 
  
    /**
     * Retrieve Access Control Rules for all defined prefixes
     * 
     * @return array
     */
    protected function getPrefixACList(){
        if (is_null($this->_prefix_list))
        {
            $this->_prefix_list = self::getDefaultPrefixList();
            $event = Am_Di::getInstance()->hook->call(Am_Event::GET_UPLOAD_PREFIX_LIST);
            foreach ($event->getReturn() as $k => $v)
                $this->_prefix_list[$k] = $v;
        }
        return $this->_prefix_list;
    }
    
    static protected function getDefaultPrefixList() {
        return array(
            EmailTemplate::ATTACHMENT_FILE_PREFIX => array(
                self::IDENTITY_TYPE_ADMIN => array(
                    Am_Auth_Admin::PERM_SETUP => self::ACCESS_ALL
                )
            ),
            'import' => array(
                self::IDENTITY_TYPE_ADMIN => array(
                    Am_Auth_Admin::PERM_IMPORT => self::ACCESS_WRITE | self::ACCESS_READ_OWN 
                )
            )
        );
    }  
}
