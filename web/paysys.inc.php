<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/**
* Defines payment base class and additional functions
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Payment System Handling Functions
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5404 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
* =============================================================================
*
*	Revision History:
*	----------------
*	2010-10-23	v3.2.3.0	K.Gary	Modified create_new_payment for aMail
*
* =============================================================================
*
*/

global $__paysystems_list;
$__paysystems_list = array();

/**
* Defines base for payment system plugins
*
*/
class payment {
    var $config = array();
    function payment($config){
        $this->config = $config;
    }
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        fatal_error("do_payment is not implemented");
    }
    function validate_thanks(&$vars){
        return '';
    }
    function process_thanks(&$vars){
        return '';
    }
}

class amember_payment extends payment {
    var $public=1;
    ////
    var $title="Credit Card";
    var $description="secure credit card payment";
    var $fixed_price=0;
    var $recurring=0;
    var $built_in_trials=0;
    ///
    function amember_payment($config){
        $this->payment($config);
        $this->init();
    }
    function init(){
        add_paysystem_to_list(
        array(
                    'paysys_id'   => $this->get_plugin_name(),
                    'title'       => ($this->config['title']=='') ? $this->title : $this->config['title'],
                    'description' => ($this->config['description']=='') ? $this->description : $this->config['description'],
                    'fixed_price' => $this->fixed_price,
                    'public'      => $this->public,
                    'recurring'   => $this->recurring,
                    'built_in_trials' => $this->built_in_trials,
                )
        );
        if ($this->recurring) {
            add_product_field(
                        'is_recurring', 'Recurring Billing',
                        'checkbox', 'should user be charged automatically<br />
                         when subscription expires'
            );
        }
        if ($this->built_in_trials){
            add_product_field('trial1_days',
                'Trial 1 Duration',
                'period',
                'trial period duration'
                );

            add_product_field('trial1_price',
                'Trial 1 Price',
                'money',
                'enter 0.0 to offer free trial'
                );
        }
    }
    function get_plugin_name(){
        if (preg_match('/^payment_(.+?)$/', get_class($this), $regs))
            return $regs[1];
        else
            die("Cannot determine payment plugin name: " . get_class($this));
    }
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        global $db, $config;
        $payment = $db->get_payment($payment_id);
        if ($payment['data'][0]['BASKET_PRODUCTS'])
            $product_ids = (array)$payment['data'][0]['BASKET_PRODUCTS'];
        else
            $product_ids = array($payment['product_id']);
        $products = array();
        foreach ($product_ids as $product_id)
            $products[] = $db->get_product($product_id);
        $member  = $db->get_user($member_id);
        $title = (count($products) == 1) ? $products[0]['title'] : $config['multi_title'];
        $invoice = $payment_id;
        return $this->do_bill($price, $title, $products, $member, $invoice);
    }
    function do_bill($amount, $title, $products, $member, $invoice){
        die("do_bill is not implemented in this plugin: " . get_class($this));
    }
    function add_config_items($notebook_page){
         $plugin = $this->get_plugin_name();
         add_config_field("payment.$plugin.title", "Payment system title",
             'text', "to be displayed on signup.php and member.php pages",
             $notebook_page,
             '','','',
             array('default' => $this->title));
         add_config_field("payment.$plugin.description", "Payment system description",
             'text', "to be displayed on signup page",
             $notebook_page,
             '','','',
             array('default' => $this->description));
         add_config_field("payment.$plugin.disable_postback_log", "PostBack messages Logging",
             'select', "by default aMember will log payment system postback messages<br />
             you can disable this functionality by changing this configuration value.<br />
             It is recommended to keep this enabled at least for first 1-2 months.",
             $notebook_page,
             '','','',
             array('options' => array('' => 'Log Postback Messages (default)', 1 => 'Disable PostBack Logging')));
    }
    function encode_and_redirect($url, $vars){
        $vars1 = array();
        foreach ($vars as $k=>$v)
            $vars1[] = urlencode($k) . '=' . urlencode($v);
        $x = join('&', $vars1);
        html_redirect("$url?$x", 0, _PLUG_PAY_CC_CORE_REDIR,
        _PLUG_PAY_CC_CORE_REDIR);
    }
    function get_dump($var){
        $s = "";
        foreach ($var as $k=>$v)
            $s .= "$k => $v<br />\n";
        return $s;
    }
    function postback_error($err){
        global $db;
        $plugin = $this->get_plugin_name();
        fatal_error("$plugin ERROR: $err<br />\n".$this->get_dump($this->postback_vars));
    }
    function postback_log($msg=''){
        global $db;
        if ($this->config['disable_postback_log'] != '') return;
        $plugin = $this->get_plugin_name();
        $db->log_error("$plugin DEBUG: $msg<br />\n".$this->get_dump($this->postback_vars));
    }
    function handle_postback($vars){
        $this->postback_vars = $vars;
        $this->postback_log();

        $ret = $this->process_postback($vars);

        if ($ret)
            $this->postback_log("successfully handled");

        return $ret;
    }

}


class amember_advanced_payment extends amember_payment{

    function amember_advanced_payment($config){
        $this->amember_payment($config);
        $this->init();
    }

    function add_config_items($notebook_page) {
        $plugin = $this->get_plugin_name();
        add_config_field('payment.'.$plugin.'.allow_create', 'Allow create new accounts', 'select',
            "aMember will create a member (if not exists) after purchase at ".$this->title." directly.<br />
            You should configure 'Paysystem  Product ID' for each product<br />
            at aMember CP -> Manage Products -> Edit<br />
            KEEP IT DISABLED IF YOU DON'T UNDERSTAND WHAT IT MEANS", $notebook_page, '','','',
            array('options' => array(0 => 'No', 1 => 'Yes'))
        );

        parent::add_config_items($notebook_page);
    }

    function create_new_payment(&$vars){
        global $db;
        // Check if enabled;
        if(!$this->config['allow_create']) return;
        $member = array();
        foreach(array('name_f', 'name_l', 'email', 'street', 'city', 'zip', 'country','state') as $v){
            $member[$v] = $this->get_value_from_vars($v, $vars);
        }
		$member['to_subscribe'] = 1;								// mod added for aMail
        // Try to find existing user with the same email;
        $users = $db->users_find_by_string($member['email'], 'email', $exact=1);
        $u = $users['0'];
        if(!$u['member_id']){
            $member['login'] = generate_login($member);
            $member['pass'] =  generate_password($member);
            $member_id = $db->add_pending_user($member);
            $u = $db->get_user($member_id);
        }
        if(!($product_id = intval($this->get_value_from_vars('product_id', $vars)))) return;
        $product = get_product($product_id);
        $amount = $this->get_value_from_vars("amount", $vars);
        if($amount == '' || ($amount === false)) $amount = $product->config['price'];
        $begin_date = date('Y-m-d');
        $expire_date = $product->get_expire($begin_date, 'expire_days');
        $payment_id = $db->add_waiting_payment($u['member_id'], $product_id, $this->get_plugin_name(), $amount, $begin_date, $expire_date, $vars);

        // Set receipt_id will be required by some payment plugins;
        $payment = $db->get_payment($payment_id);
        $payment['receipt_id'] = $this->get_value_from_vars('receipt_id', $vars);
        $db->update_payment($payment['payment_id'], $payment);

        return $payment_id;

    }

    function get_value_from_vars($var,&$vars)
    {
        return;
    }

    function find_product_by_field($field, $value){
        global $db;
        foreach($db->get_products_list() as $p)
            if($value && $p[$field] == $value) return $p['product_id'];

    }


}

class amember_protect {
    function amember_protect($config){
        $this->config = $config;
    }
    function init(){
    }
    function get_plugin_name(){
        if (preg_match('/^protect_(.+?)$/', get_class($this), $regs))
            return $regs[1];
        else
            die("Cannot determine protect plugin name: " . get_class($this));
    }
}

class amember_integration_plugin extends amember_protect {
    var $db;
    var $hooks = array(
            'subscription_added',
            'subscription_updated',
            'subscription_deleted',
            'subscription_removed',
            'subscription_check_uniq_login',
            'subscription_rebuild',
            'check_logged_in',
            'after_login',
            'after_logout',
            'fill_in_signup_form'
        );
    var $debug_sql = false;
    /**
     * This field contains pattern of tablename that
     * will be used to check database name and prefix
     * settings
     * For example if tablename is usually vb_user,
     * you should specify 'user' for this field
     * If you keep this empty, auto-detection will
     * be disabled for your plugin
     */
    var $guess_table_pattern = null;
    /**
     * This field contains list of field names that must
     * be present in the table specified above. If any of
     * fields is not present, database/prefix will be
     * detected as not-acceptable for this plugin.
     */
    var $guess_fields_pattern = array();
    ///////////////////////////////////
    function subscription_added()  { }
    function subscription_updated(){ }
    function subscription_deleted(){ }
    function subscription_removed(){ }
    function subscription_check_uniq_login($login, $email, $pass){
        return 1;
    }
    function subscription_rebuild(){}
    function check_logged_in(){}
    function after_login(){}
    function after_logout(){}
    function fill_in_signup_form(&$vars){}
    ////////////////////////////////////
    function get_db(){
        if (!$this->db)
            $this->init_database();
        return $this->db;
    }
    function query($sql, $ignore_error=0){
        if ($this->debug_sql)
            print "<br /><b>SQL query:</b><pre>$sql</pre>";
        $db = & $this->get_db();
        $sql = str_replace("[db]", $this->config['db'], $sql);
        return $db->query($sql, $ignore_error);
    }
    function query_first($sql, $ignore_error=0){
        $q = $this->query($sql, $ignore_error);
        return mysql_fetch_assoc($q);
    }
    function query_one($sql, $ignore_error=0){
        $q = $this->query($sql, $ignore_error);
        $x = mysql_fetch_array($q);
        return $x[0];
    }
    function escape_record($record){
        $db = & $this->get_db();
        settype($record, 'array');
        if (isset($record['data']))
            foreach ($record['data'] as $k=>$v)
                $d[$k] = is_array($v) ? $v : $db->escape($v);
        foreach ((array)$record as $k => $v)
            $record[$k] = $db->escape($v);
        if (isset($record['data']))
            $record['data'] = $d;
        return $record;
    }
    function escape($s){
        $db = & $this->get_db();
        return $db->escape($s);
    }
    function set_cookie($k, $v){
        if (function_exists('amember_setcookie'))
            return amember_setcookie($k, $v);
        else {
            $tm = 0;
            $d = $_SERVER['HTTP_HOST'];
            if (preg_match('/([^\.]+)\.(org|com|net|biz|info)/', $d, $regs))
                setcookie($k,$v,$tm,"/",".{$regs[1]}.{$regs[2]}");
            else
                setcookie($k,$v,$tm,"/");
        }
    }
    function del_cookie($k){
        if (function_exists('amember_delcookie'))
            return amember_delcookie($k);
        else {
            $tm = time()-24*3600;
            $d = $_SERVER['HTTP_HOST'];
            if (preg_match('/([^\.]+)\.(org|com|net|biz|info)/', $d, $regs))
                setcookie($k,"",$tm,"/",".{$regs[1]}.{$regs[2]}");
            else
                setcookie($k,"",$tm,"/");
        }
    }
    function add_db_config_items($pn, $notebook_page){
        add_config_field("protect.$pn.user", 'MySQL Database User',
            'text', "usually you can leave this field empty.<br />
            If aMember's MySQL user is unable to connect to $pn database,<br />
            you can enter MySQL connection settings here.<br />
            This field is for MySQL username",
            $notebook_page,
            'validate_plugin_database');
        add_config_field("protect.$pn.pass", 'MySQL Database Password',
         'text', "usually you can leave this field empty.<br />
            If aMember's MySQL user is unable to connect to $pn database,<br />
            you can enter MySQL connection settings here.<br />
            This field is for MySQL password",
            $notebook_page,
            '');
        add_config_field("protect.$pn.host", 'MySQL Database Hostname',
            'text', "usually you can leave this field empty.<br />
            If aMember's MySQL user is unable to connect to $pn database,<br />
            you can enter MySQL connection settings here.<br />
            This field is for MySQL hostname (often it is 'localhost')",
            $notebook_page,
            '');
    }
    function init_database(){
        if ($this->config['user'] != ''){
            list($database, $dot, $prefix) = preg_split('|(\.)|', $this->config['db'], -1, PREG_SPLIT_DELIM_CAPTURE);
            if ($dot == '')
                $database = $GLOBALS['config']['db']['mysql']['db'];
            $c = array(
                'host' => $this->config['host'],
                'pass' => $this->config['pass'],
                'user' => $this->config['user'],
                'db'   => $database
            );
            $this->db = new db_mysql($c);
        } else {
            $this->db = $GLOBALS['db'];
        }
    }
    function guess_db_settings(){
        if (!$this->guess_table_pattern || !$this->guess_fields_pattern)
            return array();
        if ($this->config['user'] == '' || $this->config['host'] == '' ||
            (isset($this->config['other_db']) && !$this->config['other_db']))
            $config = amConfig('db.mysql');
        else {
            $config = $this->config;
            /// lets get name of first available database just for DbSimple
            /// because it does not work without database name
            $c = @mysql_connect($config['host'], $config['user'], $config['pass']);
            if (!$c) return false;
            $q = mysql_query("SHOW DATABASES", $c);
            list($db) = mysql_fetch_row($q);
            if ($db == '') return false;
            $config['db'] = $db;
        }
        $c = connectMysql($config);
        if ($c->error) return false;
        $c->setErrorHandler(null);
        $res = array();
        foreach ($dbs = $c->selectCol("SHOW DATABASES") as $dbname){
            $tables = $c->selectCol("SHOW TABLES FROM $dbname LIKE '%$this->guess_table_pattern'");
			if(is_array($tables))
            foreach ($tables as $t){
                // check fields here
                $info = $c->select("SHOW COLUMNS FROM $dbname.$t");
                $infostr = "";
				if(is_array($info))
                foreach ($info as $k => $v)
                    $infostr .=  join(';', $v) . "\n";
                $wrong = 0;
                foreach ($this->guess_fields_pattern as $pat){
                    if (!preg_match('|^'.$pat.'|m', $infostr))
                        $wrong++;
                }
                if ($wrong) continue;
                $res[] = $dbname . '.' . substr($t, 0, -strlen($this->guess_table_pattern));
            }
        }
        return $res;
    }
    function init(){
        set_protect_plugin_hooks($this);
        $this->init_database();
    }
}

function validate_plugin_database($field, $vars){
    global $db;
    foreach ($vars as $k => $v){
        if (preg_match('/^\w+_(\w+)_\w+$/', $k, $regs)){
            $pn = $regs[1];
            break;
        }
    }
    if ($pn == '') die("Cannot determine plugin name at validate_plugin_database()");
    $host = $vars['protect_'.$pn.'_host'];
    $user = $vars['protect_'.$pn.'_user'];
    $pass = $vars['protect_'.$pn.'_pass'];
    if ($host == '' && $user == '' && $pass == '') return;
    $err = "";
    if ($user == '') $err .= "Please enter MySQL username.<br />";
   // if ($pass == '') $err .= "Please enter MySQL password.<br />";
    if ($host == '') $err .= "Please enter MySQL hostname.<br />";
    if ($err != '') return $err;

    if (!@mysql_connect($host, $user, $pass, 1)){
        return "Wrong MySQL username, password or hostname entered. " . mysql_error();
    }
}

function set_protect_plugin_hooks(&$protect){
    $pn = $protect->get_plugin_name();
    foreach ($protect->hooks as $f) {
        eval ("function _{$pn}_$f(\$a1='',\$a2='',\$a3='',\$a4='',\$a5='',\$a6='',\$a7='',\$a8='',\$a9=''){
            \$protect = & instantiate_plugin('protect', '{$pn}');
            return \$protect->$f(\$a1,\$a2,\$a3,\$a4,\$a5,\$a6,\$a7,\$a8,\$a9);
        };");
        setup_plugin_hook("$f", "_{$pn}_$f");
    }
}

function cmp_ps_paypal($a, $b){
    if ($a == 'paypal_pro') return -1;
    if ($b == 'paypal_pro') return 1;
}

function get_paysystems_list(){
    global $__paysystems_list;
    uksort($__paysystems_list, 'cmp_ps_paypal');
    return $__paysystems_list;
}

function add_paysystem_to_list($desc){
    global $__paysystems_list;
    $__paysystems_list[$desc['paysys_id']] = $desc;
}

function get_paysystem($paysys_id){
    global $__paysystems_list;
    return $__paysystems_list[$paysys_id];
}

add_paysystem_to_list(array(
            'paysys_id' => 'manual',
            'title'     => 'Manual Payment',
            'description' => 'Payment Entered by Admin',
            'public'    => 0
        )
);
?>
