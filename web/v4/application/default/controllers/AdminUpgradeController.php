<?php

/*
 * TODO use version_compare() to filter out already installed modules and core updates
 * TODO make manifest.xml for upgrade - to delete files, chmod and file hashes()
 */

abstract class Am_FileConnector 
{
    protected $options = array();
    protected $permFile = 0644;
    protected $permDir  = 0755;
    public function __construct(array $options)
    {
        $this->options = $options;
    }
    /**
     * @return @bool false on failure, true on ok
     */
    abstract function connect();
    abstract function cwd();
    abstract function put($from, $to);
    abstract function get($from, $to);
    abstract function ls($dir);
    abstract function chdir($dir);
    abstract function mkdir($dir);
    /**
     * @return string last error message
     */
    abstract function getError();
}

class Am_FileConnector_Local extends Am_FileConnector
{
    public function connect() { return true; }
    public function cwd() { return getcwd(); }
    public function get($from, $to) { return copy($from, $to); }
    public function getError() { }
/** @todo implement normally ! */
    public function ls($dir) { 
        $d = opendir($dir);
        if (!$d) return false;
        $ret = array();
        while ($f = readdir($d))
        {
            $ret[$f] = stat($dir . DIRECTORY_SEPARATOR . $f);
        }
        closedir($d);
        return $ret;
    }
    public function mkdir($dir) { return @mkdir($dir) && (chmod($dir, $this->permDir) || true); }
    public function put($from, $to) { return copy($from,$to) && (chmod($to, $this->permFile) || true); }
    public function chdir($dir) { return chdir($dir); }
}

class Am_FileConnector_Ftp extends Am_FileConnector
{
    /** @var ftp */
    protected $ftp;
    public function connect()
    {
        require_once 'class-ftp.php';
        $this->ftp = new ftp(false);
        if (!$this->ftp->connect($this->options['host']))
        {
            $this->ftp->PushError('connect', "Could not connect to host");
            return false;
        }
        if (!$this->ftp->login($this->options['user'], $this->options['pass']))
        {
            $this->ftp->PushError('auth', "Authentication failed");
            return false;
        }
        return true;
    }
    public function cwd()
    {
        $ret = $this->ftp->pwd();
        $ret = rtrim($ret, "/\\");
        return $ret;
    }
    public function put($from, $to)
    {
        return $this->ftp->put($from, $to) && ($this->ftp->chmod($to, $this->permFile) || true);
    }
    public function get($from, $to)
    {
        return $this->ftp->get($from, $to);
    }
    public function ls($dir)
    {
        return $this->ftp->dirlist($dir);
    }
    public function chdir($dir)
    {
        return $this->ftp->chdir($dir);
    }
    public function mkdir($dir)
    {
        return $this->ftp->mkdir($dir) && ($this->ftp->chmod($dir, $this->permDir) || true);
    }
    public function getError()
    {
        $err = $this->ftp->PopError();
        if ($err) return $err['msg'];
    }
}

class AdminUpgradeController extends Am_Controller
{
    const AM_ADMIN_KEY = 'am_admin_key';
    protected $allowedDomains = array(
        'www.amember.com',
        'www.cgi-central.net',
    );
    protected $upgrades = array();
    protected $steps = array();
    
    // do not display default layout with content
    protected $noDisplay = false;
    
    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);
        $this->steps = array(
            0 => array('stepSetCookie', ___('Create Session Key')),
            1 => array('stepLoadUpgradesList', ___('Get Available Upgrades List')),
            2 => array('stepConfirmUpgrades', ___('Choose Upgrades to Install')),
            3 => array('stepGetRemoteAccess', ___('Retreive Access Parameters if necessary')),
            4 => array('stepDownload', ___('Download Upgrades')),
            5 => array('stepUnpack', ___('Unpack Upgrades')),
            6 => array('stepSetMaint', ___('Enter Maintenance Mode')),
            7 => array('stepCopy', ___('Copy Upgrades')),
            8 => array('stepAutoEnable', ___('Enable plugins if necessary')),
            9 => array('stepUpgradeDb', ___('Upgrade Database')),
            10 => array('stepUnsetMaint', ___('Quit Maintenance Mode')),
        );
    }
    
    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->isSuper();
    }
    public function indexAction()
    {
        if (@$_GET['reset'])
        {
            $this->getSession()->unsetAll();
        }
        $upgradeProcess = new Am_BatchProcessor(array($this, 'doUpgrade'));
        $status = null;
        if ($upgradeProcess->run($status))
        {
            $this->view->title = ___('Upgrade Finished');
            $this->view->content .= 
                '<h2>' .  ___('Upgrade Finished') . '</h2>' .
                "<input type='button' value='".___('Back')."' onclick='window.location=window.rootUrl+\"/admin\"'/>";
        } else {
            $this->view->title = ___('Upgrade');
            $this->view->content .= 
                "<br /><input type='button' onclick='window.location=\"admin-upgrade\"' value='".___('Continue')."' />";
        }
        if (!$this->noDisplay)
            $this->view->display('admin/layout.phtml');
    }
    public function stepConfirmUpgrades()
    {
        $form = new Am_Form_Admin;
        $upgrades = $form->addGroup('upgrades', array('class' => 'no-label'));
        $options = array();
        $static = '';
        $upgrades->addStatic()->setContent('<h2>'.___('Available Upgrades').'</h2>');
        foreach ($this->getSession()->upgrades as $k => $upgrade)
        {
            if (!empty($upgrade->new))
            {
                $upgrades->addStatic()->setContent('<br /><h2>'.___('New Modules Available').'</h2>');
            }
            $text = sprintf('%s%s, '.___('version').' %s - %s' . '<br />',
                '<b>'.$upgrade->title.'</b>',
                $upgrade->type =='core' ? '' : sprintf(' [%s - %s]', $upgrade->type, $upgrade->id),
                '<i>'.$upgrade->version.'</i>', '<i>'.amDate($upgrade->date).'</i>');
            $upgrades->addCheckbox($k, empty($upgrade->checked) ? null : array('checked' => 'checked'))->setContent($text);
            $static .= "<div class='changelog' style='' data-for='$k'><pre>".
                $upgrade->text.
                       "</pre></div>\n";
            $upgrades->addStatic()->setContent($static);
        }
        
        $form->addCheckbox('_confirm', array('class' => 'no-label'))
            ->setContent(___('I understand that upgrade may overwrite customized PHP files and templates, I have already made a backup of aMember Pro folder and database'))
            ->addRule('required');
        
        $form->addSubmit('', array('value' => ___('Install Updates')));
        if ($form->isSubmitted() && $form->validate())
        {
            $confirmed = array_keys(array_filter($upgrades->getValue()));
            if (!$confirmed)
            {
                $this->view->title = ___('No upgrades to install');
                $this->view->content = '<a href="'.REL_ROOT_URL.'/admin">'.___('Back').'</a>';
                return false;
            }
            $upgrades = $this->getSession()->upgrades;
            foreach ($upgrades as $k => $v)
                    if (!in_array($k, $confirmed))
                        unset($upgrades[$k]);
            $this->getSession()->upgrades = $upgrades;
            return true;
        } else {
            $this->view->content = (string)$form;
            $this->view->title   = ___('Choose Upgrades to Install');
            $this->view->display('admin/layout.phtml');
            $this->noDisplay = true;
            return false;
        }
    }
    public function stepSetCookie()
    {
        if (!$this->_request->getCookie(self::AM_ADMIN_KEY))
        {
            unset($this->getSession()->admin_remote_access);
            $_COOKIE[self::AM_ADMIN_KEY] = $this->getDi()->app->generateRandomString(56);
            $this->setCookie(self::AM_ADMIN_KEY, $_COOKIE[self::AM_ADMIN_KEY], $this->getDi()->time + 3600);
        }
        return true;
    }
    
    public function checkAction()
    {
        //$this->getDi()->store->delete('upgrades-list');
        // read/write to am_store and handle dismission of upgrades and new plugins notifications
        // check if saved record exists
        $load = $this->getDi()->store->getBlob('upgrades-list');
        if (!empty($load)) 
        {
            $upgrades = unserialize($load);
        } else {
            $upgrades = array('_loaded' => null, '_dismissed' => null);
        }
        if ($upgrades['_loaded'] < (time() - 3600*2))
        {
            $upgrades['items'] = $this->loadUpgradesList(false);
            $upgrades['_loaded'] = time();
            $this->getDi()->store->setBlob('upgrades-list', serialize($upgrades));
        }
        $ret = array();
        foreach ($upgrades['items'] as $upgrade)
        {
            if (!empty($upgrades['_dismissed']
                [$upgrade->type]
                [$upgrade->id]
                [$upgrade->version]
                [$this->getDi()->authAdmin->getUserId()]
                ))
                continue;
            $upgrade->notice = ($upgrade->is_new) ? 'New Module Available: ' : 'Upgrade Available';
            $upgrade->notice .= sprintf(': %s [%s] ', $upgrade->title, $upgrade->version);
            $upgrade->dismiss_url = REL_ROOT_URL . '/admin-upgrade/dismiss?' 
                . http_build_query(array(
                    'type' => $upgrade->type,
                    'id'   => $upgrade->id,
                    'version' => $upgrade->version,
                ));
            $ret[] = $upgrade;
        }
        //$this->setCookie('am_upgrade_checked', 1, '+1 hour');
        return $this->ajaxResponse($ret);
    }
    
    public function dismissAction()
    {
        $load = $this->getDi()->store->getBlob('upgrades-list');
        if (!empty($load)) 
        {
            $upgrades = unserialize($load);
            $upgrades['_dismissed']
                [$this->_request->get('type')]
                [$this->_request->get('id')]
                [$this->_request->get('version')]
                [$this->getDi()->authAdmin->getUserId()] = time();
            $this->getDi()->store->setBlob('upgrades-list', serialize($upgrades));
        }
    }
    
    public function getTokenAction()
    {
        $form = new Am_Form_Admin;
        $form->addStatic()->setContent('Your [www.amember.com] account information');
        $login = $form->addText('login', array('size'=>40))->setLabel(___('Username or e-mail adddress'));
        $login->addRule('required');
        $form->addPassword('pass', array('size'=>40))->setLabel(___('Password'))
            ->addRule('required');
        $form->addSubmit('', array('value' => ___('Login')));
        
        if ($form->isSubmitted() && $form->validate())
        {
            $req = new Am_HttpRequest('http://www.amember.com/check-upgrades.php', Am_HttpRequest::METHOD_POST);
            $req->addPostParameter('do', 'get-token');
            $vars = $form->getValue();
            $req->addPostParameter('login', $vars['login']);
            $req->addPostParameter('pass', $vars['pass']);
            $req->addPostParameter('license', $this->getDi()->config->get('license'));
            try {
                $response = $req->send();
                if ($response->getStatus() == '401')
                {
                    throw new HTTP_Request2_Exception("Authentication failed: " . $response->getBody());
                }
                if ($response->getStatus() != '200')
                    throw new HTTP_Request2_Exception("Wrong status: " . $response->getStatus());
                if ($response->getBody() <= 10000)
                    throw new HTTP_Request2_Exception("Wrong token returned: not a number" . Am_Controller::escape($response->getBody()));
                $ok = true;
            } catch (HTTP_Request2_Exception $e) {
                $login->setError('Cannot get token: ' . $e->getMessage());
                $ok = false;
            }
            if ($ok)
            {
                $this->getDi()->store->set('amember-site-auth-token', (int)$response->getBody());
                $this->_redirect('admin-upgrade');
            }
        } 
        $this->view->title = ___('Account Verification');
        $this->view->content = (string)$form;
        $this->view->display('admin/layout.phtml');
    }
    
    public function stepLoadUpgradesList()
    {
        if (($ret = $this->loadUpgradesList(true)) === false)
            return false;
        $this->getSession()->upgrades = $ret;
        if (!$this->getSession()->upgrades)
            throw new Am_Exception_InputError(___("No Updates Available"));
        return true;
    }
    public function stepGetRemoteAccess()
    {
        if (!$this->needsRemoteAccess())
        {
            $this->storeRemoteAccess(array('method' => 'local', 'root' => ROOT_DIR, '_tested' => true ));
            return true;
        }
        return ($this->askRemoteAccess());
    }
    public function doUpgrade(& $context, Am_BatchProcessor $batch)
    {
        $session = $this->getSession();
        if (empty($session->step)) $this->getSession()->step = 0;
        do {
            $currentOperation = $this->steps[$session->step][0];
            $start = (int)@$session->start;
            $this->outStepHeader();
            $ret = call_user_func_array(array($this, $currentOperation), array($batch, & $start));
            $session->start = $start;
            if (!$ret) 
            {
                $batch->stop();
                return false;
            }
            $this->outText(___('Done') . "<br />\n");
            $session->step = $session->step + 1;
            if ($session->step >= count($this->steps))
            {
                $session->unsetAll();
                return true;
            }
            
        } while ($batch->checkLimits());
    }
    protected function outStepHeader()
    {
        $step = $this->getSession()->step;
        $title = $this->steps[$step][1];
        $out = sprintf(___('Step %d of %d', $step+1, count($this->steps)));
        $out .= ' - ' . $title;
        $this->view->content .= "<h2>".$out."</h2>\n";
    }
    protected function outText($text)
    {
        $this->view->content .= $text;
    }
    public function getSession()
    {
        static $session;
        if (empty($session))
        {
            $session = new Zend_Session_Namespace('amember_upgrade');
            $session->setExpirationSeconds(3600);
        }
        return $session;
    }
    function stepDownload(Am_BatchProcessor $batch, & $start)
    {
        $ctx = stream_context_create(array('http' => array('timeout' => 5)));
        foreach ($this->getSession()->upgrades as $k => & $upgrade)
        {
            if (!empty($upgrade->upload_id))
                continue;
            if (!$batch->checkLimits())
            {
                $start = $k;
                return false;
            }
            $fn = tempnam(DATA_DIR, '.upgrade.');
            $url = $upgrade->url;
            $parsed = parse_url($url);
            if ($parsed['scheme'] != 'http') 
                throw new Am_Exception_Security("Strange upgrade URL scheme: ".Am_Controller::escape($parsed['scheme']));
            if (!in_array($parsed['host'], $this->allowedDomains)) 
                throw new Am_Exception_Security("Strange upgrade URL host: ".Am_Controller::escape($parsed['host']));
            $remote = fopen($url, 'r', null, $ctx);
            if (!$remote)
            {
                $this->view->title = ___('Upgrade Download Problem');
                $this->view->content = ___('Could not download file [%s]. Error %s. Please %stry again%s later.',
                    Am_Controller::escape($url),
                    'connection problem',
                    '<a href="admin-upgrade?tm='.time().'">', '</a>');
                $this->view->display('admin/layout.phtml');
                return false;
            }
            stream_set_timeout($remote, 5);
            $local = fopen($fn, 'w');
            if (!($size = stream_copy_to_stream($remote, $local)))
            {
                fclose($local); unlink($fn);
                $info = stream_get_meta_data($fp);
                $this->view->title = ___('Upgrade Download Problem');
                $this->view->content = ___('Could not download file [%s]. Error %s. Please %stry again%s later.',
                    Am_Controller::escape($url),
                    'download problem',
                    '<a href="admin-upgrade?tm='.time().'">', '</a>');
                $this->view->display('admin/layout.phtml');
                return false;
            }
            $upload = $this->getDi()->uploadRecord;
            $upload->name = basename($fn);
            $upload->path = basename($fn);
            $upload->prefix = 'upgrade';
            $upload->uploaded = time();
            $upload->desc = $upgrade->title .' '.$upgrade->version;
            $upload->insert();
            $upgrade->upload_id = $upload->pk();
            $this->outText("Downloaded [$url] - " . $upload->getSizeReadable() . '<br />');
        }
        return true; // force page load
    }
    function _tarError(PEAR_Error $error)
    {
        throw new Am_Exception_InputError('Upgrade unpacking problem: ' . $error->getMessage());
    }
    function stepUnpack(Am_BatchProcessor $batch)
    {
        foreach ($this->getSession()->upgrades as $k => $upgrade)
        {
            $upgrade->dir = null;
            if (!empty($upgrade->dir)) continue; // already unpacked?
            $record = $this->getDi()->uploadTable->load($upgrade->upload_id);
            $tar = new Archive_Tar($fn = $record->getFullPath());
            $upgrade->dir = DATA_DIR . DIRECTORY_SEPARATOR . $record->getFilename() . '-unpack';
            if (!mkdir($upgrade->dir))
            {
                throw new Am_Exception_InputError("Could not create folder to unpack downloaded archive: [{$upgrade->dir}]");
                unset($upgrade->dir);
            }
            $tar->setErrorHandling(PEAR_ERROR_CALLBACK, array($this, '_tarError'));
            try {
                if (!$tar->extract($upgrade->dir))
                   throw new Am_Exception_InputError("Could not unpack downloaded archive: [$fn] to [{$upgrade->dir}]");
            } catch (Exception $e) {
                unset($upgrade->dir);
                @rmdir($upgrade->dir);
            }
            // normally we delete uploaded archive
            $record->delete();
            unset($upgrade->upload_id);
        }
        return true;
    }
    function stepSetMaint()
    {
        $this->getSession()->maintenance_stored = $this->getDi()->config->get('maintenance');
        Am_Config::saveValue('maintenance', 'Briefly unavailable for scheduled maintenance. Check back in a minute.');
        return true;
        // make the string available for translation
        ___('Briefly unavailable for scheduled maintenance. Check back in a minute.');
    }
    function stepUnsetMaint()
    {
        Am_Config::saveValue('maintenance', @$this->getSession()->maintenance_stored);
        $this->getDi()->store->delete('upgrades-list');
        return true;
    }
    function stepCopy(Am_BatchProcessor $batch)
    {
        @set_time_limit(600);
        $info = $this->loadRemoteAccess();
        $class = 'Am_FileConnector_' . ucfirst(toCamelCase($info['method']));
        $connector = new $class($info);
        if (!$connector->connect())
        {
            $this->outText('Connection error: ' . Am_Controller::escape($connector->getMessage()));
            return false;
        }
        if (!$connector->chdir($info['root']))
        {
            $this->outText('Could not chroot to root folder: [' . Am_Controller::escape($info['root']) . ']');
            return false;
        }
        foreach ($this->getSession()->upgrades as $k => $upgrade)
        {
            if (empty($upgrade->dir)) continue;
            
            $dir = $upgrade->dir . DIRECTORY_SEPARATOR . 'amember' . DIRECTORY_SEPARATOR;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                                              RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $file)
            {
                if ($file->getFileName() == '.' || $file->getFileName() == '..') continue;
                if (!strpos($file->getPathName(), $strip = $dir))
                {
                    new Am_Exception_InputError(sprintf('Could not strip local root prefix: [%s] from fn [%s]',
                        $strip, $file->getPathName()));
                }
                // path relative to amember root
                $path = substr($file->getPathName(), strlen($strip));
                if ($file->isDir())
                {
                    if (!$connector->mkdir($path) && !$connector->ls($path))
                    {
                        $this->outText('Could not create folder [' . Am_Controller::escape($path) . ']<br />' . $connector->getError());
                        return false;
                    }
                    $this->outText('created folder ' . Am_Controller::escape($path) . "<br />\n");
                } else {
                    if (!$connector->put($file->getPathName(), $path))
                    {
                        $this->outText('Could not copy file ['
                            . Am_Controller::escape($file->getPathName()) 
                            . '] to remote [' . Am_Controller::escape($path)
                            . '] ' . $connector->getError());
                        return false;
                    }
                    $this->outText('copy file ' . Am_Controller::escape($path) . "<br />\n");
                }
            }
            // remove localdirectory and files
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                                              RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $file)
            {
                if ($file->getFileName() == '.' || $file->getFileName() == '..') continue;
                if ($file->isDir())
                    rmdir($file->getPathName());
                else
                    unlink($file->getPathName());
            }
            rmdir($dir);
            rmdir($upgrade->dir);
            unset($upgrade->dir);
            
            if (!$batch->checkLimits())
            {
//                $batch->stop();
//                return false;
            }
        }
        return true;
    }
    
    
    /**
     * @todo ELIMINATE duplication source (Am_Form_Setup_Plugins)
     */
    function stepAutoEnable()
    {
        foreach ($this->getSession()->upgrades as $upgrade)
        {
            if (empty($upgrade->auto_enable)) continue;
            $type = $upgrade->type;
            if ($type == 'module') $type='modules';
            $pm = $this->getDi()->plugins[$type];
            if (!$pm) continue;
            $configKey = $type == 'modules' ? 'modules' : ('plugins.'.$type);
            $enabled = (array)$this->getDi()->config->get($configKey, array());
            if (!in_array($upgrade->id, $enabled))
            {
                if ($pm->load($upgrade->id))
                {
                    $class = $pm->getPluginClassName($upgrade->id);
                    try {
                        call_user_func(array($class, 'activate'), $upgrade->id, $type);
                    } catch(Exception $e) {
                        $this->getDi()->errorLogTable->logException($e);
                        trigger_error("Error during plugin [$upgrade->id] activation: " . get_class($e). ": " . $e->getMessage(),E_USER_WARNING);
                        continue;
                    }
                    ///
                    $enabled[] = $upgrade->id;
                    $list = Am_Config::saveValue($configKey, $enabled);
                    if ($type == 'modules')
                    {
                        // to run upgrade db with new module
                        $this->getDi()->config->set('modules', $enabled);
                    }
                }
            }
        }
        return true;
    }
    
    function stepUpgradeDb()
    {
        ob_start();
        $this->getDi()->app->dbSync(true);
        $this->outText(ob_get_clean());
        return true;
    }
    function loadUpgradesList($requireAuth = false)
    {
        $req = new Am_HttpRequest('http://www.amember.com/check-upgrades.php', Am_HttpRequest::METHOD_POST);
        $req->setConfig('connect_timeout', 5);
        $req->setConfig('timeout', 15);
        $req->addPostParameter('am-version', AM_VERSION);
        foreach ($this->getDi()->plugins as $type => $pm)
            foreach ($pm->getEnabled() as $v)
                $req->addPostParameter('plugins['.$type.']['.$v.']', $pm->loadGet($v)->getVersion());
        $req->addPostParameter('php-version', PHP_VERSION);
        $req->addPostParameter('mysql-version', $this->getDi()->db->selectCell("SELECT VERSION()"));
        $req->addPostParameter('root-url', ROOT_URL);
        $req->addPostParameter('root-surl', ROOT_SURL);
        $req->addPostParameter('license', $this->getConfig('license'));
        $token = $this->getDi()->store->get('amember-site-auth-token');
        if (!$requireAuth)
            $token = 'TRIAL';
        elseif (!$token)
            $this->_redirect('admin-upgrade/get-token');
        $req->addPostParameter('token', $token);
        //
        try {
            $response = $req->send();
            if ($response->getStatus() == 401)
            {
                $this->_redirect('admin-upgrade/get-token');
            }
        } catch (HTTP_Request2_Exception $e) {
            $this->view->title = ___('Update Error');
            $this->view->content = ___('Could not fetch upgrades list from remote server. %sTry again%', 
                '<a href="admin-upgrades">', '</a>');
            $this->view->display('admin/layout.phtml');
            return false;
        }
        if ($response->getStatus() != '200')
        {
            throw new Am_Exception_InternalError(___("Could not fetch upgrades list. Connection error [%s]",
                $response->getReasonPhrase()));
        }
        $xml = new SimpleXMLElement($response->getBody());
        $ret = array();
        foreach ($xml->item as $u)
        {
            $el = new stdclass;
            foreach ($u->attributes() as $k => $v)
                $el->$k = (string)$v;
            $el->text = (string)$u;
            $el->text = strip_tags($el->text, '<li><ul><b><i><p><hr><br>');
            $ret[] = $el;
        }
        return $ret;
    }
    
    function needsRemoteAccess()
    {
        if ( !function_exists('getmyuid') && !function_exists('fileowner')) return false;
        $fn = DATA_DIR . '/temp-write-test-' . time();
        $f = @fopen($fn, 'w');
        if (!$f )
            throw new Am_Exception_InternalError("Could not create test file - check if data dir is writeable");
        if ( getmyuid() == @fileowner($fn) ) return false;
        @fclose($f);
        @unlink($fn);
        return true;
    }
    
    function askRemoteAccess()
    {
        $form = new Am_Form_Admin;
        $info = $this->loadRemoteAccess();
        if ($info && !empty($info['_tested'])) 
            return true;
        if ($info)
            $form->addDataSource(new Am_Request($info));
        $method = $form->addSelect('method', null, array('options' => array('ftp' => 'FTP', /*'ssh' => 'SSH'*/)))
            ->setLabel(___('Access Method'));;
        $form->addText('host')->setLabel(___('Hostname'))->addRule('required')->addRule('regex', 'Incorrect hostname value', '/^[\w\._-]+$/');
        $form->addText('user')->setLabel(___('Username'))->addRule('required');
        $form->addPassword('pass')->setLabel(___('Password'));
//        $form->addTextarea('ssh_public_key')->setLabel(___('SSH Public Key'));
//        $form->addTextarea('ssh_private_key')->setLabel(___('SSH Private Key'));
        $form->addSubmit('', array('value' => ___('Continue')));
        $form->addScript()->setScript(<<<CUT
$(function(){
    $('#method-0').change(function(){
        $('#ssh_public_key-0,#ssh_private_key-0').closest('.row').toggle( $(this).val() == 'ssh' );
    }).change();
});
CUT
        );
        $error = null;
        $vars = $form->getValue();
        if ($form->isSubmitted() && $form->validate() && !($error = $this->tryConnect($vars)))
        {
            $vars['_tested'] = true;
            $this->storeRemoteAccess($vars);
            return true;
        } else {
            //$this->view->title = ___("File Access Credentials Required");
            $this->view->title = ___('Upgrade');
            $this->view->content = "";
            $this->outStepHeader();
            if ($error) $method->setError($error);
            $this->view->content .= (string)$form;
            $this->view->display('admin/layout.phtml');
            $this->noDisplay = true;
        }
    }
    function _getCrypt()
    {
        // encrypt saved info with random temporary encryption key
        return new Crypt_Blowfish($_COOKIE[self::AM_ADMIN_KEY]);
    }
    function storeRemoteAccess(array $info)
    {
        $this->getSession()->admin_remote_access = base64_encode($this->_getCrypt()->encrypt(serialize($info)));
        return true;
    }
    
    function loadRemoteAccess()
    {
        if (empty($this->getSession()->admin_remote_access)) return array();
        return unserialize($this->_getCrypt()->decrypt(base64_decode($this->getSession()->admin_remote_access)));
    }
    
    function tryConnect(array & $info)
    {
        $class = 'Am_FileConnector_' . ucfirst(toCamelCase($info['method']));
        $connector = new $class($info);
        if (!$connector->connect())
        {
            return "Connection failed: " . $connector->getError();
        }
        // create temp file locally
        $fn = tempnam(DATA_DIR, 'test-ftp-');
        $f = fopen($fn, 'w'); fclose($f);
        $cwd = $connector->cwd();
        $root = $this->guessChrootedAmemberPath($cwd, array_keys($connector->ls('.')), ROOT_DIR);
        $ls = $connector->ls($root . '/data');
        @unlink($fn);
        if (!array_key_exists(basename($fn), $ls))
        {
            return "Connection succesful, but upgrade script was unable to locate test file on remote server";
        }
        $info['root'] = $root;
    }
    
    
    function guessChrootedAmemberPath($cwd, array $lsCwd, $amRoot)
    {
        // split amRoot to dirnames
        $dirnames_r = array_filter(preg_split('|[\\/]|', $cwd));
        $dirnames_l = array_filter(preg_split('|[\\/]|', $amRoot));
        // find first occurence of dirnames in lsCwd
        $start = false;
        $foundInLs = array();
        foreach ($dirnames_l as $lstart => $d)
        {
            if ($start = array_search($d, $dirnames_r))
                break;
            if (in_array($d, $lsCwd))
                $foundInLs[] = $lstart;
        }
        if ($start === false) 
        {
            if ($foundInLs)
            {
                $start = null;
                $lstart = min($foundInLs) - 1;
            }
        }
        return '/' . implode('/', array_merge(
            array_slice($dirnames_r, 0, $start),
            array_slice($dirnames_l, $lstart)
        ));
    }
}