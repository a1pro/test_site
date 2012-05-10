<?php

/**
 * Class represents records from table admins
 * {autogenerated}
 * @property int $admin_id 
 * @property string $login 
 * @property string $name_f 
 * @property string $name_l 
 * @property string $pass 
 * @property datetime $last_login 
 * @property string $last_ip 
 * @property string $last_session 
 * @property string $email 
 * @property int $super_user 
 * @property string $perms 
 * @property int $reseller_id 
 * @see Am_Table
 */
class Admin extends Am_Record
{

    public function getName()
    {
        return $this->name_f . ' ' . $this->name_l;
    }
    public function checkPassword($pass)
    {
        $ph = new PasswordHash(8, true);
        return $pass && $ph->CheckPassword($pass, $this->pass);
    }

    public function setPass($pass)
    {
        $ph = new PasswordHash(12, true);
        $this->pass = $ph->HashPassword($pass);
    }

    public function hasPermission($perm, $priv = null)
    {
        if ($this->isSuper()) return true;
        if (empty($this->perms)) return false;
        $perms = $this->getPermissions();
        if (empty($perms[$perm]))
            return false;
        if ($priv === null) 
            return true;
        if (is_array($perms[$perm]) && empty($perms[$perm][$priv]))
            return false;
        return true;
    }
    
    public function checkPermission($perm, $priv = null)
    {
        if (!$this->hasPermission($perm, $priv))
            throw new Am_Exception_AccessDenied(___("You have no permissions to perform requested operation"));
    }
    
    public function isAllowed($role = null, $resource = null, $privilege = null)
    {
        return $this->hasPermission($resource, $privilege);
    }
    
    public function setPermissions(array $perms)
    {
        $this->perms = json_encode($perms);
        $this->_perms = null;
    }
    
    public function getPermissions()
    {
        return json_decode($this->perms, true);
    }

    public function isSuper()
    {
        return (bool) $this->super_user;
    }
}

class AdminTable extends Am_Table
{

    protected $_key = 'admin_id';
    protected $_table = '?_admin';
    protected $_recordClass = 'Admin';

    function getAuthenticatedRow($login, $pass, & $code)
    {
        if (empty($login) || empty($pass))
        {
            $code = Am_Auth_Result::INVALID_INPUT;
            return;
        }
        $u = $this->findFirstByLogin($login);
        if (!$u)
        {
            $code = Am_Auth_Result::USER_NOT_FOUND;
            return;
        }
        if (!$u->checkPassword($pass))
        {
            $code = Am_Auth_Result::WRONG_CREDENTIALS;
            return;
        }
        $code = Am_Auth_Result::SUCCESS;
        return $u;
    }

}
