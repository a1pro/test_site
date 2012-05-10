<?php
abstract class am4Options{
    protected $_options = array();
    protected $optionName;
    
    abstract function load();
    abstract function save();
    
    function getAll(){
        if(!$this->_options) $this->load();
        return $this->_options;
    }
    function setAll($options){
        $this->_options = $options;
        $this->save();
        
    }
    function get($name){
        return $this->__get($name);
    }
    
    function set($name, $value){
        $this->__set($name, $value);
    }
    
    
    function __get($name) {
        if(array_key_exists($name, $this->_options)){
            return $this->_options[$name];
        }
    }
    function __set($name,$value) {
        $this->_options[$name] = $value;
    }
    
}

class am4WpOptions extends am4Options{
    static $instance;
    
    function load(){
        if($options = get_option($this->optionName))
                $this->_options = $options;
        return $this;
    }
    function save(){
        
        update_option($this->optionName, $this->_options);
        return $this;
    }
    
}


class am4Settings extends am4WpOptions{
    static $instance; 
    protected $optionName = 'am4options';
    static function getInstance(){
        if(!self::$instance) self::$instance = new self();
        self::$instance->load();
        return self::$instance;
    }
    
}


class am4Errors extends am4WpOptions{
    static $instance; 
    protected $optionName= 'am4errors';
    function get($error){
        if(($index = $this->getIndex($error))!== false){
            return $this->_options[$index]['text'];
        }
    }
    
    function getIndex($error){
        foreach($this->_options as $k=>$v){
            if($v['name'] == $error)
                return $k;
        }
        return false; 
    }
    function set($error, $text){
        if(($index = $this->getIndex($error))!== false){
            $this->_options[$index]['text'] = $text;
        }else{
            $this->_options[] = array('name' =>$name, 'text'=>$text);
        }
    }
    static function getInstance(){
        if(!self::$instance) self::$instance = new self();
        self::$instance->load();
        return self::$instance;
    }
    
}


