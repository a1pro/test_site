<?php
include_once("api.php");
class Am_Protect_Wordpress extends Am_Protect_Databased 
{
    const PLUGIN_STATUS = self::STATUS_PRODUCTION;
    const PLUGIN_REVISION = '4.1.10';

    protected $_autoload_backup = array();
    protected $_current_view;
    protected $_page_title;
    protected $safe_jquery_load = false;
    protected $_wp;
    private $_toSave = array('_POST', '_GET', '_REQUEST');
    private $_savedVars = array();
    

    protected $guessTablePattern = 'users';
    protected $guessFieldsPattern = array(
        'ID', 'user_login', 'user_pass', 'user_nicename', 'display_name'
    );
    protected $groupMode = self::GROUP_MULTI;
    
    public function canAutoCreate()
    {
        return true;
    }

    public function  getLevels(){
        $ret = array();
        for($i=0; $i<=10; $i++){
            $ret[$i] = 'level_'.$i;
        }
        return $ret;
    }
    public function getIntegrationFormElements(HTML_QuickForm2_Container $group) {
        parent::getIntegrationFormElements($group);
        /*
        $options = $this->getLevels();
        $group->addSelect('level', array(), array('options'=>$options))->setLabel('Wordpress Level');
         */
    }


    public function afterAddConfigItems(Am_Form_Setup_ProtectDatabased $form) {
        parent::afterAddConfigItems($form);
        /* 
        $options = $this->getLevels();
        $form->addSelect('protect.wordpress.default_wplevel', array(), array("options" =>$options))
             ->setLabel(array("Default user level", "default level - user will be reset to this access level
                                when no active subscriptions exists (for example all subscriptions expired)
                        "));
         */
        $form->addText('protect.wordpress.folder', array('size'=>70))
                ->setLabel(array($this->getTitle() . ' Folder', "
                 Folder where you have " . $this->getTitle() . " installed"));
        $form->addAdvCheckbox('protect.wordpress.use_wordpress_theme')
                ->setLabel(array('Use Wordpress theme', 'aMember will use theme that you set in wordpress for header and footer'));
        /* $form->addAdvCheckbox('protect.wordpress.network')
                ->setLabel(array('Network Enabled', 'Check this if you have Wordpress Network Enabled')); */

        $form->addText('protect.wordpress.auth_key', array('size'=>70))
          ->setLabel(array($this->getTitle().' Auth Key',"
          AUTH_KEY setting from ".$this->getTitle()." config"));
        $form->addText('protect.wordpress.secure_auth_key', array('size'=>70))
          ->setLabel(array($this->getTitle().' Secure Auth Key',"
          SECURE_AUTH_KEY setting from ".$this->getTitle()." config"));
        $form->addText('protect.wordpress.logged_in_key', array('size'=>70))
          ->setLabel(array($this->getTitle().' Logged In Key',"
          LOGGED_IN_KEY setting from ".$this->getTitle()." config"));
        $form->addText('protect.wordpress.nonce_key', array('size'=>70))
          ->setLabel(array($this->getTitle().' Nonce Key',"
          NONCE_KEY setting from ".$this->getTitle()." config"));

        $form->addText('protect.wordpress.auth_salt', array('size'=>70))
          ->setLabel(array($this->getTitle().' Auth Salt',"
          AUTH_SALT setting from ".$this->getTitle()." config"));
        $form->addText('protect.wordpress.secure_auth_salt', array('size'=>70))
          ->setLabel(array($this->getTitle().' Secure Auth Salt',"
          SECURE_AUTH_SALT setting from ".$this->getTitle()." config"));
        $form->addText('protect.wordpress.logged_in_salt', array('size'=>70))
          ->setLabel(array($this->getTitle().' Logged In Salt',"
          LOGGED_IN_SALT setting from ".$this->getTitle()." config"));
        $form->addText('protect.wordpress.nonce_salt', array('size'=>70))
          ->setLabel(array($this->getTitle().' Nonce Salt',"
          NONCE_SALT setting from ".$this->getTitle()." config"));

    }

    public function getPasswordFormat() {
        return SavedPassTable::PASSWORD_PHPASS;
    }

    public function onAuthSessionRefresh(Am_Event_AuthSessionRefresh $event){
        $this->saveLinksToSession($event->getUser());
    }
    
    public function saveLinksToSession(User $user){
        $resources = $this->getDi()->resourceAccessTable->getAllowedResources($user, 
                ResourceAccess::USER_VISIBLE_TYPES);
        $links = array();
        foreach($resources as $r){
            $links[] = $r->renderLink();
        }
        $this->getDi()->session->amember_links = $links;
    }
    
    public function loginUser(Am_Record $record,$password) {
        
        $cookie_secure = $this->getWP()->get_user_meta($record->pk(), 'use_ssl');
        $this->getWP()->wp_set_auth_cookie($record->pk(), false, $cookie_secure, $record);
        $this->saveLinksToSession($this->getTable()->findAmember($record));
    }

    
    
    public function logoutUser(User $user) {
        $this->getWP()->wp_clear_auth_cookie();
    }

    public function getLoggedInRecord() {
        if(!($user_id = $this->getWP()->wp_validate_auth_cookie())){
            $logged_in_cookie = $this->getWP()->getLoggedInCookie();
            if ( empty($_COOKIE[$logged_in_cookie]) || !$user_id = $this->getWP()->wp_validate_auth_cookie($_COOKIE[$logged_in_cookie], 'logged_in') ) 
                         return;
        }
        $record = $this->getTable()->load($user_id, false);
        return $record;
    }

    public function parseExternalConfig($path) {
        // Now set config fields as required by aMember;
        if (!is_file($config_path = $path . "/wp-config.php") || !is_readable($config_path))
            throw new Am_Exception_InputError("This is not a valid " . $this->getTitle() . " installation");
        // Read config;
        $config = file_get_contents($config_path);
        $config = preg_replace(array("/include_once/", "/require_once/", "/include/", "/require/"), "trim", $config);
        $config = preg_replace(array("/\<\?php/", "/\?\>/"), "", $config);
        eval($config);
        return array(
            'db'                =>  DB_NAME,
            'prefix'            =>  $table_prefix,
            'host'              =>  DB_HOST,
            'user'              =>  DB_USER,
            'pass'              =>  DB_PASSWORD,
            'folder'            =>  $path,
            'auth_key'          =>  AUTH_KEY,
            'secure_auth_key'   =>  SECURE_AUTH_KEY,
            'logged_in_key'     =>  LOGGED_IN_KEY,
            'nonce_key'         =>  NONCE_KEY,
            'auth_salt'         =>  AUTH_SALT,
            'secure_auth_salt'  =>  SECURE_AUTH_SALT,
            'logged_in_salt'    =>  LOGGED_IN_SALT,
            'nonce_salt'        =>  NONCE_SALT,
        );
    }

    public function getAvailableUserGroups() 
    {
        $ret = array();
        foreach($this->getWP()->get_roles() as $rname =>$r){
            $g = new Am_Protect_Databased_Usergroup(array(
                                            'id'        =>  $rname,
                                            'title'     =>  $r['name'],
                                            'is_banned' =>  null,
                                            'is_admin'  =>  (in_array('level_10', array_keys($r['capabilities'])) ? $r['capabilities']['level_10']:0)
                ));
            $ret[$g->getId()] = $g;
        }
        return $ret;
    }

    public function createTable() 
    {
        $table = new Am_Protect_Wordpress_Table($this, $this->getDb(), '?_users', 'ID');
        $table->setFieldsMapping(array(
            array(Am_Protect_Table::FIELD_LOGIN, 'user_login'),
            array(Am_Protect_Table::FIELD_LOGIN, 'user_nicename'),
            array(Am_Protect_Table::FIELD_LOGIN, 'display_name'),
            array(Am_Protect_Table::FIELD_EMAIL, 'user_email'),
            array(Am_Protect_Table::FIELD_PASS, 'user_pass'),
            array(Am_Protect_Table::FIELD_ADDED_SQL, 'user_registered')
        ));
        return $table;
    }

    public function onInitFinished() {
        /* @var $b Bootstrap */
    }
    
    protected function saveGlobalVars(){
        foreach($this->_toSave as $k){
            $this->_savedVars[$k] = $GLOBALS[$k];
        }
        $this->_savedVars['_SESSION'] = array();
        foreach($_SESSION as $k=>$v){
            $this->_savedVars['_SESSION'][$k] = $v;
        }
    }
    protected function restoreGlobalVars(){
        foreach($this->_toSave as $k){
            $GLOBALS[$k] = $this->_savedVars[$k];
        }
        foreach($this->_savedVars['_SESSION'] as $k=>$v){
            $_SESSION[$k] = $v;
        }
        
    }
    public function onGlobalIncludes(Am_Event_GlobalIncludes $e) {
        if ($this->isConfigured() && $this->getConfig('use_wordpress_theme')) {
            // Disable autoload; 
            //Save superglobals to avoid modification in wordpress.
            $this->saveGlobalVars();
            foreach (spl_autoload_functions () as $f) {
                $this->_autoload_backup[] = $f;
                spl_autoload_unregister($f);
            }
            // Add theme folder to include path; 
            define("WP_CACHE", false);
            define("WP_USE_THEMES", false);
            $e->add($this->config['folder'] . "/wp-blog-header.php");
        }
    }

    public function onglobalIncludesFinished() {
        if ($this->isConfigured() && $this->getConfig('use_wordpress_theme')) {
            foreach ($this->_autoload_backup as $f) {
                spl_autoload_register($f);
            }
            // Restore superglobals;
            $this->restoreGlobalVars();
            // Change template path only if wordpress was included. 
            if(function_exists('status_header')){
                $path = defined("TEMPLATEPATH") ?  TEMPLATEPATH : 'default';
                $path = array_pop(preg_split('/[\/\\\]/',$path));
                if(preg_match("[0-9]", $path) && is_file(dirname(__FILE__). '/'.$path.'/layout.phtml')){
                    $path = $path; 
                }else if(preg_match("/^([a-zA-Z]+)/", $path, $regs) && is_file(dirname(__FILE__). '/'.$regs[1].'/layout.phtml')){
                    $path = $regs[1];
                }else{
                    $path = 'default';
                }
    
                $this->getDi()->viewPath = array_merge($this->getDi()->viewPath, array(dirname(__FILE__) . '/'.$path));
                // Setup scripts and path required for wordpress;
                status_header(200); // To prevent 404 header from wordpress;
            }
            
        }
    }

    function addHeader() {
        $this->_current_view->printLayoutHead(false, $this->safe_jquery_load);
    }

    function addTitle() {
        return $this->_page_title . " | ";
    }

    function startLayout(Am_View $view, $title, $safe_jquery_load=false) {
        
        $this->_current_view = $view;
        $this->_page_title = $title;
        $this->safe_jquery_load = $safe_jquery_load;
        add_action("wp_head", array($this, "addHeader"));
        add_filter("wp_title", array($this, "addTitle"), 10);
    }
    
    function getWP(){
        if(!$this->_wp){
            $this->_wp =   new WordpressAPI($this);
        }
        return $this->_wp;
    }


    function calculateLevels(User $user = null, $addDefault = false)
    {
        throw new Am_Exception('Deprecated!');
        
        // we have got no user so search does not make sense, return default group if configured
        $levels = array();
        if ($user && $user->pk())
        {
            foreach ($this->getIntegrationTable()->getAllowedResources($user, $this->getId()) as $integration)
            {
                $vars = unserialize($integration->vars);
                $levels[] = $vars['level'];
            }
        } 
        if(!$levels){
            return $this->getConfig('default_wplevel', 0);
        }else{
            return max($levels);
        }
    }
    
    function calculateGroups(User $user = null, $addDefault = false) {
        $groups = parent::calculateGroups($user, $addDefault);
        if($groups && $user)
        {
            $add_group = ($this->getIntegrationTable()->getAllowedResources($user, $this->getId()) ? 'amember_active' : 'amember_expired');
            if(!in_array($add_group, $groups)) $groups[] = $add_group; 
        }
        return $groups; 
    }
    function getReadme(){
        return <<<CUT
<b>Wordpress plugin readme</b>
1. Specify full path to folder where you have wordpress script installed. 
   (You can use "browse" to select it)
2. Check database settings and click "Continue..." button
3. Check all configuration settings, set Default level and Default user level 
   if necessary. Click "Save" button. 
   Do not change any settings if you are not sure.
4. Go to aMember CP -> Products -> Protect Content -> Integrations and setup protection. 

<b>Optionally</b> you can install aMember plugin into wordpress in order to 
protect content in wordpress itself: 
1. Upload plugin files from 
   /amember/application/default/plugins/protect/wordpress/upload_to_wordpress folder 
   into your /wordpress folder (keep folders structure)
2. Enable amember4 plugin from your Wordpress Admin -> Plugins
3. In Wordpress Admin -> aMember -> Settings select folder where you have aMember installed.

CUT;
    }
    
}


class Am_Protect_Wordpress_Table extends Am_Protect_Table{
    function  __construct(Am_Protect_Databased $plugin, $db = null, $table = null, $recordClass = null, $key = null) {
        parent::__construct($plugin, $db, $table, $recordClass, $key);
    }
    
    function updateMetaTags(Am_Record $record, User $user){
        $this->_plugin->getWP()->update_user_meta($record->pk(), 'first_name',$user->name_f);
        $this->_plugin->getWP()->update_user_meta($record->pk(), 'last_name',$user->name_l);
        $this->_plugin->getWP()->update_user_meta($record->pk(), 'nickname', $user->login);
        
    }

    function updateLevel(Am_Record $record, $level){
        $this->_plugin->getWP()->update_user_meta($record->pk(), $this->_plugin->getConfig('prefix')."user_level", $level);
    }
    function  insertFromAmember(User $user, SavedPass $pass, $groups) {
        $record = parent::insertFromAmember($user, $pass, $groups);
        $this->updateMetaTags($record, $user);
        return $record;
    }
    function  updateFromAmember(Am_Record $record, User $user, $groups) {
        parent::updateFromAmember($record, $user, $groups);
        $this->updateMetaTags($record, $user);
    }

    function  getGroups(Am_Record $record) {
        $groups = $this->_plugin->getWP()->get_user_meta($record->pk(), $this->_plugin->getConfig('prefix')."capabilities");
        if($groups === false) return array();
        return array_keys($groups);
    }

    function  setGroups(Am_Record $record, $groups) {
        $old_groups = $this->_plugin->getWP()->get_user_meta($record->pk(), $this->_plugin->getConfig('prefix')."capabilities");
        $ret = array();
        foreach($groups as $k){
            if($k) $ret[$k] = 1;
        }
        $this->_plugin->getWP()->update_user_meta($record->pk(), $this->_plugin->getConfig('prefix')."capabilities", $ret);
        $this->updateLevel($record, $this->getLevelFromCaps($record));
        return $ret;
    }
    
    function level_reduction( $max, $item ) {
        if ( preg_match( '/^level_(10|[0-9])$/i', $item, $matches ) ) {
            $level = intval( $matches[1] );
            return max( $max, $level );
	} else {
            return $max;
	}
    }
    
    function getLevelFromCaps(Am_Record $record){
        $roles = $this->_plugin->getWP()->get_roles();
        $allcaps = array();
        foreach($this->getGroups($record) as $g){
            $allcaps = array_merge($allcaps, $roles[$g]['capabilities']);
        }
        $level = array_reduce( array_keys( $allcaps ), array( &$this, 'level_reduction' ), 0 );
        return $level;
    }
    
}
