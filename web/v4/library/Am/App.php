<?php
class Am_Plugins {
    protected $type;
    protected $classNameTemplate = "%s"; // use %s for plugin name
    protected $configKeyTemplate = "%s.%s"; // default : type.pluginId
    protected $fileNameTemplates = array(
            '%s.php',
            '%1$s/%1$s.php',
    );
    protected $cache = array();
    protected $enabled = array();
    private $_di;

    function  __construct(Am_Di $di, $type, $path, 
            $classNameTemplate='%s',  $configKeyTemplate='%s.%s', 
            $fileNameTemplates=array('%s.php','%1$s/%1$s.php',)
        )
    {
        $this->_di = $di;
        $this->type = $type;
        $this->paths = array($path);
        $this->classNameTemplate = $classNameTemplate;
        $this->configKeyTemplate = $configKeyTemplate;
        $this->fileNameTemplates = $fileNameTemplates;
        
        if ($type == 'modules')
        {
            $en = (array)$di->config->get('modules', array());
        } else {
            $en = (array)$di->config->get('plugins.'.$type);
        }
        $this->setEnabled($en);
    }
    function getPaths() {
        return $this->paths;
    }
    function setPaths(array $paths) {
        $this->paths = (array)$paths;
    }
    function addPath($path) {
        $this->paths[] = (string)$path;
    }
    function setEnabled(array $list) {
        $this->enabled = array_unique($list);
        return $this;
    }
    function addEnabled($name) {
        $this->enabled[] = $name;
        $this->enabled = array_unique($this->enabled);
        return $this;
    }
    function getEnabled() {
        return (array)$this->enabled;
    }
    /**
     * @return array of strings - module or plugin ids
     */
    function getAvailable()
    {
        $found = array();
        foreach ($this->paths as $path)
        {
            foreach ($this->fileNameTemplates as $tpl)
            {
                $regex = '|'.str_replace('%s', '([a-zA-Z0-9_-]+?)', preg_quote($tpl)).'|';
                $glob = $path . '/' . str_replace('%s', '*', $tpl);
                foreach (glob($glob) as $s)
                {
                    $s = substr($s, strlen($path)+1);
                    if (preg_match($regex, $s, $regs))
                    {
                        if ($regs[1] == 'default') continue;
                        $found[] = $regs[1];
                    }
                }
            }
        }
        return $found;
    }
    /**
     * Return all enabled plugins
     * @return array of objects
     */
    function getAllEnabled() {
        $ret = array();
        foreach ($this->enabled as $pl)
            try {
                $ret[] = $this->get($pl);
            } catch (Am_Exception_InternalError $e) {
                trigger_error("Error loading plugin [$pl]: " . $e->getMessage(), E_USER_WARNING);
            }
        return $ret;
    }
    function isEnabled($name) 
    {
        return in_array((string)$name, $this->enabled);
    }
    /** @return bool */
    function load($name) 
    {
        if (class_exists($this->getPluginClassName($name), false)) return true;
        
        $name = preg_replace('/[^a-zA-z0-9_-]/','',$name);
        if (!$name) throw new Am_Exception_Configuration("Could not load plugin - empty name after filtering");
        foreach ($this->getPaths() as $base_dir) {
            $found = false;
            foreach ($this->fileNameTemplates as $tpl) {
                $file = $base_dir . DIRECTORY_SEPARATOR . sprintf($tpl, $name);
                if (file_exists($file)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) continue;
            include_once $file;
            return true;
        }
        trigger_error("Plugin file for plugin ({$this->type}/$name) does not exists", E_USER_WARNING);
        return false;
    }
    function loadEnabled() {
        foreach ($this->getEnabled() as $name)
            $this->load($name);
        return $this;
    }
    /**
     * Create new plugin if not exists, or return existing one from cache
     * @param string name
     * @return Am_Plugin
     */
    function get($name) 
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        if ("" == $name)
            throw new Am_Exception_InternalError("An empty plugin name passed to ".__METHOD__);
        if (!$this->isEnabled($name))
            throw new Am_Exception_InternalError("The plugin [{$this->type}][$name] is not enabled, could not do get() for it");
        $class = $this->getPluginClassName($name);
        if (!class_exists($class, false))
            throw new Am_Exception_InternalError("Error in plugin {$this->type}/$name: class [$class] does not exists!");
        return array_key_exists($name, $this->cache) ? $this->cache[$name] : $this->register($name, $class);
    }
    
    function loadGet($name, $throwExceptions = true)
    {
        $name = filterId($name);
        if ($this->isEnabled($name) && $this->load($name))
            return $this->get($name);
        if ($throwExceptions)
            throw new Am_Exception_InternalError("Could not loadGet([$name])");
    }

    /**
     * Get Class name of plugin;
     * @param string plugin name
     * @return string class name;
     */
    public function getPluginClassName($id) {
        return sprintf($this->classNameTemplate, ucfirst(toCamelCase($id)));
    }
    
    /**
     * Register a new plugin in the registry so it will be returned by @see get(type,name)
     * @param string $name
     * @param string|object $className class name or existing object
     * @return object resulting object
     */
    function register($name, $className) 
    {
        if (is_string($className)) 
        {
            $configKey = $this->getConfigKey($name);
            return $this->cache[$name] = new $className($this->_di, (array)Am_Di::getInstance()->config->get($configKey));
        } elseif (is_object($className))
            return $this->cache[$name] = (object)$className;
    }

    function getConfigKey($pluginId)
    {
        return sprintf($this->configKeyTemplate, $this->type, $pluginId);
    }
}
class Am_Pluggable_Base {
    // build.xml script will run 'grep $_pluginStatus plugin.php' to find out status
    const STATUS_PRODUCTION = 1; // product - all ok
    const STATUS_BETA = 2; // beta - display warning on configuration page
    const STATUS_DEV = 4; // development - do not include into distrubutive
    
    // by default plugins are included into main build
    const COMM_FREE = 1; // separate plugin - do not include into dist
    const COMM_COMMERCIAL = 2; // commercial plugins, build separately
    
    /** to strip when calculating id from classname */
    protected $_idPrefix = 'Am_Plugin_';
    protected $id;
    protected $config = array();
    protected $version = null;
    private $_di;
    /**
     * Usually hooks are disabled when @see isConfigured
     * returns false. However hooks from this list will
     * anyway be enabled
     * @var array of hook names
     */
    protected $hooksToAlwaysEnable = array('setupForms', 'adminWarnings');

    function __construct(Am_Di $di, array $config) {
        $this->_di = $di;
        $this->config = $config;
        $this->setupHooks();
        $this->init();
    }
    function init() {   }
    /**
     * get dependency injector
     * @return Am_Di 
     */
    function getDi()
    {
        return $this->_di;
    }
    function setupHooks() 
    {
        $manager = $this->getDi()->hook;
        foreach ($this->getHooks() as $hook => $callback)
            $manager->add($hook, $callback);
    }
    /**
     * Returns false if plugin is not configured and most hooks must be disabled
     * @return bool
     */
    public function isConfigured() {
        return true;
    }
    
    public function onAdminWarnings(Am_Event $event)
    {
        if (!$this->isConfigured())
            $event->addReturn(___("Plugin [%s] is not configured", $this->getId()));
    }
    /**
     * @return array hookName (without Am_Event) => callback
     */
    public function getHooks() {
        $ret = array();
        $isConfigured = $this->isConfigured();
        foreach (get_class_methods(get_class($this)) as $method)
            if (strpos($method, 'on')===0) {
                $hook = lcfirst(substr($method, 2));
                if ($isConfigured || in_array($hook, $this->hooksToAlwaysEnable))
                    $ret[$hook] = array($this, $method);
            }
        return $ret;
    }
    function destroy() {
        $this->getDi()->hook->unregisterHooks($this);
    }
    function getTitle() {
        return $this->getId(false);
    }
    function getId($oldStyle=true) {
        if (null == $this->id) 
            $this->id = str_ireplace($this->_idPrefix, '', get_class($this));
        return $oldStyle ? fromCamelCase($this->id, '-') : $this->id;
    }
    public function getConfig($key=null, $default=null) {
        if ($key===null) return $this->config;
        $c = & $this->config;
        foreach (explode('.', $key) as $s) {
            $c = & $c[$s];
            if (is_null($c) || (is_string($c) && $c == '')) return $default;
        }
        return $c;
    }
    /**
     * mostly for unit testing
     * @param array $config 
     * @access private
     */
    public function _setConfig(array $config)
    {
        $this->config = $config;
    }
    /** Function will be executed after plugin deactivation */
    public function deactivate() 
    {
    }
    /** Function will be executed after plugin activation */
    static function activate($id, $pluginType) 
    {
    }
    
    public function getVersion()
    {
        return $this->version === null ? AM_VERSION : $this->version;
    }
    /**
     * @return string|null directory of plugin if plugin has its own directory
     */
    public function getDir()
    {
        $c = new ReflectionClass(get_class($this));
        $fn = realpath($c->getFileName());
        if (preg_match('|([\w_-]+)'.preg_quote(DIRECTORY_SEPARATOR).'\1\.php|', $fn))
        {
            return dirname($fn);
        }
    }
    /**
     * @return string return formatted readme for the plugin
     */
    public function getReadme()
    {
    }
}
class Am_Plugin extends Am_Pluggable_Base {
    /**
     * Function will be called when user access amember/payment/pluginid/xxx url directly
     * This can be used for IPN actions, or for displaying confirmation page
     * @see getPluginUrl()
     * @param $actionName a string passed to 'a' parameter of incoming post, filtered for alnum
     * @param $vars - $_GET or $_POST
     */
    function directAction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs) {
        throw new Am_Exception_NotImplemented("'direct' action is not implemented in " . get_class($this));
    }
}
class Am_Module extends Am_Pluggable_Base 
{
    protected $_idPrefix = 'Bootstrap_';
}
class Am_Theme extends Am_Pluggable_Base
{
    protected $_idPrefix = 'Am_Theme_';
    
    /**
     * Array of paths (relative to application/default/themes/XXX/public/)
     * that must be routed via PHP to substitute vars
     * for example css/theme.css
     * all these files can be accessed directly so please do not put anything
     * sensitive inside
     * @var array
     */
    protected $publicWithVars = array();
      
    public function __construct(Am_Di $di, $id, array $config)
    {
        parent::__construct($di, $config);
        $this->id = $id;
        $rm = new ReflectionMethod(get_class($this), 'initSetupForm');
        if ($rm->getDeclaringClass()->getName() != __CLASS__) {
            $this->getDi()->hook->add(Am_Event::SETUP_FORMS, array($this, 'eventSetupForm'));
        }
    }
    function eventSetupForm(Am_Event_SetupForms $event)
    {
        $form = new Am_Form_Setup_Theme($this->getId());
        $form->setTitle(ucfirst($this->getId()) . ' Theme')
             ->setComment('Configure '  . ucfirst($this->getId()) . ' Theme');
        $this->initSetupForm($form);
        $event->addForm($form);
    }
    /** You can override it and add elements to create setup form */
    public function initSetupForm(Am_Form_Setup_Theme $form) {}
    public function getRootDir()
    {
        return APPLICATION_PATH . '/default/themes/' . $this->getId();
    }
    public function printLayoutHead(Am_View $view)
    {
        $root = $this->getRootDir();
        if (file_exists($root . '/public/' . 'css/theme.css'))
        {
            if (!in_array('css/theme.css', $this->publicWithVars))
                $view->headLink()->appendStylesheet($view->_scriptCss('theme.css'));
            else
                $view->headLink()->appendStylesheet($this->urlPublicWithVars('css/theme.css'));
        }
    }
    function urlPublicWithVars($relPath)
    {
        return REL_ROOT_URL . '/public/theme/' . $relPath;
    }
    function parsePublicWithVars($relPath)
    {
        if (!in_array($relPath, $this->publicWithVars))
            amDie("That files is not allowed to open via this URL");
        $f = $this->getRootDir() . '/public/' . $relPath;
        if (!file_exists($f)) 
            amDie("Could not find file [".htmlentities($relPath, ENT_QUOTES, 'UTF-8')."]");
        $tpl = new Am_SimpleTemplate();
        foreach ($this->config as $k => $v)
            $tpl->$k = $v;
        return $tpl->render(file_get_contents($f));
    }
}

/*** Helper Functions **/
function memUsage($op) {}

function tmUsage($op, $init=false, $start_anyway=false) { }

/* ************** GLOBAL FUNCTIONS
/**
 * Function displays nice-looking error message without
 * using of fatal_error function and template
 */
function amDie($string, $return=false) {
    $out= <<<CUT
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
  <head>
    <title>Fatal Error</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
	body {
		font: 80% verdana, arial, helvetica, sans-serif;
		text-align: center; /* for IE */
	}
	#container {
		margin: 0 auto;   /* align for good browsers */
		text-align: left; /* counter the body center */
		border: 2px solid #f00;
        background-color: #fdd;
        padding: 10px 10px 10px 10px;
		width: 80%;
	}
    .header {
        font-size: 12pt;
        font-weight: bold;

    }
    </style>
<body>
<p style="height: 50px;"></p>
<div id="container">
<div class="header">Script Error</div>
            $string
</div>

</body></html>
CUT;
    return $return ? $out : exit($out);
}

class Am_Block
{
    const TOP = 100;
    const MIDDLE = 500;
    const BOTTOM = 900;    

    protected $order = self::MIDDLE;
    protected $title = null;
    protected $id;
    protected $block;
    /** @var Am_Plugin */
    protected $plugin;
    protected $path;
    protected $callback;
    
    /**
     * @param array|string $targets where to put the block, like cart/right
     * @param string $id unique id of the block
     * @param Am_Plugin $plugin
     * @param string|callback $pathOrCallback 
     */
    function __construct($targets, $title, $id, Am_Pluggable_Base $plugin = null, $pathOrCallback = null, $order = self::MIDDLE)
    {
        $this->targets = (array)$targets;
        $this->title = (string)$title;
        $this->id = $id;
        $this->plugin = $plugin;
        $this->order = (int)$order;
        if (is_callable($pathOrCallback))
        {
            $this->callback = $pathOrCallback;
        } else {
            $this->path = $pathOrCallback;
        }
    }
    
    function getTargets()
    {
        return $this->targets;
    }
    function getTitle()
    {
        return $this->title;
    }
    function render(Am_View $view)
    {
        if ($this->path) {
            $view->block = $this;
            // add plugin folder to search path for blocks
            $paths = $view->getScriptPaths();
            $newPaths = null;
            if ($this->plugin && 
                !($this->plugin instanceof Am_Module) &&
                $dir = $this->plugin->getDir())
            {
                $newPaths = $paths;
                // we insert it to second postion, as first will be theme
                // lets keep there some place for redefenition
                array_splice($newPaths, 1, 0, array($dir));
                $view->setScriptPath(array_reverse($newPaths));
            }
            $pluginSaved = !empty($view->plugin) ? $view->plugin : null;
            if ($this->plugin) $view->plugin = $this->plugin;
            $out = $view->render("blocks/" . $this->path);
            $view->plugin = $pluginSaved;
            // remove plugin folder from view search path
            if (!empty($newPaths))
                $view->setScriptPath(array_reverse($paths));
            return $out;
        } elseif ($this->callback) {
            return call_user_func($this->callback, $view, $this);
        } else {
            throw new Am_Exception_InternalError("Unknown block path format");
        }
    }
    function getId()
    {
        return $this->id;
    }
    function getOrder()
    {
        return (int)$this->order;
    }
}

class Am_Blocks {
    protected $blocks = array();

    function add(Am_Block $block) 
    {
        foreach ($block->getTargets() as $t)
            $this->blocks[(string)$t][] = $block;
        return $this;
    }
    
    function remove($id) {
        foreach ($this->blocks as $k => $target) {
            unset($this->blocks[$k][$id]);
        }
    }
    
    function addDefaultBlocks()
    {
        $this->add(
                new Am_Block('member/main/left', ___("Your Subscriptions"), 'member-main-resources', null, 'member-main-resources.phtml')
            )->add(
                new Am_Block('member/main/left', ___("Unsubscribe from all e-mail messages"), 'member-main-unsubscribe', 
                    null, 'member-main-unsubscribe.phtml', Am_Block::BOTTOM + 100)
            )->add(
                new Am_Block('member/main/right', ___("Useful Links"), 'member-main-links', null, 'member-main-links.phtml')
            );
    }
    /**
     * @param Zend_View_Abstract $view
     * @param $blockPattern string
     *    exact path string or wildcard string
     *    wildcard * - matches any word
     *    wildcard ** - matches any number of words and delimiters
     * @return array */
    function get(Zend_View_Abstract $view, $blockPattern) {
        $out = array();
        $blockPattern = preg_quote($blockPattern, "|");
        $blockPattern = str_replace('\*\*', '.+?', $blockPattern);
        $blockPattern = str_replace('\*', '.+?', $blockPattern);
        foreach (array_keys($this->blocks) as $target) {
            if (preg_match("|^$blockPattern\$|", $target))
                foreach ($this->blocks[$target] as $block) 
                {
                    $blockRendered = array(
                        'content' => $block->render($view),
                        'title' => $block->getTitle(),
                        'id' => $block->getId(),
                    );
                    if (!strlen($blockRendered['content'])) continue;
                    $out[ $block->getOrder() ][] = $blockRendered;
                }
        }
        ksort($out);
        $ret = array();
        foreach ($out as $sort => $arr)
            $ret = array_merge($ret, $arr);
        return $ret;
    }
}

class Am_Cron {
    const HOURLY = 1;
    const DAILY  = 2;
    const KEY = 'cron-last-run';
    const LOCK = 'am-cron';
    
    static function getLockId()
    {
        return 'am-lock-'.md5(ROOT_DIR);
    }

    /** @return int */
    static function needRun() 
    {
        // check if another thread is updating time right now
        if (!Am_Di::getInstance()->db->selectCell("SELECT IS_FREE_LOCK(?)", self::getLockId())) 
            return false;
        // ok, lets check
        $last_runned = self::getLastRun();
        if (!$last_runned) $last_runned = strtotime('-2 days');
        $h_diff = date('dH') - date('dH', $last_runned);
        $d_diff = date('d') - date('d', $last_runned);
        return ($h_diff ? self::HOURLY : 0) | ($d_diff ? self::DAILY : 0);
    }
    static function getLastRun() {
        return Am_Di::getInstance()->db->selectCell("SELECT `value` FROM ?_store WHERE name=?", self::KEY);
    }
    static function setupHook() {
        Am_Di::getInstance()->hook->add('afterRender', array(__CLASS__, 'inject'));
    }
    static function inject(Am_Event_AfterRender $event) {
        static $runned = 0;
        if ($runned) return;
        $url = htmlentities('/cron');
        if ($event->replace('|</body>|i', "\n<img src='$url' width='1' height='1'>\$1", 1))
            $runned++;
    }
    static function checkCron() {
        if (defined('AM_TEST') && AM_TEST) return; // do not run during unit-testing
        
        $needRun = self::needRun();
        if (!$needRun) return;

        // get lock
        if (!Am_Di::getInstance()->db->selectCell("SELECT GET_LOCK(?, 1)", self::getLockId()))
        {
            Am_Di::getInstance()->errorLogTable->log("Could not obtain MySQL's GET_LOCK() to update cron run time. Not runned cron");
            return;
        }
        
        @ignore_user_abort(true);
        @set_time_limit(0);
        @ini_set('memory_limit', '256M');

        if ($_GET['log'])
            Am_Di::getInstance()->errorLogTable->log("cron.php started");
        
        Am_Di::getInstance()->db->query("REPLACE INTO ?_store (name, `value`) VALUES (?, ?)",
                self::KEY, time());
        
        Am_Di::getInstance()->db->query("SELECT RELEASE_LOCK(?)", self::getLockId());
        $out = "";
        if ($needRun & self::HOURLY) {
            Am_Di::getInstance()->hook->call(Am_Event::HOURLY, array('datetime' => sqlDate('now')));
            $out .= "hourly.";
        }
        if ($needRun & self::DAILY) {
            Am_Di::getInstance()->hook->call(Am_Event::DAILY, array('datetime' => sqlDate('now')));
            $out .= "daily.";
        }
        if ($_GET['log'])
            Am_Di::getInstance()->errorLogTable->log("cron.php finished ($out)");
    }
}

class Am_Config {
    protected $config = array();

    function get($item, $default = null) {
        $c = & $this->config;
        foreach (preg_split('/\./', $item) as $s) {
            $c = & $c[$s];
            if (is_null($c) || (is_string($c) && $c == '')) return $default;
        }
        return $c;
    }
    /** @return Am_Config provides fluent interface */
    function set($item, $value) {
        if (is_null($item)) throw new Exception("Empty value passed as config key to " .__FUNCTION__);
        $this->setDotValue($item, $value);
        return $this;
    }
    function read() 
    {
        try {
            $this->config = (array)unserialize(Am_Di::getInstance()->db->selectCell("SELECT config FROM ?_config WHERE name='default'"));
        } catch (Am_Exception_Db $e) {
            amDie("aMember Pro is not configured, or database tables are corrupted - could not read config (sql error #" . $e->getCode()."). You have to remove file [amember/application/configs/config.php] and reinstall aMember, or restore database tables from backup.");
        }
    }
    function save() {
        Am_Di::getInstance()->db->query("REPLACE INTO ?_config
            (name, config)
            VALUES
            ('default', ?)", serialize($this->config));
    }
    function setArray(array $config) {
        $this->config = (array)$config;
    }
    function getArray() {
        return (array)$this->config;
    }
    protected function setDotValue($item, $value) {
        $c = & $this->config;
        $levels = explode('.', $item);
        $last = array_pop($levels);
        $passed = array();
        foreach ($levels as $s) {
            $passed[] = $s;
            if (isset($c[$s]) && !is_array($c[$s])) {
                trigger_error('Unsafe conversion of scalar config value ['.implode('.',$passed).'] to array in ' . __METHOD__, E_USER_WARNING);
                $c[$s] = array('_SCALAR_' => $c[$s]);
            }
            $c = & $c[$s];
        }
        $c[$last] = $value;
        return $c;
    }
    static function saveValue($k,$v) {
        $config = new self;
        $config->read();
        $config->set($k, $v);
        $config->save();
    }
}


class Am_Db /*extends DbSimple_Mypdo  */
{ 
    /** @var bool are we inside MySQL error handler? */
    static public $inSqlError = false;

    /**
     * @return DbSimple_Mysql
     */
    static function connect($config, $onlyConnect = false) {
        require_once 'DbSimple/Generic.php';
        extract($config);
        $database = new DbSimple_Mypdo(
                array('scheme'=>'mysql',
                        'user'=>@$user,
                        'pass'=>@$pass,
                        'host'=>@$host,
                        'path'=>@$db,
                        'port'=>@$port,
        ));
        if (!$onlyConnect) {
            $database->setIdentPrefix(@$prefix);
            $database->setErrorHandler(array(__CLASS__,'defaultDatabaseErrorHandler'));
            if ($database->_isConnected())
            {
                $database->query("SET NAMES utf8");
                $database->query("SET SESSION sql_mode=''");
            }
        }
        return $database;
    }
    static function defaultDatabaseErrorHandler($message, $info) {
        if (!error_reporting()) return;
        if (self::$inSqlError) return ;
        self::$inSqlError = true;
        
        if (class_exists('ErrorLog', true))
            Am_Di::getInstance()->errorLogTable->log("MYSQL ERROR" . "<br />\n".
                    nl2br(print_r($info, true)));
        self::$inSqlError = false;

        if (!class_exists('Am_Exception_Db'))
            require_once dirname(__FILE__) . '/Exception.php';

        if ($info['code'] == 1062)
            $class = 'Am_Exception_Db_NotUnique';
        else
            $class = 'Am_Exception_Db';
        $e = new $class("$message({$info['code']}) in query: {$info['query']}", @$info['code']);
        $e->setDbMessage(preg_replace('/ at.+$/', '', $message));
        $e->setLogError(false); // already logged
        // try to parse table name
        if (($e instanceof Am_Exception_Db_NotUnique) && 
            preg_match('/insert into (\w+)/i', $info['query'], $regs))
        {
            $prefix = Am_Di::getInstance()->db->getPrefix();
            $table = preg_replace('/^'.preg_quote($prefix).'/', '?_', $regs[1]);
            $e->setTable($table);
        }
        throw $e;
    }
    static function loggerCallback($db, $sql) {
        $caller = $db->findLibraryCaller();
        if (preg_match('/phpunit/', @$_SERVER['argv'][0]) || empty($_SERVER['REMOTE_ADDR'])) {
            print_r($sql);
            print "\n";
        } else {
            $tip = "at ".@$caller['file'].' line '.@$caller['line'];
            echo "<xmp title=\"$tip\">";
            print_r($sql);
            echo "</xmp>";
        }
    }
    static function setLogger($db = null) {
        if ($db === null) $db = Am_Di::getInstance()->db;
        $db->setLogger(array(__CLASS__, 'loggerCallback'));
    }
    static function removeLogger($db = null) {
        if ($db === null) $db = Am_Di::getInstance()->db;
        $db->setLogger(null);
    }
}

/**
 * @return <type> Return formatted date string
 */
function amDate($string) {
    if ($string == null) return '';
    return date(Zend_Registry::get('Am_Locale')->getDateFormat(), amstrtotime($string));
}
function amDatetime($string) {
    if ($string == null) return '';
    return date(Zend_Registry::get('Am_Locale')->getDateTimeFormat(), amstrtotime($string));
}
function amTime($string) {
    if ($string == null) return '';
    return date(Zend_Registry::get('Am_Locale')->getTimeFormat(), amstrtotime($string));
}
function check_demo($msg="Sorry, this function disabled in the demo") {
    if (APPLICATION_ENV == 'demo')
        throw new Am_Exception_InputError($msg);
}
/**
 * Dump any number of variables, last veriable if exists becomes title
 */
function print_rr($vars, $title="==DEBUG==") {
    $args = func_get_args();
    $html = !empty($_SERVER['HTTP_CONNECTION']);
    if ($args == 1)
        $title = array_pop($args);
    else
        $title = '==DEBUG==';
    echo $html ? "\n<table><tr><td><pre><b>$title</b>\n" : "\n$title\n";
    foreach ($args as $vars) {
        print_r($vars);
        print $html ? "<br />\n" : "\n\n";
    }
    if ($html)
        print "</pre></td></tr></table><br/>\n";
}
function print_rre($vars, $title="==DEBUG==") {
    print_rr($vars, $title);
    print("\n==<i>exit() called from print_rre</i>==\n");
    print_rr(get_backtrace_callers(0), 'print_rre called from ');
    exit();
}
function moneyRound($v) {
    return round($v, 2);
}

function print_bt($title="==BACKTRACE==") { /** print backtrace **/
    print_rr(get_backtrace_callers(1), $title);
}
/** @return mixed first not-empty argument */
function get_first($arg1, $arg2) {
    $args = func_get_args();
    foreach ($args as $a)
        if ($a != '') return $a;
}

if(!function_exists("lcfirst")):
    function lcfirst($str) {
        $str[0] = strtolower($str[0]);
        return $str;
}
endif;

/**
 * Remove from string all chars except the [a-zA-Z0-9_-]
 * @param string|null input
 * @return string|null filtered
 */
function filterId($string) {
    if ($string === null) return null;
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $string);
}
/**
 * Transform any date to SQL format yyyy-mm-dd
 */
function amstrtotime($tm) {
    if ($tm instanceof DateTime)
        return $tm->getTimestamp ();
    if (strlen($tm)==14 && preg_match('/^\d{14}$/', $tm))
        return mktime(substr($tm, 8, 2),substr($tm, 10, 2),substr($tm, 12, 2),
                substr($tm, 4, 2),substr($tm, 6, 2),substr($tm, 0, 4));
    elseif (is_numeric($tm))
        return (int)$tm;
    else {
        $res = strtotime($tm, Am_Di::getInstance()->time);
        if ($res == -1) trigger_error("Problem with parcing timestamp [".htmlentities($tm)."]", E_USER_NOTICE);
        return $res;
    }
}

function sqlDate($d) {
    if (!($d instanceof DateTime) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
    else return date('Y-m-d', amstrtotime($d));
}
function sqlTime($tm) 
{
    if (!($tm instanceof DateTime) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $tm)) return $tm;
    else return date('Y-m-d H:i:s', amstrtotime($tm));
}

/**
 * Convert StringOfCamelCase to string_of_camel_case
 */
function fromCamelCase($string, $separator="_") {
    return strtolower(preg_replace('/([A-Z])/', $separator.'\1', lcfirst($string)));
}
/**
 * Convert string_of_camel_case to StringOfCamelCase
 * @param <type> $string
 */
function toCamelCase($string) {
    return lcfirst(str_replace(' ', '', ucwords(preg_replace('/[_-]+/', ' ', $string))));
}

/**
 * Find all defined not abstract successors of given $className
 * @param string $className
 */
function amFindSuccessors($className) {
    $ret = array();
    foreach (get_declared_classes() as $c) {
        if (is_subclass_of($c, $className)) {
            $r = new ReflectionClass($c);
            if ($r->isAbstract()) continue;
            $ret[] = $c;
        }
    }
    return $ret;
}


/** translate, sprintf if requested and return string */
function ___($msg) {
    try {
        $tr = Zend_Registry::get('Zend_Translate');
    } catch (Zend_Exception $e) {
        //trigger_error("Zend_Translate is not available from registry", E_USER_NOTICE);
        return $msg;
    }
    $args = func_get_args();
    $msg = $tr->_(array_shift($args));
    return $args ? vsprintf($msg, $args) : $msg;
}

/** translate and printf format string */
function __e($msg) {
    $args = func_get_args();
    echo call_user_func_array('___', $args);
}

function is_trial() {
    return '=-=TRIAL=-=' != ('=-=' . 'TRIAL=-=');
}
function check_trial($errmsg="Sorry, this function is available in aMember Pro not-trial version only") {
    if (is_trial()) {  
        throw new Am_Exception_FatalError($errmsg);
    }
}

if (PHP_VERSION < '5.1.3')
    die("This version of aMember Pro requires PHP version 5.1.3 or higher, ".PHP_VERSION." is not supported");



class Am_Recaptcha 
{
    /** @var string error code returned from previous recaptcha */
    protected $error;
    
    public function render($theme = 'clean')
    {
        if (!$this->isConfigured())
            throw new Am_Exception_Configuration("ReCaptcha error - recaptcha is not configured. Please go to aMember Cp -> Setup -> ReCaptcha and enter keys");
        
        $public = Am_Controller::escape(Am_Di::getInstance()->config->get('recaptcha-public-key'));
        $error = null;
        if ($this->error)
            $error = '&error=' . Am_Controller::escape($this->error);
        
        return <<<CUT
<script>
var RecaptchaOptions = {
    theme : '$theme'
 };        
        </script>
        <script type="text/javascript"
     src="http://www.google.com/recaptcha/api/challenge?k=$public$error">
  </script>
  <noscript>
     <iframe src="http://www.google.com/recaptcha/api/noscript?k=$public$error"
         height="300" width="500" frameborder="0"></iframe><br>
     <textarea name="recaptcha_challenge_field" rows="3" cols="40">
     </textarea>
     <input type="hidden" name="recaptcha_response_field"
         value="manual_challenge">
  </noscript>   
CUT;
    }
    /** @return bool true on success, false and set internal error code on failure */
    public function validate($challenge, $response)
    {
        if (!$this->isConfigured())
            throw new Am_Exception_Configuration("Brick: ReCaptcha error - recaptcha is not configured. Please go to aMember Cp -> Setup -> ReCaptcha and enter keys");

        $req = new Am_HttpRequest('http://www.google.com/recaptcha/api/verify', Am_HttpRequest::METHOD_POST);
        $req->addPostParameter('privatekey', Am_Di::getInstance()->config->get('recaptcha-private-key'));
        $req->addPostParameter('remoteip', $_SERVER['REMOTE_ADDR']);
        $req->addPostParameter('challenge', $challenge);
        $req->addPostParameter('response', $response);

        $response = $req->send();
        if ($response->getStatus() != '200')
        {
            $this->error = 'recaptcha-not-reachable';
        } else {
            @list($status, $this->error) = explode("\n", $response->getBody());
            $status = trim($status) == 'true';
        }
        return $status;
    }
    function getPublicKey()
    {
        return Am_Di::getInstance()->config->get('recaptcha-public-key');
    }
    function getError()
    {
        return $this->error;
    }
    public static function isConfigured()
    {
        return Am_Di::getInstance()->config->get('recaptcha-public-key') && Am_Di::getInstance()->config->get('recaptcha-private-key');
    }
}

function get_backtrace_callers($skipLevels = 1, $bt=null) {
    if ($bt === null) $bt = debug_backtrace();
    $bt = array_slice($bt, $skipLevels + 1);
    $ret = array();
    foreach ($bt as $b) {
        $b['line'] = intval(@$b['line']);
        if (!isset($b['file'])) $b['file'] = null;
        if (@$b['object'] && $className = (get_class($b['object']))) {
            $ret[] = $className."->".$b['function']." in line $b[line] ($b[file])";
        } elseif (@$b['class'])
            $ret[] = "$b[class]:$b[function] in line $b[line] ($b[file])";
        else
            $ret[] = "$b[function] in line $b[line] ($b[file])";
    }
    return $ret;
}

/**
 * Utility functions
 */
class Am_App 
{
    /** @var Am_Di */
    private $di;
    protected $config;
    
    public function __construct($config)
    {
        $this->config = is_array($config) ? $config : (require $config);
        @define('INCLUDED_AMEMBER_CONFIG', 1);
        
        if (defined('AM_DEBUG_IP') && AM_DEBUG_IP && (AM_DEBUG_IP == @$_SERVER['REMOTE_ADDR']))
            @define('APPLICATION_ENV', 'debug');

        // Define application environment
        defined('APPLICATION_ENV')
          || define('APPLICATION_ENV',
                    (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV')
                                               : 'production'));

        if (APPLICATION_ENV == 'debug' || APPLICATION_ENV == 'testing')
            @define('AM_DEBUG', true);
        
        if (!defined('APPLICATION_ENV'))
            define('APPLICATION_ENV', 'production');
    }
    
    public function bootstrap()
    {
        if (defined('APPLICATION_ENV') && (APPLICATION_ENV == 'debug'))
        {
            error_reporting(E_ALL | E_RECOVERABLE_ERROR | E_NOTICE | E_DEPRECATED | E_STRICT );
            @ini_set('display_errors', true);
        } else {
            error_reporting(error_reporting() & ~E_RECOVERABLE_ERROR); // that is really annoying
        }
        spl_autoload_register(array(__CLASS__, '__autoload'));
        $this->di = new Am_Di($this->config);
        Am_Di::_setInstance($this->di);
        $this->di->app = $this;
        set_error_handler(array($this, '__error'));
        set_exception_handler(array($this, '__exception'));
        $this->di->init();
        try {
            $this->di->getService('db');
        } catch (Am_Exception $e) {
            if (defined('AM_DEBUG') && AM_DEBUG)
                amDie($e->getMessage());
            else
                amDie($e->getPublicError());
        } 
        $this->di->config;
        $this->initConstants();
         
        // set memory limit
        $limit = @ini_get('memory_limit');
        if (preg_match('/(\d+)M$/', $limit, $regs) && ($regs[1]<=32))
            @ini_set('memory_limit', '32M');        
        //
        $this->initFront();
        require_once 'Am/License.php';
        $this->initModules(); /** @todo 4.2.0 move after initTranlate !! alex */
        $this->initSession();
        Am_Locale::initLocale($this->di);
        $this->initTranslate();
        $this->di->blocks->addDefaultBlocks();

        $this->di->hook->call(Am_Event::INIT_FINISHED);
        
        if (file_exists( APPLICATION_PATH . "/configs/site.php"))
            require_once(APPLICATION_PATH . "/configs/site.php");        
        
    }
    
    
    /***
     * Fetch updated license from aMember Pro website
     */
    function updateLicense()
    {
        if (!$this->di->config->get('license')) return; // empty license. trial?
        if ($this->di->store->get('app-update-license-checked')) return;
        try {
            $req = new Am_HttpRequest('http://update.amember.com/license.php');
            $req->setConfig('connect_timeout', 2);
            $req->setMethod(Am_HttpRequest::METHOD_POST);
            $req->addPostParameter('license', $this->di->config->get('license'));
            $req->addPostParameter('root_url', $this->di->config->get('root_url'));
            $req->addPostParameter('root_surl', $this->di->config->get('root_surl'));
            $req->addPostParameter('version', AM_VERSION);
            $this->di->store->set('app-update-license-checked', 1, '+12 hours');
            $response = $req->send();
            if ($response->getStatus() == '200')
            {
                $newLicense = $response->getBody();
                if ($newLicense)
                    if (preg_match('/^L[A-Za-z0-9\/=+\n]+X$/', $newLicense))
                        Am_Config::saveValue('license', $newLicense);
                    else
                        throw new Exception("Wrong License Key Received: [" . $newLicense . "]");
            }
        } catch (Exception $e) {
            if (APPLICATION_ENV != 'production') throw $e;
        }
    }
    
    
    function initTranslate()
    {
       /// setup test translation adapter
        if (defined('AM_DEBUG_TRANSLATE') && AM_DEBUG_TRANSLATE)
        {
            require_once ROOT_DIR . '/utils/TranslateTest.php';
            Zend_Registry::set('Zend_Translate', new Am_Translate_Test(array('disableNotices' => true,)));
            return;
        }
        
//        if ($cache = $this->getResource('Cache'))
//            Zend_Translate::setCache($cache);

        $locale = Zend_Locale::getDefault();
        $locale = key($locale);
        list($lang, ) = explode('_', $locale);
        $tr = new Zend_Translate(array(
          'adapter' => 'array',
          'content' => APPLICATION_PATH . '/default/language/user/'.$lang.'.php',
          'locale'  => $locale,
        ));
        
        if (preg_match('/\badmin\b/', @$_SERVER['REQUEST_URI']))
        {
            $tr->addTranslation(array(
              'adapter' => 'array',
              'content' => APPLICATION_PATH . '/default/language/admin/en.php',
              'locale'  => $locale,
            ));
            if (file_exists(APPLICATION_PATH . '/default/language/admin/'.$lang.'.php'))
                $tr->addTranslation(array(
                  'adapter' => 'array',
                  'content' => APPLICATION_PATH . '/default/language/admin/'.$lang.'.php',
                  'locale'  => $locale,
                ));
        }
        
        //overwrite existing translation from file
        //with custom translation from DB
        if ($data = $this->di->translationTable->getTranslationData($lang))
            $tr->addTranslation(
                array(
                    'adapter' => 'array',
                    'locale' => $locale,
                    'content'=> $data,
            ));
        Zend_Registry::set('Zend_Translate', $tr);
    }
    
    function addRoutes(Zend_Controller_Router_Abstract $router)
    {
        $router->addRoute('user-logout', new Zend_Controller_Router_Route(
          'logout/:action',
          array(
              'module' => 'default',
              'controller' => 'login',
              'action' => 'logout',
          )
        ));
        $router->addRoute('grid-inside-module', new Zend_Controller_Router_Route(
          ':module/:controller/c/:c/:action/*',
          array(
          )
        ));
        $router->addRoute('inside-pages', new Zend_Controller_Router_Route(
          ':module/:controller/p/:page_id/:action/*',
          array(
          )
        ));
        $router->addRoute('grid', new Zend_Controller_Router_Route(
          ':controller/c/:c/:action/*',
          array(
              'module' => 'default'
          )
        ));
        $router->addRoute('admin-setup', new Zend_Controller_Router_Route(
          'admin-setup/:page',
           array(
              'module'      => 'default',
              'controller'  => 'admin-setup',
              'action'      => 'display',
           )
        ));
        $router->addRoute('payment', new Zend_Controller_Router_Route(
          'payment/:plugin_id/:action',
           array(
              'module'      => 'default',
              'controller'  => 'direct',
              'action'      => 'index',
              'type'        => 'payment',
           )
        ));
        $router->addRoute('protect', new Zend_Controller_Router_Route(
          'protect/:plugin_id/:action',
           array(
              'module'      => 'default',
              'controller'  => 'direct',
              'action'      => 'index',
              'type'        => 'protect',
           )
        ));
        $router->addRoute('misc', new Zend_Controller_Router_Route(
          'misc/:plugin_id/:action',
           array(
              'module'      => 'default',
              'controller'  => 'direct',
              'action'      => 'index',
              'type'        => 'misc',
           )
        ));
        $router->addRoute('cron-compat', new Zend_Controller_Router_Route(
          'cron.php',
           array(
              'module'      => 'default',
              'controller'  => 'cron',
              'action'      => 'index',
           )
        ));        
        if($this->di->config->get('am3_urls', false)){
            $this->initAm3Routes($router);
        }
    }
    function initAm3Routes(Zend_Controller_Router_Abstract $router){

        $router->addRoute('v3_urls', new Zend_Controller_Router_Route_Regex(
                '(signup|member|login|logout|profile).php', 
                array(  'module'    =>  'default',
                        'action'    =>  'index'
                    ),
                array( 'controller' => 1)
                ));
        
        $router->addRoute('v3_logout', new Zend_Controller_Router_Route(
          'logout.php',
           array(
              'module' => 'default',
              'controller' => 'login',
              'action' => 'logout',
          )
        ));

        $router->addRoute('v3_ipn_scripts', new Zend_Controller_Router_Route_Regex(
                'plugins/payment/([a-z]+)_?r?/(ipn)r?.php', 
                array(  
                      'module'      => 'default',
                      'controller'  => 'direct',
                      'action'      => 'index',
                      'type'        => 'payment',
                    ), 
                array(
                    'plugin_id'     =>  1,
                    'action'        =>  2
                ))); 
      /*
       * @todo Do the same for affiliate programm URLs and links. 
       * 
       */  
        
    }
    
    
    public function initFront()
    {
        Zend_Controller_Action_HelperBroker::addPrefix('Am_Controller_Action_Helper');
        $front = Zend_Controller_Front::getInstance();
        $front->setParam('di', $this->di);
        $front->setParam('noViewRenderer', true);
        $front->throwExceptions(true);
        $front->addModuleDirectory(APPLICATION_PATH);
        $front->setRequest(new Am_Request);
        if (!$front->getPlugin('Am_Controller_Plugin'))
            $front->registerPlugin(new Am_Controller_Plugin($this->di), 90);
        if (!defined('REL_ROOT_URL'))
        {
            $relRootUrl = $front->getRequest()->getBaseUrl();
            // filter it for additional safety
            $relRootUrl = preg_replace('|[^a-zA-Z0-9.\\/_+-~]|', '', $relRootUrl);
            define('REL_ROOT_URL', $relRootUrl);
        }
        $this->addRoutes($front->getRouter());
        Am_License::getInstance()->init($this);
    }
    function initModules()
    {
        /// add modules inc dir
        $pathes = array();
        foreach ($this->di->modules->getEnabled() as $module)
        {
            $dir = APPLICATION_PATH . '/' . $module . '/library/';
            if (file_exists($dir))
                $pathes[] = $dir;
        }
        if ($pathes)
            set_include_path(get_include_path() .
                PATH_SEPARATOR .
                implode(PATH_SEPARATOR, $pathes));
        $this->di->modules->loadEnabled()->getAllEnabled();
    }
    
    public function run()
    {
        Zend_Controller_Front::getInstance()->dispatch();
    }
    
    public function initHooks()
    {
        class_exists('Am_Hook', true);
        
        /// load plugins
        $this->di->plugins_protect
            ->loadEnabled()->getAllEnabled();
        $this->di->plugins_payment
            ->addEnabled('free');
        $this->di->plugins_misc
            ->loadEnabled()->getAllEnabled();

        $this->di->hook
            ->add(Am_Event::HOURLY, array($this->di->app, 'onHourly'))
            ->add(Am_Event::DAILY, array($this->di->app, 'onDaily'))
            ->add(Am_Event::INVOICE_STARTED, array('EmailTemplateTable', 'onInvoiceStarted'))
            ->add(Am_Event::PAYMENT_AFTER_INSERT, array('EmailTemplateTable', 'onPaymentAfterInsert'));

        if (!$this->di->config->get('use_cron') && Am_Cron::needRun()) // we have no remote cron setup
            Am_Cron::setupHook();
    }
    
    static function __autoload($className)
    {
        $regexes = array(
            'Am_Mail_Template' => '$0',
            'Am_Mail_TemplateTypes' => '$0',
            'Am_Form_Bricked' => '$0',
            '(Am_Mail)(.+)' => '$1',
            '(Am_DbSync)(.+)' => '$1',
            '(Am_Exception)(.+)' => '$1',
            '(Am_Event)(.+)' => '$1',
 //           '(Am_Form_Brick)(.+)' => '$1',
            '(Am_Crypt)(.+)' => '$1',
            'Am_Table' => 'Am_Record',
        );
        $count = 0;
        foreach ($regexes as $regex => $replace)
        {
            $className = preg_replace('/^'.$regex.'$/', $replace, $className, 1, $count);
            if ($count)
                break;
        }
        $className = preg_replace('/[^a-zA-Z0-9_]+/', '', $className);
        $className = str_replace('_', DIRECTORY_SEPARATOR , $className);
        if (preg_match('/^pear/i', $className))
            return; // do not autoload pear classes
        if (preg_match('/^([a-zA-Z][A-Za-z0-9]+)Table$/', $className, $regs))
            $className = $regs[1];
        //memUsage('before-'.$className );
        /** @todo add @ to include before production  */
        include_once $className . '.php';
        //tmUsage('after including ' . $className);
        //memUsage('after-'.$className );
    }    
    public function onDaily(Am_Event $event)
    {
        $this->di->userTable->checkAllSubscriptions();
        $this->di->emailTemplateTable->sendCronExpires();
        $this->di->emailTemplateTable->sendCronMailNotCompleted();
        $this->di->emailTemplateTable->sendCronAutoresponders();
        $this->di->store->cronDeleteExpired();
        Am_Auth_BruteforceProtector::cleanUp();
        if ($this->di->config->get('clear_access_log_days') > 0)
        {
            $dat = sqlDate($this->di->time - $this->di->config->get('clear_access_log_days') * 3600 * 24);
            $this->di->accessLogTable->clearOld($dat);
        }
        $this->di->uploadTable->cleanUp();
        Am_Mail_Queue::getInstance()->cleanUp();
    }
    
    public function onHourly()
    {
        if ($this->di->config->get('email_queue_enabled'))
            Am_Mail_Queue::getInstance()->sendFromQueue();
    }    
    
    /**
     * Generate a string of given length
     * @param int $len
     * @param string $acceptedChars ex. "abcdef1234"
     * @return string
     */
    function generateRandomString($len, $acceptedChars = null) 
    {
        if (@is_readable('/dev/urandom'))
        {
            $f=fopen('/dev/urandom', 'r');
            $urandom=fread($f, 8);
            fclose($f);
        }
        if (@$urandom)
        {
            mt_srand(crc32($urandom));
        } else {
            $stat = @stat(__FILE__);
            if (!$stat)
                $stat = array(php_uname(), __FILE__);
            mt_srand($x=crc32(microtime(true) . implode('+', $stat)));
        }
        if (!$acceptedChars)
            $acceptedChars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPLKJHGFDSAZXCVBNM0123456789';
        $max = strlen($acceptedChars) - 1;
        $security_code = "";
        for ($i=0; $i < $len; $i++)
            $security_code .= $acceptedChars{mt_rand(0, $max)};
        return $security_code;
    }
    
    public function setSessionCookieDomain()
    {
        if (ini_get('session.cookie_domain') != '') return; // already configured
        $domain = @$_SERVER['HTTP_HOST'];
        $domain = strtolower(trim(preg_replace('/(\:\d+)$/', '', $domain)));
        
        if (!$domain) return;
        if ($domain == 'localhost') return $domain;
        if (preg_match('/\.(dev|local)$/', $domain)) return $domain;
        try {
            $min = Am_License::getMinDomain($domain);
        } catch (Exception $e) {
            return;
        }
        @ini_set('session.cookie_domain', ".$min");
    }
    
    public function initSession()
    {
        @ini_set('session.use_trans_sid', false);
        @ini_set('session.cookie_httponly', true);
        
        // lifetime must be bigger than admin and user auth timeout
        $lifetime = (int)ini_get('session.gc_maxlifetime');
        if ($lifetime < ($max = max($this->di->config->get('login_session_lifetime', 120) * 60, 7200)))
        {
            @ini_set('session.gc_maxlifetime', $max);
        }
        
        $this->setSessionCookieDomain();
        if ('db' == $this->di->config->get('session_storage', 'db'))
            Zend_Session::setSaveHandler(new Am_Session_SaveHandler($this->di->db));
        
        if(defined('AM_SESSION_NAME') && AM_SESSION_NAME){
            Zend_Session::setOptions(array('name' => AM_SESSION_NAME));
        }
        
            try {
            Zend_Session::start();
        } catch (Zend_Session_Exception $e){
            if (strpos($e->getMessage(), 'This session is not valid according to')===0) {
                $_SESSION = array();
                Zend_Session::regenerateId();
                Zend_Session::writeClose();
            }
            if (defined('AM_TEST') && AM_TEST) {
                // just ignore error
            } else
                throw $e;
        }
        //disabled as it brokes flash uploads !
        //Zend_Session::registerValidator(new Zend_Session_Validator_HttpUserAgent);
        $this->di->session = new Zend_Session_Namespace('amember');
    }    
    

    /**
     * Converts $path to a file located inside aMember folder (!)
     * to an URL (if possible, relative, if impossible, using ROOT_SURL)
     * @throws Am_Exception_InternalError
     * @return string
     */
    function pathToUrl($path) {
        $p = realpath($path);
        $r = realpath(ROOT_DIR);
        if (strpos($p, $r)!==0)
            throw new Am_Exception_InternalError("File [$p] is not inside application path [$r]");
        $rel = substr($p, strlen($r));
        return REL_ROOT_URL . str_replace('\\', '/', $rel);
    }
    
    function initConstants()
    {
        @ini_set('magic_quotes_runtime', false);
        @ini_set('magic_quotes_sybase', false);
        
        mb_internal_encoding("UTF-8");

        if (!defined('ROOT_URL'))
            define('ROOT_URL', $this->di->config->get('root_url'));
        if (!defined('ROOT_SURL'))
            define('ROOT_SURL', $this->di->config->get('root_surl'));
        if (!defined('AM_WIN'))
            define('AM_WIN', (bool)preg_match('/Win/i', PHP_OS)); // true if on windows
        if (!defined('ROOT_DIR'))
            define('ROOT_DIR', realpath(dirname(dirname(dirname(__FILE__)))));
        if (!defined('DATA_DIR'))
            define('DATA_DIR', ROOT_DIR . '/data');
        if (!defined('AM_VERSION'))
            define('AM_VERSION', '4.1.10');
    }
    
    function __exception404(Zend_Controller_Response_Abstract $response)
    {
        $response
            ->setHttpResponseCode(404)
            ->setBody('HTTP/1.1 404 Not Found')
            ->setRawHeader('HTTP/1.1 404 Not Found')
            ->sendResponse();
    }
    
    function __exception(Exception $e)
    {
        if ($e instanceof Zend_Controller_Dispatcher_Exception 
            && (preg_match('/^Invalid controller specified/', $e->getMessage())))
        {
            return $this->__exception404(Zend_Controller_Front::getInstance()->getResponse());
        }
        if ($e->getCode() == 404)
        {
            return $this->__exception404(Zend_Controller_Front::getInstance()->getResponse());
        }
        
        try {
            static $in_fatal_error; //!
            $in_fatal_error++;
            if ($in_fatal_error > 2){
                echo(nl2br("<b>\n\n".__METHOD__." called twice\n\n</b>"));
                exit();
            }
            if ((defined('AM_DEBUG') && AM_DEBUG) || (APPLICATION_ENV == 'testing')){
                $display_error = "<pre>".($e).':'.$e->getMessage()."</pre>";
            } else {
                if ($e instanceof Am_Exception)
                    $display_error = $e->getPublicError();
                elseif ($e instanceof Zend_Controller_Dispatcher_Exception) {
                    $display_error = ___("Error 404 - Not Found");
                    header("HTTP/1.0 404 Not Found");
                } else
                    $display_error = ___('An internal error happened in the script, please contact webmaster for details');
            }
            $t = new Am_View;
            $t->assign('is_html', true); // must be already escaped here!
            $t->assign('error', $display_error);
            $t->assign('admin_email', $this->di->config->get('admin_email'));
            if (defined('AM_DEBUG') && AM_DEBUG){
                $t->assign('trace', $e->getTraceAsString());
            }
            $t->display("error.phtml");
            // log error
            if (!method_exists($e, 'getLogError') || $e->getLogError())
                $this->di->errorLogTable->logException($e);
        } catch (Exception $e)
        {
            echo ($e)." thrown within the exception handler. Message: ".$e->getMessage()." on line ".$e->getLine();
        }
        exit();
    }
    
    function __error($errno, $errstr, $errfile, $errline) 
    {
        if (!(error_reporting() & $errno)) return;
        $ef = (@APPLICATION_ENV != 'debug') ?
            basename($errfile) : $errfile;
        switch ($errno) {
            case E_RECOVERABLE_ERROR:
                $msg = "<b>RECOVERABLE ERROR:</b> $errstr\nin line $errline of file $errfile";
                if (APPLICATION_ENV == 'debug')
                    echo $msg;
	    	$this->di->errorLogTable->log($msg);
                return true;
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $this->di->errorLogTable->log("<b>ERROR:</b> $errstr\nin line $errline of file $errfile");
                ob_clean();
                amDie("ERROR [$errno] $errstr\nin line $errline of file $ef");
                exit(1);
            case E_USER_WARNING:
            case E_WARNING:
                if (!defined('AM_DEBUG') || !AM_DEBUG) return;
                if (!defined('SILENT_AMEMBER_ERROR_HANDLER') 
                    && !(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'))
                   print("<b>WARNING:</b> $errstr\nin line $errline of file $ef<br />");
                $this->di->errorLogTable->log("<b>WARNING:</b> $errstr\nin line $errline of file $errfile");
                break;
                
            case E_STRICT:
            case E_USER_NOTICE:
            case E_NOTICE:
                if (!defined('AM_DEBUG') || !AM_DEBUG) return;
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
                    return;
                print_rr("<b>NOTICE:</b> $errstr\nin line $errline of file $ef<br />");
            break;
        }
    }

    function getDefaultLocale($addRegion = false) {
        @list($found,) = array_keys(Zend_Locale::getDefault());
        if (!$found) return 'en_US';
        if (!$addRegion) return $found;
        return (strlen($found)<=4) ? ___('_default_locale') : $found;
    }
    
    /**
     * Return (generate if necessary) a constant, random site ID
     * @return string
     */
    function getSiteKey()
    {
        static $key;
        if ($key) 
            return $key;
        $config = $this->di->config;
        if ($key = $config->get('random-site-key'))
            return $key;
        $key = sha1(mt_rand().@$_SERVER['REMOTE_ADDR'].microtime(true));
        Am_Config::saveValue('random-site-key', $key);
        $config->set('random-site-key', $key);
        return $key;
    }
    
    /**
     * Return hash of @link getSiteKey() + $hashString
     * You may use it to not disclose site key to public
     * @example Am_App->getSiteHash('backup-cron')
     * @param type $hashString 
     * @return string [a-zA-Z0-9]{$len}
     */
    function getSiteHash($hashString, $len = 20)
    {
        return $this->hash($this->getSiteKey() . $hashString, $len);
    }
    
    /**
     * Make a site-unique hash of given length
     * @return string [0-9a-zA-Z]
     */
    function hash($string, $len=20)
    {
        if ($len > 20) $len = 20;
        $chars = "0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM";
        $len_chars = strlen($chars);
        $raw = sha1($string, true);
        $ret = "";
        for ($i=0;$i<$len;$i++)
            $ret .= $chars[ ord($raw[$i]) % $len_chars ];
        return $ret;
    }
    
    function dbSync($reportNoChanges = true, $modules = null)
    {
        $nl = empty($_SERVER['REMOTE_ADDR']) ? "\n" : "<br />\n";
        $db = new Am_DbSync();
        $db->parseTables($this->di->db);
        $xml = new Am_DbSync();
        
        $xml->parseXml(file_get_contents(APPLICATION_PATH . '/default/db.xml'));
        if ($modules === null)
            $modules = $this->di->modules->getEnabled();
        foreach ($modules as $module) 
        {
            if (file_exists($file = APPLICATION_PATH . '/' . $module . "/db.xml"))
            {
                print "Parsing XML file: [application/$module/db.xml]$nl";
                $xml->parseXml(file_get_contents($file));
            }
        }
            
        $diff = $xml->diff($db);
        if ($sql = $diff->getSql($this->di->db->getPrefix()))
        {
            print "Doing the following database structure changes:$nl";
            print $diff->render();
            print "$nl"; ob_end_flush();
            $diff->apply($this->di->db);
            print "DONE$nl"; ob_end_flush();
        } elseif ($reportNoChanges) {
            print "No database structure changes required$nl";
        }
        
        $this->etSync($modules);
        $this->di->store->set('db_version', AM_VERSION);
    }
    
    function etSync($modules = null)
    {
        $etFiles = array();
        if ($modules === null)
            $modules = $this->di->modules->getEnabled();
        foreach ($modules as $module) {
            if (file_exists($file = APPLICATION_PATH . '/' . $module . "/email-templates.xml")) 
            {
                $etFiles[$module] = $file;
            }
        }
        $nl = empty($_SERVER['REMOTE_ADDR']) ? "\n" : "<br />\n";
        $t = $this->di->emailTemplateTable;
        $t->importXml(file_get_contents(APPLICATION_PATH . '/default/email-templates.xml'));
        foreach ($etFiles as $module => $file) {
           print "Parsing XML file: [application/$module/email-templates.xml]$nl";
           $t->importXml(file_get_contents($file));
        }   
    }
    
    function readConfig($fn)
    {
        $this->config = require_once $config;
        return $this;
    }
}

function array_remove_value(& $array, $value) {
    foreach ($array as $k => $v)
        if ($v === $value) unset($array[$k]);
}

/** 
 * class to run long operations in portions with respect to time and memory limits
 * callback function must set $context variable - it will be passed back on next
 * call, even after page reload. 
 * when operation is finished, callback function must return boolean <b>true</b>
 * to indicate completion
 */
class Am_BatchProcessor {
    protected $callback;
    protected $tm_started, $tm_finished;
    protected $max_tm;
    protected $max_mem;
    /**
     * If process was explictly stopped from a function
     * @var bool
     */
    protected $stopped = false;

    /**
     *
     * @param type $callback Callback function - must return true when processing finished
     * @param type $max_tm max execution time in seconds
     * @param type $max_mem memory limit in megabytes
     */
    public function __construct($callback, $max_tm = 20, $max_mem = 64) {
        if (!is_callable($callback))
            throw new Am_Exception_InternalError("Not callable callback passed");
        $this->callback = $callback;
        // get max time
        $this->max_tm = ini_get('max_execution_time');
        if ($this->max_tm <= 0 || $this->max_tm > 20)
            $this->max_tm = 20;
        $this->max_tm = min($this->max_tm, $max_tm);
        // get max memory
        $max_memory = strtoupper(ini_get('memory_limit'));
        if ($max_memory != '') {
            $multi = array('K' => 1024, 'M' => 1024 * 1024, 'G' => 1024 * 1024 * 1024);
            if (preg_match('/^(\d+)\s*(K|M|G)/', $max_memory, $regs))
                $max_memory = $regs[1] * $multi[$regs[2]];
            else
                $max_memory = intval($max_memory);
        }
        $this->max_mem = min($max_mem*1024*1024, $max_memory*0.9);
    }
    /**
     * @return true if process finished, false if process was breaked due to limits
     */
    function run( & $context ) {
        $this->tm_started = time();
        $breaked = false;
        $params = array(
                & $context,
                $this
        );
        while (! call_user_func_array($this->callback, $params)) {
            if ($this->isStopped() || !$this->checkLimits()) {
                $breaked = true;
                break;
            }
        }
        $this->tm_finished = time();
        return !$breaked;
    }
    
    function stop()
    {
        $this->stopped = true;
    }
    function isStopped()
    {
        return (bool)$this->stopped;
    }
    
    /**
     * @return bool false if limits are over 
     */
    function checkLimits() {
        $tm_used = time() - $this->tm_started;
        if ($tm_used >= $this->max_tm)
            return false;
        if (memory_get_usage() > $this->max_mem)
            return false;
        return true;
    }
    function getRunningTime() {
        $finish = $this->tm_finished ? $this->tm_finished : time();
        return $finish - $this->tm_started;
    }
}
