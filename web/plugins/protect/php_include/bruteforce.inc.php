<?php
if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Bruteforce protection imp
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1640 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

define('BRUTEFORCE_PROTECT_ADMIN', 1);
define('BRUTEFORCE_PROTECT_USER', 2);

/**
* This class will log failed login attempts. If configured limit is reached (within specified time),
* login attempts from given IP will be blocked to configured time. It will return false for
* loginAllowed() method.
* After $time_delay all counters will be reset, and user is able to login again.
*/
class BruteforceProtector {
    var $ip, $account_type;
    
    /** @var int Failed logins count */
    var $failed_logins_count; // default 5 attempts
    /** @var int Delay login when $failed_logins_count reached */
    var $time_delay; // default 15 minutes
    /** @var aMember_DB Db */
    var $db;
    
    function BruteforceProtector($account_type, &$db, $failed_login_attempts=5, $time_delay=120){
        $this->account_type=$account_type;
        $this->db = $db;
        $this->setParameters($failed_login_attempts, $time_delay); 
    }
    function setIP($ip){
        $this->ip = $ip;
    }
    function setParameters($failed_logins_count, $time_delay){
        $this->failed_logins_count = $failed_logins_count;
        $this->time_delay = $time_delay;
    }
    /**
    * @param int Function will set this variable to specify how much time left until block will be removed
    * @return bool True if login allowed, false if disallowed
    */
    function loginAllowed(&$left){
        $left = 0;
        $time = $this->time();
        $elem = $this->getRecord($this->ip);
        if (!isset($elem)) 
            return true;
        if ($elem['failed_logins'] < $this->failed_logins_count) 
            return true;
        if (($time - $elem['last_failed']) > $this->time_delay){
            $this->deleteRecord($this->ip);
            return true;
        }
        $left = $this->time_delay - ($time - $elem['last_failed']);
        return false;
    }
    function reportFailedLogin(){
        $elem = $this->getRecord($this->ip);
        @$elem['failed_logins']++;
        $elem['last_failed'] = $this->time();
        $this->setRecord($this->ip, $elem['failed_logins'], $elem['last_failed']);
    }
    function time(){
        return time();
    }
    function getRecord($ip){
        $prefix = $this->db->config['prefix'];
        $ip = $this->db->escape($ip);
        $q = @mysql_query("SELECT * FROM {$prefix}failed_login 
            WHERE ip='$ip' AND login_type='{$this->account_type}'", $this->db->conn);
        if (mysql_errno($this->db->conn) == 1146){
            $this->db->query("CREATE TABLE {$prefix}failed_login (
                failed_login_id int(11) NOT NULL auto_increment,
                ip char(15) NOT NULL,
                login_type int(11) NOT NULL,
                failed_logins int(11) NOT NULL,
                last_failed int(11) NOT NULL,
                PRIMARY KEY  (failed_login_id),
                UNIQUE KEY ip (ip, login_type)
            )");            
            $q = $this->db->query("SELECT * FROM {$prefix}failed_login 
                WHERE ip='$ip' AND login_type='{$this->account_type}'", $ignore_error = true);
        }
        $row = mysql_fetch_assoc($q);            
        return $row;
    }
    function setRecord($ip, $failed_logins, $last_failed){
        $prefix = $this->db->config['prefix'];
        $row = $this->getRecord($ip);
        settype($failed_logins, 'integer');
        settype($last_failed, 'integer');
        $ip = $this->db->escape($ip);
        if ($row){
            $this->db->query("UPDATE {$prefix}failed_login SET 
                failed_logins=$failed_logins, last_failed=$last_failed
                WHERE failed_login_id=$row[failed_login_id]");
        } else {
            $this->db->query("INSERT INTO {$prefix}failed_login 
            (ip, login_type, failed_logins, last_failed) 
            VALUES
            ('$ip', '{$this->account_type}', $failed_logins, $last_failed)");
        }
    }
    function deleteRecord($ip){
        $prefix = $this->db->config['prefix'];
        $ip = $this->db->escape($ip);
        $this->db->query("DELETE FROM {$prefix}failed_login 
            WHERE ip='$ip' AND login_type='{$this->account_type}'");
    }
}

function clear_failed_logins(){
    global $db, $config;
    $terminator = time() - $config['bruteforce_delay'];
    $db->query("DELETE FROM {$db->config[prefix]}failed_login WHERE last_failed<$terminator", true);
}

setup_plugin_hook('daily', 'clear_failed_logins');
?>