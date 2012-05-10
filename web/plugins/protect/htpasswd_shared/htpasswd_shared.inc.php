<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: htpasswd shared plugin
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5144 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/




function check_setup_htpasswd_shared(){
    global $plugin_config, $config, $db;
    
    $this_config = $config['protect']['htpasswd_shared'];
    
    if (!isset($config['protect']['htpasswd_shared']) || $this_config['htpasswd'] == '' || !is_file($this_config['htpasswd'])){
        $error = ".htpasswd file is not configured for 'htpasswd_shared' plugin.";
        if (!$_SESSION['check_setup_htpasswd_shared_error']) $db->log_error ($error);
        $_SESSION['check_setup_htpasswd_shared_error'] = $error;
        return $error;
    }

    return '';
}

if (!check_setup_htpasswd_shared()){
    setup_plugin_hook('subscription_added',   'htpasswd_shared_added');
    setup_plugin_hook('subscription_updated', 'htpasswd_shared_updated');
    setup_plugin_hook('subscription_deleted', 'htpasswd_shared_deleted');
    setup_plugin_hook('subscription_rebuild', 'htpasswd_shared_rebuild');
    setup_plugin_hook('subscription_check_uniq_login', 'htpasswd_shared_check_uniq');
}

function htpasswd_shared_added($member_id, $product_id, $member){
    $member_1 = $member; $member_1['pass'] = 'XX-'.md5(time());
    htpasswd_shared_updated($member_id, $member_1, $member);
}

function htpasswd_shared_updated($member_id, $oldmember, $newmember){
    global $db, $config;
    $this_config = $config['protect']['htpasswd_shared'];
    if (($oldmember['pass'] == $newmember['pass']) &&
        ($oldmember['login'] == $newmember['login']))
        return; //nothing to change
    if ($newmember['pass'] == '') //empty password is not allowed
        return; 
    if ($this_config['htpasswd'] == ''){
        $db->log_error(".htpasswd file is not configured for htpasswd_secure plugin.");
        return;
    }
    if ($need = $this_config['products']){
        $found = 0;
        foreach ($oldmember['data']['status'] as $pid => $status)
            if ($status>0 && in_array($pid, $need)) {$found++; break; }
        if (!$found) { return;}
    }
    $users = file($this_config['htpasswd']);
    $f = fopen($this_config['htpasswd'], 'w');
    if (!$f) fatal_error("Cannot open .htpasswd file: '$this_config[htpasswd]'");
    if (!flock($f, LOCK_EX)) fatal_error("Cannot lock file");
    $login = $newmember['login']; 
    $pass  = crypt($newmember['pass'], uniqid(rand()));
    $found = 0;
    foreach ($users as $k => $l){
        if (preg_match("/^$oldmember[login]:/", $l)) {
            fputs($f, "$login:$pass\n");
            $found++;
        } else
            fputs($f, $l);
    }
    if (!$found)
        fputs($f, "$login:$pass\n");
    fclose($f);
}

function htpasswd_shared_deleted($member_id, $product_id, $member){
    global $db, $config;
    $this_config = $config['protect']['htpasswd_shared'];

    $need = $this_config['products'];
    $found = 0;
    foreach ($member['data']['status'] as $pid => $status)
        if ($status>0 && (!$need || in_array($pid, $need))) {$found++; break; }
    if ($found) return;

    if ($this_config['htpasswd'] == ''){
        $db->log_error(".htpasswd file is not configured for htpasswd_secure plugin.");
        return;
    }
    $users = file($this_config['htpasswd']);
    $f = fopen($this_config['htpasswd'], 'w');
    if (!$f) fatal_error("Cannot open .htpasswd file: '$this_config[htpasswd]'");
    if (!flock($f, LOCK_EX)) fatal_error("Cannot lock file");
    foreach ($users as $l){
        if (preg_match("/^$member[login]:.+/", $l)) continue;
        fputs($f, $l);
    }
    fclose($f);
}

function htpasswd_shared_rebuild(&$members) {
    foreach ($members as $login => $m){
        $status = array();
        foreach ($m['product_id'] as $i) $status[$i] = 1;
        $member = array('login' => $login, 'pass' => $m['pass'], 
            'data' => array('status' => $status));
        $member_1 = $member; $member_1['pass'] = 'XX-'.md5(time());
        htpasswd_shared_updated(0, $member_1, $member);
    }
}

function htpasswd_shared_check_uniq($login, $email){
    global $db, $config;
    $this_config = $config['protect']['htpasswd_shared'];
    if ($this_config['htpasswd'] == ''){
        $db->log_error(".htpasswd file is not configured for htpasswd_secure plugin.");
        return;
    }
    $f = fopen($this_config['htpasswd'], 'r');
    if (!$f) fatal_error("Cannot open .htpasswd file: '$this_config[htpasswd]'");
    while ($l = fgets($f, 256)){
        if (preg_match("/^$login:.+/", $l))     
            return 0;
    }
    return 1;
}

?>
