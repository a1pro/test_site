<?php

class Am_Grid_ReadOnly
{
    const ACTION_KEY = 'a';
    const ID_KEY = 'id';
    const GROUP_ID_KEY = 'group_id';
    const BACK_KEY = 'b';

    const CB_RENDER_TABLE = 'onRenderTable';
    const CB_RENDER_CONTENT = 'onRenderContent';
    const CB_RENDER_STATIC = 'onRenderStatic';
    const CB_TR_ATTRIBS = 'onGetTrAttribs';
    
    /** @var Zend_Controller_Response_Abstract */
    protected $response;    
    protected $cssClass = 'grid-wrap';
    /** @var string */
    protected $id;
    /** @var string */
    protected $title;
    /** @var array Am_Grid_Field */
    protected $fields = array();
    /** @var Am_Grid_DataSource_Interface_ReadOnly */
    protected $dataSource;
    /** @var Am_Request all request as it submitted */
    protected $completeRequest;
    /** @var Am_Request only vars specific to this grid */
    protected $request;
    /** @var Am_View passed from controller may be null */
    protected $view = null;
    /** @var Am_Grid_Filter_Interface */
    protected $filter;
    /** @var bool set this to not-null to override autodetection */
    protected $isAjax = null;
    /** @var int */
    protected $countPerPage;
    /** @var Am_Di */
    private $di;
    /** @var string by default 'grid'.$this->getId() */
    protected $permissionId = null;
    
    
    /** @var array callbackConst => array of callbacks */
    protected $callbacks = array();

    public function __construct($id, $title, 
        Am_Grid_DataSource_Interface_ReadOnly $ds, Am_Request $request, Am_View $view, Am_Di $di = null)
    {
        if ($id[0] != '_') throw new Am_Exception_InternalError("id must start with underscore _ in " . __METHOD__);
        $this->id = $id;
        $this->title = $title;
        $this->dataSource = $ds;
        $this->view = $view;
        $this->di = ($di === null) ? Am_Di::getInstance() : $di;
        $this->countPerPage = $this->getDi()->config->get('admin.records-on-page', 10);
        $this->initGridFields();
        $this->setRequest($request);
        $this->init();
    }
    
    function init(){}
    
    public function getId() { return $this->id; } 
    /** @return Am_Di */
    public function getDi() { return $this->di; }
    public function setRequest(Am_Request $request)
    {
        $this->completeRequest = $request;
        $arr = array();
        foreach ($request->toArray() as $k => $v)
            if (strpos($k, $this->id.'_')===0)
            {
                $k = substr($k, strlen($this->id)+1);
                if (!strlen($k)) continue;
                $arr[$k] = $v;
            }
        $this->request = new Am_Request($arr);
        $sort = $this->request->get('sort');
        if (!empty($sort))
        {
            $sort = explode(' ', $sort, 2);
            $this->getDataSource()->setOrder(filterId($sort[0]), !empty($sort[1]));
        }
    }
    
    /** must be overriden */
    protected function initGridFields() {}
    function getCountPerPage()
    {
        return $this->countPerPage;
    }
    function setCountPerPage($count)
    {
        $this->countPerPage = (int)$count;
    }
    function getCurrentPage()
    {
        return $this->request->getInt('p');
    }
    /**
     * @param Am_Grid_Field|string $field 
     * @return Am_Grid_Field
     */
    function addField($field, $title=null, $sortable=null,
            $align=null, $renderFunc=null, $width=null)
    {
        if (func_num_args()>1 || !$field instanceof Am_Grid_Field) 
            $field = $this->_createField(func_get_args());
        $this->fields[] = $field;
        $field->init($this);
        return $field;
    }
    /**
     * Find a field by name
     * @throws Am_Exception_InternalError
     * @param string $fieldName
     * @return Am_Grid_Field
     */
    function getField($fieldName)
    {
        foreach ($this->fields as $field)
            if ($field->getFieldName() == $fieldName) return $field;
        throw new Am_Exception_InternalError("Field [$fieldName] not found in " . __METHOD__);
    }
    /**
     * @deprecated use @link addField instead
     */
    function addGridField($field)
    {
        $args = func_get_args();
        return call_user_func_array(array($this, 'addField'), $args);
    }
    function removeField($fieldName)
    {
        foreach ($this->fields as $k => $field)
            if ($field->getFieldName() == $fieldName)
                unset($this->fields[$k]);
        return $this;
    }
    private function _createField(array $args)
    {
        $reflectionObj = new ReflectionClass('Am_Grid_Field');
        return $reflectionObj->newInstanceArgs($args);
    }
    function prependField($field, $title=null, $sortable=null,
            $align=null, $renderFunc=null, $width=null)
    {
        if (func_num_args()>1 || !$field instanceof Am_Grid_Field) 
            $field = $this->_createField(func_get_args());
        array_unshift($this->fields, $field);
        $field->init($this);
        return $field;
    }
    function setFilter(Am_Grid_Filter_Interface $filter)
    {
        $this->filter = $filter;
        $this->filter->initFilter($this);
    }
    function getFilter()
    {
        return $this->filter;
    }
    function getFields()
    {
        return $this->fields;
    }
    /** @return Am_Grid_DataSource_Interface_ReadOnly */
    function getDataSource()
    {
        return $this->dataSource;
    }
    function renderTitle($noTags = false)
    {
        if ($noTags) return $this->title;
        $total = $this->getDataSource()->getFoundRows();
        $page = $this->getCurrentPage();
        $count = $this->getCountPerPage();
        $ret  = "";
        $ret .= '<h1>';
        $ret .= Am_Controller::escape($this->title);
        $msgs = array();
        if ($total)
        {
            $msgs[] = ___("displaying records %d-%d from %d", 
                    $page*$count+1, min($total, ($page+1)*$count), $total);
        } else {
            $msgs[] = ___("no records");
        }
        if ($this->filter && $this->filter->isFiltered())
        {
            $override = array();
            foreach ($this->filter->getVariablesList() as $k)
                $override[$k] = null;
            $msgs[] = sprintf('<a class="filtered" href="%s">%s</a>',
                $this->escape($this->makeUrl($override)),
                ___("filtered"));
        }
        if ($msgs) $ret .= ' (' . implode(", ", $msgs) . ')';
        $ret .= "</h1>";
        return $ret;
    }
    function getCssClass()
    {
        return $this->cssClass;
    }
    function getTrAttribs($record)
    {
        $ret = array();
        $args = array(& $ret, $record);
        $this->runCallback(self::CB_TR_ATTRIBS, $args);
        return $ret;
    }
    function getHiddenVars()
    {
        return array(
            'totalRecords' => $this->getDataSource()->getFoundRows(),
            'page' => $this->getRequest()->getInt('p'),
        );
    }
    /** @return Am_Request - with filtered vars */
    function getRequest()
    {
        return $this->request;
    }
    /** @return Am_Request - global */
    function getCompleteRequest()
    {
       return $this->completeRequest; 
    }
    function renderTable()
    {
        $this->checkPermission(null, 'browse');
        if (empty($this->request))
            throw new Am_Exception_InternalError("request is empty in " . __METHOD__);
            
        $records = $this->getDataSource()->selectPageRecords($this->getCurrentPage(), $this->getCountPerPage());
        $out = "";
        $out .= '<div class="grid-container">'.PHP_EOL;
        $out .= sprintf('<table class="grid" data-info="%s">'.PHP_EOL, Am_Controller::escape(Am_Controller::getJson($this->getHiddenVars())));
        $out .= "\t<thead><tr>\n";
        foreach ($this->getFields() as $field)
            $out .= "\t\t" . $field->renderTitle($this) . PHP_EOL;
        $out .= "\t</tr></thead><tbody>\n";
        foreach ($records as $record)
            $out .= $this->renderRow($record);
        $out .= "</tbody></table></div>\n\n";
        // run callback
        $args = array(& $out, $this);
        $this->runCallback(self::CB_RENDER_TABLE, $args);
        // done
        return $out;
    }
    function renderRow($record)
    {
        $out = "";
        
        $attribs = (array)$this->getTrAttribs($record);
        static $odd = 0; // to get zebra colors
        if ($odd++ % 2) {
            if (empty($attribs['class'])) $attribs['class'] = "";
            $attribs['class'] = "odd " . $attribs['class'];
        }
        $astring = "";
        foreach ($attribs as $k => $v)
            $astring .= ' ' . htmlentities($k, null, 'UTF-8') . '="' . htmlentities($v, ENT_QUOTES, 'UTF-8').'"';
        
        $out .= "\t<tr".$astring.">\n";
        foreach ($this->getFields() as $field)
            $out .= "\t\t" . $field->render($record, $this) . "\n";
        $out .= "\t</tr>\n";
        return $out;
    }
    function renderPaginator()
    {
        $urlTemplate = null;
        $total = $this->getDataSource()->getFoundRows();
        $p = new Am_Paginator(ceil($total/$this->getCountPerPage()), $this->getCurrentPage(), $urlTemplate, $this->id . '_p', $this->getCompleteRequest());
        return $p->render();
    }
    function renderFilter()
    {
        if ($this->filter)
            return $this->filter->renderFilter();
    }
    function renderContent()
    {
        // it is important to run it first to get query executed
        $table = $this->renderTable();
        $out =
            $this->renderFilter() .
            $table .
            $this->renderPaginator();

        // run callback
        $args = array(& $out, $this);
        $this->runCallback(self::CB_RENDER_CONTENT, $args);
        // done
        return $this->renderTitle() .
                $out;
    }
    /**
     * Render static html or js or css code that must not be reloaded
     * during AJAX requests
     */
    function renderStatic()
    {
        $out = "";
        foreach ($this->fields as $field)
            $out .= $field->renderStatic() . PHP_EOL;
        if ($this->filter)
            $out .= $this->filter->renderStatic() . PHP_EOL;
        // run callback
        $args = array(& $out, $this);
        $this->runCallback(self::CB_RENDER_STATIC, $args);
        return $out;
    }
    function render()
    {
        return sprintf(
            '<!-- start of grid -->' . PHP_EOL .
            '<div class="%s" id="%s">'. PHP_EOL .
            '%s' . PHP_EOL .
            "</div>" . PHP_EOL .
            '%s' . PHP_EOL .
            '<!-- end of grid -->'
            ,
            $this->getCssClass(), 
            'grid-' . preg_replace('/^_/','', $this->getId()),
            $this->renderContent(),
            $this->renderStatic()
            );
    }
    /** @array string html */
    function renderGridHeaderSortHtml(Am_Grid_Field $field)
    {
        $desc = null;
        @list($sort, $desc) = explode(' ', $this->request->getParam('sort'), 2);
        if ($sort == $field->getFieldName())
            $desc = ($desc != "DESC");
        $url = $this->escape($this->makeUrl(array(
            'sort' => $field->getFieldName() . ($desc ? " DESC" : ""),
        )));

        $cssClass = "a-sort";
        if ($sort == $field->getFieldName()) 
        { 
            $cssClass .= $desc ? ' sorted-desc' : ' sorted-asc';
        }
        $sort1 = sprintf("<a class='$cssClass' href='%s'>", $url);
        $sort2 = "</a>";
        return array($sort1, $sort2);
    }
    
    static function renderTd($content, $doEscape=true){
        return sprintf('<td>%s</td>',
            $doEscape ? self::escape($content) : $content);
    }
    
    /**
     * if $override === null return url without ANY parameters
     */
    public function makeUrl($override = array(), $includeGlobal = true)
    {
        if ($includeGlobal)
            $req = $this->completeRequest->toArray();
        else
            $req = null;
        $uri = $this->completeRequest->getRequestUri();
        $uri = preg_replace('/\?.*/', '', $uri);
        
        if ($override === null) return $uri;
        
        foreach ($override as $x => $y)
        {
            $x = $this->id . '_' . $x;
            if ($y === null)
                unset($req[$x]);
            else
                $req[$x] = $y;
        }
        return $uri . '?' . http_build_query($req);
    }
    
    public function run(Zend_Controller_Response_Abstract $response = null)
    {
        if ($response===null) 
            $response = new Zend_Controller_Response_Http;
        $this->response = $response;
        $action = $this->getCurrentAction();
        $this->request->setActionName($action);
        
        ob_start();
        $this->actionRun($action);
 
        if ($this->response->isRedirect() && $this->completeRequest->isXmlHttpRequest())
        {
            $url = null;
            foreach ($response->getHeaders() as $header)
                if ($header['name'] == 'Location') $url = $header['value'];
            $code = $response->getHttpResponseCode();
            // change request to ajax response
            $response->clearAllHeaders(); $response->clearBody();
            $response->setHttpResponseCode(200);
            $response->setHeader("Content-type","application/json; charset=utf8");
            $response->setBody(Am_Controller::getJson(array('ngrid-redirect' => $url, 'status' => $code)));
            //throw new Am_Exception_Redirect($url);
        } else {
            $this->response->appendBody(ob_get_clean());
        }
        unset($this->response);
        return $response;
    }
    /** @return string */
    public function getCurrentAction()
    {
        return $this->request->getFiltered(self::ACTION_KEY, 'index');
    }
    
    public function actionRun($action)
    {
        $callback = array($this, $action.'Action');
        if (!is_callable($callback))
            throw new Am_Exception_InternalError("Action [$action] does not exists in " . get_class($this));
        call_user_func($callback);
    }
    
    public function runWithLayout($layout = 'admin/layout.phtml')
    {
        $response = new Zend_Controller_Response_Http;
        $this->run($response);
        if ($this->completeRequest->isXmlHttpRequest() || $response->isRedirect())
        {
            $response->sendResponse ();
        } else {
            $view = $this->getDi()->view;
            $view->layoutNoTitle = true;
            $view->title = $this->renderTitle(true);
            $view->content = $response->getBody();
            $view->display($layout);
        }
    }
    
    public function indexAction()
    {
        echo $this->isAjax() ? 
            $this->renderContent()  : $this->render();
    }
    
    public function isAjax($setFlag = null)
    {
        if ($setFlag !== null)
            $this->isAjax = (bool)$setFlag;
        if ($this->isAjax !== null)
            return $this->isAjax;
        return $this->completeRequest->isXmlHttpRequest();
    }
    
    /**
     * @return array string of variable names to pass between requests
     */
    public function getVariablesList()
    {
        $ret = $this->filter ? $this->filter->getVariablesList() : array();
        $ret[] = self::ACTION_KEY;
        return $ret;
    }
    
    
    function addCallback($gridEvent, $callback)
    {
        $this->callbacks[$gridEvent][] = $callback; 
    }
    function getCallbacks()
    {
        return $this->callbacks;
    }
    function runCallback($gridEvent, array & $args)
    {
        if (empty($this->callbacks[$gridEvent])) return;
        foreach ($this->callbacks[$gridEvent] as $callback)
        {
            call_user_func_array($callback, $args);
        }
    }
    static function escape($s)
    {
        return htmlentities($s, ENT_QUOTES, 'UTF-8');
    }
    function getView()
    {
        return $this->view;
    }
    function hasPermission($perm = null, $priv = null)
    {
        if (!defined('AM_ADMIN') ||!AM_ADMIN) 
            return true;
        if ($perm === null)
            $perm = $this->getPermissionId();
        return $this->getDi()->authAdmin->getUser()->hasPermission($this->getPermissionId(), $priv);
    }
    function checkPermission($perm = null, $priv = null)
    {
        if ($perm === null)
            $perm = $this->getPermissionId();
        if (!$this->hasPermission($perm, $priv))
            $this->throwPermission($perm, $priv);
    }
    function getPermissionId()
    {
        return $this->permissionId ? $this->permissionId : 'grid'.$this->id;
    }
    function setPermissionId($id)
    {
        $this->permissionId = $id;
    }
    function throwPermission($perm = null, $priv = null)
    {
        if ($perm === null)
            $perm = $this->getPermissionId();
        throw new Am_Exception_AccessDenied(___("You have no enough permissions for this operation") 
            ." (".$perm."-$priv)");
    }
}