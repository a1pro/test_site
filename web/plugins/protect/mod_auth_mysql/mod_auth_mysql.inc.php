<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 


function check_setup_mod_auth_mysql(){
    global $plugin_config, $config, $db;
    
    $this_config = $plugin_config['protect']['mod_auth_mysql'];
    $nuke_db = $this_config['db'];
    if (!$nuke_db) {
        $error = "Error. Please configure 'mod_auth_mysql' plugin at aMember CP -> Setup -> mod_auth_mysql";
        if (!$_SESSION['check_setup_mod_auth_mysql_error']) $db->log_error ($error);
        $_SESSION['check_setup_mod_auth_mysql_error'] = $error;
        return $error;
    }
    return '';
}

if (!check_setup_mod_auth_mysql()){
    setup_plugin_hook('subscription_added',   'mod_auth_mysql_added');
    setup_plugin_hook('subscription_updated', 'mod_auth_mysql_updated');
    setup_plugin_hook('subscription_deleted', 'mod_auth_mysql_deleted');
    setup_plugin_hook('subscription_removed', 'mod_auth_mysql_removed');
    setup_plugin_hook('subscription_rebuild', 'mod_auth_mysql_rebuild');
}

function mod_auth_mysql_rebuild(&$members){
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['mod_auth_mysql'];
    $m_db = $this_config['db'];
    if ($m_db=='') return;
    ///
//    $db->query("DELETE FROM $m_db");
    ///
    foreach ($members as $login => $m){
        $pwd = crypt($m['pass']);
        $groups = '';
        foreach ($m['product_id'] as $k=>$i)
            $groups[] = "PRODUCT_$i";
        $groups = join(',', $groups);
        ///
        $db->query("INSERT IGNORE INTO $m_db
            (username, passwd, groups)
            VALUES 
            ('$login', '$pwd', '$groups')");
    }

}
    
function mod_auth_mysql_added($member_id, $product_id,
    $member){
    return mod_auth_mysql_update_record($member['login'], $member['pass'],
        $member['data']['status']);
    
}

function mod_auth_mysql_updated($member_id, $oldmember,
    $newmember){
    if ($oldmember['login'] != $newmember['login']){
        global $config, $db, $plugin_config;
        $this_config = $plugin_config['protect']['mod_auth_mysql'];
        $m_db = $this_config['db'];
        if ($m_db=='') return;
        $db->query("DELETE FROM $m_db WHERE username='{$oldmember[login]}'");
    }
    return mod_auth_mysql_update_record($newmember['login'], $newmember['pass'],
        $oldmember['data']['status']);
}

function mod_auth_mysql_deleted($member_id, $product_id,
    $member){
    return mod_auth_mysql_update_record($member['login'], $member['pass'],
        $member['data']['status']);
}

function mod_auth_mysql_removed($member_id, 
    $member){
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['mod_auth_mysql'];
    $m_db = $this_config['db'];
    if ($m_db=='') return;
    $db->query("DELETE FROM $m_db WHERE username='{$oldmember[login]}'");
}

function mod_auth_mysql_update_record($login, $pass, $member_status){
    global $config, $db, $plugin_config;
    $this_config = $plugin_config['protect']['mod_auth_mysql'];
    $m_db = $this_config['db'];
    if ($m_db=='') return;
    /// find out active groups
    $groups = array();
    foreach ($member_status as $product_id => $status)
         if ($status) 
            $groups[] = "PRODUCT_".$product_id;
    $groups = join(',', $groups);
    ////
    if (!$groups) {
        $db->query($s = "DELETE FROM $m_db WHERE username='$login'");
    } else {
        $q = $db->query($s = "SELECT passwd, groups
                FROM $m_db
                WHERE username='$login'");
        list($oldp, $oldg) = mysql_fetch_row($q);
        $pwd = crypt($pass);
        if ($oldp) { // user found,  update record
            $db->query($s = "UPDATE $m_db
             SET passwd='$pwd', groups='$groups'
             WHERE username='$login'");
        } else { // insert new record
            $db->query($s = "INSERT INTO $m_db     
            (username, passwd, groups)
            VALUES
            ('$login', '$pwd', '$groups')");
        }
    }    
}

?>