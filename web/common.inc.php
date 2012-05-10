<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/**
* This file contain commonly used subroutines
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Common Routines
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5455 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

set_magic_quotes_runtime(0);
if (ini_get('register_globals'))
  foreach($_REQUEST as $k=>$v) {
    if (preg_match('/^(GLOBALS|_SERVER|_GET|_POST|_COOKIE|_FILES|_ENV|_REQUEST|_SESSION)$/i', $k)) exit();
    unset(${$k});
  }

$config['version'] = '3.2.3PRO';
$config['require_db_version'] = '320';

$config['tables']  = array(
    'access_log',
    'admin_log',
    'admins',
    'aff_clicks',
    'aff_commission',
    'config',
    'coupon',
    'cron_run',
    'error_log',
    'email_templates',
    'folders',
    'failed_login',
    'members',
    'newsletter_archive',
    'newsletter_guest',
    'newsletter_guest_subscriptions',
    'newsletter_member_subscriptions',
    'newsletter_thread',
    'payments',
    'products',
);

$config['tables_skip_backup']  = array(
    'access_log',
    'error_log'
);


/**
* Create new Smarty object
* @return double Smarty to newly created Smarty object
*
*/
function smarty_modifier_amember_date_format($string, $format=null)
{
    if ($string == MAX_SQL_DATE)
        return _COMMON_LIFETIME;
    if ($string == RECURRING_SQL_DATE)
        return _COMMON_RECURRING;
    if ($format == null)
        $format = $GLOBALS['config']['date_format'];        
    if ($string != '') {
        return strftime($format, smarty_make_timestamp($string));
    } 
}
function smarty_prefilter_literal_script($source, &$smarty){
    $result=&$source;
    $result=preg_replace('~<script\b(?![^>]*smarty)~iU', '<!--{literal} --><script', $result);
    $result=preg_replace('~</script>~iU', '</script><!--{literal} {/literal}-->', $result);
    return $result;
}
function smarty_prefilter_literal_style($source, &$smarty){
    $result=&$source;
    $result=preg_replace('~<style\b(?![^>]*smarty)~iU', '<!--{literal} --><style', $result);
    $result=preg_replace('~</style>~iU', '</style><!--{literal} {/literal}-->', $result);
    return $result;
}
function smarty_prefilter_put_config_cb($args){
    global $config;
    return $config[$args[1]];
}
function smarty_outputfilter_put_config($source, &$smarty){
    $result=&$source;
    $result=preg_replace_callback('/#\$config\.(.+?)#/', 'smarty_prefilter_put_config_cb',
        $result);
    return $result;
}
function smarty_prefilter_put_lang_cb($args){
    $vars = split('\|', $args[1]);
    $const = array_shift($vars);
    if ($vars){
        return vsprintf(constant($const), $vars);
    } else {
        return constant($args[1]);
    }
}
function smarty_outputfilter_put_lang($source, &$smarty){
    $result=&$source;
    $result=preg_replace_callback('/#(_[A-Z].+?)#/', 'smarty_prefilter_put_lang_cb',
        $result);
    return $result;
}
function smarty_outputfilter_literal_cleanup($source, &$smarty){
    $result=&$source;
    $result=preg_replace('~<!--({literal})? -->~iU', '', $result);
    return $result;
}

function smarty_function_country_options($params, &$smarty){
    global $db;
    $ret = "";
    foreach (db_getCountryList($add_empty=true) as $c => $t){    
        $sel = ($c == $params['selected']) ? 'selected="selected"' : '';
        $ret .= "<option value='".htmlspecialchars($c)."' $sel>".htmlspecialchars($t)."</option>\n";
    }
    return $ret;
}
function smarty_function_state_options($params, &$smarty){
    global $db;
    $ret='';
    $state=db_getStateByCode($country, $state_code);
    $states=db_getStatesForCountry($country, 1);
    foreach ($states as $c=>$t) {
            $sel = ($c == $params['selected']) ? 'selected="selected"' : '';
            $ret .= "<option value='".htmlspecialchars($c)."' $sel>".htmlspecialchars($t)."</option>\n";
        }  
    return $ret;
}
function smarty_function_lookup_country($params, &$smarty){
	$d = & amDb();
	return $d->selectCell("SELECT title FROM ?_countries WHERE country=?", $params['key']);
}
function smarty_function_lookup_state($params, &$smarty){
	$d = & amDb();
	return $d->selectCell("SELECT title FROM ?_states WHERE country=? and state=?", $params['country'], $params['key']);
}
/** 
* @return relative URL path to Root URL with slash included if needed
* It is calculated based on location of file relative to root_dir
* If called not from a file within aMember root, root_surl will be
* returned
*/
function smarty_function_root_url($params, &$smarty){
	$rd  = ROOT_DIR;
	$fn = normalizePath(dirname(array_shift(get_included_files()))); // filename of the script
	if (($c = strpos($fn, $rd)) === FALSE)
		return amConfig('root_surl');
	$fn = substr($fn, $c + strlen($rd)+1);
	if ($fn == '') return '';
	$fnn = '';
	foreach (explode('/', $fn) as $f)
		$fnn .= '../';
	return $fnn;
}

function new_smarty(){
    global $config;
    static $has_templates_c;
    $tc = ROOT_DIR . '/templates_c/';
    if (!isset($has_templates_c)){
    	$has_templates_c = is_dir($tc) && is_writable($tc) && file_exists("$tc/.htaccess");
    }
    require_once($config['root_dir'].'/smarty/Smarty.class.php');
    if (!$has_templates_c){
    	require_once($config['root_dir'].'/smarty/SmartyNoWrite.class.php');
    	$t = new _SmartyNoWrite();
	    $t->force_compile = 1;
	    $t->compile_check = 1;
    } else {
    	$t = new _Smarty;
	    $t->compile_check = 1;
    }
    $t->register_prefilter('smarty_prefilter_literal_script');
    $t->register_prefilter('smarty_prefilter_literal_style');
    require_once $t->_get_plugin_filepath('shared','make_timestamp');
    $t->register_modifier('amember_date_format', 'smarty_modifier_amember_date_format');
    $t->register_outputfilter('smarty_outputfilter_put_config');
    $t->register_outputfilter('smarty_outputfilter_put_lang');
    $t->register_outputfilter('smarty_outputfilter_literal_cleanup');
    $t->register_outputfilter('amember_filter_output');
    $t->register_function('country_options', 'smarty_function_country_options');    
    $t->register_function('state_options', 'smarty_function_state_options');    
    $t->register_function('lookup_country', 'smarty_function_lookup_country');    
    $t->register_function('lookup_state', 'smarty_function_lookup_state');    
    $t->register_function('root_url', 'smarty_function_root_url');    
    
    $t->template_dir = ROOT_DIR . "/templates";
    $t->compile_dir  = ROOT_DIR . "/templates_c";
    
    $t->register_resource("memory", array("memory_get_template",
                                       "memory_get_timestamp",
                                       "memory_get_secure",
                                       "memory_get_trusted"));
    $t->assign('config', $config);
    return $t;
}

/**
* Check email using regexes
* @param string email
* @return bool true if email valid, false if not
*/

function check_email($email) {
    #characters allowed on name: 0-9a-Z-._ on host: 0-9a-Z-. on between: @
    if (!preg_match('/^[0-9a-zA-Z\.\-\_]+\@[0-9a-zA-Z\.\-]+$/', $email))
        return false;
    #must start or end with alpha or num
    if ( preg_match('/^[^0-9a-zA-Z]|[^0-9a-zA-Z]$/', $email))
        return false;
    #name must end with alpha or num
    if (!preg_match('/([0-9a-zA-Z_]{1})\@./',$email) )
        return false;
    #host must start with alpha or num
    if (!preg_match('/.\@([0-9a-zA-Z_]{1})/',$email) )
        return false;
    #pair .- or -. or -- or .. not allowed
    if ( preg_match('/.\.\-.|.\-\..|.\.\..|.\-\-./',$email) )
        return false;
    #pair ._ or -_ or _. or _- not allowed
//    if ( preg_match('/.\._.|.-_.|._\..|._-./',$email) )
//        return false;
    #host must end with '.' plus 2-6 alpha for TopLevelDomain
    if (!preg_match('/\.([a-zA-Z]{2,6})$/',$email) )
        return false;

    return true;
}

/**
* Retrieve input vars, trim spaces and return as array
* @return array array of input vars (_POST / _GET)
*
*/
function get_input_vars(){
    $vars = $_SERVER['REQUEST_METHOD'] == 'POST' ? $_POST : $_GET;
    am_filter_input_var($vars, '');
    return $vars;
}
function am_filter_input_var(&$s, $key){
    if (is_array($s)){
        array_walk($s,  'am_filter_input_var') ;
    } else {
        $s = trim($s);
        if (get_magic_quotes_gpc())
            $s = stripslashes($s);
    }
}

/**
* Display fatal error to user.
* Should send mail and save log (not implemented yet)
* Exit current script
* return bool should never return
*/
function fatal_error($error, $log_error=1, $is_html=0){
    global $config, $db;
    global $in_fatal_error; //!
    $in_fatal_error++;
    if ($in_fatal_error > 2){
        die("<br /><br /><b>fatal_error called twice</b>");
    }
    $display_error = $error;
    if (defined('AM_DEBUG')){
        $display_error .= 
        "<br /><br /><pre><small>" .
        print_r(debug_backtrace(), true) .
        "</small></pre>";
    }

    $t = & new_smarty();
    $t->assign('is_html', $is_html);
    $t->assign('error', $display_error);
    $t->assign('admin_email', $config['admin_email']);
    $t->display("fatal_error.html");
    // log error
    if ($log_error && is_object($db))
        $db->log_error($error);
    exit();
}

/**
* Load URL
* if post specified, will be POST, if not, will be GET request
* return string with result of fetch
*/
function get_url($url, $post='', $add_referer=0, $no_proxy=0){
    global $db, $config;
    if (extension_loaded("curl")){
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if ($post)  {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($add_referer)
            curl_setopt($ch, CURLOPT_REFERER, "$config[root_surl]/signup.php");

/*
        if(!$no_proxy)
        if (strpos($db->config['host'], ".secureserver.net") > 0){
            //use GoDaddy proxy
            curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE); 
            curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); 
			curl_setopt ($ch, CURLOPT_PROXY,"http://proxy.shr.secureserver.net:3128");
            curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);        
        }
*/
        $buffer = curl_exec($ch);
        if(curl_errno($ch))$db->log_error("CURL ERROR: (".curl_errno($ch).") - ".curl_error($ch));
        curl_close($ch);
        return $buffer;
    } else {
        $curl = $config['curl'];
        if (!strlen($curl)) {
            $db->log_error("cURL path is not set - cc transaction cannot be completed");
            return;
        }
        $params = "";
        if ($add_referer){
            $params .= " -e ". escapeshellarg($config['root_surl']."/signup.php");
        }
        if (substr(php_uname(), 0, 7) == "Windows") {
            if ($post)
            $ret = `$curl $params -d "$post" "$url"`;
            else
            $ret = `$curl $params "$url"`;
        } else {
            $url  = escapeshellarg($url);
            $post = escapeshellarg($post);
            if ($post)
            $ret = `$curl $params -d $post $url`;
            else
            $ret = `$curl $params $url`;
        }
        return $ret;
    }
}


///////////////// smarty resource //////////////////////////////////
function memory_get_template($tpl_name, &$tpl_source, &$smarty){
    global $_AMEMBER_TEMPLATE;
    if (isset($_AMEMBER_TEMPLATE[$tpl_name])){
        $tpl_source = $GLOBALS['_AMEMBER_TEMPLATE'][$tpl_name];
        return true;
    } else {
        return false;
    }
}

function memory_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty)
{
    global $_AMEMBER_TEMPLATE;
    if (isset($_AMEMBER_TEMPLATE[$tpl_name])){
        $tpl_timestamp = time();
        return true;
    } else {
        return false;
    }
}
function memory_get_secure($tpl_name, &$smarty){ return true; }
function memory_get_trusted($tpl_name, &$smarty){ }


function set_date_from_smarty($prefix, &$vars){
    return $vars[$prefix] = sprintf('%04d-%02d-%02d',
        $vars[$prefix.  'Year'],
        $vars[$prefix.  'Month'],
        $vars[$prefix.  'Day']
    );
}

function mail_signup_user($member_id){
    global $db, $config;
    $u = $db->get_user($member_id);
    $pl = $db->get_user_payments($u['member_id'], 1);
    $u['name'] = $u['name_f'] . ' ' . $u['name_l'];
    ////////////
    $t = new_smarty();
    $t->assign('login', $u['login']);
    $t->assign('pass',  $u['pass']);
    $t->assign('name_f', $u['name_f']);
    $t->assign('name_l', $u['name_l']);
    $t->assign('user', $u);
    $t->assign('payment', $pl[0]);
    ///////////////////////////////////
    $et = & new aMemberEmailTemplate();
    $et->name = "send_signup_mail";
    $et->product_id = $pl[0]['product_id'] ? $pl[0]['product_id'] : null;
    mail_template_user($t, $et, $u);
}

function mail_signup_affiliate($member_id){
    global $db, $config;
    $u = $db->get_user($member_id);
    $u['name'] = $u['name_f'] . ' ' . $u['name_l'];
    ////////////
    $t = new_smarty();
    $t->assign('login', $u['login']);
    $t->assign('pass',  $u['pass']);
    $t->assign('name_f', $u['name_f']);
    $t->assign('name_l', $u['name_l']);
    $t->assign('user', $u);
    ///////////////////////////////////
    $et = & new aMemberEmailTemplate();
    $et->name = "aff.mail_signup_user";
    mail_template_user($t, $et, $u);
}



function mail_pending_user($member_id, $payment_id){
    global $db, $config;
    $t = & new_smarty();
    $p = $db->get_payment($payment_id);
    $u = $db->get_user($member_id);
    ///////////////////////////////////
    $t->assign('login', $u['login']);
    $t->assign('pass',  $u['pass']);
    $t->assign('name_f', $u['name_f']);
    $t->assign('name_l', $u['name_l']);
    ///////////////////////////////////
    $t->assign('user', $u);
    $t->assign('payment', $p);
    ///////////////////////////////////
    if (!($prices = $p['data'][0]['BASKET_PRICES'])){
        $prices = array($p['product_id'] => $p['amount']);
    }
    foreach ($prices as $product_id => $price){
        $v  = $db->get_product($product_id);
        $v['price'] = $price;
        $pr[$product_id] = $v;
    }
    $t->assign('total', $total = array_sum($prices));
    $t->assign('products', $pr);
    ///
    ///////////////////////////////////
    $et = & new aMemberEmailTemplate();
    $et->name = "send_pending_email";
    mail_template_user($t, $et, $u);
}
function mail_pending_admin($member_id, $payment_id){
    global $db, $config;
    $t = & new_smarty();
    $p = $db->get_payment($payment_id);
    $u = $db->get_user($member_id);
    ///////////////////////////////////
    $t->assign('login', $u['login']);
    $t->assign('pass',  $u['pass']);
    $t->assign('name_f', $u['name_f']);
    $t->assign('name_l', $u['name_l']);
    ///////////////////////////////////
    $t->assign('user', $u);
    $t->assign('payment', $p);
    ///////////////////////////////////
    if (!($prices = $p['data'][0]['BASKET_PRICES'])){
        $prices = array($p['product_id'] => $p['amount']);
    }
    foreach ($prices as $product_id => $price){
        $v  = $db->get_product($product_id);
        $v['price'] = $price;
        $pr[$product_id] = $v;
    }
    $t->assign('total', $total = array_sum($prices));
    $t->assign('products', $pr);
    ///
    ///////////////////////////////////
    $et = & new aMemberEmailTemplate();
    $et->name = "send_pending_admin";
    mail_template_admin($t, $et);
}


function mail_aff_sale_user($payment_id, $aff_id, $commission, $receipt_id, $product_id){
    global $db, $config;

    $p = $db->get_payment($payment_id);
    $u = $db->get_user($p['member_id']);
    $aff = $db->get_user($aff_id);
    $pr = $db->get_product($p['product_id']);
    ///////////////////////////////////
    $t = new_smarty();
    $t->assign('user', $u);
    $t->assign('payment', $p);
    $t->assign('affiliate', $aff);
    $t->assign('product', $pr);
    $t->assign('commission', $commission);
    ///////////////////////////////////
    $et = & new aMemberEmailTemplate();
    $et->name = "aff.mail_sale_user";
    mail_template_user($t, $et, $aff);
}

function mail_aff_sale_admin($payment_id, $aff_id, $commission, $receipt_id, $product_id){
    global $db, $config;

    $p = $db->get_payment($payment_id);
    $u = $db->get_user($p['member_id']);
    $aff = $db->get_user($aff_id);
    $pr = $db->get_product($p['product_id']);
    ///////////////////////////////////
    $t = new_smarty();
    $t->assign('user', $u);
    $t->assign('payment', $p);
    $t->assign('affiliate', $aff);
    $t->assign('product', $pr);
    $t->assign('commission', $commission);
    ///////////////////////////////////
    $et = & new aMemberEmailTemplate();
    $et->name = "aff.mail_sale_admin";
    mail_template_admin($t, $et);
}

function mail_approval_wait_user($member_id){
    global $db, $config;
    $t = & new_smarty();
    $u = $db->get_user($member_id);
    ///////////////////////////////////
    $t->assign('login', $u['login']);
    $t->assign('pass',  $u['pass']);
    $t->assign('name_f', $u['name_f']);
    $t->assign('name_l', $u['name_l']);
    ///////////////////////////////////
    $t->assign('user', $u);
    ///////////////////////////////////
    $et = & new aMemberEmailTemplate();
    $et->name = "manually_approve";
    mail_template_user($t, $et, $u);
}

function mail_approval_wait_admin($member_id){
    global $t, $db, $config;
    $u = $db->get_user($member_id);
    mail_admin("
    New signup completed and is awaiting your approval.
    Please login to aMember CP at
        $config[root_url]/admin/
    Then click the following link:
        $config[root_url]/admin/users.php?member_id=$member_id&action=edit

    Short details:
        Username: $u[login]
        E-Mail:    $u[email]
        Name:     $u[name_f] $u[name_l]
    --
    Your aMember Pro script
    $config[root_url]/admin
    ", "*** Your approval required");
}

function mail_payment_admin($payment_id, $member_id){
    global $db, $config;

    $t = &new_smarty();
    $p = $db->get_payment($payment_id);
    $u = $db->get_user($p['member_id']);
    $pr = $db->get_product($p['product_id']);
    ///////////////////////////////////
    $t->assign('user',    $u);
    $t->assign('payment', $p);
    $t->assign('product', $pr);
    ///////////////////////////////////
    $et = & new aMemberEmailTemplate();
    $et->name = "send_payment_admin";
    mail_template_admin($t, $et);
}

function mail_payment_user($payment_id, $member_id){
    global $db, $config, $_AMEMBER_TEMPLATE;
    $t = new_smarty();
    $p = $db->get_payment($payment_id);
    if ($p['data'][0]['ORIG_ID'] > 0) return; // don't sent for child payments

    if (!($prices = $p['data'][0]['BASKET_PRICES'])){
        $prices = array($p['product_id'] => $p['amount']);
    }
    $u = $db->get_user($p['member_id']);
    $subtotal = 0;
    foreach ($prices as $product_id => $price){
        $v  = $db->get_product($product_id);
        $subtotal += $v['price'];
        //$v['price'] = $price;
        $pr[$product_id] = $v;
    }
    if (!$t) $t = &new_smarty();
    
    $total = array_sum($prices);

    if ($total == 0) return; // don't email zero receipt
    
    if ($p['receipt_id'] == 'manual'){ // ONLY for single product !!!
        
        $coupon_discount    = $p['data']['COUPON_DISCOUNT'];
        //$tax_amount         = $p['data']['TAX_AMOUNT'];
        $tax_amount         = $p['tax_amount'];
        if ($subtotal - $coupon_discount + $tax_amount != $total){
            foreach ($prices as $product_id => $price){
                $v  = $db->get_product($product_id);
                $subtotal = $total - $tax_amount + $coupon_discount;
                $v['price'] = $subtotal;
                $pr[$product_id] = $v;
            }
        }
        
    }
    
    $t->assign('total', $total);
    $t->assign('subtotal', $subtotal);
    $t->assign('user',    $u);
    $t->assign('payment', $p);
    $t->assign('products', $pr);
    $t->assign('config', $config);

    $attachments = array();
    if ($config['send_pdf_invoice']){
        require_once("$config[root_dir]/includes/fpdf/fpdf.php");
        $attachments[] = get_pdf_invoice($payment_id, $p[member_id]);
    }

    $et = & new aMemberEmailTemplate();
    $et->name = "send_payment_mail";
    $et->lang = $u['data']['selected_lang'] ? $u['data']['selected_lang'] : get_default_lang();

    // load and find templated
    if (!$et->find_applicable()){
        trigger_error("Cannot find applicable e-mail template for [{$et->name},{$et->lang},{$et->product_id},{$et->day}]", E_USER_WARNING);
        return false;
    }
    $_AMEMBER_TEMPLATE['text'] = $et->get_smarty_template();
    $parsed_mail = $t->fetch('memory:text');
    unset($_AMEMBER_TEMPLATE['text']);
    mail_customer($u['email'], $parsed_mail,
        null, null, $attachments, false,
        $u['name_f'] . ' ' . $u['name_l']);
}

function get_pdf_invoice($payment_id, $member_id){
    global $t, $db, $config;
    $p = $db->get_payment($payment_id);
    
    if ($p['member_id'] != $member_id)
        return array();
        
    //if ($p['data'][0]['ORIG_ID'] > 0) return array(); // don't sent for child payments
    if ($p['data'][0]['ORIG_ID'] > 0)
        $p1 = $db->get_payment($p['data'][0]['ORIG_ID']);

    if($p['tax_amount']=='0.00' && $p1['tax_amount']>0){
        $p['tax_amount'] = $p1['tax_amount'];
        $db->update_payment($p['payment_id'], $p);
    }

    if (!($prices = $p['data'][0]['BASKET_PRICES'])){
        $prices = array($p['product_id'] => $p['amount']);
    }
    $u = $db->get_user($p['member_id']);
    $products   = array();
    $total      = 0;
    $subtotal   = 0;
    foreach ($prices as $product_id => $price){
        $v  = $db->get_product($product_id);
        $products[$product_id] = $v;
        $total += $price;
        $subtotal += $v['price'];
    }
    if (!$t) $t = &new_smarty();

    //if ($p['receipt_id'] == 'manual'){ // ONLY for single product !!!
    if ($p['receipt_id'] != 'manual'||1){ // ONLY for single product !!!
        
        $coupon_discount    = $p['data']['COUPON_DISCOUNT'];
        //$tax_amount         = $p['data']['TAX_AMOUNT'];
        $tax_amount         = $p['tax_amount'];
        if ($subtotal - $coupon_discount + $tax_amount != $total){
            foreach ($prices as $product_id => $price){
                $v  = $db->get_product($product_id);
                $subtotal = $total - $tax_amount + $coupon_discount;
                $v['price'] = $subtotal;
                $products[$product_id] = $v;
            }
        }
        
    }

    $t->assign('total', $total);
    $t->assign('subtotal', $subtotal);
    $t->assign('payment', $p);
    $t->assign('products', $products);
    $content = $t->fetch('mail_receipt.pdf.txt');

    $pdf_content = parse_pdf_content ($content, $p['member_id']);

    $attach = array();
    $attach['string'] = $pdf_content; // use stringEncoded in attachment
    $attach['name'] = 'Invoice.pdf';
    $attach['type'] = 'application/pdf';

    return $attach;

}

function mm_to_pt ($value){ // convert millimeters to points
    $value  = $value / 25.4; //inch
    $value  = $value * 72;   //pt
    return $value;
}

function get_font_styles ($string){ // apply an allowed text formatting tags
    $attr = '';
    if (preg_match('/<b>(.*)<\/b>/i', $string, $regs)){
        $attr .= "B";
        $string = $regs[1];
    }
    if (preg_match('/<i>(.*)<\/i>/i', $string, $regs)){
        $attr .= "I";
        $string = $regs[1];
    }
    if (preg_match('/<u>(.*)<\/u>/i', $string, $regs)){
        $attr .= "U";
        $string = $regs[1];
    }
    return $attr;
}

function parse_pdf_content ($content, $member_id){ // parse text content from Smarty to pdf content
    global $db, $config, $t;
    $pdf_content = '';

    $margins = array(mm_to_pt(20), mm_to_pt(20), mm_to_pt(20), mm_to_pt(20)); //left, top, right, bottom (in points) 56pt ~= 20mm
    $font_size = 14; //points

    $pdf = new FPDF('P', 'pt', 'A4'); // portrait, A4
    $pdf->SetCompression (false);
    $pdf->SetMargins ($margins[0], $margins[1], $margins[2]); //only left, top, right margins. bottom margin equals to 20mm by default.

    $pdf->SetTitle ('Your Invoice');
    $pdf->SetSubject ('*** Your Payment');
    $pdf->SetAuthor ('aMember');
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', $font_size);

    $current_x = $pdf->GetX();
    $current_y = $pdf->GetY();

    $width  = mm_to_pt (210);
    $height = mm_to_pt (270);

    $width  = $width - $margins[0] - $margins[2]; // target width
    $height = $height - $margins[1] - $margins[3];// target height

    $image = $config['root_dir'] . "/logo.jpg"; // logo path to include in pdf at top-right corner
    if (is_file($image)){
        $size = getimagesize($image);
        $x = $width - $size[0] + $margins[0];
        $y = $current_y;
        $pdf->Image ($image, $x, $y, $size[0], $size[1]); // use original size
        $current_y += $size[1];
    }
    $current_y += $font_size; //pt

    $contacts = explode("\n", $config['invoice_contacts']); // output contact information right-aligned
    $max_length = 0;
    foreach ($contacts as $row){
        $row = trim($row);
        $length = $pdf->GetStringWidth ($row);
        if ($length > $max_length)
            $max_length = $length;
    }
    $x = $width - $max_length + $margins[0];
    $y = $current_y;
    foreach ($contacts as $row){
        $row = trim($row);
        $attr = get_font_styles ($row);
        $pdf->SetFont('Arial', $attr, $font_size);
        $pdf->Text ($x, $y, strip_tags($row));
        $y += $font_size;
    }
    $current_y = $y;
    $pdf->SetFont('Arial', '', $font_size); //return font settings

    // customer contacts
    $u = $db->get_user($member_id);

    if (!$t) $t = &new_smarty();
    $t->assign('u', $u);
    $cust_contacts = $t->fetch('mail_receipt_contact.pdf.txt');
    $cust_contacts = explode("\n", $cust_contacts); // output contact information left-aligned
    $num_rows = count($contacts);
    $x = $margins[0];
    $y = $current_y - $font_size * $num_rows; // $num_rows rows up from contact information and output customer data
    foreach ($cust_contacts as $row){
        $row = trim($row);
        $attr = get_font_styles ($row);
        $pdf->SetFont('Arial', $attr, $font_size);
        $pdf->Text ($x, $y, strip_tags($row));
        $y += $font_size;
    }
    $current_y = $y;

    /*
    $y = $current_y - $font_size * 4; // 4 rows up from contact information and output customer data

    $string = $u['name_f'] . ' ' . $u['name_l'];
    $pdf->Text ($x, $y, $string);
    $y += $font_size;

    $string = $u['street'];
    $pdf->Text ($x, $y, $string);
    $y += $font_size;

    $string = $u['zip'] . ' ' . $u['city'];
    $pdf->Text ($x, $y, $string);
    $y += $font_size;

	$state = db_getStateByCode($u['country'], $u['state']);
	$country = db_getCountryByCode($u['country']);
    $string = $state .  ' '  . $country;
    $pdf->Text ($x, $y, $string);
    $y += $font_size;
    */

    $current_y = $y + $font_size * 2; //2 rows down
    $pdf->SetFont('Arial', '', $font_size); //return font settings

    // remove new lines
    $content = str_replace ("\n", "", $content);
    $content = str_replace ("\r", "", $content);
    $content = str_replace ("&pound;", chr(163), $content);

    // split text by <br />
    $content = explode ("<br />", $content);
    $y = $current_y;

    // count maximum columns widths
    $widths = array();
    foreach ($content as $text){
        $text = trim($text);
        if (preg_match('/\|/i', $text, $regs)){
            $column = 0;
            $items = explode ("|", $text);
            foreach ($items as $item){
                $length = $pdf->GetStringWidth (trim(strip_tags($item))) + 10;
                if ($widths[$column] < $length) $widths[$column] = $length;
                $column++;
            }
        }
    }
    $length = 0;
    for ($i=1; $i < count($widths); $i++){
        $length += $widths[$i];
    }
    // width of column 0 is *
    $widths[0] = $width - $length;

    foreach ($content as $hr_content){
        $hr_content = trim($hr_content);

        // split text by <hr>
        $hr_content = explode ("<hr>", $hr_content);
        $hr_count = count($hr_content) - 1;
        //<br /> add new line
		    if($hr_count<1 && strip_tags($hr_content[0]) == '')$y += $font_size;
        foreach ($hr_content as $text){

            $line_feeds = 1; // how much rows feed

            if (strip_tags($text) != ''){ // if there is a text
                if (!preg_match('/\|/i', $text, $regs)){ // simple text
                    $y += $font_size * $line_feeds;

                    $attr = get_font_styles ($text);
                    $text = trim(strip_tags($text));
                    $pdf->SetFont('Arial', $attr, $font_size);
                    $pdf->Text ($x, $y, $text); // simple textout. no line feeds allowed.
                    /*
                    $length = $pdf->GetStringWidth ($text);
                    while ($length > $width)
                        $line_feeds++;
                    */

                } else { //table content (splitted by "|")
                    $border = 0;
                    $fill = 0;
                    $pdf->SetFillColor (192, 192, 192); // Silver
                    if (preg_match('/<fill>(.*)<\/fill>/i', $text, $regs)){
                        $text = $regs[1];
                        $fill = 1;
                    }
                    $text = strip_tags($text);
                    $items = explode ("|", $text);
                    $column = 0;
                    $x = $margins[0];
                    foreach ($items as $item){
                        $attr = get_font_styles ($item);
                        $item = trim(strip_tags($item));

                        $pdf->SetFont('Arial', $attr, $font_size);
                        if ($column > 0) $align = 'R'; else $align = 'L';

                        $pdf->SetXY ($x, $y);
                        $pdf->MultiCell ($widths[$column], $font_size, $item, $border, $align, $fill); // multi rows output

                        for ($i = 1; $i < $line_feeds; $i++){
                            $_y = $y + ($i * $font_size);
                            $pdf->SetXY ($x, $_y);
                            $pdf->MultiCell ($widths[$column], $font_size, '', $border, $align, $fill); // empty rows
                        }

                        if ($column == 0){ // count line feeds only for 0 column

                            $length = $pdf->GetStringWidth ($item);
                            while ($length > $widths[$column]){
                                $line_feeds++;
                                $length -= $widths[$column];
                            }

                        }
                        $x += $widths[$column];
                        $column++;
                    }
                    $y += $font_size * $line_feeds;
                    $pdf->SetXY ($margins[0], $y);

                }

            } // (strip_tags($text) != '')

            if ($hr_count > 0){ // check count of <hr> (do not draw last <hr>)
                $y += 2;
                $pdf->Line ($margins[0], $y, $margins[0] + $width, $y);
                $y += 2;
                $hr_count--;
            }
            $x = $margins[0];
        } //foreach hr_content
        
    } //foreach content
    $current_y = $y;
    
    $pdf_content = $pdf->Output('', 'S'); //get pdf content

    return $pdf_content;
}

function mail_rebill_failed_member($user, $payment_id, $product, $error, $new_expire){
    global $db;
    $t = &new_smarty();
    $t->assign('user',    $user);
    $t->assign('payment', $db->get_payment($payment_id));
    $t->assign('product', $product->config);
    $t->assign('error',   $error);
    $t->assign('new_expire', $new_expire);
    ///
    $et = & new aMemberEmailTemplate();
    $et->name = "cc_rebill_failed";
    mail_template_user($t, $et, $user);
}

function mail_rebill_failed_admin($user, $payment_id, $product, $error, $new_expire){
    global $db;
    $t = &new_smarty();
    $t->assign('user',    $user);
    $t->assign('payment', $db->get_payment($payment_id));
    $t->assign('product', $product->config);
    $t->assign('error',   $error);
    $t->assign('new_expire', $new_expire);
    ///
    $et = & new aMemberEmailTemplate();
    $et->name = "cc_rebill_failed_admin";
    mail_template_admin($t, $et);
}

function mail_rebill_success_member($user, $payment_id, $product){
    global $db;
    $t = &new_smarty();
    $t->assign('user',    $user);
    $t->assign('payment', $db->get_payment($payment_id));
    $t->assign('product', $product->config);
    ///
    $et = & new aMemberEmailTemplate();
    $et->name = "cc_rebill_success";
    mail_template_user($t, $et, $user);
}

function mail_card_expires_member($user, $payment_id, $product, $expires){
    global $db;
    $t = &new_smarty();
    $t->assign('user',    $user);
    $t->assign('payment', $db->get_payment($payment_id));
    $t->assign('product', $product->config);
    $t->assign('expires', $expires);
    ///
    $et = & new aMemberEmailTemplate();
    $et->name = "card_expires";
    mail_template_user($t, $et, $user);
}

function mail_verification_email($user, $url,$source=""){
    global $db;
    $t = &new_smarty();
    $t->assign('user',    $user);
    $t->assign('url',     $url);
    ///
    $et = & new aMemberEmailTemplate();
    $et->name = "verify_email".($source=="profile" ? "_profile" : "");
    mail_template_user($t, $et, $user);
}

function determine_mime_type($filename){
    $common_mime_types = array(
         "exe" => "application/octet-stream",
         "class" => "application/octet-stream",
         "so" => "application/octet-stream",
         "dll" => "application/octet-stream",
         "oda" => "application/oda",
         "hqx" => "application/mac-binhex40",
         "cpt" => "application/mac-compactpro",
         "doc" => "application/msword",
         "bin" => "application/octet-stream",
         "dms" => "application/octet-stream",
         "lha" => "application/octet-stream",
         "lzh" => "application/octet-stream",
         "pdf" => "application/pdf",
         "ai" => "application/postscript",
         "eps" => "application/postscript",
         "ps" => "application/postscript",
         "smi" => "application/smil",
         "smil" => "application/smil",
         "bcpio" => "application/x-bcpio",
         "wbxml" => "application/vnd.wap.wbxml",
         "wmlc" => "application/vnd.wap.wmlc",
         "wmlsc" => "application/vnd.wap.wmlscriptc",
         "vcd" => "application/x-cdlink",
         "pgn" => "application/x-chess-pgn",
         "cpio" => "application/x-cpio",
         "csh" => "application/x-csh",
         "dcr" => "application/x-director",
         "dir" => "application/x-director",
         "dxr" => "application/x-director",
         "dvi" => "application/x-dvi",
         "spl" => "application/x-futuresplash",
         "gtar" => "application/x-gtar",
         "hdf" => "application/x-hdf",
         "skp" => "application/x-koan",
         "skd" => "application/x-koan",
         "js" => "application/x-javascript",
         "skt" => "application/x-koan",
         "skm" => "application/x-koan",
         "latex" => "application/x-latex",
         "nc" => "application/x-netcdf",
         "cdf" => "application/x-netcdf",
         "sh" => "application/x-sh",
         "shar" => "application/x-shar",
         "swf" => "application/x-shockwave-flash",
         "sit" => "application/x-stuffit",
         "sv4cpio" => "application/x-sv4cpio",
         "sv4crc" => "application/x-sv4crc",
         "tar" => "application/x-tar",
         "tcl" => "application/x-tcl",
         "tex" => "application/x-tex",
         "texinfo" => "application/x-texinfo",
         "texi" => "application/x-texinfo",
         "t" => "application/x-troff",
         "tr" => "application/x-troff",
         "roff" => "application/x-troff",
         "man" => "application/x-troff-man",
         "me" => "application/x-troff-me",
         "ms" => "application/x-troff-ms",
         "ustar" => "application/x-ustar",
         "src" => "application/x-wais-source",
         "xhtml" => "application/xhtml+xml",
         "xht" => "application/xhtml+xml",
         "zip" => "application/zip",
         "au" => "audio/basic",
         "snd" => "audio/basic",
         "mid" => "audio/midi",
         "midi" => "audio/midi",
         "kar" => "audio/midi",
         "mpga" => "audio/mpeg",
         "mp2" => "audio/mpeg",
         "mp3" => "audio/mpeg",
         "aif" => "audio/x-aiff",
         "aiff" => "audio/x-aiff",
         "aifc" => "audio/x-aiff",
         "m3u" => "audio/x-mpegurl",
         "ram" => "audio/x-pn-realaudio",
         "rm" => "audio/x-pn-realaudio",
         "rpm" => "audio/x-pn-realaudio-plugin",
         "ra" => "audio/x-realaudio",
         "wav" => "audio/x-wav",
         "pdb" => "chemical/x-pdb",
         "xyz" => "chemical/x-xyz",
         "bmp" => "image/bmp",
         "gif" => "image/gif",
         "ief" => "image/ief",
         "jpeg" => "image/jpeg",
         "jpg" => "image/jpeg",
         "jpe" => "image/jpeg",
         "png" => "image/png",
         "tiff" => "image/tiff",
         "tif" => "image/tif",
         "djvu" => "image/vnd.djvu",
         "djv" => "image/vnd.djvu",
         "wbmp" => "image/vnd.wap.wbmp",
         "ras" => "image/x-cmu-raster",
         "pnm" => "image/x-portable-anymap",
         "pbm" => "image/x-portable-bitmap",
         "pgm" => "image/x-portable-graymap",
         "ppm" => "image/x-portable-pixmap",
         "rgb" => "image/x-rgb",
         "xbm" => "image/x-xbitmap",
         "xpm" => "image/x-xpixmap",
         "xwd" => "image/x-windowdump",
         "igs" => "model/iges",
         "iges" => "model/iges",
         "msh" => "model/mesh",
         "mesh" => "model/mesh",
         "silo" => "model/mesh",
         "wrl" => "model/vrml",
         "vrml" => "model/vrml",
         "mpeg" => "video/mpeg",
         "mpg" => "video/mpeg",
         "mpe" => "video/mpeg",
         "qt" => "video/quicktime",
         "mov" => "video/quicktime",
         "mxu" => "video/vnd.mpegurl",
         "avi" => "video/x-msvideo",
         "movie" => "video/x-sgi-movie",
         "css" => "text/css",
         "asc" => "text/plain",
         "txt" => "text/plain",
         "rtx" => "text/richtext",
         "rtf" => "text/rtf",
         "sgml" => "text/sgml",
         "sgm" => "text/sgml",
         "tsv" => "text/tab-seperated-values",
         "wml" => "text/vnd.wap.wml",
         "wmls" => "text/vnd.wap.wmlscript",
         "etx" => "text/x-setext",
         "xml" => "text/xml",
         "xsl" => "text/xml",

         "htm" => "text/html",
         "html" => "text/html",
         "shtml" => "text/html"
    );

    if (!preg_match('/\.([\w\d]+)$/', $filename, $regs))
        return 'application/octet-stream';
    $ext = $regs[1];
    if ($mime = $common_mime_types[$ext])
        return $mime;
    else
        return 'application/octet-stream';
}

function sql_to_timestamp($sqldate){
    list($y,$m,$d) = split('-', $sqldate);
    return mktime(0,0,0,$m,$d,$y);
}

function add_unsubscribe_link($email_only, $text, $is_html, $is_guest='0', $is_newsletter='0'){
    global $config, $t;
    
    if ($is_newsletter && !$is_guest){
        
        $link = "$config[root_url]/member.php";
        
    } else {
        
        $e = urlencode($email_only);
        if ($is_guest == '1') {
                $sign = substr(md5($email_only.'-GUEST'), 0, 4);
                $link = "$config[root_url]/unsubscribe_guest.php?e=$e&s=$sign";
        } else {
                $sign = substr(md5($email_only.'-AMEMBER'), 0, 4);
                $link = "$config[root_url]/unsubscribe.php?e=$e&s=$sign";
        }
        
    }
    if (!$t) $t = &new_smarty();
    $t->assign('is_html', $is_html);
    $t->assign('link', $link);
    $add = $t->fetch("unsubscribe_link.inc.html");
        
    if ($is_html){
        if (preg_match('|</body>|', $text))
            $text = str_replace('</body>', "$add</body>", $text);
        else
            $text .= "$add";
    } else {
        $text .= "\r\n$add";
    }
    return $text;
}

function mail_template_user($t, $et, $u, $add_unsubscribe=false){
    global $db, $config, $_AMEMBER_TEMPLATE;
    $t->assign('config', $config);
    $et->lang = $u['data']['selected_lang'] ? $u['data']['selected_lang'] : get_default_lang();
    // load and find templated
    if (!$et->find_applicable()){
        trigger_error("Cannot find applicable e-mail template for [{$et->name},{$et->lang},{$et->product_id},{$et->day}]", E_USER_WARNING);
        return false;
    }
    $_AMEMBER_TEMPLATE['text'] = $et->get_smarty_template();
    $parsed_mail = $t->fetch('memory:text');
    unset($_AMEMBER_TEMPLATE['text']);
    mail_customer($u['email'], $parsed_mail,
        null, null, null, $add_unsubscribe,
        $u['name_f'] . ' ' . $u['name_l']);
}

function mail_template_admin($t, $et){
    global $db, $config, $_AMEMBER_TEMPLATE;
    $t->assign('config', $config);
    $et->lang = get_default_lang();
    // load and find templated
    if (!$et->find_applicable()){
        trigger_error("Cannot find applicable e-mail template for [{$et->name},{$et->lang},{$et->product_id},{$et->day}]", E_USER_WARNING);
        return false;
    }
    $_AMEMBER_TEMPLATE['text'] = $et->get_smarty_template();
    $parsed_mail = $t->fetch('memory:text');
    unset($_AMEMBER_TEMPLATE['text']);
    mail_admin($parsed_mail, "");
}

function mail_customer($email, $text, $subject='',
        $is_html=0, $attachments='', $add_unsubscribe=0,
        $name="", $is_guest='0', $is_newsletter='0',$bcc = ""){
    global $config, $db;
    require_once($config['root_dir']."/includes/phpmailer/class.phpmailer.php");

    if (!strlen($email))
        return;
    if (preg_match('/^Subject: (.+?)[\n\r]+/im', $text, $args)){
        // found subject in body of message! then save it and remove from
        // message
        $subject = $args[1];
        $text = preg_replace('/(^Subject: .+?[\n\r]+)/im', '', $text);
    }
    $charset = "iso-8859-1";
    if (preg_match('/^Charset: (.+?)[\n\r]+/im', $text, $args)){
        // found subject in body of message! then save it and remove from
        // message
        $charset = $args[1];
        $text = preg_replace('/(^Charset: .+?[\n\r]+)/im', '', $text);
    }
    if (preg_match('/^Format: (\w+?)\s*$/im', $text, $args)){
        $format = $args[1];
        if (!strcasecmp('MULTIPART', $format)){
            $is_html = 2;
            $text = preg_replace('/^Format: (\w+?)\s*$/im', '', $text);
        } elseif (!strcasecmp('HTML', $format)){
            $is_html = 1;
            $text = preg_replace('/^Format: (\w+?)\s*$/im', '', $text);
        } elseif (!strcasecmp('TEXT', $format)){
            $is_html = 0;
            $text = preg_replace('/^Format: (\w+?)\s*$/im', '', $text);
        }
    }
    if (preg_match_all('/^Attachment: (.+?)\s*$/im', $text, $args)){
        foreach ($args[1] as $fname){
            $fname = str_replace('..', '', $fname);
//            if ($fname[0] != '/')
            $fname = $config['root_dir'] . '/templates/' . $fname;
            if (!file_exists($fname))
                $db->log_error("Email attachment file : '$fname' is not exists - check your e-mail templates");
            elseif (!is_readable($fname))
                $db->log_error("Email attachment file : '$fname' is not readable for the script - check your e-mail templates and/or chmod file");
            else
                $attachments[] = array(
                   'name'     => basename($fname),
                   'type'     => determine_mime_type(basename($fname)),
                   'tmp_name' => $fname
                );
        }
        $text = preg_replace('/^Attachment: (.+?)\s*$/im', '', $text);
    }

    if (preg_match('/"*(.*?)"*\s*\<(.+?)\>\s*$/', $email, $regs)){
        $email_only = $regs[2];
        if ($name == '') $name = $regs[1];
    } else
        $email_only = $email;

    $mail = & new PHPMailer();
    $mail->CharSet = $charset;
    $mail->From     = $config['admin_email_from'] ? $config['admin_email_from'] : $config['admin_email'];
    $mail->FromName = $config['admin_email_name'] ? $config['admin_email_name'] : $config['site_title'];
    if ($config['email_method'] == 'smtp'){
        if (preg_match('/^(.+?):(.+?)@(.+)$/', $config['smtp_host'], $regs)){
            $mail->Username = $regs[1];
            $mail->Password = $regs[2];
            $mail->SMTPAuth = true;
            $mail->Host = $regs[3];
        } else {
            $mail->Host = $config['smtp_host'];
	    if($config['smtp_user'] && $config['smtp_pass']){
		$mail->SMTPAuth = true;
		$mail->Username = $config['smtp_user'];
		$mail->Password = $config['smtp_pass'];
	    }
        }
	if($config['smtp_port']) $mail->Port = $config['smtp_port'];
	$mail->SMTPSecure = $config['smtp_security'];
        $mail->Mailer   = "smtp";
    }

    if ($config['email_method'] == 'sendmail'){
        $mail->Mailer   = "sendmail";
        $mail->Sendmail = $config['sendmail_path'];
    }
    if ($attachments){
        foreach ((array)$attachments as $a){
            $file_type     = $a['type'];
            if (!strlen($file_type))
                $file_type="application/octet-stream";
            if ($file_type == 'application/x-msdownload')
                $file_type = "application/octet-stream";

            if ($a['tmp_name'] != '' && is_file($a['tmp_name']))
                $mail->AddAttachment($a['tmp_name'], $a['name'], "base64", $file_type);
            else
                $mail->AddStringAttachment($a['string'], $a['name'], "base64", $file_type);
        }
    }
    if ($is_html){
        $mail->isHTML(true);
    }
    if ($is_html == 2){
        list($text, $plain_text) = preg_split('/^!{10}!+\s*$/m', $text);
        if ($add_unsubscribe)
            $plain_text = add_unsubscribe_link($email_only,
                $plain_text, 0, $is_guest, $is_newsletter);
        $mail->AltBody = $plain_text;
    }
    if (!$config['disable_unsubscribe_link'] && $add_unsubscribe)
        $text = add_unsubscribe_link($email_only,
            $text, $is_html, $is_guest, $is_newsletter);
    $mail->Body = $text;

    $mail->Subject = $subject;
    $mail->AddAddress($email_only, $name);
    if($bcc){
        foreach($bcc as $e){
            if($e) $mail->AddBCC($e);
        }
    }
    if (!$mail->Send()){
        $db->log_error("There was an error sending the email message\n<br />" . $mail->ErrorInfo);
        $GLOBALS['AMEMBER_DONT_LOG_NEXT_ERROR'] = true;
        trigger_error("There was an error sending the email message", E_USER_WARNING);
    }

}

function mail_admin($text, $subject=''){
    global $config;
    $email = $config['admin_email'];
    if($config['copy_admin_email']){
        $bcc = preg_split("/[,;]/",$config['copy_admin_email']);
    }else{
        $bcc = "";
    }
    mail_customer($email, $text, $subject, 0, '', 0, $config['site_title'] . " Admin",0,0,$bcc);
}

// output html code and possible header, suitable for redirect
function html_redirect($url, $print_header=0, $title='', $text=''){
    global $t;
    if (!$t) $t = &new_smarty();
    $t->assign('title', $title);
    $t->assign('text', $text);
    $t->assign('url', $url);
    $t->display('redirect.html');
}

// output html code and possible header, suitable for redirect
function admin_html_redirect($url, $title='Redirect', $text='', $target_top = false){
    global $t;
    if (!$t) $t = &new_smarty();
    $t->assign('title', $title);
    $t->assign('text', $text);
    $t->assign('url', $url);
    $t->assign('target_top', $target_top);
    $t->display('admin/redirect.html');
}

function check_demo($msg="Sorry, this function disabled in the demo"){
    if ($GLOBALS['config']['demo'])
        die($msg);
}

function compare_with_pattern($pattern, $value){
    $value = strtolower(trim($value));
    $pattern = strtolower(trim($pattern));
    if (!strlen($pattern))
        return 0;
    $pattern = preg_quote($pattern);
    $pattern = str_replace('\*', '.*?', $pattern);
    $pattern = str_replace('/', '\/', $pattern);
    return preg_match("/^$pattern\$/i", $value);
}

function add_password_to_url($url, $username_password=''){
    global $db, $config;
    if (!strlen($url))
        return "";
    elseif (preg_match('~^(http://|https://)(.+?)(/.*|)$~', $url, $regs)){
        if (preg_match('/\@(.+?)$/', $regs[2], $regs_x))
            $regs[2] = $regs_x[1];
        return $regs[1] . ($username_password?"$username_password@":'') . $regs[2] . $regs[3];
    } elseif ($url[0] == '/') {
        $u = parse_url($config['root_url']);
        $s = "$u[scheme]://".($username_password?"$username_password@":'')."$u[host]$url";
        return $s;
    } else { /// url is relative to aMember folder
        $u = parse_url($config['root_url']);
        if (!preg_match('/\/$/', $u['path']))
            $u['path'] .= "/";
        $s = "$u[scheme]://".($username_password?"$username_password@":'')."$u[host]$u[path]$url";
        return $s;
    }
}

function amember_setcookie($k, $v){
    $tm = 0;
    $d = $_SERVER['HTTP_HOST'];
    if (preg_match('/([^\.]+)\.(org|com|net|biz|info|ru|co.uk|co.za)$/', $d, $regs))
        setcookie($k,$v,$tm,"/",".{$regs[1]}.{$regs[2]}");
    else
        setcookie($k,$v,$tm,"/");
}
function amember_delcookie($k){
    $tm = time()-24*3600;
    $d = $_SERVER['HTTP_HOST'];
    if (preg_match('/([^\.]+)\.(org|com|net|biz|info|ru|co.uk|co.za)$/', $d, $regs)) {
        setcookie($k,"",$tm,"/",".{$regs[1]}.{$regs[2]}");
        setcookie($k,"",$tm,"/", $d);
        setcookie($k,"",$tm,"/");
    } else
        setcookie($k,"",$tm,"/");
}

function generate_login($vars='') {
    global $db, $config;
    $vars = (array)$vars;

    // try to use first part of email
    if (preg_match("/^([a-zA-Z0-9_]+)\@/", $vars['email'], $regs)){
        $login = $regs[1];
        if (strlen($login) > $config['login_max_length'])
            $login = substr($login, 0, $config['login_max_length']);
        if ((strlen($login)>=$config['login_min_length']) && $db->check_uniq_login($login)){
            return $login;
        }
    }

    // from first and last name
    $fn = strtolower(preg_replace('/[^\w\d_]/', '', $vars['name_f']));
    $ln = strtolower(preg_replace('/[^\w\d_]/', '', $vars['name_l']));
    if ($fn && $ln)
        $login = $fn.'_'.$ln;
    else
        $login = $fn . $ln;
    if (strlen($login) > $config['login_max_length'])
        $login = substr($login, 0, $config['login_max_length']);
    if ((strlen($login)>=$config['login_min_length']) && $db->check_uniq_login($login)){
        return $login;
    }

    // try to add numbers while free login not found
    if (strlen($login) > $config['login_max_length'])
        $login = substr($login, 0, $config['login_max_length']);
    for ($i=1;$i<999;$i++){
        if (strlen($login) > $config['login_max_length'])
            $login = substr($login, 0, $config['login_max_length']);
        $nlogin = $login . $i;
        if ((strlen($login)>$config['login_min_length']) && $db->check_uniq_login($nlogin)){
            return $nlogin;
        }
    }

    // will generate it
    // a bit of configuration
    global $config;
    $min_length=$config['login_min_length'] < 4 ? 4 : $config['login_min_length'];
    $max_length=$config['login_max_length'] > 10 ? 10 : $config['login_max_length'];
    $all_g = "aeiyo";
    $all_s = "bcdfghjkmnpqrstwxz";
    /// let's go
    do {
        $pass = "";
        srand((double)microtime()*1000000);
        $length = rand($min_length, $max_length);
        for($i=0;$i<$length;$i++) {
            srand((double)microtime()*1000000);
            if ($i % 2)
                $pass .= $all_g[ rand(0, strlen($all_g) - 1) ];
            else
                $pass .= $all_s[ rand(0, strlen($all_s) - 1) ];
        }
    } while (!$db->check_uniq_login($pass));
    return $pass;
}

function generate_password($vars=''){
    // a bit of configuration
    global $config;
    $vars = (array)$vars;
    $min_length=$config['pass_min_length'] < 4 ? 4 : $config['pass_min_length'];
    $max_length=$config['pass_max_length'] > 10 ? 10 : $config['pass_max_length'];
    $all_g = "aeiyo";
    $all_gn = $all_g . "1234567890";
    $all_s = "bcdfghjkmnpqrstwxz";
    /// let's go
    $pass = "";
    srand((double)microtime()*1000000);
    $length = rand($min_length, $max_length);
    for($i=0;$i<$length;$i++) {
        srand((double)microtime()*1000000);
        if ($i % 2)
            if ($i < $min_length)
                $pass .= $all_g[ rand(0, strlen($all_g) - 1) ];
            else
                $pass .= $all_gn[ rand(0, strlen($all_gn) - 1) ];
        else
            $pass .= $all_s[ rand(0, strlen($all_s) - 1) ];
    }
    return $pass;
}

/** Function returns list of languages **/
function languages_get_options($for_select=false){
    $langs = $GLOBALS['_LANG'];
    ksort($langs);
    foreach ($langs as $k=>$l){
        $kk = $for_select ? $k : ($k . ':' . $l['title']);
        $ret[$kk] = $l['title'];
    }
    return $ret;
}

function print_rr($vars, $title="==DEBUG=="){
    print "\n<table><tr><td><pre><b>$title</b>\n";
    print_r($vars);
    print "</pre></td></tr></table><br/>\n";
}
function print_rre($vars, $title="==DEBUG=="){
    print_rr($vars, $title);
    die("\n==<i>exit() called from print_rre</i>==\n");
}

function print_bt($title="==BACKTRACE=="){ /** print backtrace **/
    print_rr(debug_backtrace(), $title);
}
function get_first($arg1, $arg2){
    $args = func_get_args();
    foreach ($args as $a)
        if ($a != '') return $a;
}
function get_first_set($arg1, $arg2){
    $args = func_get_args();
    foreach ($args as $a)
        if (isset($a)) return $a;
}

function get_default_lang(){
    global $config;
    if ($config['lang']['default']) {
        list($lang,) = split(':', $config['lang']['default']);
    } else {
        $lang = "en";
    }
    return $lang;
}
/**
 * Function must return a needed language to use
 * based on auth'ed user, session, cookie or get/port vars,
 * or config settings
 * @return string like "en" or "ru_KOI8"
 */
function guess_language(){
    global $_LANG_SELECTED;
    
    if ($_GET['lang'] != '') {
        $_SESSION['amember_lang'] = $_GET['lang'];
        $lang = $_GET['lang'];
        // user is logged-in and changed language
        global $db;
        if ($db && ($_SESSION['_amember_user']) && ($_SESSION['_amember_user']['data']['selected_lang'] != $lang)){
            #print "Changing language to [$lang]<br />\n";
            $u = $db->get_user($_SESSION['_amember_user']['member_id']);
            $u['data']['selected_lang'] = $lang;
            $db->update_user($u['member_id'], $u);
            $_SESSION['_amember_user']['data']['selected_lang'] = $lang;
        }
    } elseif ($_SESSION['amember_lang'] != ''){
        $lang = $_SESSION['amember_lang'];
    } elseif ($_SESSION['_amember_user']['data']['selected_lang'] != ''){
        $lang = $_SESSION['_amember_user']['data']['selected_lang'];
    } else {
        $lang = get_default_lang();
    }
    return $_LANG_SELECTED = $lang;
}

function load_language_defs(){
    global $config;
    $d = opendir($dir="$config[root_dir]/language");
    if (!$d) {
        trigger_error("Cannot open $config[root_dir]/languages/ folder to read language definitions", E_USER_WARNING);
        return false;
    }
    $ret =  array();
    while ($f = readdir($d)){
        if (preg_match('/^(.+?)\-def.php$/i', $f, $regs)){
            include_once($dir."/".$f);
        }
    }
    closedir($d);
}

function load_language($folder){
    global $config;
    static $loaded;
    if (isset($loaded[$folder])) return; // don't run twice

    $lang = guess_language();
    $lang = preg_replace('/[^a-zA-Z0-9_-]/', '', $lang);

    if (!file_exists($config['root_dir'] . $folder."/$lang.php")){
        trigger_error("Could not load language file [$folder] for language [$lang], using [en] instead", E_USER_WARNING);
        $lang = "en";
    }
    // now check enlighs language file as last chance
    if (!file_exists($config['root_dir'] . $folder."/$lang.php")){
        trigger_error("Could not load default (English) language file [$folder] for language [$lang]", E_USER_WARNING);
        return;
    }
    ////
    if (file_exists($config['root_dir'] . $folder."/$lang-custom.php")){
        include_once($config['root_dir'] . $folder . "/$lang-custom.php");
    }
    require_once($config['root_dir'] . $folder . "/$lang.php");
    if ($lang != 'en') {
        $e = error_reporting(0);
        @require_once($config['root_dir'] . $folder . "/en.php");
        error_reporting($e);
    }
    if (!headers_sent() || ($GLOBALS['_LANG'][$lang]['encoding'] != '')){
	try {
            header("Content-type: text/html; charset=".$GLOBALS['_LANG'][$lang]['encoding']);
	} catch (Exception $e) {
	}
    }
    if ($GLOBALS['_LANG'][$lang]['locale'] != ''){
        setlocale(LC_TIME, $GLOBALS['_LANG'][$lang]['locale']);
    }

    $loaded[$folder] = 1;
}

srand((double) microtime() * 1000000);

/**
* Returns array of period presentation or null if error
*    array(int, 'd'|'m'|'y') if ok
*    array(string-date, 'fixed') if yyyy-mm-dd date passed
*    array(string,'error') if error
* 'w' unit is depricated and automatically replaced to days
**/
function parse_period($days){
    $days = trim(strtolower($days));
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $days))
        return array($days, 'fixed');
    if (preg_match('/^(\d+)(d|w|m|y)$/', $days, $regs)) {
        $count = $regs[1];
        $period = $regs[2];
        if ($period == 'w'){
            return array($count*7, 'd');
        }
        return array($count, $period);
    } elseif (preg_match('/^\d+$/', $days))
        return array($days, 'd');
    else
        return array(null, 'error');
}

function display_lang_choice(){
    global $config, $in_fatal_error;
    if ($in_fatal_error) return "";
    if (function_exists('admin_auth') && function_exists('admin_login_form')) return "";
    $in_fatal_error++;
    $url = htmlspecialchars($_SERVER['PHP_SELF']);
    $ret = "<form method='get' action='$url'>";
    $ret .= _COMMON_LANGUAGE . ": <select name='lang' size=\"1\" onchange='this.form.submit()'>\n";
    $selected = guess_language();
    foreach ($config['lang']['list'] as $s){
        list($l,$t) = split(':', $s);
        $sel = $selected == $l ? 'selected="selected" ' : '';
        $ret .= "<option value='$l' $sel>$t</option>\n";
    }
    $ret .= "</select>\n";
    foreach ($_GET as $k=>$v){
        if ($k == 'lang' || is_array($k) || is_array($v)) continue;
        $ret .= "<input type=\"hidden\" name='".htmlspecialchars($k)."' value='".htmlspecialchars($v)."' />\n";
    }
    $ret .= "</form>\n";
    return $ret;
}

function set_session_cookie_domain(){
    if (ini_get('session.cookie_domain') != '') return; // already configured
    $domain = $_SERVER['HTTP_HOST'];

    if ($domain == 'localhost') return $domain;
    $tlds = preg_split('/\s+/', ".com .net .org .co.uk .org.uk .ltd.uk .plc.uk .edu .mil .br.com .cn.com
    .eu.com .hu.com .no.com .qc.com .sa.com .se.com .se.net .us.com .uy.com
    .za.com .ac .co.ac .gv.ac .or.ac .ac.ac .af .am .as .at .ac.at .co.at
    .gv.at .or.at .asn.au .com.au .edu.au .org.au .net.au .be .ac.be .biz .br
    .adm.br .adv.br .am.br .arq.br .art.br .bio.br .cng.br .cnt.br .com.br
    .ecn.br .eng.br .esp.br .etc.br .eti.br .fm.br .fot.br .fst.br .g12.br
    .gov.br .ind.br .inf.br .jor.br .lel.br .med.br .mil.br .net.br .nom.br
    .ntr.br .odo.br .org.br .ppg.br .pro.br .psc.br .psi.br .rec.br .slg.br
    .tmp.br .tur.br .tv.br .vet.br .zlg.br .ca .ab.ca .bc.ca .mb.ca .nb.ca
    .nf.ca .ns.ca .nt.ca .on.ca .pe.ca .qc.ca .sk.ca .yk.ca .cc .ac.cn .com.cn
    .edu.cn .gov.cn .net.cn .org.cn .bj.cn .sh.cn .tj.cn .cq.cn .he.cn .nm.cn
    .ln.cn .jl.cn .hl.cn .js.cn .zj.cn .ah.cn .hb.cn .hn.cn .gd.cn .gx.cn
    .hi.cn .sc.cn .gz.cn .yn.cn .xz.cn .sn.cn .gs.cn .qh.cn .nx.cn .xj.cn
    .tw.cn .hk.cn .mo.cn .cx .cz .de .dk .fo .com.ec .org.ec .net.ec .mil.ec
    .fin.ec .med.ec .gov.ec .fr .tm.fr .com.fr .asso.fr .presse.fr .gf .gs
    .co.il .org.il .net.il .ac.il .k12.il .gov.il .muni.il .ac.in .co.in
    .ernet.in .gov.in .net.in .res.in .info .is .it .ac.jp .co.jp .go.jp
    .or.jp .ne.jp .ac.kr .co.kr .go.kr .ne.kr .nm.kr .or.kr .re.kr .li .lt .lu
    .asso.mc .tm.mc .com.mm .org.mm .net.mm .edu.mm .gov.mm .ms .mx .com.mx
    .org.mx .net.mx .edu.mx .gov.mx .name .nl .no .nu .pl .com.pl .net.pl
    .org.pl .pt .com.ro .org.ro .store.ro .tm.ro .firm.ro .www.ro .arts.ro
    .rec.ro .info.ro .nom.ro .nt.ro .ru .com.ru .net.ru .org.ru .se .si
    .com.sg .org.sg .net.sg .gov.sg .sk .st .tc .tf .ac.th .co.th .go.th
    .mi.th .net.th .or.th .tj .tm .to .bbs.tr .com.tr .edu.tr .gov.tr .k12.tr
    .mil.tr .net.tr .org.tr .com.tw .org.tw .net.tw .ac.uk .uk.co .uk.com
    .uk.net .gb.com .gb.net .vg .ac.za .alt.za .co.za .edu.za .gov.za .mil.za
    .net.za .ngo.za .nom.za .org.za .school.za .tm.za .web.za .sh .kz .ch
    .info .ua .biz .ws .nz .com.nz .co.nz .org.nz .com.pk .int");
    $min = '';
    foreach ($tlds as $d){
        $dd = preg_quote($d);
        if (preg_match("/([^\.]+?$dd)\$/",$domain, $regs)){
            if (strlen($regs[1]) > strlen($min)){
                $min = $regs[1];
            }
        }
    }
    if (!strlen($min)) return;
    @ini_set('session.cookie_domain', ".$min");
}

// workaround for Windows root_dir detection
if (($config['root_dir'] == dirname(__FILE__)) && (substr(php_uname(), 0, 7) == "Windows") ){
    $config['root_dir'] = preg_replace('|^[A-Za-z]:|', '', $config['root_dir']);
    $config['root_dir'] = str_replace("\\", '/', $config['root_dir']);
}

// checking URL accessibility
function is_url_accessible ($url=''){
    
    if (!preg_match("/^(http:\/\/|https:\/\/|www\.)([0-9a-z\.\-\_\/\@\?\&\:]+)$/i", $url))
        return false;
    
    $fp = @fopen ($url, "r");
    if (!$fp)
        return false;

    $url_arr = parse_url($url);
    $host = $url_arr['host'];
    $path = ($url_arr['path']) ? $url_arr['path'] : '/';
    $path .= $url_arr['query'] ? '?'.$url_arr['query'] : '';
    $fp = @fsockopen($host, 80, $errnum, $errstr, 30);

    if (!$fp) {
        $error = "$errstr ($errno)<br />\n";
        return false;
    } else {
        $out = "GET $path HTTP/1.1\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Connection: Close\r\n\r\n";
    
        fwrite($fp, $out);
        $buffer = '';
        while (!feof($fp)) {
            $buffer .= fgets($fp, 128);
        }
        fclose($fp);
        if (!preg_match("/200 OK/i", $buffer))
            return false;
    }
    
    $buffer = get_url ($url);
    if (preg_match("/404 Not Found/i", $buffer))
        return false;

    return true;
    
}

function start_amember_session(){
    if (($_SERVER['REMOTE_ADDR'] == '') && ($_SERVER['REQUEST_METHOD'] == ''))
        @session_start(); // run from command-line
    else
        session_start();    
}

/**
 * Function returns full config or given config variable value
 * $item can be empty - then functions returns full config
 * or it can be set to some value, lets say "use_address_info",
 * or "payment.paypal_r.email"
 * @param null|string $item
 * @return array|string|mixed
 */
function amConfig($item=null){
    if (is_null($item)) return $GLOBALS['config'];
    $c = & $GLOBALS['config'];
    foreach (preg_split('/\./', $item) as $s) {
        $c = & $c[$s];
        if (is_null($c)) return $c;
    }
    return $c;
}

/**
 * Function displays nice-looking error message without
 * using of fatal_error function and template
 * @param string Message to display
 * @param boolean Return string or display message?
 */
function amDie($string, $return=false){
$out= <<<CUT
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
  <head>
    <title>Fatal Error</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
	body {
		font: 80% verdana, arial, helvetica, sans-serif;
		text-align: center; /* for IE */
	}
	#container {
		margin: 0 auto;   /* align for good browsers */
		text-align: left; /* counter the body center */
		border: 2px solid #f00;
        background-color: #fdd;
        padding: 10px 10px 10px 10px;
		width: 80%;
	}
    .header {
        font-size: 12pt;
        font-weight: bold;

    }
    </style>
<body>
<p style="height: 50px;"></p>
<div id="container">
<div class="header">Script Error</div>
$string
</div>

</body></html>
CUT;
    return $return ? $out : die($out);
}

function getLoginRegex(){
    global $config;
    return $config['login_disallow_spaces'] ? 
        '/^[0-9a-zA-Z_]+$/D' :
        '/^[0-9a-zA-Z_][0-9a-zA-Z_ ]+[0-9a-zA-Z_]$/D';
}
