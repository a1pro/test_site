<?php

class am4Protection extends am4Plugin{
    private $actions = array();
    function action_AdminInit(){
        // Setup filters to display post/page requirements;
        foreach(get_post_types() as $type=>$o){
            if(!class_exists($cname = "am4Protection_".$type)) $cname = "am4ProtectionFormController";
            add_meta_box( 'amember_sectionid', __("aMember Protection Settings", 'am4-plugin'),
            am4PluginsManager::createController($cname), $type, 'advanced','high' );

            add_filter("manage_edit-".$type."_columns", array($this, "addProtectColumn"), 10, 1);
            add_filter("manage_".$type."_posts_custom_column", array($this, 'addProtectionContent'), 10, 2);
        }
    }
    
    function addProtectColumn($list){
        $list['am4requirements'] = __('Protection', 'am4-plugin');
        return $list;
        
    }
    function addProtectionContent($name, $id){
        if($name =='am4requirements'){
            $p = (array)get_post_meta($id, AM4_POST_META, true);
            if(!$p['protected']) return ;
            foreach(am4AccessRequirement::createRequirements($p['access']) as $r){
                $value .= $r."<br/>";
            }
        }
        print $value;
    }
    
    function action_SavePost(){
        if(!current_user_can("manage_options") || !is_admin()) return;
        $screen = get_current_screen();
        if($screen->action == 'add') return;
        $class = "am4Protection_".am4Request::getWord("post_type");
        if(!class_exists($class)) $class = "am4ProtectionFormController";
        $c = new $class;
        $c->directAction('save');
        
    }
    
    function action_EditCategoryForm($category){
        if(!$category->term_id) return;
        $controller = new am4Protection_category();
        $controller->category = $category;
        $controller->run();
    }
    function action_AdminActionEditedtag(){
        am4PluginsManager::runController('am4Protection_category');
    }
    function action_AdminFooter(){
        $screen = get_current_screen();
        
        if(($screen->id == 'edit-post' || $screen->id =='edit-page') && !am4Request::getWord('action'))
            am4PluginsManager::runController ('am4Protection_bulk');
    }
    
    
    function makeRedirect($type, $settings,$is_category=false){
        $is_cat = ($is_category?"_cat":"");
        $api = am4PluginsManager::getAPI();
        $action = $settings[$type.'_action'.$is_cat]; 
        if(empty($action)) $action = 'login';
        switch($action){
            case 'page' : $url = get_page_link($settings[$type.'_action'.$is_cat.'_page']); break;
            case 'redirect'  : $url = $settings[$type.'_action'.$is_cat.'_redirect'.$is_cat]; break;
            case 'login'    : 
                $url = $api->isLoggedIn() ? $api->getSignupURL() : $api->getLoginURL($_SERVER['REQUEST_URI']); 
                break;
            default:   $url = false;
        }
        // not redirect action;
        if($url === false) return;
        if(!headers_sent()){
            if(!$url) $url = get_site_url();
            wp_redirect($url);
        }else{
            throw new Exception(__("Headers already sent! Can't redirect.", 'am4-plugin'));
        }
    }
    
    function action_Wp($wp){
        if(current_user_can("manage_options")) return; 
        $settings = am4Settings::getInstance()->getAll();
        $access = new am4UserAccess();
        $type = $access->isLoggedIn() ? "user" : "guest";
        // handle blog protection;
        if($settings['protected'] && !defined('AM_VERSION')){
            if((!$access->isLoggedIn()) || ($access->isLoggedIn() && !$access->anyTrue(am4AccessRequirement::createRequirements($settings['access'])))){
                // First check if user try to access page that he is redirected to
                if(!(($settings[$type.'_action'] == 'page') 
                        && is_page() 
                        && ($page = get_page($settings[$type.'_action_page']))
                        && ($page->ID == $GLOBALS['wp_query']->post->ID))
                    ){
                    $this->makeRedirect($type, $settings);
                }       
            }
        }
        if(is_single() || is_page()){
            $settings = $this->getPostAccess($GLOBALS['wp_query']->post);
            if($settings['protected'] && !$access->anyTrue($this->getPostRequirements($GLOBALS['wp_query']->post)))
                    $this->makeRedirect($type, $settings);
        }
        if(is_category()){
            $cat_settings = get_option(AM4_CAT_OPTION);
            if(is_array($cat_settings) && ($settings = $cat_settings[$GLOBALS['wp_query']->query_vars['cat']]) && $settings['protected']){
                if(!$access->anyTrue(am4AccessRequirement::createRequirements($settings['access']))){
                    $this->makeRedirect($type,$settings, true);
                }
            }
        }
    }
    
    function getPostAccess(&$post){
        $settings = (array)get_post_meta($post->ID, AM4_POST_META,true);
        if(!$settings['protected']){
            // Check category;
            if($post->post_type == 'post'){ // Check category protecton as well; 
                $cat_settings = get_option(AM4_CAT_OPTION);
                if($cat_settings)
                    foreach(get_the_category($post->ID) as $cat){
                        if($cat_settings[$cat->cat_ID] && $cat_settings[$cat->cat_ID]['protected']) $settings = $cat_settings[$cat->cat_ID]; 
                    }
        
            }
        }
        return (array)$settings;
    }
    
    function getPostRequirements(&$post){
        $settings = (array)get_post_meta($post->ID, AM4_POST_META,true);
        if(!$settings['protected']){
            // Check category;
            if($post->post_type == 'post'){ // Check category protecton as well; 
                $cat_settings = get_option(AM4_CAT_OPTION);
                if($cat_settings)
                    foreach(get_the_category($post->ID) as $cat){
                        if($cat_settings[$cat->cat_ID] && $cat_settings[$cat->cat_ID]['protected']) $access[] = $cat_settings[$cat->cat_ID]['access']; 
                    }
            }
        }else{
            $access = array($settings['access']);
        }
        return call_user_func_array(array('am4AccessRequirement', 'createRequirements'), $access);
        
    }
    
//  protection here;
    protected function getErrorText($error){
        if($error = am4Errors::getInstance()->get($error))
            return do_shortcode($error);
        return __('Template not found:', 'am4-plugin').$error;
    }
    function filter_ThePosts($posts){
        global $current_user;
        if(!is_array($posts)) return $posts;
        // Admin have access to all;
        $api = am4PluginsManager::getAPI();
        if(current_user_can("manage_options")) return $posts;
        $access  = new am4UserAccess();
        $type = $api->isLoggedIn() ? "user" : "guest";
        $is_search = (is_archive() || is_search()) && !is_category();
        foreach($posts as $k=>$post){
            $settings = $this->getPostAccess($post);
            $being_displayed = is_single($post) || is_page($post);
            if($settings['protected'] && (!$access->isLoggedIn() ||!$access->anyTrue($this->getPostRequirements($post)))){
                    if(is_feed()){
                        // Remove protected posts from feed;
                        unset($posts[$k]);
                    }else switch(($is_search ? $settings[$type.'_action_search'] : $settings[$type.'_action'])){
                        case 'hide' :  unset($posts[$k]); break; 
                        case 'text' : 
                            if($being_displayed || $is_search)
                                $posts[$k]->post_content = $this->getErrorText($is_search ? $settings[$type.'_action_search_text'] : $settings[$type.'_action_text']); break;
                    }
            }
            
            
        }
        $posts = array_merge($posts);
        return $posts;
    }
    
    
    function filter_TheContent($content){
        $api = am4PluginsManager::getAPI();
        if(current_user_can("manage_options")) return $content;
        $access  = new am4UserAccess();
        $type = $api->isLoggedIn() ? "user" : "guest";
        
        if(is_single()){
            $post = $GLOBALS['post'];
            if(!$post) return $content;
            $settings = $this->getPostAccess($post);
            if($settings['protected'] && (!$access->isLoggedIn() ||!$access->anyTrue($this->getPostRequirements($post)))){
                switch(($settings[$type.'_action'])){
                   case 'text' : 
                      $content = $this->getErrorText($settings[$type.'_action_text']); break;
                }                
            }

            
        }
        return $content;
    }
        
    
    function action_WpListPagesExcludes($excludes){
       // if(current_user_can('manage_options')) return $excludes;
        $access = new am4UserAccess();
        $type = $access->isLoggedIn() ? "user" : "guest";
        foreach(get_pages(array('post_type'=>'page', 'post_status'=>'publish')) as $page){
            $settings = $this->getPostAccess($page);
            if($settings['exclude']) $excludes[] = $page->ID;
            if($settings['protected'] && !$access->anyTrue($this->getPostRequirements($page)) && $settings[$type.'_action'] == 'hide') $excludes[] = $page->ID;
        }
        return (array)$excludes;
    }
    function filter_WpNavMenuObjects($items, $args){
        if(current_user_can("manage_options")) return $items;
        $access = new am4UserAccess();
        $type = $access->isLoggedIn() ? "user" : "guest";
        foreach($items as $id => $i){
            switch($i->object){
                case 'page' : 
                    $page = get_page($i->object_id);
                    $settings = $this->getPostAccess($page);
                    if($settings['protected'] && !$access->anyTrue($this->getPostRequirements($page)) && $settings[$type.'_action_menu'] == 'hide') 
                        unset($items[$id]);
                    break;
                case 'category'  :
                    $cat_settings = get_option(AM4_CAT_OPTION);
                    $settings = $cat_settings[$i->object_id];
                    if(!$settings) break;
                    if($settings['protected'] && !$access->anyTrue(am4AccessRequirement::createRequirements($settings['access'])) && $settings[$type.'_action_cat_menu'] == 'hide') 
                        unset($items[$id]);
                    
                    break;
            }
        }
            
        return $items;
        
    }
    
}



class am4ProtectionFormController extends am4FormController{
    var $hidden = 0;
    protected $skip_actions = array('autosave', 'inline-save');
    function getPages(){
        $pages = get_pages();
        $ret=array();
        foreach($pages as $p){
            $ret[get_page_link($p->ID)] = $p->post_title;
        }
        return $ret; 
    }
    function doSave(){
        $options = am4Request::get('options', null);
        if(($errors = $this->validate($options)) === true){
            $this->saveForm($options);
        }
    }
    
    function getViewName(){
        return "protection";
    }
    
    function getOptions(){
        $options = get_post_meta($GLOBALS['post']->ID, AM4_POST_META,true);
        return ($options ? $options : $this->getProtectionDefaults());
    }
    function saveForm($options){
        $post_id = am4Request::get('post_ID', null);
        if(isset($options)&&isset($post_id)) update_post_meta($post_id, AM4_POST_META, $options);
        
    }
    function run($isAjax=0){
        if(in_array(am4Request::get('action'), $this->skip_actions) || am4Request::defined('action2')) return; 
        parent::run($isAjax);
    }
    
    function getErrorMessages(){
        return (array)get_option(AM4_ERROR_OPTION);
    }
}


class am4Protection_post extends am4ProtectionFormController{
    
    
}
class am4Protection_page extends am4ProtectionFormController{
    function getViewName(){
        return "page_protection"; 
    }
    
}
class am4Protection_category extends am4ProtectionFormController{
    function getViewName() {
        return "category";
    }
    
    function getOptions(){
        if($this->category){
            $options = get_option(AM4_CAT_OPTION);
            if(array_key_exists($this->category->term_id, (array)$options)) return $options[$this->category->term_id];
        }
        return array_merge($this->getProtectionDefaults(), $this->getCatProtectionDefaults());
    }
    function saveForm($options){
        $cat_option = get_option(AM4_CAT_OPTION);
        $cat_option[am4Request::get('tag_ID')] = $options;
        update_option(AM4_CAT_OPTION, $cat_option);
    }
}

class am4Protection_bulk extends am4ProtectionFormController{
    function getViewName() {
        return "bulk_protection";
    }
    
    function preDispatch() {
        parent::preDispatch();
        $this->amPostScript();
        return true; 
    }
    function getOptions(){
        return $this->getProtectionDefaults();
    }
    function doIndex($options = array(), $errors = array()) {
        parent::doIndex(array(), array(), array('hidden'=>true));
        $script = am4View::init("bulk_action", $this, am4View::TYPE_JS)->render();
    }
    
    function doAjaxSave(){
        $data = am4Request::get("data");
        parse_str($data, $vars);
        $options = $vars['options'];
        if($vars['post'] && is_array($vars['post'])){
            foreach($vars['post'] as $v){
                update_post_meta($v, AM4_POST_META, $options);
            }
        }
        
    }
    function doAjaxRemove(){
        $data = am4Request::get("data");
        parse_str($data, $vars);
        if($vars['post'] && is_array($vars['post'])){
            foreach($vars['post'] as $v){
                update_post_meta($v, AM4_POST_META, array());
            }
        }
        
    }
    
}
