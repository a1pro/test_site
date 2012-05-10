<?php

/**
 * @todo if the same db connection used, make sure it uses NEW database connection for am itself
 *
 * @method array parseExternalConfig(string $path) 
 * 
 * return array of config variables parsed from third-party script config
 *
 * Accept path to external script as argument. 
 *
 * throw Am_Exception if path is not correct or unable to parse config.
 * 
 * <b>Return example:</b> 
 * 
 * array(   'db'    =>  'MYSQL_DBNAME',
 *          'user'  =>  'MYSQL_USER',
 *          'prefix'=>  'MYSQL_PREFIX'
 *      )
 *          
 */
abstract class Am_Protect_Databased extends Am_Protect_Abstract implements Am_Protect_SingleLogin
{
    const USER_NEED_SETPASS = 'user_need_setpass';
    
    protected $_tableClass = "Am_Protect_Table";
    /** @var Am_Protect_Table */
    public $_table = null;
    /** @var DbSimple_Mysql */
    public $_db;
    /** @var IntegrationTable */
    public $_integrationTable;
    /** @var UserTable */
    public $_userTable;

    /** @var Am_Protect_SessionTable */
    public $_sessionTable;
    
    /** @var string table name without prefix */
    protected $guessTablePattern = null;
    /** @var array of several fieldnames in the table */
    protected $guessFieldsPattern = array(
    );
    protected $groupMode = self::GROUP_NONE;
    const GROUP_NONE = 0;
    const GROUP_SINGLE = 1;
    const GROUP_MULTI = 2;

    public $sqlDebug = false;
    protected $skipAfterLogin = false;
    protected $skipCheckUniqLogin = false;

    public function __construct(Am_Di $di, array $config)
    {
        parent::__construct($di, $config);
        if ($this->_tableClass === null)
            $this->_tableClass = get_class($this) . "_Table";
    }

    /** @return IntegrationTable */
    public function getIntegrationTable()
    {
        if (!$this->_integrationTable)
            $this->_integrationTable = $this->getDi()->integrationTable;
        return $this->_integrationTable;
    }

    /** @return UserTable */
    public function getUserTable()
    {
        if (!$this->_userTable)
            $this->_userTable = $this->getDi()->userTable;
        return $this->_userTable;
    }

    public function _getTableClass()
    {
        return $this->_tableClass;
    }

    function onSetupForms(Am_Event_SetupForms $event)
    {
        $f = new Am_Form_Setup_ProtectDatabased($this);
        if($plugin_readme = $this->getReadme()) 
        {   
            $plugin_readme = str_replace(
                array('%root_url%', '%root_surl%', '%root_dir%'), 
                array(ROOT_URL, ROOT_SURL, ROOT_DIR),
                $plugin_readme);
            $f->addEpilog('<div class="info"><pre>'.$plugin_readme.'</pre></div>');
        }
        $event->addForm($f);
        // addConfigItems will be called when necessary
    }

    function afterAddConfigItems(Am_Form_Setup_ProtectDatabased $form)
    {
        
    }

    function guessDbPrefix(DbSimple_Mysql $db, $database=null, $prefix=null)
    {
        $res = array();
        foreach ($dbs = $db->selectCol("SHOW DATABASES") as $dbname)
        {
            try {
                $tables = $db->selectCol("SHOW TABLES FROM ?# LIKE '%$this->guessTablePattern'", $dbname);
            } catch (Am_Exception_Db $e) {
                continue;
            }
            if (is_array($tables))
                foreach ($tables as $t)
                {
                    // check fields here
                    $info = $db->select("SHOW COLUMNS FROM `$dbname`.$t");
                    $infostr = "";
                    if (is_array($info))
                        foreach ($info as $k => $v)
                            $infostr .= join(';', $v) . "\n";
                    $wrong = 0;
                    foreach ($this->guessFieldsPattern as $pat)
                    {
                        if (!preg_match('|^' . $pat . '|m', $infostr))
                            $wrong++;
                    }
                    if ($wrong)
                        continue;
                    $res[] = $dbname . '.' . substr($t, 0, -strlen($this->guessTablePattern));
                }
        }
        return $res;
    }

    /** @return bool true if plugin is able to create customers without signup */
    public function canAutoCreate()
    {
        return false;
    }
    
    function configCheckDbSettings(array $config)
    {
        $class = get_class($this);
        $np = new $class($this->getDi(), $config);
        try
        {
            $db = $np->getDb();
        } catch (Am_Exception_PluginDb $e)
        {
            return "Cannot connect to database, check hostname,username and password settings " . $e->getMessage();
        }
        try
        {
            $table = $this->guessTablePattern;
            $fields = join(',', $this->guessFieldsPattern);
            $db->query("SELECT $fields FROM ?_{$table} LIMIT 1");
        } catch (Am_Exception_PluginDb $e)
        {
            $defaultDb = $this->getDi()->getParameter('db');
            $defaultDb = $defaultDb['mysql']['db'];
            $dbname = $config['db'] ? $config['db'] : $defaultDb;
            $prefix = $config['prefix'];
            return "Database name or prefix is wrong - could not find table [$prefix{$table}] with fields [$fields] inside database [$dbname]:  " .
            $e->getMessage();
        }
        return $np->configDbChecksAdditional($db);
    }

    function configDbChecksAdditional()
    {
        
    }

    function dbErrorHandler($message, $info)
    {
        $class = 'Am_Exception_PluginDb';
        $e = new $class("$message({$info['code']}) in query: {$info['query']}", @$info['code']);
        throw $e;
    }

    function getConfigPageId()
    {
        return get_first($this->defaultTitle, $this->getId(true));
    }

    public function isConfigured()
    {
        return $this->getConfig('db') || $this->getConfig('prefix');
    }

    function getGroupMode()
    {
        return $this->groupMode;
    }

    function getAdminGroups()
    {
        return array_filter(array_map('trim', $this->getConfig('admin_groups', array())));
    }

    function getBannedGroups()
    {
        return array_filter(array_map('trim', $this->getConfig('banned_groups', array())));
    }

    /**
     * Return plugin groups that must be set according to 
     * aMember user subscriptions and aMember configuration
     * @param User $user if null, defaul group returned
     * @return array of int third-party group ids, or int for single-group, or true/false for GROUP_NONE
     */
    function calculateGroups(User $user = null, $addDefault = false)
    {
        // we have got no user so search does not make sense, return default group if configured
        $groups = array();
        if ($user && $user->pk())
        {
            foreach ($this->getIntegrationTable()->getAllowedResources($user, $this->getId()) as $integration)
            {
                $vars = unserialize($integration->vars);
                $groups[] = $vars;
            }
            if ($this->groupMode == self::GROUP_NONE)
                return (bool) $groups;
        } else
        {
            if ($this->groupMode == self::GROUP_NONE)
                return false;
        }
        $groups = $this->chooseGroups($groups, $user);
        if ($addDefault && !$groups)
        {
            $ret = $this->getConfig('default_group', null);
            if (($this->groupMode == self::GROUP_MULTI) && (!is_array($ret)))
                $ret = array($ret);
            return $ret;
        } else
            return $groups;
    }

    /**
     *
     * @param type $groups array of configs from ?_integration table
     * @return array of int|int return sorted array or most suitable single int 
     */
    function chooseGroups($groups, User $user = null)
    {
        $ret = array();
        foreach ($groups as $config)
            $ret[] = $config['gr'];

        if ($this->groupMode == self::GROUP_SINGLE)
            return $ret ? max($ret) : null;
        else
            return $ret;
    }

    function getDb()
    {
        if (!$this->_db)
        {
            $dsn = array();
            $dsn['scheme'] = 'mysql';
            $dsn['path'] = $this->getConfig('db');
            if ($this->getConfig('other_db') == "1")
            {
                $dsn = array_merge($dsn, array(
                    'host' => $this->getConfig('host'),
                    'user' => $this->getConfig('user'),
                    'pass' => $this->getConfig('pass'),
                    ));
            } else
            {
                $appOptions = $this->getDi()->getParameters();
                $dbConfig = $appOptions['db']['mysql'];
                $dsn = array_merge($dsn, array(
                    'host' => $dbConfig['host'],
                    'user' => $dbConfig['user'],
                    'pass' => $dbConfig['pass'],
                    ));
            }
            $this->_db = Am_Db::connect($dsn, true);
            $this->_db->setErrorHandler(array($this, 'dbErrorHandler'));
            $this->_db->setIdentPrefix($this->getConfig('prefix'));
            $this->_db->query("USE ?#", $dsn['path']);
        }
        if ($this->sqlDebug)
        {
            if (!empty($this->getDi()->db->_logger))
                $this->_db->setLogger($this->getDi()->db->_logger);
        }
        return $this->_db;
    }
    function _setDb($db) { $this->_db = $db; }

    /** lazy-load the table 
     * @return Am_Protect_Table */
    function getTable()
    {
        if (!$this->_table)
            $this->_table = $this->createTable()->setDi($this->getDi());
        return $this->_table;
    }
    
    /**
     * create table
     * you can (in fact you must) override this function to fine-tune
     * @return Am_Protect_Table
     */
    function createTable()
    {
        return new $this->_tableClass($this, $this->getDb());
    }
    
    
    /**
     * create session table if applicable
     * @return Am_Protect_SingleLogin|null
     */
    function createSessionTable()
    {
        return null;
    }
    /**
     * get session table if applicable
     * @return Am_Protect_SingleLogin|null
     */
    function getSessionTable()
    {
        if(!$this->_sessionTable)
           $this->_sessionTable = $this->createSessionTable();
        if(!is_null($this->_sessionTable)) $this->_sessionTable->setDi($this->getDi());
        return $this->_sessionTable;
    }

    /**
     * @param Am_Event_SubscriptionChanged $event 
     * @param User $oldUser presents if called from onUserLoginChanged
     */
    function onSubscriptionChanged(Am_Event_SubscriptionChanged $event, User $oldUser = null)
    {
        if ($oldUser === null) $oldUser = $event->getUser();
        $user = $event->getUser();
        $found = $this->getTable()->findByAmember($oldUser);
        if ($found)
        {
            if($this->canUpdate($found)){
                $this->getTable()->updateFromAmember($found, $user, $this->calculateGroups($user, true));
                $pass = $this->findPassword($user, true);
                if ($pass) $this->getTable()->updatePassword($found, $pass);
            }
        } elseif ($groups = $this->calculateGroups($user, false))
        { // we will only insert record if it makes sense - there are groups
            $this->getTable()->insertFromAmember($user, $this->findPassword($user, true), $groups);
        }
    }
    
    function onSubscriptionUpdated(Am_Event_SubscriptionUpdated $event)
    {
        $e = new Am_Event_SubscriptionChanged($event->getUser(), array(), array());
        return $this->onSubscriptionChanged($e, $event->getOldUser());
    }

    function onSubscriptionRemoved(Am_Event_SubscriptionRemoved $event)
    {
        $found = $this->getTable()->findByAmember($event->getUser());
        if (!$found || !$this->canRemove($found))
            return;
        if ($this->getConfig('remove_users'))
        {
            $this->_table->removeRecord($found);
        } elseif (!$this->isBanned($found)) { 
            $this->_table->disableRecord($found, $this->calculateGroups(null, true));
        }
    }

    function onSetPassword(Am_Event_SetPassword $event)
    {
        $user = $event->getUser();
        $found = $this->getTable()->findByAmember($user);
        if ($found && $this->canUpdate($found))
        {
            $this->_table->updatePassword($found, $event->getSaved($this->getPasswordFormat()));
            $user->data()->set(self::USER_NEED_SETPASS, null)->update();
        }
    }

    /**
     * Return Object that will handle single login. If SessionTable is not created return $this 
     * @return Am_Protect_SingleLogin
     */
    function getSingleLoginObject(){
        return is_null($sessionTable = $this->getSessionTable()) ? $this  : $sessionTable;
    }

    function onAuthCheckLoggedIn(Am_Event_AuthCheckLoggedIn $event)
    {
        $record = $this->getSingleLoginObject()->getLoggedInRecord();
        if (!$record || !$this->canLogin($record))
            return;
        $user = $this->getTable()->findAmember($record);
        if (!$user) 
            return;
        if ($this->getTable()->checkPassword($record, $user))
        {
            $event->setSuccessAndStop($user);
            $this->skipAfterLogin = true;
        }
    }

    
    function onAuthAfterLogin(Am_Event_AuthAfterLogin $event)
    {
        if ($this->skipAfterLogin)
            return;
        $record = $this->getTable()->findByAmember($event->getUser());
        if (!$record || !$this->canLogin($record))
            return;

        // there we handled situation when user was added without knowledge of password
        // @todo implement situation when we have found there is not password
        // in related user record during login
        if ($event->getPassword() && $event->getUser()->data()->get(self::USER_NEED_SETPASS))
        {
            $evPass = new Am_Event_SetPassword($event->getUser(), $event->getPassword());
            $this->getDi()->savedPassTable->setPass($evPass);
            $evPass->run();
            $event->getUser()->data()->set(self::USER_NEED_SETPASS, null)->update();
            $record->refresh();
        }
        if (!$this->getTable()->checkPassword($record, $event->getUser(), $event->getPassword()))
            return;
        $this->getSingleLoginObject()->loginUser($record, $event->getPassword());
    }

    function onAuthAfterLogout(Am_Event_AuthAfterLogout $event)
    {
        $this->getSingleLoginObject()->logoutUser($event->getUser());
    }
    
    function onCheckUniqLogin(Am_Event_CheckUniqLogin $event)
    {
        $table = $this->getTable();
        if ($table->getIdType() != Am_Protect_Table::BY_LOGIN) return;
        if ($table->findFirstByLogin($event->getLogin()))
            $event->setFailureAndStop();
    }
    
    function onCheckUniqEmail(Am_Event_CheckUniqEmail $event)
    {
        $table = $this->getTable();
        if ($table->getIdType() != Am_Protect_Table::BY_EMAIL) return;
        if ($event->getUserId()) {
            $user = $this->getDi()->userTable->load($event->getUserId());
        } else {
            $user = $this->getDi()->userRecord;
            $user->email = $event->getEmail();
        }
        $record = $table->findByAmember($user);
        if ($record) {
            $user = $table->findAmember($record);
            if ($user && $user->pk()!=$event->getUserId())
                $event->setFailureAndStop();
        }
    }    

    public function onAuthTryLogin(Am_Event_AuthTryLogin $event)
    {
        if (!$this->getConfig('auto_create'))
            return;
        $login = $event->getLogin();
        $isEmail = preg_match('/^.+@.+\..+$/', $login);
        $found = !$isEmail ? 
            $this->getTable()->findFirstByLogin($login) : 
            $this->getTable()->findFirstByEmail($login);
        /* @var $found Am_Record */
        if (!$found || !$this->canLogin($found))
            return;
        // now create fake user for checkPassword
        $user = $this->getDi()->userTable->createRecord();
        if ($isEmail)
            $user->email = $login; else
            $user->login = $login;
        $user->toggleFrozen(true);
        if (!$this->getTable()->checkPassword($found, $user, $event->getPassword()))
            return;
        // all checked, now create user in aMember

        $user = $this->getDi()->userRecord;
        $this->getTable()->createAmember($user, $found);
        if (!$user->login)
        {
            $this->skipCheckUniqLogin = true;
            $user->generateLogin();
            $this->skipCheckUniqLogin = false;
        }
        $user->setPass($event->getPassword());
        $user->insert();
        $event->setCreated($user);
    }

    function getLoggedInRecord()
    {
        return null;
    }

    function loginUser(Am_Record $record, $password)
    {
        return false;
    }

    function logoutUser(User $user)
    {
        
    }

    /**
     * If there is a saved password, return it
     * If user has plaintext pass set, encrypt it, save and return
     * if second paramter is true, it will return dummy record
     * and mark user for password update on next login
     * @return SavedPass|null
     */
    public function findPassword(User $user, $returnNoPass = false)
    {
        // try find password
        $saved = $this->getDi()->savedPassTable->findSaved($user, $this->getPasswordFormat());
        if ($saved)
            return $saved;
        /// else encrypt it again
        $pass = $user->getPlaintextPass();
        if ($pass)
        {
            $saved = $this->getDi()->savedPassRecord;
            $saved->user_id = $user->user_id;
            $saved->format = $this->getPasswordFormat();
            $saved->salt = null;
            $saved->pass = $this->cryptPassword($pass, $saved->salt, $user);
            $saved->insert();
            return $saved;
        }
        // nothing
        if ($returnNoPass)
        {
            $pass = $this->getDi()->savedPassRecord;
            $pass->pass = '-nopass-' . uniqid();
            $pass->salt = 'NNN';
            if ($user->isLoaded())
                $user->data()->set(self::USER_NEED_SETPASS, 1)->update();
            return $pass;
        }
    }

    public function isAdmin(Am_Record $record)
    {
        if ($this->getGroupMode() == Am_Protect_Databased::GROUP_NONE)
            return false;
        return (bool)array_intersect(
            (array)$this->_table->getGroups($record),
            $this->getAdminGroups()
        );
    }
    public function isBanned(Am_Record $record)
    {
        if ($this->getGroupMode() == Am_Protect_Databased::GROUP_NONE)
            return false;
        return (bool)array_intersect(
            (array)$this->_table->getGroups($record),
            $this->getBannedGroups()
        );
    }
    /**
     * Return true if we can edit this record, and false if we can not -
     * for example if it is admin record or a banned record
     * @return bool
     */
    function canUpdate(Am_Record $record)
    {
        return !$this->isAdmin($record) && !$this->isBanned($record);
    }

    function canRemove(Am_Record $record)
    {
        return !$this->isAdmin($record);
    }

    function canLogin(Am_Record $record)
    {
        return !$this->isAdmin($record) && !$this->isBanned($record);
    }

    function deinstall()
    {
    }
    
    /**
     * @see getAvailableUserGroupsSql
     * @see getManagedUserGroups
     * @return array Am_Protect_Databased_Usergroup
     */
    function getAvailableUserGroups()
    {
        $ret = array();
        if ($this->groupMode == self::GROUP_NONE) {
            $g = new Am_Protect_Databased_Usergroup(array(
                'id'=>1,
                'title'=>'Registered',
                'isAdmin'=>0,
                'isBanned'=>0
            ));
            $ret[$g->getId()] = $g;
            return $ret;
        }
        foreach ($this->getDb()->select($this->getAvailableUserGroupsSql()) as $r)
        {
            $g = new Am_Protect_Databased_Usergroup($r);
            $ret[$g->getId()] = $g;
        }
        return $ret;
    }
    
    function getAvailableUserGroupsSql()
    {
        throw new Am_Exception_NotImplemented("getAvailableUserGroupsSql or getAvailableUserGroups must be redefined");
    }
    
    /**
     * return only list of user groups the script must manage:
     * excluding for example Banned and Admin user groups
     */
    function getManagedUserGroups()
    {
        $groups = $this->getAvailableUserGroups();
        foreach ($groups as $k => $group)
            if ($group->isAdmin() || $group->isBanned()) 
                unset($groups[$k]);
        return $groups;
    }

    public function getIntegrationFormElements(HTML_QuickForm2_Container $container)
    {
        $groups = $this->getManagedUserGroups();
        $options = array();
        foreach ($groups as $g)
            $options[$g->getId()] = $g->getTitle();
        $container
            ->addSelect('gr', array(), array('options' => $options))
            ->setLabel($this->getTitle() . ' usergroup');
    }

    public function getIntegrationSettingDescription(array $config)
    {
        $groups = array_combine((array) $config['gr'], (array) $config['gr']);
        try
        {
            foreach ($this->getAvailableUserGroups() as $g)
            {
                $id = $g->getId();
                if (!empty($groups[$id]))
                    $groups[$id] = '[' . $g->getTitle() . ']';
            }
        } catch (Am_Exception_PluginDb $e)
        {
            
        }
        return "Assign Group " . implode(",", array_values($groups));
    }

    public function onRebuild(Am_Event_Rebuild $event)
    {
        $batch = new Am_BatchProcessor(array($this, 'batchProcess'), 5);

        $context = $event->getDoneString();
        $this->_batchStoreId = 'rebuild-' . $this->getId() . '-' . Zend_Session::getId();
        if ($batch->run($context))
        {
            $event->setDone();
            $this->getDi()->store->delete($this->_batchStoreId);
        } else
        {
            $event->setDoneString($context);
        }
    }

    public function batchProcess(&$context, Am_BatchProcessor $batch)
    {
        @list($step, $start) = explode('-', $context);
        $pageCount = 30;
        switch ($step)
        {
            case 0:
                $q = new Am_Query($this->getTable());
                $count = 0;
                $updated = array();
                foreach ($q->selectPageRecords($start / $pageCount, $pageCount) as $r)
                {
                    $count++;
                    if (!$this->canUpdate($r))
                        continue;
                    /* @var $r Am_Record */
                    $user = $this->_table->findAmember($r);
                    if (!$user)
                    {
                        // no such records in aMember, disable user record ?
                        $this->_table->disableRecord($r, $this->calculateGroups(null, true));
                    } else
                    {
                        $updated[] = $user->user_id;
                        $this->getTable()->updateFromAmember($r, $user, $this->calculateGroups($user));
                        $pass = $this->getDi()->savedPassTable->findSaved($user, $this->getPasswordFormat());
                        if ($pass) $this->getTable()->updatePassword($r, $pass);
                    }
                }
                if (!$count)
                {
                    $step++;
                    $context = "$step-0";
                }else{
                    $this->getDi()->store->appendBlob($this->_batchStoreId, implode(",", $updated) . ",");
                    $start += $count;
                    $context = "$step-$start";
                }
                break;
            case 1:
                /// now select aMember users not exists in plugin db
                $q = new Am_Query(new UserTable);
                $q->addWhere("(select not find_in_set(t.user_id, s.`blob_value`) 
                              from ?_store s 
                              where s.name=?)", $this->_batchStoreId);
                $count = 0;
                $records = $q->selectPageRecords($start / $pageCount, $pageCount);
                foreach ($records as $user)
                {
                    $count++;
                    /* @var $user User */
                    $this->onSubscriptionChanged(new Am_Event_SubscriptionChanged($user, array(), array()));
                }
                if (!$count)
                {
                    $context = null;
                    return true;
                }
                $start += $count;
                $context = "$step-$start";
                break;
            default:
                throw new Am_Exception_InputError("Wrong step");
        }
    }
}

class Am_Protect_Databased_Usergroup
{

    protected $id;
    protected $title;
    protected $isAdmin = false;
    protected $isBanned = false;

    function __construct($row)
    {
        $this->id = $row['id'];
        $this->title = $row['title'];
        $this->isAdmin = (bool) @$row['is_admin'];
        $this->isBanned = (bool) @$row['is_banned'];
        if (!$this->id)
            throw new Am_Exception_PluginDb("Wrong group record passed - id is empty");
        if (empty($this->title))
            throw new Am_Exception_PluginDb("Wrong group record passed - title is empty");
    }

    function isBanned()
    {
        return $this->isBanned;
    }

    function isAdmin()
    {
        return $this->isAdmin;
    }

    function getId()
    {
        return $this->id;
    }

    function getTitle()
    {
        return $this->title;
    }

}
