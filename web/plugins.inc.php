<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/**
* Plugins functions
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Plugins handling functions
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5400 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/

/*
* Instantiated plugins
* @global mixed $___plugins
**/
global $___plugins;
$__plugins = array();

/*
* For plugin hooks setup
* @global mixed $___hooks
**/
global $__hooks;
$__hooks = array();

/**
* Load Plugins
* Include all plugins of type to setup all hooks
*
* @param string $type Plugin Type = db|payment|protect
*
* @global array Plugins List
* @global mixed Script Config
*/
function load_plugins($type){
    global $plugins;
    global $config;

    foreach ((array)$plugins[$type] as $name){
        if (!strlen($name)) continue;
        $file = $config['plugins_dir'][$type]."/$name/$name.inc.php";
        if (!file_exists($file)){
            trigger_error("Plugin file ($file) for plugin ($type/$name) does not exists", E_USER_WARNING);
            continue;
        }
        if ($type == 'protect'){
            if (is_lite()){
                if (!in_array($name, array('htpasswd', 'php_include')))
                    fatal_error("Sorry, but this plugin ($type/$name) cannot be used with aMember Lite");
            }
        }
        if ($type == 'payment'){
            if (is_lite()){
                if (!in_array($name, array('twocheckout', 'twocheckout_r', 'paypal', 'paypal_r', 'free')))
                    fatal_error("Sorry, but this plugin ($type/$name) cannot be used with aMember Lite");
            }
        }
        $open = include($file);
        //@LPCHK@
    }
}

/**
* Setup Plugin Hook
* Setup plugin hook to be called at specified event
*
* @param string $hook Hook Name
* @param string $func_name Function Name to be called
*
* @global array Hooks List
*/
function setup_plugin_hook($hook, $func_name){
    global $__hooks;
    if (is_callable($func_name))
        $__hooks[$hook][] = $func_name;
    else {
        $ptr = ',';
        if (is_array($func_name)) {
            if (is_object($func_name[0])) {
                $func_name[0] = get_class($func_name[0]);
                $ptr = '->';
            } else {
                $ptr = '::';
            }
            $func_name = join($ptr, $func_name);
        }
        fatal_error(sprintf("Hook function is not defined: '%s' for $hook", 
            $func_name));
    }
}

/**
* Instantiate Plugin 
* Get it from cache if it already exists
*
* @param string $type Plugin Type = db|payment|protect
* @param string $name Plugin Name
* @return mixed Plugin Object
*
* @global array Plugins List
* @global mixed Script Config
* @global mixed Plugins Config
* @global mixed Plugins Cache
*/
function &instantiate_plugin($type, $name, $need_to_include=0){
    global $plugins;
    global $config, $plugin_config;
    global $___plugins; //array of existsing plugins, indexed by [type][name]

    if (!strlen($type))
        fatal_error("Plugin type is empty in instantiate_plugin(NULL, '$type')");
    if (!strlen($name))
        fatal_error("Plugin name is empty in instantiate_plugin('$type',NULL)");
    if (!in_array($name, $plugins[$type]))
        fatal_error("Plugin '$name' is not enabled. Died");


    $class = $type . "_" . $name;
    $exists = & $___plugins[$type][$name];
    if (gettype($exists) == 'object')
        return $___plugins[$type][$name];
    
    if ($need_to_include){
        $file = $config['plugins_dir'][$type]."/$name/$name.inc.php";
        if (!file_exists($file))
            fatal_error("Plugin file ($file) for plugin ($type/$name) not exists");
        $open = include($file);
    }

    if (!class_exists($class)) 
        fatal_error("Error in plugin $type/$name: class $class not exists!");
    return $___plugins[$type][$name] = new $class($plugin_config[$type][$name]);
}

/**
* Instantiate Database Plugin 
* Return always first of database plugins.
* Use {@link instaniate_plugin}
*
* @param string $name Database Plugin Name
* @return mixed db Object
*
* @global array Plugins List
*/
function instantiate_db(){
    global $plugins;
    return instantiate_plugin('db', $plugins['db'][0], 1);    
}

///////////////////// PAYMENT PLUGINS hooks ////////////////////////////

function plugin_do_payment($paysys_id, $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
    $pay_plugin = &instantiate_plugin('payment', $paysys_id);

    $ps = get_paysystem($paysys_id);
    global $db;
    if ($ps['fixed_price'] &&  
        ($product=$db->get_product($product_id))
    && ($product['price'] != $price) && ($product['trial1_price'] != $price)){
        return "Sorry, it is impossible to use this payment method for 
        this order. Please select another payment method";
    }
    return $pay_plugin->do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
}

function plugin_validate_thanks($paysys_id, &$vars){
    $pay_plugin = &instantiate_plugin('payment', $paysys_id);
    return $pay_plugin->validate_thanks($vars);
    
}

function plugin_process_thanks($paysys_id, &$vars){
    $pay_plugin = &instantiate_plugin('payment', $paysys_id);
    return $pay_plugin->process_thanks($vars);
}

//////////////////// PROTECT PLUGINS hooks ////////////////////////////////

function plugin_finish_waiting_payment($payment_id, $member_id=0){
    //payment finished ok - call plugins to possible update databases, etc.
    global $__hooks;
    foreach ((array)$__hooks['finish_waiting_payment'] as $func_name){
        call_user_func($func_name, $payment_id, $member_id);
    }
}


function check_for_signup_mail($payment_id, $member_id){
    ### fix me! Signup mail ####
    global $config, $db;
    if (!$payment_id && !$member_id) return;
    global $db;
    if (!$member_id) {
        $payment = $db->get_payment($payment_id);
        $member_id = $payment['member_id'];
    }
    $member = $db->get_user($member_id);
    if ($member['data']['signup_email_sent']) return;
    if (!$config['manually_approve'] || $member['data']['is_approved']){
        $payments = $db->get_user_payments($member_id, 1);
        foreach ($payments as $p) $exists_payments++;
         // send mail only if it FIRST payment for this product
         if ($exists_payments && $config['send_signup_mail']){
             mail_signup_user($member_id);
             $member['data']['signup_email_sent']++;
             $db->update_user($member_id, $member);
         }
    } else {
        if ($member['data']['approval_email_sent']) return;
        mail_approval_wait_user($member_id);
        mail_approval_wait_admin($member_id);
        $member['data']['approval_email_sent']++;
        $db->update_user($member_id, $member);
    }
}

function plugin_update_users($member_id=0){
    //payment finished ok - call plugins to possible update databases, etc.
    global $__hooks;
    if (!is_array($__hooks['update_users'])) return;
    foreach ($__hooks['update_users'] as $func_name){
        call_user_func($func_name, $member_id);
    }
}

function plugin_update_payments($payment_id=0, $member_id=0){
    global $__hooks;
    if (!is_array($__hooks['update_payments'])) return;
    foreach ($__hooks['update_payments'] as $func_name){
        call_user_func($func_name, $payment_id, $member_id);
    }
}


function plugin_check_logged_in(){
    //check if customer already logged-in in the slave application
    global $__hooks;
    foreach ((array)$__hooks['check_logged_in'] as $func_name){
        list ($l, $p) = (array)call_user_func($func_name);
        if ($l) return array($l,$p);
    }
    return array('','');
}

function plugin_after_login($user){
    //called after successful payment
    //mutiple calls per session POSSIBLE!
    global $__hooks;
    foreach ((array)$__hooks['after_login'] as $func_name){
        call_user_func($func_name, $user);
    }
}

function plugin_after_logout($user){
    //called after logout
    global $__hooks;
    foreach ((array)$__hooks['after_logout'] as $func_name){
        call_user_func($func_name, $user);
    }
}

function plugin_get_member_links($user){
    //get array of links to display on the member.php page
    global $__hooks;
    $res = array();
    foreach ((array)$__hooks['get_member_links'] as $func_name){
        $res += (array)call_user_func($func_name, $user);
    }
    return $res;
}
function plugin_get_left_member_links($user){
    //get array of links to display on the member.php page
    global $__hooks;
    $res = array();
    foreach ((array)$__hooks['get_left_member_links'] as $func_name){
        $res += (array)call_user_func($func_name, $user);
    }
    return $res;
}


function loggingObHandler($output)
{
    // Free a piece of memory.
    unset($GLOBALS['_tmp_buf']);
    // Now we have additional 100K of memory, so - continue to work.
    if ($output == '' || trim($output) == '.') return;
    if (strstr($output, 'Fatal error') !== false){
        $GLOBALS['db']->log_error("FATAL CRON ERROR:<br />\n$output");
        return amDie("ERROR: Cron run resulted to fatal script execution error. Please look for details
        in the aMember CP -> Error Log (seek for FATAL CRON ERROR string)", true);
    } else
        $GLOBALS['db']->log_error("DEBUG (CRON OUTPUT):<br />\n$output");
}


function start_special_logging(){
    // Reserve 100K of memory for emergency needs.
    $GLOBALS['_tmp_buf'] = str_repeat('x', 1024 * 100);
    // Handle the output stream and set a handler function.
    ob_start('loggingObHandler');
}

function stop_special_logging(){
    unset($GLOBALS['_tmp_buf']);
    ob_end_clean();
}

// cron hourly
function plugin_hourly(){
    global $__hooks;
    start_special_logging();
    foreach ((array)$__hooks['hourly'] as $func_name){
        call_user_func($func_name);
    }
    stop_special_logging();
}

// cron daily
function plugin_daily(){
    global $__hooks;
    start_special_logging();
    foreach ((array)$__hooks['daily'] as $func_name){
        call_user_func($func_name);
    }
    stop_special_logging();
}

// called if subscription added
function plugin_subscription_added($member_id, $product_id,
    $member=0){
    global $__hooks;
    global $__plugins_in_rebuild;
    if ($__plugins_in_rebuild) return;
    foreach ((array)$__hooks['subscription_added'] as $func_name){
        call_user_func($func_name,$member_id, $product_id, $member);
    }
}

// called if member info updated
function plugin_subscription_updated($member_id, 
    $oldmember=0, $member=0){
    global $__hooks;
    global $__plugins_in_rebuild;
    if ($__plugins_in_rebuild) return;
    foreach ((array)$__hooks['subscription_updated'] as $func_name){
        call_user_func($func_name, $member_id, $oldmember, $member);
    }
}

// called if subscription expired/deleted
function plugin_subscription_deleted($member_id, $product_id,
    $member=0){
    global $__hooks;
    global $__plugins_in_rebuild;
    if ($__plugins_in_rebuild) return;
    foreach ((array)$__hooks['subscription_deleted'] as $func_name){
        call_user_func($func_name, $member_id, $product_id, $member);
    }
}

// called if member removed
function plugin_subscription_removed($member_id, 
    $member=0){
    global $__hooks;
    global $__plugins_in_rebuild;
    if ($__plugins_in_rebuild) return;
    foreach ((array)$__hooks['subscription_removed'] as $func_name){
        call_user_func($func_name, $member_id, $member);
    }
}

// called to check if login is not exists in second databases
// return 1 if not exists
// return 0 if exists
function plugin_subscription_check_uniq_login($login, $email, $pass){
    global $__hooks;
    foreach ((array)$__hooks['subscription_check_uniq_login'] as $func_name){
        $count += !call_user_func($func_name, $login, $email, $pass);
    }
    return !$count;
}

function plugin_subscription_rebuild(){
    global $__hooks;
    global $db;
    global $__plugins_in_rebuild;
    $ul = $db->get_allowed_users(); // should return array[product_id][user_login]=password
    $users = array();
    foreach ($ul as $product_id => $user)
        foreach ($user as $l => $p){
            $users[$l]['pass'] = $p;
            $users[$l]['product_id'][] = $product_id;
        }
    $__plugins_in_rebuild++;
    
    $db->check_subscriptions_for_all();    
    
    $__plugins_in_rebuild--;
    foreach ((array)$__hooks['subscription_rebuild'] as $func_name){
        call_user_func($func_name, $users);
    }
}

function plugin_display_signup_form(){
    global $__hooks;
    foreach ((array)$__hooks['display_signup_form'] as $func_name)
        call_user_func($func_name, $vars);
}

function plugin_validate_signup_form(&$vars, $scope='signup'){
    global $__hooks;
    $res = array();
    foreach ((array)$__hooks['validate_signup_form'] as $func_name){
        $res = array_merge((array)$res, 
            (array)call_user_func_array($func_name, array(&$vars, $scope)));
    }
    return (array)$res;
}

function plugin_validate_member_form(&$vars){
    global $__hooks;
    $res = array();
    foreach ((array)$__hooks['validate_member_form'] as $func_name){
        $res = array_merge((array)$res, 
            (array)call_user_func_array($func_name, array(&$vars, $scope)));
    }
    return (array)$res;
}

function plugin_fill_in_signup_form(&$vars){
    global $__hooks;
    $res = array();
    foreach ((array)$__hooks['fill_in_signup_form'] as $func_name){
        $res = array_merge((array)$res, (array)call_user_func_array($func_name, 
            array(&$vars)));
    }
    return (array)$res;
}

/**
 * @param $menu AdminMenu  
 */
function plugin_init_admin_menu(& $menu){
    global $__hooks;
    foreach ((array)$__hooks['init_admin_menu'] as $func_name)
        call_user_func_array($func_name, array(& $menu));
}

////////////////////////////////////////////////////////////////////////////


function check_cron(){
    global $db;
    $last_runned = $db->load_cron_time(1);
    $h_diff = date('dH') - date('dH', $last_runned);
    $d_diff = date('d') - date('d', $last_runned);
    if ($h_diff || $d_diff) $db->save_cron_time(1);
    if ($h_diff) plugin_hourly();
    if ($d_diff) plugin_daily();
}

global $db;
/*
* Database (db) object
* @global object $db
**/
$db = & instantiate_db();

// set error handler
set_error_handler('_amember_error_handler');

// load language
load_language_defs();
load_language("/language");

/// load plugins
load_plugins('protect');
load_plugins('payment');

global $config;

if ($config['product_paysystem']){
    $ps_list = array('' => '* Choose a paysystem *');
    foreach ($l=get_paysystems_list() as $p)
        $ps_list[$p['paysys_id']] = $p['title'];
    add_product_field('paysys_id', 'Payment System',
        'select', "Choose payment system to be used with this product.<br />
        This option only available if you have enabled option<br />
        \"Assign paysystem to product\" in aMember CP => Setup => Advanced
        ",'',
        array('options' => $ps_list)
    );
};

if (!is_lite()){
// add require another subscription field
$require_options = array();    
$prevent_options = array();
foreach ($db->get_products_list() as $pr){
    $require_options['ACTIVE-'.$pr['product_id']] = 'Require ACTIVE subscription for "' . $pr['title'] . '"';
    $require_options['EXPIRED-'.$pr['product_id']] = 'Require EXPIRED subscription for "' . $pr['title'] . '"';
    $prevent_options['ACTIVE-'.$pr['product_id']] = 'Member has ACTIVE subscription for "' . $pr['title'] . '"';
    $prevent_options['EXPIRED-'.$pr['product_id']] = 'Member has EXPIRED subscription for "' . $pr['title'] . '"';
}

add_product_field('require_other', 'Require another subscription<br />to order this product',
    'multi_select', "When user orders this subscription, aMember will<br />
    check that he has one from the following subscriptions<br />
    hold CTRL key to select several options
    ", '', array(
    'insert_before' => '##13',
    'size' => 4,
    'options' => array(
        ''  => "Don't require anything (default)",
    ) + $require_options
    
    ));
add_product_field('prevent_if_other', 'Disallow subscription to this<br />
    product if the following conditions meet and user has:',
    'multi_select', "When user orders this subscription, aMember will<br />
    check that he has not any from the following subscriptions<br />
    hold CTRL key to select several options
    ", '', array(
    'insert_before' => '##13',
    'size' => 4,
    'options' => array(
        ''  => "Don't prevent anything (default)",
    ) + $prevent_options
    
    ));
}

setup_plugin_hook('daily', array(&$db, 'check_subscriptions_for_all'));

setup_plugin_hook('validate_signup_form', 'member_check_ban');
setup_plugin_hook('validate_signup_form', 'member_check_additional_fields');
setup_plugin_hook('validate_member_form', 'member_check_ban');

setup_plugin_hook('daily', 'mail_not_completed_members');

setup_plugin_hook('daily', 'member_send_autoresponders');
setup_plugin_hook('finish_waiting_payment', 'member_send_zero_autoresponder');

if ($config['send_payment_mail'])
    setup_plugin_hook('finish_waiting_payment', 'mail_payment_user');
if ($config['use_address_info'] == 1)
    setup_plugin_hook('validate_signup_form', 'vsf_address');

function clear_expired_guests(){
    global $db;
    $db->delete_expired_guests();
    $db->delete_expired_threads();
}

setup_plugin_hook('daily', 'clear_expired_guests');

add_product_field('additional_subscriptions',
    'Additional subscriptions', 'multi_select',
    "User will get free subscription for these<br />
        products if subscribed for current product<br />
        hold CTRL key to select several options", '',
    array('options' => get_products_options())
);

function add_additional_subscriptions($payment_id) {
	global $additional_subscriptions_added;
    $payment = $GLOBALS['db']->get_payment($payment_id);
    $product = $GLOBALS['db']->get_product($payment['product_id']);
    $additional_subscriptions_added[$payment['member_id']][$product['product_id']]++;
    foreach (array_filter((array)$product['additional_subscriptions']) as $product_id) {
    	//to avoid recursion
    	if(@intval($additional_subscriptions_added[$payment['member_id']][$product_id])) continue;

        $pr = &get_product($product_id);

        if (!$pr->config['product_id']) {
            continue; //there is not product with such id
        }

        $begin_date = $pr->get_start($payment['member_id']);
        $expire_date = $pr->get_expire($begin_date);

        $newp = array();
        $newp['member_id']   = $payment['member_id'];
        $newp['product_id']  = $product_id;
        $newp['paysys_id']   = 'manual';
        $newp['receipt_id']  = 'ADDITIONAL ACCESS:' . $payment['receipt_id'];
        $newp['begin_date']  = $begin_date;
        $newp['expire_date'] = $expire_date;
        $newp['amount']      = 0;
        $newp['completed']   = 1;
        $newp['data'][0]['MAIN_PAYMENT_ID'] = $payment_id;

        $additional_subscriptions_added[$payment['member_id']][$product_id]++;
        $GLOBALS['db']->add_payment($newp);
    }
}

setup_plugin_hook('finish_waiting_payment',   'add_additional_subscriptions');

@define('AMEMBER_BEFORE_CONTENT', "\n<!-- content_start mark -->\n");
@define('AMEMBER_AFTER_CONTENT', "\n<!-- content_end mark -->\n");

function amember_get_header_footer($vars){
    global $config;
    static $__amember_header, $__amember_footer;
    if(empty($__amember_header) && empty($__amember_footer))
    {
        $text = file_get_contents($config['root_dir']."/templates/layout.html");
        if (empty($text)) {
            $__amember_header = "Unable to read templates/layout.html template, make sure that file exists and has correct permissions";
        } elseif (strstr($text, '{$CONTENT}')===false) {
            $__amember_header = 'File templates/layout.html does not contain required phrase {$CONTENT} in the middle, please edit and add text {$CONTENT} to template';
        } else {
            $mark = '-=--------CONTENTMARK-----------=-';
            $t = new_smarty();
            $t->assign($vars);
            $t->assign('CONTENT', $mark);
            $out = $t->fetch("layout.html");
            list($__amember_header, $__amember_footer) = explode($mark, $out, 2);
        }
    }
    return array($__amember_header, $__amember_footer);
}

function amember_get_header_code_standard()
{
    $root_url = htmlentities($GLOBALS['config']['root_surl']);
$out = <<<CUT

    <link rel="stylesheet" type="text/css" href="$root_url/templates/css/reset.css" />
    <link rel="stylesheet" type="text/css" href="$root_url/templates/css/amember.css" />
CUT;
if (file_exists(ROOT_DIR . '/templates/css/site.css'))
$out .= '
    <link rel="stylesheet" type="text/css" href="'.$root_url.'/templates/css/site.css" />';
    return $out . "\n";
}

function amember_get_header($vars){
    global $__hooks;
    $ret = "";
    if(!empty($__hooks['get_header'])){
        foreach($__hooks['get_header'] as $func_name)
            call_user_func_array($func_name, array(& $ret, $vars));
    } else {
        list($ret,) = amember_get_header_footer($vars);
    }
    $ret .= AMEMBER_BEFORE_CONTENT;
    return $ret;
}

function amember_get_footer($vars){
    global $__hooks;
    $ret = "";
    if(!empty($__hooks['get_footer'])){
        foreach($__hooks['get_footer'] as $func_name)
            call_user_func_array($func_name, array(& $ret, $vars));
    } else {
        list(,$ret) = amember_get_header_footer($vars);
    }
    $ret = AMEMBER_AFTER_CONTENT . $ret;
    return $ret;
}

function amember_get_header_code()
{
    global $__hooks;
    $ret = amember_get_header_code_standard();
    if (!empty($__hooks['get_header_code']))
    {
        foreach($__hooks['get_header_code'] as $func_name)
            call_user_func_array($func_name, array(& $ret));
    }
    return $ret;
}

function amember_filter_output($source, &$smarty, $resource_name='')
{
    global $__hooks;
    if (!preg_match('/.html$/i', $resource_name) || !$__hooks['filter_output'])
        return $source;
    foreach ($__hooks['filter_output'] as $func_name)
       call_user_func_array($func_name, array(& $source, $resource_name, $smarty));
    return $source;
}

function escape_for_js($s) {
    return strtr($s, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n'));
}

function insert_google_analytics(&$source, $resource_name, $smarty)
{
    global $db;
    static $_ga_tracked, $_ga_tracked_sale;

$ga = htmlentities(amConfig('google_analytics'));
if (!$_ga_tracked) {
    $out = <<<CUT
    <!-- google analytics code start (insert_google_analytics()) -->
    <script type="text/javascript">
        var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
        document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
    </script>
    <script type="text/javascript">
        var pageTracker = _gat._getTracker("$ga");
        pageTracker._trackPageview();

CUT;
} else {
    $out = <<<CUT
    <!-- google analytics sale start (insert_google_analytics()) -->
    <script type="text/javascript">

CUT;
}
    if (($resource_name == 'thanks.html') && ($p = $smarty->get_template_vars('payment')) && $p['amount'] && $p['completed'])
    {
        $_ga_tracked++;
        if ($_ga_tracked_sale++) return;
        if (!$p['data']['0']['BASKET_PRICES'])
            $p['data']['0']['BASKET_PRICES'] = array($p['product_id'] => $p['amount']);
        foreach ($p['data']['0']['BASKET_PRICES'] as $pid => $price){
            $pr = $db->get_product($pid);
            $pr['subtotal'] = $pr['trial1_price'] ? $pr['trial1_price'] : $pr['price'];
            $subtotal += $pr['subtotal'];
            $receipt_products[$pid] = $pr;
        }
        $user = $db->get_user($p['member_id']);
        $total = array_sum($p['data']['0']['BASKET_PRICES']);
        $city = escape_for_js($user['city']);
        $state = escape_for_js($user['state']);
        $country = escape_for_js($user['country']);
        $payment_id = $p['payment_id'];
        $tax_amount = $p['tax_amount'];
$out .= <<<CUT
  pageTracker._addTrans(
    "$payment_id", "", "$total","$tax_amount", "",
    "$city", "$state", "$country"
  );

CUT;
foreach ($receipt_products as $pr) {
    $product_id = $pr['product_id'];
    $subtotal  = $pr['subtotal'];
    $title = escape_for_js($pr['title']);
    $out .= <<<CUT
  pageTracker._addItem("$payment_id","$product_id","$title","","$subtotal","1");

CUT;
}
$out .= <<<CUT
  pageTracker._trackTrans();
CUT;
    } else { // this is not a sale
        if ($_ga_tracked++) return;
    }
        $out .= <<<CUT
    </script>
    <!-- google analytics code end -->

CUT;
    $source = preg_replace('|</body>|i', $out . "\n</body>", $source, 1, $count);
    if (!$count) $source .= $out;
}

if (amConfig('google_analytics'))
    setup_plugin_hook('filter_output', 'insert_google_analytics');

/**
 * @return bool true if customer is logged-in
 */
function amember_is_loggedin()
{
    return (bool)$_SESSION['_amember_user']['member_id'];
}
/**
 * Return user record of logged-in customer
 * @return array user record
 */
function amember_get_userrecord()
{
    if ($_SESSION['_amember_user']['member_id'])
        return $_SESSION['_amember_user'];
    else
        fatal_error("User is not logged-in in " . __FUNCTION__);
}
/**
 * Return Name (first and last) of logged-in customer
 * @return string
 */
function amember_get_name()
{
    return htmlentities($_SESSION['_amember_user']['name_f'] . ' ' . $_SESSION['_amember_user']['name_l']);
}
/**
 * Return E-Mail address of logged-in customer
 * @return string
 */
function amember_get_email()
{
    return $_SESSION['_amember_user']['email'];
}
/**
 * @return array[] of all payment records for logged-in customer
 * @throws error if user is not logged-in
 */
function amember_get_payments_all()
{
    if ($_SESSION['_amember_user']['member_id'])
        return $GLOBALS['db']->get_user_payments(intval($_SESSION['_amember_user']['member_id']), 1);
    else
        fatal_error("User is not logged-in in " . __FUNCTION__);
}
/**
 * @return array[] of ACTIVE payment records for logged-in customer
 * @throws error if user is not logged-in
 */
function amember_get_payments_active()
{
    $d = date('Y-m-d');
    $ret = array();
    foreach (amember_get_payments_all() as $p)
        if ($p['begin_date']<=$d && $p['expire_date']>=$d)
            $ret[] = $p;
    return $ret;
}
/**
 * Return all expired records, even if user now has an active record
 * to such product !
 * @return array[] of EXIRED spayment records for logged-in customer
 * @throws error if user is not logged-in
 */
function amember_get_payments_expired()
{
    $d = date('Y-m-d');
    $ret = array();
    foreach (amember_get_payments_all() as $p)
        if ($p['expire_date']<$d)
            $ret[] = $p;
    return $ret;
}
/**
 * Checks if user has active subscription to $product_id or to any from
 * array $product_id
 * @example amember_has_active_subscription(1,2,3)
 * @param array|int $product_id
 */
function amember_has_active_subscription($pid)
{
    $product_id = array();
    foreach (func_get_args() as $p)
        is_array($p) ?
            $product_id = array_merge($p, $product_id) :
            $product_id[] = $p;
    return (bool)array_intersect($product_id, $_SESSION['_amember_product_ids']);
}
/**
 * Checks if user has subscription expired to $product_id or to any from
 * array $product_id
 * Return FALSE if user has active subscription to any of these products
 * @param array|int $product_id
 * @example amember_has_active_subscription(1,3,2,4)
 * @throws error if user is not logged-in
 */
function amember_has_expired_subscription($pid)
{
    $product_id = array();
    foreach (func_get_args() as $p)
        is_array($p) ?
            $product_id = array_merge($p, $product_id) :
            $product_id[] = $p;
    if (amember_has_active_subscription($product_id)) return false;
    foreach (amember_get_payments_expired() as $p)
        if (in_array($p['product_id'], (array)$product_id))
            return true;
    return false;
}
