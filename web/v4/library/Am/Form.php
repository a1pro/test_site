<?php
/**
 * @package Form
 */

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

HTML_Common2::setOption('charset', 'UTF-8');

/**
 * Adds the following functionality to QF2 forms:
 * - adds submit detecition
 * - adds init() method support
 * - adds JqueryValidation rendering
 */
class Am_Form extends HTML_QuickForm2 {
    protected $width;
    
    protected $prolog = null;
    protected $epilog = null;

    function  __construct($id=null, $attributes=null, $method='post') {
        $this->addFilter(array(__CLASS__, '_trimArray'));
        if ($id === null) $id = get_class($this);
        if (!$attributes) $attributes = array();
        if (empty($attributes['action']))
            $attributes['action'] = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
        parent::__construct($id, $method, $attributes, false);
        $this->addElement('hidden', '_save_')->setValue($id);
        $this->init();
    }

    public function addElement($elementOrType, $name = null, $attributes = null, array $data = array()) {
        $ret = parent::addElement($elementOrType, $name, $attributes, $data);
        if ($ret instanceof HTML_QuickForm2_Element_InputFile)
            $this->setAttribute('enctype', 'multipart/form-data');
        return $ret;
    }

    static function _trimArray($var) {
        array_walk_recursive($var, array(__CLASS__, '_trim'));
        return $var;
    }
    static function _trim(& $var) {
        if (is_string($var)) $var = trim($var);
    }
    /**
     * Add your elements here
     */
    function init() {

    }
    /**
     * Determine if form was submitted
     * @return bool
     */
    function isSubmitted() {
        foreach ($this->getDataSources() as $ds)
            if ($ds->getValue('_save_') == $this->getId())
                return true;
        return false;
    }
    function addProlog($string) { $this->prolog .= $string; }
    function addEpilog($string) { $this->epilog .= $string; }
    /** return rendered code before <form... tag */
    function renderProlog() {
        return $this->prolog;
    }
    /** return rendered code after </form> tag */
    function renderEpilog() {
        return $this->epilog;
    }
    function setAction($url) {
        $this->setAttribute('action', $url);
    }
    public function __toString() {
        return $this->render(new Am_Form_Renderer_User)->__toString();
    }
    public function render(HTML_QuickForm2_Renderer $renderer) {
        if (method_exists($renderer->getJavascriptBuilder(), 'addValidateJs'))
            $renderer->getJavascriptBuilder()->addValidateJs('errorElement: "span"');
        return parent::render($renderer);
    }
    public function setWidth($cssWidth) {
        $this->width = (string)$cssWidth;
    }
    public function getWidth() {
        return $this->width;
    }

    /** @return object with rendered input elements + ->hidden for hidden inputs */
    public function toObject() {
        $arr = $this->render(HTML_QuickForm2_Renderer::factory('array'))->toArray();
        $ret = new stdclass;
        foreach ($arr['elements'] as $el)
            $ret->{preg_replace('/-\d+$/', '', $el['id'])} = $el['html'];
        $ret->_id = $arr['id'];
        $ret->_hidden = implode("\n", $arr['hidden']);
        $ret->_javascript = $arr['javascript'];
        $ret->_attributes = $arr['attributes'];
        return $ret;
    }

    public function getAllErrors() {
        $ret = array();
        if ($this->getError()) $ret[] = $this->getError();
        foreach ($this->getIterator() as $el)
            if ($el->getError())
                $ret[] = $el->getError();
        return $ret;
    }
    
    public function removeElementByName($name)
    {
        foreach ($this->getIterator() as $el)
            if ($el->getName() == $name)
                $el->getContainer()->removeChild($el);
    }
    
    public function findRuleMessage(HTML_QuickForm2_Rule $rule, HTML_QuickForm2_Node $el)
    {
        $strings = array(
            'rule.required' => ___('This is a required field'),
        );
        $type = lcfirst(preg_replace('/^.+rule_/i', '', get_class($rule)));
        $tr = Zend_Registry::get('Zend_Translate');
        $fuzzy = sprintf('rule.%s', $type);
        if (array_key_exists($fuzzy, $strings))
            return $strings[$fuzzy];
    }
}    

/**
 * Callback function must return error message if failed,
 * and empty string or null if OK
 */
class Am_Rule_Callback2 extends HTML_QuickForm2_Rule_Callback {
    /**
     * Validates the owner element
     *
     * @return   bool    the value returned by a callback function
     */
    protected function validateOwner() {
        $value  = $this->owner->getValue();
        $config = $this->getConfig();
        $ret = call_user_func_array(
                $config['callback'], array_merge(array($value), array($this->owner), $config['arguments'])
        );
        $this->setMessage($ret);
        return (bool)($ret == "");
    }
}

class Am_Form_Element_Integer extends HTML_QuickForm2_Element_Input {
    protected $attributes = array('type' => 'text', 'size' => 10, 'maxlength' => 10,);
    public function __construct($name = null, $attributes = null, $data = null) {
        parent::__construct($name, $attributes, $data);
        $this->addRule('regex', 'Integer value required', '/^\d+$/');
    }
}

class Am_Form_Element_OptionsEditor extends HTML_QuickForm2_Element_Input {
    protected $attributes = array('type' => 'hidden');
    public function __construct($name = null, $attributes = null, $data = null) {
        if (is_array($attributes) && isset($attributes['class'])) {
            $attributes['class'] = $attributes['class'] . ' options-editor';
        } else {
            $attributes['class'] = 'options-editor';
        }
        parent::__construct($name, $attributes, $data);
    }

    public function setValue($value) {
        $value = is_array($value) ? Am_Controller::getJson($value) : $value;
        parent::setValue($value);
    }

    public function getRawValue() {
        $value = parent::getRawValue();
        return Am_Controller::decodeJson($value);
    }
}


class Am_Form_Element_AdvCheckbox extends HTML_QuickForm2_Element_InputCheckbox {
    /**
     * returns empty string instead of null, so value is present in Form::getValue()
     */
    function getValue() {
        $value = parent::getValue();
        return $value == null ? "" : $value;
    }
}

class Am_Form_Element_CheckboxedGroup extends HTML_QuickForm2_Container_Group
{
    public function __construct($name = null, $attributes = null, $data = null)
    {
        parent::__construct(null, $attributes, $data);
        $el = $this->addAdvCheckbox($name);
        $id = $el->getId();
        $this->addScript()->setScript(<<<CUT
// init checkboxed group $id
$(function(){
    var el = $("#$id").closest(".element");
    el.html(el.html().replace(/(<.+checkbox.+?>)(.+)/, '$1<span class="checkboxed-cnt">$2</span>'));
    $(".checkboxed-cnt", el).toggle( $("#$id").is(":checked") );
    $("#$id").change(function()
    {
        $(this).closest(".element").find(".checkboxed-cnt").toggle( this.checked );
    });
});
// end of init checkboxed group    
CUT
);
    }
}


class Am_Form_Element_AdvRadio extends HTML_QuickForm2_Element_Select {
    protected $separator = "<br />\n";
    /**
     * Support list of options like it is done for <select>
     * @param array of options key => value
     */
    public function __toString() {
        if ($this->frozen) 
        {
            return $this->getFrozenHtml();
        } else {
            if (empty($this->attributes['multiple'])) {
                $attrString = $this->getAttributes(true);
            } else {
                $this->attributes['name'] .= '[]';
                $attrString = $this->getAttributes(true);
                $this->attributes['name']  = substr($this->attributes['name'], 0, -2);
            }
            $indent = $this->getIndent();
            return $indent . $this->renderRadios();
        }
    }
    function renderRadios() {
        $out = "";
        $num = 0;
        foreach ($this->optionContainer as $option) {
            $id = $this->getName() . '---' . $num++;
            $attrs = "";
            foreach ($option['attr'] as $k => & $v)
                $attrs .= "$k=\"".Am_Controller::escape($v)."\" ";
            $out .= sprintf('<input type="radio" name="%s" %s id="%s"%s>'.
                    '<label for="%s" class="radio">%s</label>',
                    htmlentities($this->getName()),
                    $attrs,
                    $id, $option['attr']['value'] == $this->getValue() ? ' checked="checked"' : '',
                    htmlentities($id), $option['text']
            );
            $out .= $this->separator;
        }
        return $out . '<br />';
    }
    function setSeparator($string) {
        $this->separator = $string;
    }
}

class Am_Form_Element_Upload extends HTML_QuickForm2_Element_InputText {
    protected $prefix = null;
    protected $upload_id = null;
    protected $mimeTypes = array();
    protected $jsOptions = '{}';


    public function  __construct($name = null, $attributes = null, $data = null) {
        if (!is_null($attributes) && isset($attributes['class'])) {
            $attributes['class'] = $attributes['class'] . ' ' . 'upload';
        } else {
            $attributes['class'] = 'upload';
        }

        if (!is_array($data) || !isset($data['prefix'])) {
            throw new Am_Exception_InternalError('prefix is not defined in element ' . __CLASS__);
        }
        $this->prefix = $data['prefix'];
        unset($data['prefix']);
        $attributes['data-prefix'] = $this->prefix;

        parent::__construct($name, $attributes, $data);
    }

    public function setAllowedMimeTypes(array $mimeTypes) {
        $this->mimeTypes = $mimeTypes;
        $this->_setJsOptions();
        return $this;
    }

    public function __toString() {
        try {
            if ($this->frozen) {
                return $this->getFrozenHtml();
            } else {
                if (empty($this->attributes['multiple'])) {
                    $attrString = $this->getAttributes(true);
                } else {
                    $this->attributes['name'] .= '[]';
                    $attrString = $this->getAttributes(true);
                    $this->attributes['name']  = substr($this->attributes['name'], 0, -2);
                }
                $indent = $this->getIndent();
                return $indent . '<input' . $attrString . '>';
            }
        } catch (Exception $e) {
            echo "Internal Error:" . $e->getMessage();
            exit();
        }
    }

    public function getFrozenHtml() {
        $value = $this->getValue();
        if (!$value) return '';
        $value = empty($this->attributes['multiple']) ? array($value) : $value;
        $renderedFiles = array();
        $hiddens = array();
        foreach (array_filter($value) as $upload_id) {
            $upload = Am_Di::getInstance()->uploadTable->load($upload_id);
            $renderedFiles[] = sprintf('<a href="%s" target="_top">%s</a> (%s)',
                    Am_Di::getInstance()->config->get('root_url') . '/admin-upload/get?id=' . $upload->pk(),
                    $upload->getName(),
                    $upload->getSizeReadable()
            );
            $hiddens[] = sprintf('<input type="hidden" name="%s" value="%s" />',
                    $this->getName() . (empty($this->attributes['multiple']) ? '' : '[]'),
                    $upload->pk()
            );
        }

        return sprintf("<div>%s\n%s</div>",
                implode(', ', $renderedFiles),
                implode("\n", $hiddens)
        );
    }

    public function setValue($value) {
        if (!$value) return;
        if (!empty($this->attributes['multiple'])) {
            $value = array_filter($value);
            $plainValue = implode(',', $value);
        } else {
            $plainValue = $value;
        }

        $data = array();
        $value = empty($this->attributes['multiple']) ? array($value) : $value;
        foreach ($value as $upload_id) {
            $upload = Am_Di::getInstance()->uploadTable->load($upload_id, false);
            if ($upload) {
                $data[$upload_id] = array (
                        'name' => $upload->getName(),
                        'size_readable' => $upload->getSizeReadable(),
                        'upload_id' => $upload->pk(),
                        'mime' => $upload->mime
                );
            }
        }

        $this->setAttribute('data-info', Am_Controller::getJson($data));

        parent::setValue($plainValue);
    }

    function getRawValue() {
        $value = parent::getRawValue();
        return (empty($this->attributes['multiple']) || is_null($value)) ? $value : explode(',', $value);
    }

    protected function updateValue() {
        $name = $this->getName();

        //proceess upload only once fo each name
        static $executed = array();
        if (!isset($executed[$name])) {
            $executed[$name]=1;

            $name = $this->getName();
            $upload = new Am_Upload(Am_Di::getInstance());
            $upload->setPrefix($this->prefix);
            $upload->loadFromStored();
            $ids_before = $this->getUploadIds($upload);
            $upload->processSubmit($name);
            //find currently uploaded file
            $x = array_diff($this->getUploadIds($upload), $ids_before);
            $upload_id = array_pop($x);
            if ($upload_id) {
                $this->upload_id = $upload_id;
            }
        }

        $value = null;
        foreach ($this->getDataSources() as $ds) {
            if (null !== ($value = $ds->getValue($name))) {
                break;
            }
        }

        if (empty($this->attributes['multiple'])) {
            $value = $this->upload_id ? $this->upload_id : $value;
        } else {
            if ($value) {
                $value = $this->upload_id ?
                        array_merge($value, array($this->upload_id)) :
                        $value;
            } else {
                $value = $this->upload_id ? array($this->upload_id) : null;
            }
        }

        $this->setValue($value);
    }

    protected function getUploadIds(Am_Upload $upload) {
        $upload_ids = array();
        foreach($upload->getUploads() as $upload) {
            $upload_ids[] = $upload->pk();
        }
        return $upload_ids;
    }

    /**
     * @param string $jsOptions
     */
    public function setJsOptions($jsOptions) {
        $this->jsOptions = $jsOptions;
        $this->_setJsOptions();
        return $this;
    }

    protected function getAllJsOptions() {
        $jsOptions = $this->jsOptions;

        if (!count($this->mimeTypes)) {
            return $jsOptions;
        }

        $jsOptions = trim($jsOptions);
        $jsOptions = trim($jsOptions, '{},');
        $jsOptions .= ($jsOptions ? ',' : '') . sprintf("\nfileMime : [%s]",
                implode(',', array_map(create_function('$el', "return '\'' . \$el . '\'';"), $this->mimeTypes))
        );
        return sprintf("{%s}", $jsOptions);
    }

    protected function _setJsOptions() {
        $jsOptions = $this->getAllJsOptions();

        $classes = explode(' ', $this->getAttribute('class'));
        $customClassHere = false;
        foreach ($classes as $k=>$class) {
            if ($class == 'upload') {
                unset($classes[$k]);
            }
            if ($class == 'custom-' . $this->getId()) {
                $customClassHere = true;
            }
        }
        if (!$customClassHere) {
            $classes[] = 'custom-' . $this->getId();
        }
        $this->setAttribute('class', implode(' ', $classes));

        $id = $this->getId();

        $jsScript = <<<CUT
(function($){
    $(function(){
        $('.custom-{$id}').upload(
                {$jsOptions}
        );
    })
})(jQuery)
CUT;
        $elements = $this->getContainer()->getElementsByName('script-' . $this->getId());
        if (count($elements)) {
            $script = $elements[0];
        } else {
            $script = $this->getContainer()->addElement('script', 'script-' . $this->getId());
        }
        $script->setScript($jsScript);
    }
}

class Am_Form_Element_Date extends HTML_QuickForm2_Element_InputText {
    const DATE_FORMAT_SQL_REGEXPR = '/^\d{4}-\d{2}-\d{2}$/';

    public function  __construct($name = null, $attributes = null, $data = null) {
        if (!is_null($attributes) && isset($attributes['class'])) {
            $attributes['class'] = $attributes['class'] . ' ' . 'datepicker';
        } else {
            $attributes['class'] = 'datepicker';
        }
        parent::__construct($name, $attributes, $data);
        $this->addRule('callback2', 'error', array($this, 'checkDate'));
    }

    public function checkDate($date) 
    {
        if ($date === false) 
        {
            return ___('Date must be in format %s',
                Zend_Registry::get('Am_Locale')->getDateFormat());
        }
    }

    /*
     * @param string $value date in SQL or Readable format
     *
    */

    public function setValue($value) {
        if (preg_match(self::DATE_FORMAT_SQL_REGEXPR, $value)) { //SQL format
            parent::setValue($this->convertSqlToReadable($value));
        } else { //Readable format
            parent::setValue($value);
        }
    }

    /*
     *
     * @return mixed (string|null|false) @see self::convertReadableToSQL
     *
    */
    public function getValue() {
        return $this->convertReadableToSQL(parent::getValue());
    }

    /*
     * 
     * @param string $date date in Readable format
     * @return mixed (string|false|null) date in SQL format, 
     * null - if string is empty, 
     * false - if string is incorrect 
     * 
    */
    public function convertReadableToSQL($date) 
    {
        if (!$date) return null;
        $format = Zend_Registry::get('Am_Locale')->getDateFormat();
        if (is_callable(array('DateTime', 'createFromFormat')))
            $d = DateTime::createFromFormat($format, $date);
        else
            $d = self::createFromFormat($format, $date);
        if ($d === false) return false;
        return $d->format('Y-m-d');
    }
    
    /**
     * Parse date from string (for PHP 5.2.x)
     * @param string $dateFormat subset of DateTime::createFromFormat() : dmyYM or null to use default
     * @param strin $string to parse
     * @return DateTime|false
     */
    public static function createFromFormat($dateFormat = null, $string)
    {
        if ($dateFormat === null)
            $dateFormat = Zend_Registry::get('Am_Locale')->getDateFormat();
        $regex = "";
        $vars = array( 0 => null );
        for ($i=0;$i<strlen($dateFormat);$i++)
        {
            switch ($dateFormat[$i])
            {
                case 'd':
                    $vars[] = 'day';
                    $regex .= "([0-3]*[0-9])"; 
                    break;
                case 'm':
                    $vars[] = 'month';
                    $regex .= "([0-1]*[0-9])"; 
                    break;
                case 'y':
                    $vars[] = 'year';
                    $regex .= "([0-9]{2})"; 
                    break;
                case 'Y':
                    $vars[] = 'year';
                    $regex .= "([0-9]{4})"; 
                    break;
//                case 'M':
//                    $regex .= "(?<MONTH>\w+)"; 
//                    break;
                default:
                    $regex .= preg_quote($dateFormat[$i], '/');
            }
        }
        if (!preg_match('/'. $regex.'/u',  $string, $regs))
            return false;
        foreach ($regs as $k => $v)
        {
            if (!empty($vars[$k]))
                $regs[ $vars[$k] ] = $v;
        }
        // work it out
        if ($regs['year'] < 100) $regs['year'] += 2000;
        if (empty($regs['month']) && !empty($regs['MONTH']))
        {
            $monthNames = Zend_Registry::get('Am_Locale')->getMonthNames();
        }
        $dt = new DateTime;
        $dt->setDate($regs['year'], $regs['month'], $regs['day']);
        $dt->setTime(0,0,0);
        return $dt;
    }

    private function convertSqlToReadable($date) {
        if (!$date) return '';
        return date(Zend_Registry::get('Am_Locale')->getDateFormat(), strtotime($date));
    }
}

class Am_Form_Element_EmailCheckbox extends Am_Form_Element_AdvCheckbox {
    function __toString() {
        return Am_Form_Element_EmailLink::decorateWithLink(parent::__toString(), $this);
    }
}

class Am_Form_Element_EmailSelect extends HTML_QuickForm2_Element_Select {
    function __toString() {
        return Am_Form_Element_EmailLink::decorateWithLink(parent::__toString(), $this);
    }
}

class Am_Form_Element_EmailLink extends HTML_QuickForm2_Element {

    function setValue($value) {

    }
    function getRawValue() {
        return null;
    }
    function getType() {
        return 'email_link';
    }
    function updateValue() {

    }

    function __toString() {
        return sprintf('<div>%s</div>', self::decorateWithLink('', $this));
    }

    public static function decorateWithLink($str, HTML_QuickForm2_Element $el) {
        return self::getPrefix($el)
                . $str
                . ( ($el instanceof HTML_QuickForm2_Element_Select) ? '<br /><div>' : '' )
                . self::getEditLink($el)
                . ( ($el instanceof HTML_QuickForm2_Element_Select) ? '</div>' : '' );
    }

    public static function getPrefix(HTML_QuickForm2_Element $el) {
        return sprintf('<a name="%s"></a>', $el->getName());
    }

    public static function getEditLink($el, $day = null, $product_id=null) {
        $label = $el->getLabel();
        if (is_array($label)) {
            $label = array_map('strip_tags', $label);
            $label = implode(' ', $label);
        }

        $url = REL_ROOT_URL .
                sprintf('/admin-email-templates/edit/?name=%s&b=%s',
                Am_Form_Setup::name2dots($el->getName()),
                rawurlencode($_SERVER['REQUEST_URI'] . '#' . $el->getName())
        );

        if (!is_null($day)) {
            $url .= sprintf('&day=%d', $day);
        }

        if (!is_null($product_id)) {
            $url .= sprintf('&product_id=%d', $product_id);
        }

        $url .= sprintf('&label=%s', rawurlencode($label));

        return sprintf('<a href="%s" class="email-template">%s</a>',
                $url,
                ___('Edit E-Mail Template')
        );
    }
}

class Am_Form_Element_Period extends HTML_QuickForm2_Element_Input {
    /** @var Am_Period */
    protected $period;
    protected $options = array(
    );

    function __construct($name = null, $attributes = null, $data = null) {
        $this->attributes['type'] = 'period';
        $this->options = array(
            'd' => ___('Days'),
            'm' => ___('Months'),
            'y' => ___('Years'),
            'lifetime' => ___('Lifetime'),
//            'fixed' => ___('Fixed'),
        );
        parent::__construct($name, $attributes, $data);
        $this->period = new Am_Period();
    }

    function setValue($value) {
        if (is_array($value)) {
            if ($value['u'] == 'lifetime')
                $this->period = Am_Period::getLifetime();
            else if ($value['c'] && $value['u'])
                $this->period = new Am_Period($value['c'], $value['u']);
            else
                $this->period = new Am_Period;
        } else {
            $this->period = new Am_Period($value);
        }
        $value = $this->period->__toString();
        parent::setValue($value);
    }
    function  __toString() {
        return sprintf('<div class="input_period">'.
                '<input type="text" name="%s[c]" value="%s" size=10 id="%s">&nbsp;'.
                '<select name="%s[u]" size="1" id="%s">'.
                Am_Controller::renderOptions($this->options,
                $this->period->getCount() != Am_Period::MAX_SQL_DATE ?
                $this->period->getUnit() : 'lifetime') .
                '</select></div>',
                $this->getName(),
                $this->period->getCount(),
                $this->getId() . '-c',
                $this->getName(),
                $this->getId() . '-u'
        );
    }
}

class Am_Form_Element_Script extends HTML_QuickForm2_Element {
    protected $script;
    public function getType() {
        return 'script';
    }
    public function getRawValue() {
        return null;
    }
    public function setValue($value) {

    }
    public function __toString() {
        return
                '<script type="text/javascript">'.  "\n" .
                $this->script .
                "\n" . '</script>';
    }
    public function setScript($script) {
        $this->script = $script;
    }
    public function render(HTML_QuickForm2_Renderer $renderer) {
        $renderer->renderHidden($this);
        return $renderer;
    }
}

class Am_Form_Element_Html extends HTML_QuickForm2_Element {
    protected $html = '';

    public function getType() {
        return 'html';
    }

    public function setValue($value) {

    }
    public function getRawValue() {
        return null;
    }

    public function setHtml($html) {
        $this->html = $html;
        return $this;
    }

    public function __toString() {
        return $this->html;
    }
}

class Am_Form_Element_Csrf extends HTML_QuickForm2_Element_InputHidden {
    protected $minutes = 15;
    protected $session;
    protected $uniqId;
    protected $submittedValue = "", $sessionValue = "";

    public function __construct($name = null, $attributes = null, $data = null) {
        parent::__construct($name, $attributes, $data);
        $this->addRule('callback2', $this->getErrorMessage(),  array($this, 'checkValue'));
    }
    function checkValue($value) {
        if (strlen($this->sessionValue) && $this->sessionValue === $this->submittedValue)
            return null;
        return $this->getErrorMessage();
    }
    public function getErrorMessage() {
        return sprintf(___("CSRF protection error - form must be submitted within %d minutes after displaying, please repeat"), $this->minutes);
    }
    public function updateValue() {
        $name = $this->getName();
        foreach ($this->getDataSources() as $ds) {
            if (null !== ($value = $ds->getValue($name))) {
                $this->submittedValue = $value;
                break;
            }
        }
        if ($this->uniqId) {
            if ($this->sessionValue === "" && $this->getSession()->value)
                $this->sessionValue = $this->getSession()->value;
            $this->generateValue();
        }
    }
    public function generateValue() {
        $v = uniqid('c', true);
        $session = $this->getSession();
        $session->setExpirationHops(1);
        $session->setExpirationSeconds($this->getMinutes()*60);
        $session->value = $v;
        $this->setValue($v);
        return $v;
    }
    /** @return Zend_Session_Namespace */
    public function getSession() {
        if (!$this->session)
            $this->session = new Zend_Session_Namespace('qf2_csrf_' . $this->getUniqId());
        return $this->session;
    }
    public function getMinutes() {
        return (int)$this->minutes;
    }
    public function setMinutes($m) {
        $this->minutes = (int)$m;
    }
    public function getUniqId() {
        if (!$this->uniqId)
            $this->uniqId = 'formcsrf';
        return $this->uniqId;
    }
    public function setUniqId($uniqId) {
        $this->uniqId = $uniqId;
    }
}

class Am_Form_Element_HtmlEditor extends HTML_QuickForm2_Element_Textarea {
    protected $dontInitMce = false;

    public function __construct($name = null, $attributes = null, $dontInitMce = null) {
        if ($attributes === null)
            $attributes = array('rows' => 10);
        $attributes = (array)$attributes;
        $attributes['class'] = 'no-label';
        $attributes['style'] = 'width: 95%';
        $this->dontInitMce = $dontInitMce;
        parent::__construct($name, $attributes, null);
    }
    public function render(HTML_QuickForm2_Renderer $renderer) {
        $id = $this->getId();
        $url = REL_ROOT_URL . '/application/default/views/public/js/ckeditor/ckeditor.js';
        $renderer->getJavascriptBuilder()->addElementJavascript(<<<CUT

if (!window.CKEDITOR) {            
    var script = $('<script type="text/javascript" src="$url"></' + 'script>');  
    $('head').append(script);    
}

CUT
);
        if (!$this->dontInitMce)
            $renderer->getJavascriptBuilder()->addElementJavascript(<<<CUT
$(function(){            
    initCkeditor('$id');   
});
CUT
            );
        return parent::render($renderer);
    }
}

class Am_Form_Element_MagicSelect extends HTML_QuickForm2_Element_Select
{
    public function __construct($name = null, $attributes = null, array $data = array())
    {
        if ($attributes === null) $attributes = array();
        $attributes['class'] = 'magicselect';
        $attributes['multiple'] = 'multiple';
        $attributes['data-offer'] = '-- ' . ___("Please Select") . ' --';
        parent::__construct($name, $attributes, $data);
    }
}

class Am_Form_Container_AdvFieldset extends HTML_QuickForm2_Container_Fieldset
{
    public function __construct($name = null, $attributes = null, $data = null)
    {
        if ($attributes === null) $attributes = array();
        parent::__construct($name, $attributes, $data);
        $id = $this->getId();

        $opened = explode(';', @$_COOKIE['am-adv-fieldset']);
        if (in_array($id,$opened)) {
            $this->setAttribute('data-hidden', false);
        } else {
           if (!isset($attributes['data-hidden']))
               $this->setAttribute('data-hidden', true);
        }

        $this->addScript()->setScript(<<<CUT
$(function(){
    $("#$id legend").click(function(){
        var cookieName = 'am-adv-fieldset';
        var fs = $(this).closest("fieldset");
        var hidden = !fs.data('hidden');
        hidden ? setClosed('$id') : setOpened('$id');
        fs.data('hidden', hidden);
        fs.find(".row").toggle(!hidden);
        fs.find(".plus-minus").html(hidden ? '+' : '&minus;' );
        fs.find(".dots").text(hidden ? '...' : '' );

        function setOpened(id) {
            var openedIds = getOpenedIds();
            if (!isOpened(id)) {
                openedIds.push(id);
            }
            setCookie(cookieName, openedIds.join(';'));
        }

        function setClosed(id) {
            var openedIds = getOpenedIds();
            for (var i=0; i<openedIds.length; i++) {
                if (openedIds[i] == id) {
                    openedIds.splice(i, 1);
                    break;
                }
            }
            setCookie(cookieName, openedIds.join(';'));
        }

        function getOpenedIds() {
            var cookie = getCookie(cookieName);
            return cookie ? cookie.split(';') : [];
        }

        function isOpened(id) {
            var openedIds = getOpenedIds();
            for (var i=0; i<openedIds.length; i++) {
                if (openedIds[i] == id) {
                    return true;
                }
            }
            return false;
        }

        function setCookie(name, value) {
                var today = new Date();
                var expiresDate = new Date();
                expiresDate.setTime(today.getTime() + 365 * 24 * 60 * 60 * 1000); // 1 year
                document.cookie = name + "=" + escape(value) + "; path=/; expires=" + expiresDate.toGMTString() + ";";
            }

        function getCookie(name) {
            var prefix = name + "=";
            var start = document.cookie.indexOf(prefix);
            if (start == -1) return null;
            var end = document.cookie.indexOf(";", start + prefix.length);
            if (end == -1) end = document.cookie.length;
            return unescape(document.cookie.substring(start + prefix.length, end));
        }
    });
    $("#$id").data('hidden', !$("#$id").data('hidden')).find('legend').click();
});
CUT
        );
    }
    public function getLabel()
    {
        $label = (array)parent::getLabel();
        if (preg_match('/plus-minus/', $label[0])) return $label;
        if ($this->getAttribute('data-hidden')) {
            $sign = '+';
            $points = '...';
        } else {
            $sign = '-';
            $points = '';
        }
        $label[0]  = '[<span class="plus-minus"><b>' . $sign. '</b></span>]&nbsp;' . $label[0] . 
            '<span class="dots">'.$points.'</span>';
        return $label;
    }
}

class Am_Form_Renderer extends HTML_QuickForm2_Renderer_Default {
    public function __construct() 
    {
        parent::__construct();
        $this->setOption(array(
                'errors_prefix' => null,
                'errors_suffix' => null,
                'required_note' => null,
        ));
        $this->setJavascriptBuilder(new Am_Form_JavascriptBuilder);
        $this->setTemplateForClass('html_quickform2_element', '<div class="row" id="row-{id}"><div class="element-title"><label for="{id}"><qf:required><span class="required">* </span></qf:required>{label}</label><qf:label_2><div class="comment">{label_2}</div></qf:label_2></div><div class="element<qf:error> error</qf:error>">{element}<qf:error><br /><span class="error">{error}</span></qf:error></div></div>'."\n");
        $this->setTemplateForClass('html_quickform2_container_group', '<div class="row" id="row-{id}"><div class="element-title"><label><qf:required><span class="required">* </span></qf:required>{label}</label><qf:label_2><div class="comment">{label_2}</div></qf:label_2></div><div class="element group<qf:error> error</qf:error>">{content}<qf:error><br /><span class="error">{error}</span></qf:error></div></div>'."\n");
        $this->setTemplateForClass('html_quickform2', '<div id="form_hide3" class="am-form">{errors}<form{attributes}>{content}{hidden}</form><qf:reqnote><div class="reqnote">{reqnote}</div></qf:reqnote></div>'."\n");
        $this->setTemplateForClass('html_quickform2_container_fieldset', '<fieldset{attributes}><qf:label><legend id="{id}-legend">&nbsp;&nbsp;{label}</legend></qf:label>{content}</fieldset>'."\n");
    }
    public function finishForm(HTML_QuickForm2_Node $form) 
    {
        // a bug in QF2 - form errors are not added to array
        if ($form->getError())
        {
            $this->errors[] = $form->getError();
        }
        parent::finishForm($form);
        // insert width
        if (method_exists($form, 'getWidth') && $form->getWidth())
            $this->html[0][0] = preg_replace('|<div class="am-form">|', '<div class="am-form" style="width: '.$form->getWidth().'">', $this->html[0][0]);
        $this->html[0][0] =
                $form->renderProlog() .
                join("\n", $this->getJavascriptBuilder()->getLibraries()) .
                $this->html[0][0] .
                $form->renderEpilog();
    }
    public function renderHidden(HTML_QuickForm2_Node $element) {
        if ($err = $element->getError())
            $this->errors[] = $err;
        return parent::renderHidden($element);
    }
    public function findTemplate(HTML_QuickForm2_Node $element, $default = null) 
    {
        $ret = parent::findTemplate($element, $default);
        if ($element->hasClass('no-label'))
        {
            $ret = str_replace('class="row"', 'class="row no-label"', $ret);
        }
        return $ret;
    }
    /**
     * format multi-line labels
     */
    public function startForm(HTML_QuickForm2_Node $form)
    {
        foreach ($form->getRecursiveIterator() as $el)
        {
            $label = (array)$el->getLabel();
            if (empty($label)) continue;
            if (count($label)==1)
            {
                $label = explode("\n", $label[0], 2);
            }
            if (count($label) > 1)
                $label[1] = nl2br($label[1]);
            
            if ($url = $this->findHelpUrl($el))
                $label[0] .= sprintf("&nbsp;<span class='admin-help'><a href='%s' target='_blank'><sup>?</sup></a></span>", Am_Controller::escape($url));
            
            $el->setLabel($label);
        }
        return parent::startForm($form);
    }
    function findHelpUrl(HTML_QuickForm2_Node $el)
    {
        /// find help id
        $data = $el->getData();
        if (!empty($data['help-path']) || !empty($data['help-id']))
        {
            $url = "";
            do {
                $data = $el->getData();
                if (!empty($data['help-path']))
                {
                    $url = $data['help-path'] . $url;
                    break;
                } elseif (!empty($data['help-id'])) {
                    $url = $data['help-id'] . $url;
                }
            } while ($el = $el->getContainer());
            $url = 'http://v4.amember.com/docs/' . $url;
            return $url;
        }
    }
}

class Am_Form_Renderer_User extends Am_Form_Renderer {
}
class Am_Form_Renderer_Admin extends Am_Form_Renderer 
{
}

HTML_QuickForm2_Factory::registerElement('period', 'Am_Form_Element_Period');
HTML_QuickForm2_Factory::registerElement('date', 'Am_Form_Element_Date');
HTML_QuickForm2_Factory::registerElement('integer', 'Am_Form_Element_Integer');
HTML_QuickForm2_Factory::registerElement('advcheckbox', 'Am_Form_Element_AdvCheckbox');
HTML_QuickForm2_Factory::registerElement('advradio', 'Am_Form_Element_AdvRadio');
HTML_QuickForm2_Factory::registerElement('email_checkbox', 'Am_Form_Element_EmailCheckbox');
HTML_QuickForm2_Factory::registerElement('email_select', 'Am_Form_Element_EmailSelect');
HTML_QuickForm2_Factory::registerElement('email_link', 'Am_Form_Element_EmailLink');
HTML_QuickForm2_Factory::registerElement('email_with_days', 'Am_Form_Element_EmailWithDays');
HTML_QuickForm2_Factory::registerElement('upload', 'Am_Form_Element_Upload');
HTML_QuickForm2_Factory::registerElement('script', 'Am_Form_Element_Script');
HTML_QuickForm2_Factory::registerElement('html', 'Am_Form_Element_Html');
HTML_QuickForm2_Factory::registerElement('csrf', 'Am_Form_Element_Csrf');
HTML_QuickForm2_Factory::registerElement('options_editor', 'Am_Form_Element_OptionsEditor');
HTML_QuickForm2_Factory::registerElement('htmleditor', 'Am_Form_Element_HtmlEditor');
HTML_QuickForm2_Factory::registerElement('magicselect', 'Am_Form_Element_MagicSelect');
HTML_QuickForm2_Factory::registerElement('checkboxedgroup', 'Am_Form_Element_CheckboxedGroup');
HTML_QuickForm2_Factory::registerElement('advfieldset', 'Am_Form_Container_AdvFieldset');

HTML_QuickForm2_Factory::registerRule('callback2', 'Am_Rule_Callback2');