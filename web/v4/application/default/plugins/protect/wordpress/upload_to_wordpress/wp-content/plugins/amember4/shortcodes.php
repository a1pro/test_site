<?php

class am4Shortcodes extends am4Plugin{
    protected $shortcodes;
    function action_Init(){
        foreach(get_declared_classes() as $c){
            if(preg_match("/^am4Shortcode_(\S+)$/", $c, $regs)){
                $this->registerShortcode($regs[1], $c);
            }
        }
    }
    function action_AdminInit(){
        add_meta_box( 'amember_shortcodes_sectionid', __("aMember Shortcodes Help", 'am4-plugin'),
                array($this, "getHelp"), "post", 'advanced','high' );
        add_meta_box( 'amember_shortcodes_sectionid', __("aMember Shortcodes Help", 'am4-plugin'),
                array($this, "getHelp"), "page", 'advanced','high' );
        
    }
    function registerShortcode($name, $class){
        $shortcode = new $class();
        $shortcode->init();
        add_shortcode($name, array($shortcode,'run'));
        $this->shortcodes[$name] = $shortcode;
    }
    
    function getHelp(){
        $view = new am4View("shortcodes_help");
        $view->assign("shortcodes", $this);
        $view->render();
    }
    function getShortcodes(){
        if($this->shortcodes) return $this->shortcodes;
        else return array();
        
    }
    
}

class am4Shortcode{

    
    function getSyntax(){
        // Return Fill Showrtcode syntax;
        return "[".$this->getName()."]";
    }
    function getDescription(){
        return '';
    }
    function getName(){
        preg_match("/^am4Shortcode_(\S+)$/", get_class($this), $regs);
        return $regs[1];
    }
    
    function run($atts=array(), $content=''){
        if(strlen($content)) return do_shortcode($content);
    }
    function init(){
    }
    
    function getHelp($incTable = true){
        $name = $this->getName();
        try{
            $view = new am4View("shortcode_".$name);
            $view->assign("shortcode", $this);
            $help = $view->fetch();
            return $help;
        } catch(Exception $e){
        }
    }

    function convertToAccessRequirement($setting){
        $records = array();    
        $lines = split(";", $setting);
        foreach((array)$lines as $l){
            $items = split(',', $l);
            if(preg_match("/^([pg])(\d+)$/", $items[0],$regs)){
                $records[] = new am4AccessRequirement(array('id' => $regs[2], 
                                                            'type'=>($regs[1] == 'g' ? am4UserAccess::CATEGORY : am4UserAccess::PRODUCT),
                                                            'start' =>  $items[1],
                                                            'stop'  =>  $items[2]
                    ));
            }
        }
        return $records;
    }
    
    
    

}


class am4Shortcode_am4user extends am4Shortcode{
    function getDescription()
    {
        return __('Show User Info', 'am4-plugin');
    }
    function getSyntax() {
        return "[am4user var='']";
    }
    function run($atts=array(), $content=''){
        if(am4PluginsManager::getAPI()){
            $user = am4PluginsManager::getAPI()->getUser();
            if(!$atts) $atts['var'] = 'name';
            switch($atts['var']){
                case 'name' : $ret = $user['name_f']." ".$user['name_l']; break;
                default     :   $ret = $user[$atts['var']]; break;
            }
        }
        return $ret; 
    }
}

class am4Shortcode_am4info extends am4Shortcode{
    function getDescription(){
        return __('Show System Info', 'am4-plugin');
    }
    function getSyntax(){
        return "[am4info var='' redirect='']";
    }
    function run($atts=array(), $content=''){
        if(!is_array($atts)) return;
        switch($atts['var']){
            case 'signupurl'    :   $ret = am4PluginsManager::getAPI()->getSignupURL(); break;
            case 'logouturl'    :   $ret = am4PluginsManager::getAPI()->getLogoutURL(); break;
            case 'loginurl'     :   $ret = am4PluginsManager::getAPI()->getLoginURL(); break;
            case 'renewurl'     :   $ret = am4PluginsManager::getAPI()->getRenewURL(); break;
            case 'profileurl'   :   $ret = am4PluginsManager::getAPI()->getProfileURL(); break;
            default : $ret = ''; break;
        }
        return ($ret ? ($atts['title'] ? '<a href="'.$ret.'">'.$atts['title'].'</a>' : $ret):'');
    }
}


class am4Shortcode_am4guest extends am4Shortcode{
    function getDescription()
    {
        return __('Show content only for guest users', 'am4-plugin');
    }
    function getSyntax(){
        return "[am4guest notactive=1][/am4guest]";
    }
    function run($atts=array(), $content=''){
        if(!am4PluginsManager::getAPI()->isLoggedIn()){
            return do_shortcode($content);
        }else if($atts['notactive'] && !am4PluginsManager::getAPI()->isUserActive()){
            return do_shortcode($content);
        }
    }
}


class am4Shortcode_am4aff extends am4Shortcode{
    function getDescription()
    {
        return __('Show content only for affiliates', 'am4-plugin');
    }
    function getSyntax(){
        return "[am4aff][/am4aff]";
    }
    function run($atts=array(), $content=''){
        $api = am4PluginsManager::getAPI();
        if($api->isLoggedIn()){
            $user = $api->getUser();
            if($user['is_affiliate'] > 0) 
                return do_shortcode($content);
        }
    }
}



class am4Shortcode_am4show extends am4Shortcode{
    function getDescription(){
        return __('Show block if user have access', 'am4-plugin');
    }
    function getSyntax(){
        return "[am4show have='' not_have='' user_error='' guest_error=''][/am4show]";
        
    }
    function run($atts=array(), $content=''){
        $errors = am4Errors::getInstance();
        if(!am4PluginsManager::getAPI()->isLoggedIn()){
            return do_shortcode($errors->get($atts['guest_error']));
        }
        if(!am4PluginsManager::getAPI()->isUserActive()) return do_shortcode($errors->get($atts['user_error']));

        $access = new am4UserAccess();        
        //User is logged in let's check his access level;
        if($atts['have']){
            $records = $this->convertToAccessRequirement($atts['have']);
            if(!$access->anyTrue($records)) return do_shortcode($errors->get($atts['user_error']));
        }
        if($atts['not_have']){
            $records = $this->convertToAccessRequirement($atts['not_have']);
            if(!$access->allFalse($records)){
                return do_shortcode($errors->get($atts['user_error']));
                
            }
        }
        return do_shortcode($content);
    }
}
