<?php

class Am_Lite
{
    const PAID = 'paid';
    const FREE = 'free';
    const ANY = 'any';
    const ONLY_LOGIN = 'only_login';
    const ACTIVE = 'active';
    const EXPIRED = 'expired';

    const SESSION_NAME = 'PHPSESSID';
    protected static $_instance = null;
    protected $_db_config = null;
    protected $_db = null;
    protected $_session = null;

    protected function __construct()
    {
        
    }

    /**
     *
     * @return Am_Lite
     */
    static public function getInstance()
    {
        if (is_null(self::$_instance))
        {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public function isLoggedIn()
    {
        return $this->hasIdentity();
    }

    public function getUsername()
    {
        return $this->getUserField('login');
    }

    public function getName()
    {
        if ($this->hasIdentity())
        {
            return sprintf("%s %s", $this->getUserField('name_f'), $this->getUserField('name_l')
            );
        }
        else
        {
            return null;
        }
    }

    public function getEmail()
    {
        return $this->getUserField('email');
    }

    public function getLogoutURL()
    {
        return $this->getConfigValue('root_surl') . '/login/logout';
    }

    public function getProfileURL()
    {
        return $this->getConfigValue('root_surl') . '/profile';
    }

    public function getLoginURL($redirect=null)
    {
        return $this->getConfigValue('root_surl')
            . '/login/index'
            . ($redirect ?
                '?amember_redirect_url=' . urlencode($redirect) :
                ''
            );
    }

    public function getSignupURL()
    {
        return $this->getConfigValue('root_surl') . '/signup';
    }

    public function renderLoginForm($redirect=null)
    {
        return '<form method="POST" action="'
            . $this->getLoginURL($redirect)
            . '">'
            . '<label for="form-amember_login">E-Mail Address or Username</label>'
            . '<input type="text" name="amember_login" id="form-amember_login" />'
            . '<label for="form-amember_pass">Password</label>'
            . '<input type="password" name="amember_pass" id="form-amember_pass" />'
            . '<input type="submit" value="Login" />'
            . '</form>';
    }

    function getRootURL()
    {
        return $this->getConfigValue("root_url");
    }

    /**
     * Retrieve logged-in user
     *
     * @return array|null
     */
    public function getUser()
    {
        return $this->getIdentity();
    }
    
    /**
     * Check if user logged in and have required subscription
     * otherwise redirect to login page or no-access page
     * 
     * @param int|array $require product_id or array of product_id or 
     * one of special const self::PAID, self:FREE, self::ANY, self::ONLY_LOGIN
     * @param string $title description of protected content, 
     * it will be shown at no-access page 
     */
    public function checkAccess($require, $title='') {
        if (!$this->hasIdentity()) { 
            header("Location: " . $this->getLoginURL($_SERVER['REQUEST_URI']));
            exit;
        }
        
        if (self::ONLY_LOGIN!=$require && !$this->haveSubscriptions($require)) {
            $params = array(
                'id' => $require,
                'title' => $title
            );
            
            header("Location: " . $this->getRootURL() . '/no-access/lite?' . http_build_query($params));
            exit;
        }
    }

    /**
     * Whether logged-in user have active subscription or not
     *
     * @param int|array $search
     * @return bool
     */
    public function haveSubscriptions($search = self::ANY)
    {
        if ($this->hasIdentity())
        {
            $accessRecors = $this->_filterNotActiveAccess($this->_getAccessRecords($search));
            return (bool) count($accessRecors);
        }
        else
        {
            return false;
        }
    }

    /**
     * Whether logged-in user had active subscription or not
     *
     * @param int|array $search
     * @return bool
     */
    public function hadSubscriptions($search = self::ANY)
    {
        if ($this->hasIdentity())
        {
            $accessRecors = $this->_getAccessRecords($search);
            return (bool) count($accessRecors);
        }
        else
        {
            return false;
        }
    }

    /**
     * Retrieve max expire date for selected products
     * for logged-in user
     *
     * @param <type> $search
     * @return string|null date in SQL format YY-mm-dd
     */
    public function getExpire($search = self::ANY)
    {
        $expire = null;
        if ($this->hasIdentity())
        {
            $accessRecors = $this->_getAccessRecords($search);
            foreach ($accessRecors as $access)
            {
                if ($access['expire_date'] > $expire)
                {
                    $expire = $access['expire_date'];
                }
            }
        }
        return $expire;
    }

    /**
     * Retrieve payments for logged-in user
     *
     * @return array
     */
    public function getPayments()
    {
        $result = array();
        if ($this->hasIdentity())
        {
            $user_id = $this->getUserField('user_id');
            $res = $this->query(
                'SELECT * FROM ?_invoice_payment
                        WHERE user_id=?', $user_id);
            foreach ($res as $p_rec)
            {
                $result[] = $p_rec;
            }
        }
        return $result;
    }

    public function getUserLinks()
    {
        $sess = $this->getSession();
        return $sess['amember']['amember_links'];
    }

    /**
     * Retrieve access records for logged-in user
     *
     * @return array
     */
    public function getAccess()
    {
        return $this->hasIdentity() ?
            $this->_getAccessRecords(self::ANY) :
            array();
    }

    public function getAccessCache()
    {
        return $this->hasIdentity() ?
            $this->_getAccessCache($this->getUserField('user_id')) :
            array();
    }

    public function isUserActive()
    {
        $access_cache = $this->getAccessCache();
        foreach ($access_cache as $r)
        {
            if ($r['status'] == self::ACTIVE)
                return true;
        }
        return false;
    }

    public function getProducts()
    {
        $products = array();
        $res = $this->query("SELECT product_id, title
            FROM ?_product
            ORDER BY 0+sort_order, title");
        foreach ($res as $r)
        {
            $products[$r['product_id']] = $r['title'];
        }
        return $products;
    }

    public function getCategories()
    {
        $ret = $parents = array();
        $sql = "SELECT product_category_id,
                parent_id, title, code
                FROM ?_product_category
                ORDER BY parent_id, 0+sort_order";
        $rows = $this->query($sql);
        
        foreach ($rows as $id => $r)
        {
	        $parents[$r['product_category_id']] = $r;
            $title = $r['title'];
            $parent_id = $r['parent_id'];
            while ($parent_id)
            {
                $parent = $parents[$parent_id];
                $title = $parent['title'] . '/' . $title;
                $parent_id = $parent['parent_id'];
            }
            $ret[$r['product_category_id']] = $title;
        }
        return $ret;
    }

    /**
     * Remove not active access from array
     *
     * @param array $access
     * @return array
     */
    protected function _filterNotActiveAccess($access)
    {
        $now = date('Y-m-d');
        foreach ($access as $k => $v)
        {
            if ($v['begin_date'] > $now || $v['expire_date'] < $now)
            {
                unset($access[$k]);
            }
        }
        return $access;
    }

    /**
     * Remove active access from array
     *
     * @param array $access
     * @return array
     */
    protected function _filterActiveAccess($access)
    {
        $now = date('Y-m-d');
        foreach ($access as $k => $v)
        {
            if ($v['begin_date'] <= $now && $v['expire_date'] >= $now)
            {
                unset($access[$k]);
            }
        }
        return $access;
    }

    protected function _getAccessCache($user_id)
    {
        $sql = "SELECT * FROM ?_access_cache where user_id =?";
        $res = $this->query($sql, $user_id);
        $result = array();
        foreach ($res as $r)
        {
            $result[] = $r;
        }
        return $result;
    }

    protected function _getAccessRecords($search)
    {
        $result = array();
        $user_id = $this->getUserField('user_id');
        $args = func_get_args();
        if (count($args) == 1 && !is_array($args[0]))
        {
            switch ($args[0])
            {
                case self::ANY :
                    $sql = "SELECT * FROM ?_access WHERE user_id=?";
                    break;
                case self::PAID :
                    $sql = "SELECT a.* FROM ?_access a
                            LEFT JOIN ?_invoice_payment p
                            USING(invoice_payment_id)
                            WHERE p.amount>0 AND a.user_id=?";
                    break;
                case self::FREE :
                    $sql = "SELECT a.* FROM ?_access a
                            LEFT JOIN ?_invoice_payment p
                            USING(invoice_payment_id)
                            WHERE (p.amount=0 OR p.amount IS NULL) AND a.user_id=?";
                    break;
                default:
                    $sql = sprintf("SELECT * FROM ?_access WHERE user_id=?
                            AND product_id='%d'", $args[0]);
            }
        }
        else
        {
            $p_ids = is_array($args[0]) ? $args[0] : $args;
            $p_ids = array_map('intval', $p_ids);

            $sql = sprintf("SELECT * FROM ?_access WHERE user_id=?
                    AND product_id IN (%s)", implode(',', $p_ids));
        }

        $res = $this->query($sql, $user_id);
        foreach ($res as $a_rec)
        {
            $result[] = $a_rec;
        }

        return $result;
    }

    /**
     *
     * @return PDO
     */
    protected function getDb()
    {
        if (is_null($this->_db))
        {
            $config = $this->getDbConfig();
            $this->_db = new PDO(
                    sprintf('mysql:host=%s;dbname=%s', $config['host'], $config['db']
                    ), $config['user'], $config['pass']
            );
            $this->_db->query("SET NAMES UTF8");
        }

        return $this->_db;
    }

    /**
     * Execute SQL query
     *
     * @param string $sql
     * @return PDOStatement
     */
    protected function query($sql, $args=null)
    {
        $db_config = $this->getDbConfig();
        $sql = preg_replace('/(\s)\?_([a-z0-9_]+)\b/', '\1'.$db_config['prefix'].'\2', $sql);
        $argv = func_get_args();
        $argc = func_num_args();
     
        for ($i=1; $i<$argc; $i++) //skip first value, it is $sql
        {
            $arg = $argv[$i];
            if (is_array($arg)) {
                $arg = implode(',', array_map(array($this->getDb(), 'quote'), $arg));
            } elseif(is_null($arg)) {
                $arg = 'NULL';
            } else {
                $arg = $this->getDb()->quote($arg);
            }
            
            $sql = preg_replace('/\?/', $arg, $sql, 1); // $arg is already quoted
        }

        $statement = $this->getDb()->query($sql);
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        return $statement;
    }

    protected function getDbConfig()
    {
        if (is_null($this->_db_config))
        {
            $file = dirname(__FILE__) . '/../../application/configs/config.php';
            if (!file_exists($file))
            {
                throw new Exception('Can not find file with aMember config');
            }
            $config = @include($file);
            if (!is_array($config))
            {
                throw new Exception('aMember config should return array');
            }
            $this->_db_config = $config['db']['mysql'];
        }
        return $this->_db_config;
    }

    protected function getConfig()
    {
        $res = $this->query("SELECT config FROM ?_config WHERE name='default'");
        $config = $res->fetch();
        return unserialize($config['config']);
    }

    protected function getConfigValue($name)
    {
        $config = $this->getConfig();
        return $config[$name];
    }

    protected function hasIdentity()
    {
        $session = $this->getSession();
        return @isset($session['amember_auth']['user']);
    }

    protected function getIdentity()
    {
        if ($this->hasIdentity())
        {
            $session = $this->getSession();
            return $session['amember_auth']['user'];
        }
        else
        {
            return null;
        }
    }

    protected function getUserField($name)
    {
        if ($this->hasIdentity())
        {
            $user = $this->getIdentity();
            return $user[$name];
        }
        else
        {
            return null;
        }
    }

    protected function getSession()
    {
        if (is_null($this->_session))
        {
            $session_id = @$_COOKIE[$this->getSessionName()];
            if ($session_id)
            {
                /** @var $res PDOStatement */
                $res = $this->query(
                    sprintf("SELECT * FROM ?_session WHERE id=? AND (%s - modified) < lifetime", time()), $session_id);

                $session = $res->fetch();
                $this->_session = $session ?
                    $this->unserializeSession($session['data']) :
                    array();
            }
            else
            {
                $this->_session = array();
            }
            $this->processStartupMetadata($this->_session);
        }
        return $this->_session;
    }

    /**
     * remove expired namespaces and variables
     *
     * @see Zend_Session::_processStartupMetadataGlobal
     * @param array $session 
     */
    protected function processStartupMetadata(&$session)
    {
        if (isset($session['__ZF']))
        {
            foreach ($session['__ZF'] as $namespace => $namespace_metadata)
            {
                // Expire Namespace by Time (ENT)
                if (isset($namespace_metadata['ENT']) && ($namespace_metadata['ENT'] > 0) && (time() > $namespace_metadata['ENT']))
                {
                    unset($session[$namespace]);
                    unset($session['__ZF'][$namespace]);
                }

                // Expire Namespace Variables by Time (ENVT)
                if (isset($namespace_metadata['ENVT']))
                {
                    foreach ($namespace_metadata['ENVT'] as $variable => $time)
                    {
                        if (time() > $time)
                        {
                            unset($session[$namespace][$variable]);
                            unset($session['__ZF'][$namespace]['ENVT'][$variable]);
                        }
                    }
                    if (empty($session['__ZF'][$namespace]['ENVT']))
                    {
                        unset($session['__ZF'][$namespace]['ENVT']);
                    }
                }
            }
        }
    }

    /**
     *
     * @param string $data session encoded
     * @return array
     */
    protected function unserializeSession($data)
    {
        $result = array();
        $vars = preg_split(
            '/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\|/', $data, -1, PREG_SPLIT_NO_EMPTY |
            PREG_SPLIT_DELIM_CAPTURE
        );
        for ($i = 0; isset($vars[$i]); $i++)
        {
            $result[$vars[$i]] = unserialize($vars[++$i]);
        }
        
        return $result;
    }
    
    /**
     * @return Name of aMember's session variable.
     */
    protected function getSessionName(){
        if(defined('AM_SESSION_NAME') && AM_SESSION_NAME)
            return AM_SESSION_NAME;
        else
            return self::SESSION_NAME;
        
    }

}

