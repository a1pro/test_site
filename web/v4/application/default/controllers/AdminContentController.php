<?php

class Am_Form_Element_ResourceAccess extends HTML_QuickForm2_Element
{

    protected $value = array();

    public function getType()
    {
        return 'resource-access';
    }

    public function getRawValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        $name = Am_Controller::escape($this->getName());
        $ret = "<div class='resourceaccess' id='$name'>";
        
        $ret .= "<span class='protected-access'>\n";
        $ret .= ___('Choose Products and/or Product Categories that allows access') . "<br />\n";
        $ret .= ___('or %smake access free%s', "<a href='javascript:' class='make-free'>", '</a>') . "<br /><br />\n";
  
        $select = new HTML_QuickForm2_Element_Select(null, array('class' => 'access-items'));
        $select->addOption(___('Please select an item...'), '');
        $g = $select->addOptgroup(___('Product Categories'), array('class' => 'product_category_id', 'data-text' => ___("Category")));
        $g->addOption(___('Any Product'), '-1', array('style' => 'font-weight: bold'));
        foreach (Am_Di::getInstance()->productCategoryTable->getAdminSelectOptions() as $k => $v)
        {
            $g->addOption($v, $k);
        }
        $g = $select->addOptgroup(___('Products'), array('class' => 'product_id', 'data-text' => ___("Product")));
        foreach (Am_Di::getInstance()->productTable->getOptions() as $k => $v)
        {
            $g->addOption($v, $k);
        }
        $ret .= (string)$select;
        
        foreach (Am_Di::getInstance()->resourceAccessTable->getFnValues() as $k)
            $ret .= "<div class='$k-list'></div>";
        
        $ret .= "</span>\n";

        $ret .= "<span class='free-access' style='display:none;'>this item is available for all registered customers.<br />
            <a href='javascript:' class='make-free'>click to make this item protected</a>
            </span>";
        
        $json = array();
        if (!empty($this->value['product_category_id']) || !empty($this->value['product_id']) || !empty($this->value['free'])) {
            $json = $this->value;
            foreach ($json as & $fn)
                foreach ($fn as & $rec)
                {   
                    if (is_string($rec)) $rec = json_decode($rec, true);
                }
        } else
            foreach ($this->value as $cl => $access)
            {
                $json[$access->getClass()][$access->getId()] = array(
                    'text' => $access->getTitle(),
                    'start' => $access->getStart(),
                    'stop' => $access->getStop(),
                );
            }
            
        $json = Am_Controller::escape(Am_Controller::getJson($json));
        $ret .= "<input type='hidden' class='resourceaccess-init' value='$json' />\n";
        $ret .= "</div>";

        $without_period = $this->getAttribute('without_period') ? 'true' : 'false';
        $ret .= "
        <script type='text/javascript'>
             $('.resourceaccess').resourceaccess({without_period: $without_period});
        </script>
        ";
        return $ret;
    }

}

class Am_Grid_Editable_Files extends Am_Grid_Editable_Content
{

    public function __construct(Am_Request $request, Am_View $view)
    {
        parent::__construct($request, $view);
        $this->addCallback(self::CB_AFTER_DELETE, array($this, 'afterDelete'));
    }

    protected function afterDelete(File $record, $grid)
    {
        if (ctype_digit($record->path)
            && !$this->getDi()->fileTable->countBy(array('path'=>$record->path)))
        {
            $this->getDi()->uploadTable->load($record->path)->delete();
        }
    }

    protected function initGridFields()
    {
        $this->addGridField('title', ___('Title'))->setRenderFunction(array($this, 'renderAccessTitle'));
        $this->addGridField('path', ___('Filename'))->setRenderFunction(array($this, 'renderPath'));
        parent::initGridFields();
    }

    public function renderPath(File $file)
    {
        $upload = $file->getUpload();
        return file_exists($file->getFullPath()) ?
            $this->renderTd($file->getDisplayFilename()) :
                $this->renderTd(
                    '<div class="reupload-conteiner"><span class="upload-name">'. $this->escape($file->getDisplayFilename()) . '</span><br />' .
                    '<div class="reupload-conteiner-hide"><span class="error">' . ___('File was removed from disk or corrupted. Please re-upload it.') . '</span>'
                    . '<div><span class="reupload" data-upload_id="' . $upload->pk() . '" id="reupload-' . $upload->pk() . '"></span></div></div></div>', false);
    }

    protected function createAdapter()
    {
        return new Am_Query(Am_Di::getInstance()->fileTable);
    }

    function createForm()
    {
        $form = new Am_Form_Admin;
        $form->setAttribute('enctype', 'multipart/form-data');
        $form->setAttribute('target', '_top');

        $maxFileSize = min(ini_get('post_max_size'),ini_get('upload_max_filesize'));
        $el = $form->addElement(new Am_Form_Element_Upload('path', array(), array('prefix'=>'downloads')))
            ->setLabel(___("File\n(max filesize %s)", $maxFileSize))->setId('form-path');

        $jsOptions = <<<CUT
{
    onFileAdd : function (info) {
        var txt = $(this).closest("form").find("input[name='title']");
        if (txt.data('changed-value')) return;
        txt.val(info.name);
    }
}
CUT;
        $el->setJsOptions($jsOptions);
        $form->addScript()->setScript(<<<CUT
$(function(){
    $("input[name='title']").change(function(){
        $(this).data('changed-value', true);
    });
});
CUT
        );
        
        
        $el->addRule('required', ___('File is required'));
        $form->addText('title', array('size'=>50))->setLabel(___('Title'))->addRule('required', 'This field is required');
        $form->addText('desc', array('size'=>50))->setLabel(___('Description'))->addRule('required', 'This field is required');
        $form->addAdvCheckbox('hide')->setLabel(___("Hide\n". "do not display this item link in members area"));
        $form->addElement(new Am_Form_Element_ResourceAccess)->setName('_access')->setLabel(___('Access Permissions'));
        return $form;
    }
}

class Am_Grid_Editable_Pages extends Am_Grid_Editable_Content
{


    protected function initGridFields()
    {
        $this->addGridField('title', ___('Title'))->setRenderFunction(array($this, 'renderAccessTitle'));
        parent::initGridFields();
    }

    protected function createAdapter()
    {
        return new Am_Query(Am_Di::getInstance()->pageTable);
    }

    function createForm()
    {
        $form = new Am_Form_Admin;
        $form->addText('title', array('size'=>80))->setLabel('Title')->addRule('required', 'This field is required');
        $form->addText('desc', array('size'=>80))->setLabel(___('Description'));
        $form->addAdvCheckbox('hide')->setLabel(___("Hide\n". "do not display this item link in members area"));
        $form->addAdvCheckbox('use_layout')->setLabel("Dislpay inside layout\nwhen displaying to customer will\nthe header/footer from current theme be displayed?");
        $form->addHtmlEditor('html');
            //->setLabel('HTML code')->addRule('required', 'This field is required');
        $form->addElement(new Am_Form_Element_ResourceAccess)->setName('_access')->setLabel(___('Access Permissions'));
        return $form;
    }
}

class Am_Grid_Editable_Links extends Am_Grid_Editable_Content
{

    protected function initGridFields()
    {
        $this->addGridField('title', ___('Title'))->setRenderFunction(array($this, 'renderAccessTitle'));
        parent::initGridFields();
    }

    protected function createAdapter()
    {
        return new Am_Query(Am_Di::getInstance()->linkTable);
    }

    function createForm()
    {
        $form = new Am_Form_Admin;
        $form->addText('title', array('size'=>80))->setLabel(___('Title'))->addRule('required');
        $form->addText('url', array('size'=>80))->setLabel(___('URL'))->addRule('required');
        $form->addAdvCheckbox('hide')->setLabel(___("Hide\n". "do not display this item link in members area"));
        $form->addElement(new Am_Form_Element_ResourceAccess)->setName('_access')->setLabel(___('Access Permissions'));
       return $form;
    }
    public function renderContent()
    {
        return parent::renderContent() . '<p><b>' . ___("IMPORTANT NOTE: This will not protect content. If someone know link url, he will be able to open link without a problem. This just control what additional links user will see after login to member's area.") . '</b></p>';
    }

}


class Am_Grid_Editable_Integrations extends Am_Grid_Editable_Content
{
    public function init()
    {
        parent::init();
        $this->addCallback(self::CB_VALUES_FROM_FORM, array($this, '_valuesFromForm'));
    }
    
    public function createAdapter()
    {
        return new Am_Query(Am_Di::getInstance()->integrationTable);
    }

    protected function initGridFields()
    {
        $this->addGridField('plugin', ___('Plugin'))->setRenderFunction(array($this, 'renderPluginTitle'));
        $this->addGridField('resource', ___('Resource'))->setRenderFunction(array($this, 'renderResourceTitle'));
        parent::initGridFields();
        $this->removeField('_link');
    }

    public function renderPluginTitle(Am_Record $r)
    {
        return $this->renderTd($r->plugin);
    }

    public function renderResourceTitle(Am_Record $r)
    {
        try
        {
            $pl = Am_Di::getInstance()->plugins_protect->get($r->plugin);
        }
        catch (Am_Exception_InternalError $e)
        {
            $pl = null;
        }
        $config = unserialize($r->vars);
        $s = $pl ? $pl->getIntegrationSettingDescription($config) : Am_Protect_Abstract::static_getIntegrationDescription($config);
        return $this->renderTd($s);
    }

    public function getGridPageTitle()
    {
        return ___("Integration plugins");
    }

    function createForm()
    {
        $form = new Am_Form_Admin;
        $plugins = $form->addSelect('plugin')->setLabel(___('Plugin'));
        $plugins->addRule('required');
        $plugins->addOption('*** '.___('Select a plugin').' ***', '');
        foreach (Am_Di::getInstance()->plugins_protect->getAllEnabled() as $plugin)
        {
            if (!$plugin->isConfigured()) continue;
            $group = $form->addFieldset($plugin->getId())->setId('headrow-' . $plugin->getId());
            $group->setLabel($plugin->getTitle());
            $plugin->getIntegrationFormElements($group);
            // add id[...] around the element name
            foreach ($group->getElements() as $el)
                $el->setName('_plugins[' . $plugin->getId() . '][' . $el->getName() . ']');
            if (!$group->count())
                $form->removeChild($group);
            else
                $plugins->addOption($plugin->getTitle(), $plugin->getId());
        }
        $group = $form->addFieldset('access')->setLabel(___('Access'));
        $group->addElement(new Am_Form_Element_ResourceAccess)
            ->setName('_access')
            ->setLabel(___('Access Permissions'))
            ->setAttribute('without_period', 'true');
        
        $form->addScript()->setScript(<<<CUT
$(function(){
    $("select[name='plugin']").change(function(){
        var selected = $(this).val();
        $("[id^='headrow-']").hide();
        if (selected) {
            $("[id^=headrow-"+selected+"]").show();
        }
    }).change();
});
CUT
);
        return $form;
    }

    public function _valuesFromForm(array & $vars)
    {
        if ($vars['plugin'] && !empty($vars['_plugins'][$vars['plugin']]))
            $vars['vars'] = serialize($vars['_plugins'][$vars['plugin']]);
    }

    public function _valuesToForm(array & $vars)
    {
        if (!empty($vars['vars']))
        {
            foreach (unserialize($vars['vars']) as $k => $v)
                $vars['_plugins'][$vars['plugin']][$k] = $v;
        }
        parent::_valuesToForm($vars);
    }

}

class Am_Grid_Editable_Folders extends Am_Grid_Editable_Content
{
    public function init()
    {
        parent::init();
        $this->addCallback(self::CB_AFTER_UPDATE, array($this, 'afterUpdate'));
    }
    
    public function validatePath($path)
    {
        if (!is_dir($path))
            return "Wrong path: not a folder: " . htmlentities($path);
        if (!is_writeable($path))
            return "Specified folder is not writeable - please chmod the folder to 777, so aMember can write .htaccess file for folder protection";
    }

    function createForm()
    {
        $form = new Am_Form_Admin;

        $title = $form->addText('title')->setLabel(___("Title\ndisplayed to customers"))->setAttribute('size', 50);
        $title->addRule('required');
        $form->addAdvCheckbox('hide')->setLabel(___("Hide\n". "do not display this item link in members area"));

        $path = $form->addText('path')->setLabel(___('Path to Folder'))->setAttribute('size', 50)->addClass('dir-browser');
        $path->addRule('required');
        $path->addRule('callback2', '-- Wrong path --', array($this, 'validatePath'));

        $url = $form->addGroup()->setLabel(___('Folder URL'));
        $url->addRule('required');
        $url->addText('url')->setAttribute('size', 50)->setId('url');
        $url->addHtml()->setHtml('&nbsp;<a href="#" id="test-url-link">'.___('open in new window').'</a>');

        $methods = array(
            'new-rewrite' => ___('New Rewrite'),
            'htpasswd'    => ___('Traditional .htpasswd'),
        );
        foreach ($methods as $k => $v)
            if (!Am_Di::getInstance()->plugins_protect->isEnabled($k)) unset($methods[$k]);
            
        
        $method = $form->addAdvRadio('method')->setLabel(___('Protection Method'));
        $method->loadOptions($methods);
        if (count($methods) == 0) 
        {
            throw new Am_Exception_InputError("No protection plugins enabled, please enable new-rewrite or htpasswd at aMember CP -> Setup -> Plugins");
        } elseif (count($methods) == 1) {
            $method->setValue(key($methods))->toggleFrozen(true);
        }
        
        $form->addElement(new Am_Form_Element_ResourceAccess)->setName('_access')->setLabel(___('Access Permissions'));
        $form->addScript('script')->setScript('
        $(function(){
            $(".dir-browser").dirBrowser({
                urlField : "#url",
                rootUrl  : ' . Am_Controller::getJson(REL_ROOT_URL) . ',
            });
            $("#test-url-link").click(function() {
                var href = $("input", $(this).parent()).val();
                if (href)
                    window.open(href , "test-url", "");
            });
        });
        ');
        return $form;
    }

    protected function initGridFields()
    {
        $this->addGridField('title', ___('Title'))->setRenderFunction(array($this, 'renderAccessTitle'));
        $this->addGridField('path-url', ___('Path/URL'))->setRenderFunction(array($this, 'renderPathUrl'));
        $this->addGridField('method', ___('Protection Method'));
        parent::initGridFields();
    }

    public function renderPathUrl(Folder $f)
    {
        $url = Am_Controller::escape($f->url);
        return $this->renderTd(
            Am_Controller::escape($f->path) .
            "<br />" .
            "<a href='$url' target='_blank'>$url</a>", false);
    }

    protected function createAdapter()
    {
        return new Am_Query(Am_Di::getInstance()->folderTable);
    }

    public function getGridPageTitle()
    {
        return ___("Folders");
    }

    public function getHtaccessRewriteFile(Folder $folder)
    {
		if(AM_WIN)
			$rd = str_replace("\\", '/', DATA_DIR);
		else
			$rd = DATA_DIR;
        
        $root_url = ROOT_SURL;
        return <<<CUT
########### AMEMBER START #####################
Options +FollowSymLinks
RewriteEngine On

# if cookie is set and file exists, stop rewriting and show page
RewriteCond %{HTTP_COOKIE} amember_nr=([a-zA-Z0-9]+)
RewriteCond $rd/new-rewrite/%1-{$folder->folder_id} -f
RewriteRule ^(.*)\$ - [S=3]

# if cookie is set but folder file does not exists, user has no access to given folder
RewriteCond %{HTTP_COOKIE} amember_nr=([a-zA-Z0-9]+)
RewriteCond $rd/new-rewrite/%1-{$folder->folder_id} !-f
RewriteRule ^(.*)$ $root_url/no-access/folder/id/$folder->folder_id [L,R]
    
## if user is not authorized, redirect to login page
# BrowserMatch "MSIE" force-no-vary
RewriteCond %{QUERY_STRING} (.+)
RewriteRule ^(.*)$ $root_url/protect/new-rewrite?f=$folder->folder_id&url=%{REQUEST_URI}?%{QUERY_STRING} [L,R]
RewriteRule ^(.*)$ $root_url/protect/new-rewrite?f=$folder->folder_id&url=%{REQUEST_URI} [L,R]
########### AMEMBER FINISH ####################
CUT;
    }

    public function getHtaccessHtpasswdFile(Folder $folder)
    {
        $rd = DATA_DIR;

        $require = '';
        if ($folder->hasAnyProducts())
            $require = 'valid-user';
        else
            $require = 'group FOLDER_' . $folder->folder_id;

//        $redirect = ROOT_SURL . "/no-access?folder_id={$folder->folder_id}";
//        ErrorDocument 401 $redirect
        
        return <<<CUT
########### AMEMBER START #####################
AuthType Basic
AuthName "Members Only"
AuthUserFile $rd/.htpasswd
AuthGroupFile $rd/.htgroup
Require $require
########### AMEMBER FINISH ####################

CUT;
    }

    public function protectFolder(Folder $folder)
    {
        switch ($folder->method)
        {
            case 'new-rewrite':
                $ht = $this->getHtaccessRewriteFile($folder);
                break;
            case 'htpasswd':
                $ht = $this->getHtaccessHtpasswdFile($folder);
                break;
            default: throw new Am_Exception_InternalError('Unknown protection method');
        }
        $htaccess_path = $folder->path . '/' . '.htaccess';
        if (file_exists($htaccess_path))
        {
            $content = file_get_contents($htaccess_path);
            $new_content = preg_replace('/#+\sAMEMBER START.+AMEMBER FINISH\s#+/ms', $ht, $content, 1, $found);
            if (!$found)
                $new_content = $ht . "\n\n" . $content;
        } else
        {
            $new_content = $ht . "\n\n";
        }
        if (!file_put_contents($htaccess_path, $new_content))
            throw new Am_Exception_InputError("Could not write file [$htaccess_path] - check file permissions and make sure it is writeable");
    }

    public function unprotectFolder(Folder $folder)
    {
        $htaccess_path = $folder->path . '/.htaccess';
        if (!is_dir($folder->path))
        {
            trigger_error("Could not open folder [$folder->path] to remove .htaccess from it. Do it manually", E_USER_WARNING);
            return;
        }
        $content = file_get_contents($htaccess_path);
        if (strlen($content) && !preg_match('/^\s*\#+\sAMEMBER START.+AMEMBER FINISH\s#+\s*/s', $content))
        {
            trigger_error("File [$htaccess_path] contains not only aMember code - remove it manually to unprotect folder", E_USER_WARNING);
            return;
        }
        unlink($folder->path . '/.htaccess');
    }

    public function afterInsert(array &$values, ResourceAbstract $record)
    {
        parent::afterInsert($values, $record);
        $this->protectFolder($record);
    }

    public function afterDelete($record)
    {
        parent::afterDelete();
        $this->unprotectFolder($record);
    }
    public function renderContent()
    {
        return parent::renderContent() . '<p><b>' . ___("After making any changes to htpasswd protected areas, please run [Utiltites->Rebuild Db] to refresh htpasswd file") . '</b></p>';
    }

}

class Am_Grid_Editable_Emails extends Am_Grid_Editable_Content
{
    protected $comment = array();
    
    public function init()
    {
        $this->comment = array(
        EmailTemplate::AUTORESPONDER => 
        "Autoresponder message will be automatically sent by cron job 
         when configured conditions met. If you set message to be sent
         after payment, it will be sent immediately after payment received.
         Auto-responder message will not be sent if:
         <ul> 
            <li>User has unsubscribed from e-mail messages</li>
         </ul>
        ",
        EmailTemplate::EXPIRE => 
        "Expiration message will be sent when configured conditions met.
         Additional restrictions applies to do not sent unnecessary e-mails.
         Expiration message will not be sent if:
         <ul> 
            <li>User has other subscriptions that lasts later</li>
            <li>User has any active recurring subscription</li>
            <li>User has unsubscribed from e-mail messages</li>
         </ul>
        "
    );
        parent::init();
        $this->addCallback(self::CB_VALUES_FROM_FORM, array($this, '_valuesFromForm'));
    }
    public function initActions()
    {
        parent::initActions();
        $this->actionDelete('insert');
        $this->actionAdd($a0 = new Am_Grid_Action_Insert('insert-'.EmailTemplate::AUTORESPONDER, ___('New Autoresponder')));
        $a0->addUrlParam('name', EmailTemplate::AUTORESPONDER);
        $this->actionAdd($a1 = new Am_Grid_Action_Insert('insert-'.EmailTemplate::EXPIRE, ___('New Expiration E-Mail')));
        $a1->addUrlParam('name', EmailTemplate::EXPIRE);
    }
    protected function createAdapter()
    {
        $ds = new Am_Query(Am_Di::getInstance()->emailTemplateTable);
        $ds->addWhere('name IN (?a)', array(EmailTemplate::AUTORESPONDER, EmailTemplate::EXPIRE));
        return $ds;
    }
    protected function initGridFields()
    {
        $this->addField('name', ___('Name'));
        $this->addField('day', ___('Send'))->setGetFunction(array($this, 'getDay'));
        $this->addField('subject', ___('Subject'))->addDecorator(new Am_Grid_Field_Decorator_Shorten(30));
        parent::initGridFields();
        $this->removeField('_link');
    }
    
    public function getDay(EmailTemplate $record)
    {
        switch ($record->name)
        {
            case EmailTemplate::AUTORESPONDER:
                return ($record->day>1) ? ___("%d-th subscription day",$record->day) : ___("immediately after purchase");
                break;
            case EmailTemplate::EXPIRE:
                switch (true)
                {
                    case $record->day > 0:
                        return ___("%d days after expiration", $record->day);
                    case $record->day < 0:
                        return ___("%d days before expiration", -$record->day);
                    case $record->day == 0:
                        return ___("on expiration day");
                }
                break;
        }
    }
    
    public function createForm()
    {
        $form = new Am_Form_Admin;
        
        $record = $this->getRecord();
        
        $name = empty($record->name) ?  
            $this->getCompleteRequest()->getFiltered('name') : 
            $record->name;
        
        $form->addHidden('name');
        
        $form->addStatic()->setContent(nl2br($this->comment[$name]))->setLabel(___('Description'));
        
        $form->addStatic()->setLabel(___('E-Mail Type'))->setContent($name);
        $form->addElement(new Am_Form_Element_MailEditor($name));
        $form->addElement(new Am_Form_Element_ResourceAccess('_access'))->setAttribute('without_period', true)
            ->setLabel(___('Access Permissions'));
        $group = $form->addGroup('day')->setLabel(___('Send E-Mail Message'));
        $options = ($name == EmailTemplate::AUTORESPONDER) ?
            array('' => ___('..th subscription day (starts from 1)'), '1' => ___('immediately after purchase')) :
            array('-' => ___('days before expiration'), '0'=>___('on expiration day'), '+' => ___('days after expiration'));;
        $group->addInteger('count', array('size'=>3, 'id'=>'days-count'));
        $group->addSelect('type', array('id'=>'days-type'))->loadOptions($options);
        $group->addScript()->setScript(<<<CUT
$("#days-type").change(function(){
    var sel = $(this);
    if ($("input[name='name']").val() == 'autoresponder')
        $("#days-count").toggle( sel.val() != '1' );  
    else
        $("#days-count").toggle( sel.val() != '0' );  
}).change();
CUT
        );
        return $form;
    }
    public function _valuesToForm(array &$values)
    {
        parent::_valuesToForm($values);
        switch (get_first(@$values['name'], @$_GET['name']))
        {
            case EmailTemplate::AUTORESPONDER :
                $values['day'] = (empty($values['day']) || ($values['day'] == 1)) ?
                    array('count' => 1, 'type' => '1') :
                    array('count' => $values['day'], 'type' => '');
                break;
            case EmailTemplate::EXPIRE :
                $day = @$values['day'];
                $values['day'] = array('count' => $day, 'type' => '');
                if ($day > 0)
                    $values['day']['type'] = '+';
                elseif ($day < 0) {
                    $values['day']['type'] = '-';
                    $values['day']['count'] = -$day;
                } else
                    $values['day']['type'] = '0';
                break;
        }
    }
    public function _valuesFromForm(array &$values)
    {
        switch ($values['day']['type']) {
            case '0': $values['day'] = 0; break;
            case '1': $values['day'] = 1; break;
            case '': case '+':
                $values['day'] = (int)$values['day']['count']; 
                break;
            case '-': 
                $values['day'] = - $values['day']['count']; 
                break;
        }
        $values['attachments'] = implode(',', @$values['attachments']);
        ///////
        if (!empty($values['_access']['product_category_id']))
            foreach ($values['_access']['product_category_id'] as & $item)
            {
                if (is_string($item)) $item = json_decode($item, true);
                $item['start'] = $item['stop'] = $values['day'] . 'd';
            }
        if (!empty($values['_access']['product_id']))
            foreach ($values['_access']['product_id'] as & $item)
            {
                if (is_string($item)) $item = json_decode($item, true);
                $item['start'] = $item['stop'] = $values['day'] . 'd';
            }
    }

    public function renderProducts(ResourceAbstract $resource)
    {
        $access_list = $resource->getAccessList();
        if (count($access_list) > 6)
            $s = count($access_list) . ' access records...';
        else
        {
            $s = "";
            foreach ($access_list as $access)
                $s .= sprintf("%s <b>%s</b> %s<br />\n", $access->getClass(), $access->getTitle(), "");
        }
        return $this->renderTd($s, false);
    }
    
}

class Am_Grid_Editable_Video extends Am_Grid_Editable_Content
{

    protected function initGridFields()
    {
        $this->addGridField('title', ___('Title'))->setRenderFunction(array($this, 'renderAccessTitle'));
        $this->addGridField('path', ___('Filename'))->setRenderFunction(array($this, 'renderPath'));
        $this->addGridField(new Am_Grid_Field_Expandable('_code', ___('JavaScript Code')))
            ->setGetFunction(array($this, 'renderJsCode'));
        parent::initGridFields();
    }
    
    public function renderJsCode(Video $video)
    {
        $root = Am_Controller::escape(ROOT_URL);
        $cnt = <<<CUT
<!-- the following code you may insert into any HTML, PHP page of your website or into WP post -->
<!-- you may skip including Jquery library if that is already included on your page -->
<script type="text/javascript" 
        src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
<!-- end of JQuery include -->
<!-- there is aMember video JS code starts -->
<script type="text/javascript" id="am-video-{$video->video_id}"
    src="$root/video/js/id/{$video->video_id}?width=550&height=330">
</script>        
<!-- end of aMember video JS code -->
CUT;
        return "<pre>".Am_Controller::escape($cnt) . "</pre>";
    }

    public function renderPath(Video $file)
    {
        return $this->renderTd($file->getDisplayFilename());
    }

    protected function createAdapter()
    {
        return new Am_Query(Am_Di::getInstance()->videoTable);
    }

    function createForm()
    {
        $form = new Am_Form_Admin;
        $form->setAttribute('enctype', 'multipart/form-data');
        $form->setAttribute('target', '_top');
        
        $maxFileSize = min(ini_get('post_max_size'),ini_get('upload_max_filesize'));
        $el = $form->addElement(new Am_Form_Element_Upload('path', array(), array('prefix'=>'video')))
            ->setLabel(___("Video File\n(max upload size %s)", $maxFileSize))
            ->setId('form-path');

        $jsOptions = <<<CUT
{
    onFileAdd : function (info) {
        var txt = $(this).closest("form").find("input[name='title']");
        if (txt.data('changed-value')) return;
        txt.val(info.name);
    }
}
CUT;
        $el->setJsOptions($jsOptions);
        $form->addScript()->setScript(<<<CUT
$(function(){
    $("input[name='title']").change(function(){
        $(this).data('changed-value', true);
    });
});
CUT
        );
        $el->addRule('required');
        
        $form->addText('title', array('size'=>50))->setLabel(___('Title'))->addRule('required', 'This field is required');
        $form->addText('desc', array('size'=>50))->setLabel(___('Description'))->addRule('required', 'This field is required');
        $form->addAdvCheckbox('hide')->setLabel(___("Hide\n". "do not display this item link in members area"));
        $form->addElement(new Am_Form_Element_ResourceAccess)->setName('_access')->setLabel(___('Access Permissions'));
        return $form;
    }
    
    public function renderContent() {
        return $this->getPlayerInfo() . parent::renderContent();
    }
    
    function getPlayerInfo()
    {
        $out = "";
        if (!file_exists($fn = APPLICATION_PATH . '/default/views/public/js/flowplayer/flowplayer.js'))
            $out .= "Please upload file [<i>$fn</i>]<br />";
        if (!file_exists($fn = APPLICATION_PATH . '/default/views/public/js/flowplayer/flowplayer.swf'))
            $out .= "Please upload file [<i>$fn</i>]<br />";
        if (!file_exists($fn = APPLICATION_PATH . '/default/views/public/js/flowplayer/flowplayer.controls.swf'))
            $out .= "Please upload file [<i>$fn</i>]<br />";
        if ($out)
        {
            $out = "To starting sharing video files, you have to download either free or commercial version of <a href='http://flowplayer.org/'>FlowPlayer</a><br />"
                . $out;
        }
        return $out;
    }
}

class AdminContentController extends Am_Controller_Pages
{

    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission('grid_content');
    }

    public function preDispatch()
    {
        parent::preDispatch();
        $this->view->headScript()->appendFile(REL_ROOT_URL . "/application/default/views/public/js/resourceaccess.js");
        $this->setActiveMenu('products-protect');
    }

    public function initPages()
    {
        $this
            ->addPage('Am_Grid_Editable_Folders', 'folders', ___('Folders'))
            ->addPage('Am_Grid_Editable_Files', 'files', ___('Files'))
            ->addPage('Am_Grid_Editable_Pages', 'pages', ___('Pages'))
            ->addPage('Am_Grid_Editable_Integrations', 'integrations', ___('Integrations'))
            ->addPage('Am_Grid_Editable_Emails', 'emails', ___('E-Mail Messages'))
            ->addPage('Am_Grid_Editable_Links', 'links', ___('Links'))
            ->addPage('Am_Grid_Editable_Video', 'video', ___('Video'));
        $this->getDi()->hook->call(Am_Event::INIT_CONTENT_PAGES, array('controller' => $this));
    }

}