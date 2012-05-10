<?php

class Am_Protect_NewRewrite extends Am_Protect_Abstract
{
    const PLUGIN_STATUS = self::STATUS_PRODUCTION;
    const PLUGIN_REVISION = '4.1.10';

    const NR_COOKIE = 'amember_nr';

    
    function getFilePath($cookie, $folder_id = null)
    {
        $file = $cookie;
        if ($folder_id) $file .= '-' . $folder_id;
        return DATA_DIR . '/new-rewrite/' . $file;
    }
    function createFile($fn)
    {
        if (@file_put_contents($fn , "") === false)
            throw new Am_Exception_InternalError("Cannot create file [$fn] in " . __METHOD__);
    }

    function getEscapedCookie()
    {
        if (empty($_COOKIE[self::NR_COOKIE]))
            return null;
        $c = preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE[self::NR_COOKIE]);
        return strlen($c) ? $c : null;
    }

    function onAuthAfterLogin(Am_Event_AuthAfterLogin $event)
    {
        /** @var User */
        $user = $event->getUser();
        $cookie = $this->getEscapedCookie();
        if (!$cookie)
        {
            $cookie = md5(rand() . $user->login);
            Am_Controller::setCookie(self::NR_COOKIE, $cookie, 0, '/',
                $this->getDi()->request->getHttpHost());
            $_COOKIE[self::NR_COOKIE] = $cookie;
        }
        if ($user->status == User::STATUS_ACTIVE)
            $this->createFile($this->getFilePath($cookie));

        foreach ($this->getDi()->resourceAccessTable->getAllowedResources($user, ResourceAccess::FOLDER) as $f)
        {
            $this->createFile($this->getFilePath($cookie, $f->pk()));
        }
    }
    function onAuthAfterLogout(Am_Event_AuthAfterLogout $event)
    {
        $this->deleteCookieFiles();
        Am_Controller::setCookie(self::NR_COOKIE, null, time() - 36000, '/',
                $this->getDi()->request->getHttpHost());
    }
    
    function deleteCookieFiles()
    {
        $c = $this->getEscapedCookie();
        if (!$c)
            return;
        $dirname = DATA_DIR . '/new-rewrite';
        foreach ((array)glob("$dirname/$c*") as $f)
            if (strlen($f))
                @unlink("$dirname/$f");
    }

    function onDaily()
    {
        $d = opendir($dirname = DATA_DIR . "/new-rewrite");
        if (!$d)
            return;
        while ($f = @readdir($d))
        {
            if ($f[0] == '.')
                continue;
            if ($f == '_vti_cnf')
                continue;
            if ($f == 'readme.txt')
                continue;
            if ((time() - @filectime("$dirname/$f")) > 3 * 3600)
                @unlink("$dirname/$f");
        }
        closedir($d);
    }

    public function needRefresh(User $user){
        // logout and login, just to be sure
        $this->deleteCookieFiles();
        $event = new Am_Event_AuthAfterLogin($user);
        $this->onAuthAfterLogin($event);
    }
    public function onAuthSessionRefresh(Am_Event_AuthSessionRefresh $event){
        $this->needRefresh($event->getUser());
    }
    public function directAction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        // if user is logged in and went here, something is definitely wrong
        if ($this->getDi()->auth->getUserId())
        {
            $this->needRefresh($this->getDi()->auth->getUser());
            if(parse_url($request->getParam('url'), PHP_URL_SCHEME))
            {
                $url = $request->getParam('url');
            }
            else
            {
                $url = sprintf('%s://%s%s', $request->isSecure()?'https':'http', $request->getHttpHost(),
                $request->getParam('url'));
            }
            
            Am_Controller::redirectLocation($url);
            return;
        }
        // 
        require_once APPLICATION_PATH . '/default/controllers/LoginController.php';
        $c = new LoginController($request, $response, $invokeArgs);
        $c->setRedirectUrl($request->getEscaped('url'));
        $c->run();
    }

    public function getPasswordFormat()
    {
        return null;
    }

}

;