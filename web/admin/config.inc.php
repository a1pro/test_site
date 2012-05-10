<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Configuration directives for common aMember installation
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5174 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

$notebook_page = 'Global';
config_set_notebook_comment($notebook_page, 'main configuration directives');
config_set_notebook_comment('Plugins', 'configure list of enabled plugins');
if (!function_exists('is_trial') || !is_trial())
    config_set_notebook_comment('License', 'aMember License Code');
config_set_notebook_comment('E-Mail', 'E-Mail configuration');
config_set_notebook_comment('Advanced', 'advanced configuration');
config_set_notebook_comment('Ban', 'ban customers by email, IP and username');
config_set_notebook_comment('Countries', 'Countries and state management');
if ($config['use_tax'])
    config_set_notebook_comment('Tax Settings', 'tax settings');
#config_set_notebook_comment('Countries', 'Countries and states list');


/*********** GLOBALS *******************************************************/
add_config_field('site_title', 'Site Title',
    'text', 'will be used on member-side pages and in email messages',
    $notebook_page,
    '', '', '',
    array('size' => 40, 'default' => 'aMember'));
add_config_field('root_url', 'Root URL',
    'text', 'root script URL, usually <i>http://example.com/amember</i>',
    $notebook_page,
    'validate_root_url', '', '',
    array('size' => 40));
add_config_field('root_surl', 'Secure Root URL',
    'text', 'secure script URL, usually <i>https://example.com/amember</i>',
    $notebook_page,
    'validate_root_url', '','',
    array('size' => 40));



/*
add_config_field('admin_login', 'Admin Username',
    'text', "",
    $notebook_page);
add_config_field('admin_pass', 'Admin Password',
    'password', "",
    $notebook_page,
    'validate_password', '', '');
*/
if (!extension_loaded("curl")){
    add_config_field('curl', 'cURL executable file location',
        'text', "you need it only if you are using payment processors<br />
        like Authorize.Net or PayFlow Pro<br />
        usually valid path is /usr/bin/curl or /usr/local/bin/curl",
        $notebook_page, 'validate_curl');
    function validate_curl($field, $vars){
        $fname = $field['name']; $val = $vars[$fname];
        if (!$val) return;
        exec("$val http://www.yahoo.com/ 2>&1", $out, $return);
        if ($return){
            return "Couldn't execute '$val http://www.yahoo.com/'. Exit code: $return";
        }
    }
}

add_config_field('##02', 'Signup Form Configuration',
    'header', '', $notebook_page);
if (!is_lite())
add_config_field('generate_login', 'Generate Login',
    'checkbox', "should aMember generate username for customer?",
    $notebook_page,
    '','','',
    array(
    ));

add_config_field('login_min_length', 'Login minimum length',
    'integer', "",
    $notebook_page,
    '','','',
    array(
        'default' => 5
    ));
add_config_field('login_max_length', 'Login maximum length',
    'integer', "",
    $notebook_page,
    '','','',
    array(
        'default' => 8
    ));
add_config_field('login_disallow_spaces', 'Do not allow spaces in username',
    'checkbox', "disallow spaces in login",
    $notebook_page);
add_config_field('login_dont_lowercase', 'Do not lowercase username',
    'checkbox', "by default, aMember automatically lowercases entered username<br>
    here you can disable this function",
    $notebook_page);

if (!is_lite())
add_config_field('generate_pass', 'Generate Password',
    'checkbox', "should aMember generate password for customer?",
    $notebook_page,
    '','','',
    array(
    ));
add_config_field('pass_min_length', 'Password minimum length',
    'integer', "",
    $notebook_page,
    '','','',
    array(
        'default' => 6
    ));
add_config_field('pass_max_length', 'Password maximum length',
    'integer', "",
    $notebook_page,
    '','','',
    array(
        'default' => 8
    ));

add_config_field('unique_email', 'Require Unique Email',
    'checkbox', "require unique email address for each member",
    $notebook_page,
    '','','',
    array(
    ));
add_config_field('use_address_info', 'Use Address Info',
    'select', "display address fields in the member forms",
    $notebook_page,
    '','','',
    array(
        'options' => array(
            0 => 'No',
            1 => 'Yes, required',
            2 => 'Yes, optional',
        )
    ));

add_config_field('currency', 'Display Currency',
    'text', "currency to be displayed on membership and signup pages<br />
    NOTE - it is display change only - real price/currency change must be<br />
    made in product settings and not all payment processors allow you to<br />
    change currency. Make sure that EACH enabled payment processor will work<br />
    in selected currency.",
    $notebook_page,
    '', '', '',
    array('default' => '$', size=>6)
    );


add_config_field('##03', 'Enable Features',
    'header', '', $notebook_page);
if (!is_lite())
add_config_field('use_coupons', 'Allow usage of coupons',
    'checkbox', "there will be a box to enter coupon code<br />
    on aMember signup.php and member.php pages if you enable<br />
    this option",
    "Global",
    '', '', '',
    array(
    ));

if (!is_lite())
add_config_field('use_affiliates', 'Enable affiliate program',
    'checkbox', "aMember CP -> Setup -> Affiliates will appear<br />
    when you enable this option",
    "Global",
    '', '', '',
    array(
    ));
if (!is_lite())
add_config_field('use_tax', 'Enable Tax',
    'checkbox', "calculate sales tax or VAT
    ",
    "Global",
    '', '', '',
    array(
    ));


/* END OF Globals */

/************************* Plugins ****************************************/

add_config_field('plugins.payment', 'Payment Plugins',
    'multi_checkbox', "select plugins for payment. It is always recommended<br />
    to have 'free' plugin enabled. It will not be displayed in payment<br />
    methods list.<br />
    <b>HOLD Ctrl key to select multiple plugins</b><br />
    <b>Note - 'free' payment plugin is always enabled, but invisible</b>
    ",
    "Plugins",
    '', '', 'payment_plugins_set',
    array(
        'store_type' => 1,
        'size' => '15em',
        'options' => read_plugins_list("$config[root_dir]/plugins/payment")
    ));

add_config_field('plugins.protect', 'Protect Plugins',
    'multi_checkbox', "select plugins for protection. It is always recommended<br />
    to have 'php_include' plugin enabled.<br />
    <b>HOLD Ctrl key to select multiple plugins</b>
    ",
    "Plugins",
    '', '', '',
    array(
        'store_type' => 1,
        'size' => '15em',
        'options' => read_plugins_list("$config[root_dir]/plugins/protect")
    ));

/**** END OF PLUGINS ***************************************************/
/************* LICENSE *************************************************/
if (!function_exists('is_trial') || !is_trial())
add_config_field('license', 'License',
    'textarea', "please enter license text",
    "License",
    'validate_license', '', '',
    array(
        'cols' => 100,
        'rows' => 7,
        'store_type' => 2
    ));
/****** END OF LICENSE *************************************************/

/********************** E-MAIL *****************************************/
require_once($config['root_dir']."/admin/config_email.inc.php");
/********************** END OF E-Mail **********************************/

/********************** ADVANCED ***************************************/
add_config_field('use_cron', 'Use External Cron',
    'checkbox', "use external cron - recommended.<br />
    you may find more details
    <a href='http://manual.amember.com/Setup_a_Cron_Job' target=_blank>here</a><br/>
    External cron is required if you are using credit card recurring billing, or there is<br />
    a lot of customers in your database<br />
    ",
    "Advanced",
    '', '', '',
    array(
    ));

add_config_field('##2', 'Access Log automatic clean-up',
    'header', '', 'Advanced');
add_config_field('clear_access_log', 'Clear Access Log',
    'checkbox', "should aMember clear access log to save database space and speed?",
    "Advanced",
    '','','',
    array(
    ));
add_config_field('clear_access_log_days', 'Clear Access Log before .. days',
    'integer', "number of days",
    "Advanced",
    'validate_integer');

add_config_field('##3', "Account Sharing Prevention
 <a href='http://manual.amember.com/Account_Sharing_Prevention' target=_blank>?</a>",
    'header', '', 'Advanced');
add_config_field('max_ip_count', 'Maximum count of different IP',
    'integer', "if member will reach this limit, his account will be locked",
    "Advanced",
    'validate_integer');
add_config_field('max_ip_period', 'Count IP for ... minutes',
    'integer', "the above limit is for previous ... minutes",
    "Advanced",
    'validate_integer');
add_config_field('max_ip_actions', 'Account Sharing Prevention',
    'select', "When account sharing violation detected<br />
    (have a look to 2 above options), aMember will do specific<br />
    actions to stop account misuse<br />",
    "Advanced",
    '', 'email_select_get', '',
    array(
        'options' => array(
            '' => 'Both disable Customer Account and email admin',
            1 => 'Only disable customer account',
            2 => 'Only email admin regarding account sharing',
        )
    ));

add_config_field('##4', 'Select multiple Products',
    'header', '', 'Advanced');
if (!is_lite())
add_config_field('select_multiple_products', 'Select Multiple Products on Signup Page',
    'checkbox', "allow select for multiple products for order<br />
    it might cause problems with recurring plugins or plugins,<br />
    which don't support this behaviour",
    "Advanced",
    '', '', '',
    array(
    ));
if (!is_lite())
add_config_field('member_select_multiple_products', 'Select Multiple Products on Member Page',
    'checkbox', "allow select for multiple products for order<br />
    on member.php page (for existing members)
    ",
    "Advanced",
    '', '', '',
    array(
    ));
if (!is_lite())
add_config_field('multi_title', 'Multiple Order Title',
    'text', "when user ordering multiple products, what should be<br />
    displayed on the payment system receipt page?",
    "Advanced",
    '', '', '',
    array(
        'default' => 'Membership'
    ));


add_config_field('##5', 'Advanced Options',
    'header', '', 'Advanced');

if (function_exists('imagecreatefrompng'))
add_config_field('use_captcha_signup', 'Use CAPTCHA on signup page',
    'checkbox', "use verification image on signup page,<br />
    to prevent automatic signups
    ",
    "Advanced",
    '', '', '',
    array(
    ));



if (!is_lite())
add_config_field('manually_approve', 'Manually Approve New Members',
    'checkbox', "manually approve all new members (first payment)<br />
    don't enable it if you have huge members base already - all old<br />
    members become not-approved
    ",
    "Advanced",
    '', 'email_checkbox_get', '',
    array(
    ));

add_config_field('product_paysystem', 'Assign paysystem to product',
    'checkbox', "
    if you enable this option, you will get select in product<br />
    options. You will be allowed to choose a payment system to<br />
    be used with this product. Don't enable this option along<br />
    with \"Select Multiple Product\" or be very careful!<br />
    Usually this option is not very useful.
    ",
    "Advanced",
    '', '', '',
    array(
    ));
add_config_field('limit_renewals', 'Limit Renewals',
    'select', "
    don't allow members to order new subscriptions,<br />
    when they already have active subscriptions.<br />
    Please be aware - in some situations, enabling<br />
    of this option will make impossible for user<br />
    to use your service without interruption.<br />
    All these options means that there is already<br />
    another ACTIVE subscription.
    ",
    "Advanced",
    '', '', '',
    array(
        'options' => array(
            '' =>"Don't limit - recommended",
            1 => "Disallow subscription for the same product",
            2 => "Disallow subscription for the same renewal group",
            3 => "Disallow if there is any other active subscription"
        )
    ));

/*if (!is_lite())
add_config_field('use_shop', 'Use Shopping-cart alike interface',
    'select', "use shopping cart interface (/amember/shop/) instead<br />
               of default one-step signup interface.
    ",
    "Advanced",
    '', '', '',
    array(
        'options' => array(0 => 'No', 1 => 'Yes')
    ));
*/
add_config_field('date_format', 'Date Format',
    'text', "php <a target=_top
    href='http://www.php.net/manual/en/function.strftime.php'>strftime()</a> format used,<br />
    use <i>%m/%d/%y<i> for US format, <i>%d/%m/%Y</i> for European
    ",
    "Advanced",
    '', '', '',
    array(
        'default' => '%m/%d/%y'
    ));
add_config_field('time_format', 'Time Format',
    'text', "php <a target=_top
    href='http://www.php.net/manual/en/function.strftime.php'>strftime()</a> format used,<br />
    use <i>%m/%d/%y %H:%M:%S<i> for US format, <i>%d/%m/%Y %H:%M:%S</i> for European
    ",
    "Advanced",
    '', '', '',
    array(
        'default' => '%m/%d/%y'
    ));
add_config_field('bruteforce_count', 'Bruteforce Attemps Limit',
    'text', "how many incorrect login attempts allowed before<br />
    delay. When limit is reached, user will see message:<br />
    \"Please wait XX minutes before next login attempt\"<br />
    where XX can be configure below
    ",
    "Advanced",
    '', '', '',
    array(
        'default' => '5'
    ));
add_config_field('bruteforce_delay', 'Bruteforce Delay',
    'text', "when user entered incorrect username/password<br />
    NN times (configured above), he will be forced to wait<br />
    XX seconds before next login attempt. Enter value of delay<br />
    in seconds into this field. Default value - 2 minutes
    ",
    "Advanced",
    '', '', '',
    array(
        'default' => '120'
    ));
add_config_field('profile_fields', 'User can change the following fields',
    'multi_select', "user can change the following fields<br />
     when he clicks \"Edit Profile\" link",
    "Advanced",
    '','','',
    array(
        'options' => array(
            'login' => 'Login (username)',
            'pass0'  => 'Password',
            'name_f' => 'First Name',
            'name_l' => 'Last Name',
            'email'  => 'E-Mail',
        ) + (($GLOBALS['config']['use_address_info']) ?
        array(
            'street' => 'Street',
            'city'   => 'City',
            'state'  => 'State',
            'zip'    => 'ZIP',
            'country'=> 'Country'
        ) : array()
        ) 
//        + array('unsubscribed' => 'Unsubscribe')
        ,'store_type' => 1
    ));

if (!is_lite())
add_config_field('safe_send_pass', 'Enable Secure Password Reminder',
    'checkbox', "aMember will email a password change link instead<br />
    of actual password
    ",
    "Advanced",
    '', 'email_checkbox_get', '',
    array(
        'email_template' => 'send_security_code',
    ));
if (!is_lite())
add_config_field('dont_check_updates', 'Don\'t check for aMember updates',
    'checkbox', "disable automatic checking for new aMember Pro versions<br />
    when you enter into aMember Control panel",
    "Advanced",
    '', '', '',
    array(
    ));


if (!is_lite())
add_config_field('archive_for_browsing', 'Display messages archive for guests',
    'checkbox', "enable view newsletter messages",
    "Advanced",
    '', '', '',
    array(
    ));
if (!is_lite())
add_config_field('keep_messages_online', 'Keep messages online, months',
    'text', "keep newsletter messages",
    "Advanced",
    '', '', '',
    array());

if (!is_lite())
add_config_field('dont_confirm_guests', 'Do not confirm guest subscriptions',
    'select', "send confirmation email or not",
    "Advanced",
    '', 'email_select_get', '',
    array(
        'email_template' => 'verify_guest',
        'options' => array(
            "" => 'Confirm Newsletter Subscriptions',
            1 => 'No, do not confirm Newsletter Subscriptions'
        )
    ));
if (!is_lite())
add_config_field('auto_login_after_signup', 'Automatically login customer after signup',
    'checkbox', "",
    "Advanced",
    '', '', '',
    array(
    ));
if (!is_lite())
add_config_field('hide_password_cp', 'Hide customer passwords in aMember CP',
    'checkbox', "",
    "Advanced",
    '', '', '',
    array(
    ));
add_config_field('terms_is_price', 'Display only Product Price in terms',
    'checkbox', "on signup.php and member.php display only price instead<br />
    of full subscriptino terms description. this simulates behaviour of<br />
    aMember version up to 3.1.2",
    "Advanced",
    '', '', '',
    array(
    ));
    
add_config_field('payment_report_num_days', 'Number of days for payment report on admin index page',
    'text', "",
    "Advanced",
    '', '', '',
    array('default'=>7));

add_config_field('##6', 'XML-RPC Integration Interface',
    'header', '', 'Advanced');
if (!is_lite())
add_config_field('use_xmlrpc', 'Enable XML-RPC Library',
    'checkbox', "",
    "Advanced",
    '', '', '',
    array(
    ));
if (!is_lite())
add_config_field('xmlrpc_login', 'Login for XML-RPC server',
    'text', "Login for access to XML-RPC interface",
    "Advanced",
    '', '', '',
    array(
        'default' => ''
    ));
if (!is_lite())
add_config_field('xmlrpc_password', 'Password for XML-RPC server',
    'password_c', "Password for access XML-RPC inteface",
    "Advanced",
    '', '', '');
if (!is_lite())
add_config_field('google_analytics', 'Google Analytics Account ID',
    'text', "To enable automatic sales and hits tracking with GA,
    <br />
    enter Google Analytics cAccount ID into this field. 
    <a href='http://www.google.com/support/googleanalytics/bin/answer.py?answer=55603' target=_blank>Where can I find my tracking ID?</a>
    <br />
    The tracking ID will look like <i>UA-1231231-1</i>.<br />
    Please note - this tracking is only for pages displayed by aMember,<br />
    pages that are just protected by aMember, cannot be tracked. Use<br />
    <a href=http://www.google.com/support/googleanalytics/bin/search.py?query=how+to+add+tracking&ctx=en%3Asearchbox'' target=_blank>GA instructions</a>
    how to add tracking code to your own pages.
    ",
    "Advanced",
    '', '', '');
//array('store_type' => 3)

//
/********* END OF ADVANCED **********************************************************/
/********************************** BAN *********************************************/
add_config_field('ban.email', 'Denied Email address list',
    'textarea', 'put one email per line<br />
    use * for substitution, like <i>*@aol.com</i>',
    'Ban',
    '', '', '',
    array('cols' => 30, 'rows'=>10, 'store_type' => 2));
add_config_field('ban.email_action', 'Denied Email Action',
    'select', 'what to do if user enter denied email',
    'Ban',
    '', '', '',
    array('options' => array(
    'error' => 'Display error message',
    'die'   => 'Die and show ugly internal error page'
    )));
add_config_field('ban.ip', 'Denied IP address list',
    'textarea', 'put one IP per line<br />
    use * for substitution, like <i>193.122.123.*</i>',
    'Ban',
    '', '', '',
    array('cols' => 30, 'rows'=>10, 'store_type' => 2));
add_config_field('ban.ip_action', 'Denied IP Action',
    'select', 'what to do if user comes from denied IP<br />
    to signup or renewal page',
    'Ban',
    '', '', '',
    array('options' => array(
    'error' => 'Display error message',
    'die'   => 'Die and show ugly internal error page'
    )));
add_config_field('ban.login', 'Denied usernames list',
    'textarea', 'put one denined username per line<br />
    use * for substitution, like <i>admin*</i>',
    'Ban',
    '', '', '',
    array('cols' => 30, 'rows'=>10, 'store_type' => 2));
add_config_field('ban.login_action', 'Denied Username Action',
    'select', 'what to do if user enter denied username',
    'Ban',
    '', '', '',
    array('options' => array(
    'error' => 'Display error message',
    'die'   => 'Die and show ugly internal error page'
    )));
/********* END OF BAN **********************************************************/
/********************************** TAX *********************************************/
add_config_field('tax', 'Tax Settings',
    '', '',
    'Tax Settings',
    '', 'tax_page_get', 'tax_page_set'
);
/********* END OF TAX **********************************************************/
/********************************** COUNTRIES ***********************************/
/*
add_config_field('countries', 'Countries list',
    'textarea', 'put one country per line comma separated:<br />
    Country code, Country title',
    'Countries',
    '', '', '',
    array('cols' => 50, 'rows'=>20, 'store_type' => 2));
add_config_field('states', 'States list',
    'textarea', 'put one state per line comma separated:<br />
    Country code, State title, State code',
    'Countries',
    '', '', '',
    array('cols' => 50, 'rows'=>20, 'store_type' => 2));
*/
/********************************** AFFILIATES ***********************************/
if ($config['use_affiliates'])
    aff_config();

/********************************** SHOP ***********************************/
function shop_config(){
    $page = "Shopping cart";
    config_set_notebook_comment($page, 'configure shopping-cart interface');
    add_config_field('shop.price_groups', 'Price Groups',
    'textarea', 'Enter one per line, in the following format:<br />
    price_group_number price group description<br />
    will be displayed in products list',
    $page,
    '', '', '',
    array('cols' => 30, 'rows'=>10, 'store_type' => 2));
}

if ($config['use_shop'])
    shop_config();

/********************************** LANGUAGES ***********************************/
$page = "Languages";
config_set_notebook_comment($page, 'configure multi-language user interface');
if ($_REQUEST['notebook'] == $page){
    add_config_field('lang.list', 'Available Languages',
        'multi_checkbox', 'choose which languages are available<br />
        for customers choice',
        $page,
        '', '', '',
        array('options' => languages_get_options(), 'store_type' => 1, size=> '5em'));
    add_config_field('lang.default', 'Default Language',
        'select', 'will be used by default until customer<br />
        makes another choice. Make sure it is also<br />
        selected in the list above',
        $page,
        '', '', '',
        array('options' => languages_get_options(), 'default'=>'en:English'));
    add_config_field('lang.display_choice', 'Display Language Choice',
        'checkbox', 'allow customer to choose another language<br />
        from signup, login and other pages',
        $page,
        '', '', '',
        array());
    config_set_readme($page, $config['root_dir'].'/language/readme.txt');
}

/*############################## FUNCTIONS #########################################*/


function validate_license($field, $vars){
    if (function_exists('is_trial') && is_trial()) return;
    global $config;
    $v = $vars[$field['name']];
    if (!strlen($v)) $errors[] = "Please enter license code";
    $domains = array();
    foreach (preg_split('|===== ENF OF LICENSE =====[\r\n\s]*|m', $v, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) as $v){
        $v .= "===== ENF OF LICENSE =====";

        if (!preg_match('/^===== .+?===== EN(F|D) OF LICENSE =====$/s', $v)) $errors[] = "Please enter full license code (it should start and end with ======)";
        if (!preg_match('/^===== .+?=====$/m', $v)) $errors[] = "Please enter valid license code (seems there is a not-necessary linebreak in the first line between ===== and =====)";
        if ( preg_match('/^===== .+? \((.+?), (.+?), valid thru (.+?)\) =====/m', $v, $regs)){
            $d = preg_quote($regs[1]);
            $sd = preg_quote($regs[2]);
            $exp = $regs[3];
            $domains[] = $d;
            $domains[] = $sd;

        }
    }
    $u1 = parse_url($url=$config['root_url']);
    $u2 = parse_url($surl=$config['root_surl']);
    // check if license matches domains
    $matched = 0;
    foreach ($domains as $d) {
        if (preg_match($x = "/($d)\$/", $u1['host']))
            $matched++;
    }
    if (!$matched)
        $errors[] = "Root URL '$url' doesn't match license domain";
    // check if license matches secure root url
    $matched = 0;
    foreach ($domains as $d) {
        if (preg_match($x = "/($d)\$/", $u2['host']))
            $matched++;
    }
    if (!$matched)
        $errors[] = "Secure Root URL '$url' doesn't match license domain";
    ////
    return $errors[0];
}


/// helper function

function read_plugins_list($ds){
    $d = opendir($ds);
    if (!$d) fatal_error("Cannot open '$ds'");
    $res = array();
    while ($dd = readdir($d)){
        $fpath = "$ds/$dd";
        if (($dd[0] == '.') || !is_dir($fpath)) continue;
        if ($dd == 'CVS') continue;
        if ($dd == 'cc_core') continue;
        if (!is_file("$fpath/$dd.inc.php")) continue;
        $res[$dd] = $dd;
    }
    ksort($res);
    return $res;
}


function payment_plugins_set($field,$vars,$db_vars){
    global $db;
    $v = $vars['plugins_payment'];
    if (!in_array('free', $v)) $v[] = "free";
    $db->config_set('plugins.payment', $v, 1);
}

///////////////////////////////////////////////
function get_select($options, $value=''){
    foreach ($options as $k=>$v){
        $sel = ($k == $value) ? 'selected' : '';
        $res .= "<option value=\"$k\">$v\n";
    }
    return $res;
}

function tax_page_get(&$field, &$vars){
    global $config;
    $t = new_smarty();
    $t->assign('v', $vars);
    return $t->fetch('admin/setup_tax.html');
}

function tax_page_set(&$field, &$vars, &$db_vars){
    if ($vars['remove_regional']){
        global $db, $config;
        $rt = $config['regional_taxes'];
        unset($rt[$vars['id']]);
        $db->config_set('regional_taxes', $rt, 1);
        $vars['regional_taxes'] = $rt;
        return;
    }
 
    $db_vars['tax_type']  = intval($vars['tax_type']);
    if ($vars['tax_type'] == 1){
	    $db_vars['tax_value'] = doubleval($vars['tax_value']);
	    $db_vars['tax_title'] = $vars['tax_title'];
    } elseif ($vars['tax_type'] == 2 
    	&& $vars['regional_tax_value'] > 0
        && $vars['country']){
        global $db, $config;
        $rt = $config['regional_taxes'];
        $rt[] = array(
        	'tax_value' => $vars['regional_tax_value'],
        	'country' => $vars['country'],
        	'state' => $vars['state'],
                'zip'   => $vars['zip']
        );
        $db->config_set('regional_taxes', $rt, 1);
        $vars['regional_taxes'] = $rt;
    }

}

//////////// validation functions
function validate_root_url($field, $vars){
    $url = $vars[$field['name']];
    if (!preg_match('/^http(s|):\/\/.+$/', $url))
        return "$field[title] - must start from <i>http://</i> or <i>https://</i>";
    if (preg_match('/\/+$/', $url))
        return "$field[title] - must be without trailing slash";

    ////
    if (function_exists('is_trial') && is_trial()) return;

    $up = parse_url($url);
    global $_amember_license;
    $matched_url = 0;
    foreach (array_merge($_amember_license['domain'], $_amember_license['secure_domain']) as $d){
        $d = preg_quote($d);
        if (preg_match("/(^|\.)$d\$/", $up['host']))
            $matched_url++;
    }
    $list_domains = join(',', array_unique(array_merge($_amember_license['domain'], $_amember_license['secure_domain'])));
    if (!$matched_url)
        return "URL '$url' doesn't match license domain(s): $list_domains";
}
function validate_email_address($field, $vars){
    if (!check_email($vars[$field['name']]))
        return "$field[title] - incorrect email address";
}

function validate_emails($field, $vars){
    if(!$vars[$field['name']]) return; // Allow empty value;
    $arr = preg_split("/[,;]/", $vars[$field['name']]);
    $incorrect = 0;
    foreach($arr as $e){
        if ($e && !check_email(trim($e))) $incorrect++;
    }
    if($incorrect) return "$field[title] - incorrect email address";
}

function validate_integer($field, $vars){
    if (!preg_match('/^(\-|)\d+$/', $vars[$field['name']]))
        return "$field[title] - incorrect number";

}
function validate_password($field, $vars){
    $fname = str_replace('.', '_', $field['name']);
    if ($vars[$fname] != $vars[$fname . '_confirm'])
        return "$field[title] - password and confirmation different";
}
function validate_risk_level($field, $vars){
    if (!preg_match('/^\d+(\.|)(\d+|)$/', $vars[$field['name']]) || $vars[$field['name']] < 0 || $vars[$field['name']] > 10)
        return "$field[title] - incorrect number";

}
