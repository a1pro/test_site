<?php

class AdminMenuItem {
    var $title, $url;
    function AdminMenuItem($title, $url){
        $this->title = $title;
        $this->url = $url;
    }
    function render(){
        return "<li class='menu_item'><a href='".AdminMenu::formatUrl($this->url)."'>".$this->title."</a></li>\n";    
    }
}

class AdminMenuSection {
    var $id, $title, $url;
    var $_items = array(); 
    function AdminMenuSection($id, $title, $url=null, $order=0){
        $this->id = $id;
        $this->title = $title;
        $this->url = $url;
        $this->order = $order;
    }
    function & addItem($title, $url){
        return $this->_items[] = & new AdminMenuItem($title, $url);
    }
    function & addHtml($html){
        $this->_items[] = $html;
    }
    function render(){
        $out = "<ul class='admin_menu_section' id='".htmlentities($this->id)."'>\n<li class='sect_head'>";
        if ($this->url)
            $out .= "<a href='".AdminMenu::formatUrl($this->url)."'>".$this->title."</a>";
        else 
            $out .= $this->title;
        $out .= "\n<ul class='admin_menu_indent'>\n";
        foreach ($this->_items as $i){
            if (is_string($i))
                $out .= $i;
            else
                $out .= $i->render();
        }
        $out .= "</ul></li>\n</ul>\n";
        return $out;
    }
}

class AdminMenu {
    var $_sections = array();
    /**
     * Add Menu Section
     *
     * @return AdminMenuSection
     */
    function & addSection($id, $title, $url=null, $order=0){
        $this->_sections[$id] = & new AdminMenuSection($id, $title, $url, $order);
        return $this->_sections[$id]; 
    }
    /**
     * Get existing section
     *
     * @param string $id
     * @return AdminMenuSection
     */
    function & getSection($id){
        if (!array_key_exists($id, $this->_sections))
            trigger_error("Section [$id] is not found in AdminMenu::getSection", E_USER_WARNING);
        return $this->_sections[$id];
    }
    
    function _cmpSections($a, $b){
        return $a->order - $b->order;
    }
    function render(){
        uasort($this->_sections, array(&$this, '_cmpSections'));
        $out = "";
        foreach ($this->_sections as $k => $s){
            $s = & $this->_sections[$k];
            $out .= $s->render();
        }
        return $out;
    }
    /**
     * if url is relative, add root_url/admin to it
     * and return htmlentities escaped value
     * @static
     */ 
    function formatUrl($url){
        global $config;
        if (!preg_match('|^/|', $url) && !preg_match('|^https*://.+|', $url)){
            $root = $_SERVER['SERVER_PORT']==443 ?  $config['root_surl'] : $config['root_url'];
            $url = $root . '/admin/' . $url; 
        }
        return htmlentities($url);
    }
}


/**
 * return AdminMenu object reference
 */
function & initMainMenu(){
    static $menu_list = array();
    if ($menu_list[0]) return $menu_list[0];
    
    global $config;
    
    $m = & new AdminMenu();
    $s = & $m->addSection('users', 'Browse Users', 'users.php?letter=A', -1000);
    $s->addItem('Search Users', 'users.php?action=search_form');
    $s->addItem('Add User', 'users.php?action=add_form');
    if ($config['manually_approve'])
        $s->addItem('Not-Approved Users', 'users.php?action=not_approved');
    if (!is_lite()){
        $s->addItem('Email Users', 'email.php');
        $s->addHTML('<li class="menu_item"><a href="import.php">Import</a>&nbsp;/&nbsp;<a href="export.php">Export</a> </li>' . "\n");
        $s->addHTML('<li class="menu_item"><a href="backup.php">Backup</a>&nbsp;/&nbsp;<a href="restore.php">Restore</a> </li>' . "\n");
    }
    $s->addItem('Rebuild DB', 'rebuild.php');
    
    $s = & $m->addSection('reports', 'Reports', null, -100);
    $s->addItem('Payments', 'payments.php');
    if (function_exists('cc_core_rebill')){
        $s->addItem('Rebills', 'rebill_log.php');
    }

    if (!is_lite())
        $s->addItem('Reports', 'report.php');
    
    $s = & $m->addSection('products', 'Manage Products', 'products.php', 100);
    $s->addItem('Protect Folders', 'protect.php');
    if (!is_lite())
        $s->addItem('Coupons', 'coupons.php');
    
    $s = & $m->addSection('utils', 'Utilities', null, 1000);
    $s->addItem('Error/Debug Log', 'error_log.php');
    $s->addItem('Access Log', 'access_log.php');
    if ($config['use_affiliates'])
        $s->addItem('Affiliate Program', 'aff_commission.php?do=aff_menu');
    $s->addItem('Delete Old Records', 'clear.php');
    $s->addItem('<b>Setup/Configuration</b>', 'setup.php');
    $s->addHTML('<li class="menu_item"><a href="admins.php">Admin Accounts</a>&nbsp;/&nbsp;<a href="admin_log.php">Logs</a> </li>' . "\n");
    if (!is_lite())
        $s->addItem('Add Fields', 'fields.php');
    $s->addItem('Version Info', 'info.php');
    $s->addItem('<b>Logout</b>', 'logout.php');
    
    plugin_init_admin_menu($m);
    
    return $menu_list[0] = & $m;
}