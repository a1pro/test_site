<?php

/*
Plugin Name: aMember PRO v4 Integration Plugin for Wordpress
Plugin URI: http://www.amember.com/
Description: Add aMember functionality to Wordpress blog
Version: 0.1
Author: Alexander Smith <alexander@cgi-central.net>
Author URI: http://www.amember.com/
*/
ini_set("display_errors",1);
define("AM4_PLUGIN_DIR", WP_PLUGIN_DIR."/amember4");
define("AM4_PLUGIN_URL", WP_PLUGIN_URL . "/amember4");
define("AM4_INCLUDES", AM4_PLUGIN_DIR."/includes");
define('AM4_POST_META', 'am4options');
define('AM4_CAT_OPTION', 'am4catoptions');
define('AM4_STYLE_OPTION', 'am4templates');
define('AM4_ERROR_OPTION', 'am4errors'); 
define('DIRECTORY_SEPARATOR', '/');

//register_activation_hook(AM4_PLUGIN_DIR."/amember4.php", "amember4_plugin_activated");
//register_deactivation_hook(AM4_PLUGIN_DIR."/amember4.php", "amember4_plugin_deactivated");

class am4PluginsManager {
    private static $__plugins = array();
    private static $cache = array();
    
    static function get($name){
        if(array_key_exists($name, self::$__plugins)) return self::$__plugins[$name];
        return null; 
    }
    static function getPlugin($name){
        return self::get($name);
    }
    
    static function initPlugin($name){
        // Check if plugin exists already; 
        if(($plugin = self::get($name)) !== null) return $plugin;
        if(!class_exists($cname = "am4".ucfirst($name)) && is_file($plugin_file = dirname(__FILE__)."/".$name.".php")){
            require_once($plugin_file);
        }
        if(class_exists($cname = "am4".ucfirst($name))){
            $plugin = new $cname();
            $plugin->init();
            self::$__plugins[$name] = $plugin; 
            return $plugin;
        }
        
        return null;
    }
    
    static function includes(){
        include_once(AM4_INCLUDES . "/utils.php"); 
        include_once(AM4_INCLUDES . "/plugin.php"); 
        include_once(AM4_INCLUDES . "/controller.php"); 
        include_once(AM4_INCLUDES . "/view.php"); 
        include_once(AM4_INCLUDES . "/access.php"); 
        include_once(AM4_INCLUDES . "/options.php"); 
    }
    
    static function init(){
        load_plugin_textdomain('am4-plugin', false, basename(dirname(__FILE__)).'/languages');
        self::includes();
        self::initPlugin("basic");
        self::initPlugin("menu");
        if(self::isConfigured()){
            self::initAPI();
            self::initPlugin("protection");
            self::initPlugin("shortcodes");
            self::initPlugin("widgets");
            self::initPlugin('tinymce');
        }
        self::initAjaxActions();
        
    }
    
    static function createController($name, $is_ajax=false){
        $f = create_function('', $a = '$class = new '.$name.'(); $class->run'.($is_ajax ? 'Ajax' : '').'();');
        return $f;
    }
    static function runController($name, $is_ajax=false){
        call_user_func(self::createController($name, $is_ajax));
    }
    static function initAjaxActions(){
        foreach(get_declared_classes() as $cname){
            if(is_subclass_of($cname, 'am4PageController')){
                foreach(get_class_methods($cname) as $m){
                    if(preg_match("/^doAjax(.*)/", $m, $r)){
                        $hook = "wp_ajax_".am4PageController::getAjaxActionValue($cname);
                        add_action($hook, self::createController($cname, am4PageController::AJAX));
                        break;
                    }
                    
                }
            }
        }
        
    }
    
    static function getOption($option){
        return am4Settings::getInstance()->get($option);
    }
    static function getAmemberPath(){
        return self::getOption("path");
    }
    static function getAmemberURL(){
        return self::getOption("url");
    }
    
    static function initAPI($path=''){
        $path = $path ? $path : self::getAmemberPath();
        if($path === false) throw new Exception(__('aMember path is empty', 'am4-plugin'));
        if(!is_file($lite = $path.'/library/Am/Lite.php')) {
            throw new Exception(__('Specified path is not an aMember installation', 'am4-plugin'));
        }
        require_once($lite);
    }
    static function getAPI(){
        if(!class_exists('Am_Lite')){
            try{
                self::initAPI();
            }catch(Exception $e){
                return false;
            }
        }
        return Am_Lite::getInstance();
    }
    
    static function isConfigured(){
        if(!self::getAPI()) return false;
        else return true;
        
    }
    static function getAMProducts(){
        
        if(!array_key_exists("products", self::$cache)){
            self::$cache['products'] = self::getAPI()->getProducts();
        }
        return self::$cache['products'];
    }
    
    static function getAMCategories(){
        if(!array_key_exists("categories", self::$cache)){
            self::$cache['categories'] = self::getAPI()->getCategories();
        }
        return self::$cache['categories'];
                
    }
    
}


//if(!defined('AM_VERSION')) 
am4PluginsManager::init();

