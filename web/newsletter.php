<?php 

include('./config.inc.php');
$t = & new_smarty();
$_product_id = array('ONLY_LOGIN');
    
////////////////////////////////////////////////////////////////////////
function show_archive() {
    global $db, $t, $vars, $config;
    global $all_count, $count, $start;
    
    if ($_SESSION['_amember_id'])
        $member_id = $_SESSION['_amember_id'];  // is member
    else
        $member_id = -1;                        // is guest
    
    if ($config['archive_for_browsing'] != '1' && !$_SESSION['_amember_id']){

        $redirect = $config['root_url'] . "/newsletter.php";
        html_redirect("$redirect", 0, 'Redirect', _TPL_REDIRECT_CLICKHERE);
        exit;
    }
    
    if ($vars['archive_id']) {
        
        $a = & $db->get_newsletter($vars['archive_id'], $member_id);
        $t->assign('a', $a);
        $t->display('newsletter_archive_more.html');
        
    } else {
        
        //$db->delete_old_newsletters();
        $all_count = $db->get_archive_list_c($vars['thread_id'], $member_id);
        $count = 20;
        $al = & $db->get_archive_list($start, $count, $vars['thread_id'], $member_id);
        $t->assign('al', $al);
        $t->display('newsletter_archive.html');
        
     }
    
}

function show_guest_form($vars='') {
    global $config, $_product_id, $t, $db;
    settype($vars, 'array');
    
    $threads_count = $db->get_threads_list_c();
    $threads_list = $db->get_threads_list(0, $threads_count);
    
    if (count($vars['tr']) > 0) {
        
        $threads = array_flip($vars['tr']);
        while (list($thread_id, ) = each ($threads)) $threads[$thread_id] = '1';
        
    } else {
        
        $threads = array();
        
    }

    $guest_threads_list = array();
    foreach ($threads_list as $thread_row) {
        if ($db->is_thread_available_to_guests ($thread_row['thread_id']))
        $guest_threads_list[] = $thread_row;
    }
    
    if (!$guest_threads_list){
        fatal_error(_NEWSLETTER_NO_GUEST_THREADS, false);
        exit;
    }
    
    $t->assign('vars', $vars);
    $t->assign('threads', $threads);
    $t->assign('threads_list', $guest_threads_list);
    $t->display ("newsletter_guests.html");
}

function add_guest() {
     global $db, $config, $t;
     settype($vars, 'array');

    $errors = array();
    $vars = get_input_vars();

    //check member

    if (!$vars['e'] && $vars['s']){
        $member_code = split (":", $vars['s']);
        $member_code = intval($member_code[0]);
        $q = $db->query($s = "
            SELECT guest_email
            FROM {$db->config[prefix]}newsletter_guest
            WHERE guest_id='".$member_code."'
        ");
        $row = mysql_fetch_assoc($q);
        if ($row['guest_email'])
            $vars['e'] = $row['guest_email'];
    }

    $is_member = ($db->users_find_by_string($vars['e'], 'email', 1)) ? true : false;;
    if ($vars['e'] && $is_member){
        
        $t->display('add_guest_failed_email.html');
        exit;
        
    } else {

        $security_code = '';
        $securitycode_expire = '';

        if (!$config['dont_confirm_guests'] && $vars['s'] == '') {

            //generate a security code
            $acceptedChars = 'azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN0123456789';
            $max = strlen($acceptedChars) - 1;
            $security_code = "";
            for($i=0; $i < 16; $i++) $security_code .= $acceptedChars{mt_rand(0, $max)};
            $security_code = $security_code . time();
            $security_code = md5($security_code);
            $security_code = substr($security_code, 0, 16);
            
            $hours = 48;
            $securitycode_expire = date("Y-m-d H:i:s", time() + $hours * 60 * 60);
            
        }
        
        if (!$config['dont_confirm_guests'] && $vars['s'] != '') {
            
            //check security_code
            $security_code = $vars['s'];
            $member_code = split (":", $security_code);
            $security_code = $member_code[1];
            $member_code = intval($member_code[0]);

            $unix_timestamp = time();
            
            $q = $db->query($s = "
                SELECT guest_id, security_code, UNIX_TIMESTAMP(securitycode_expire)
                FROM {$db->config[prefix]}newsletter_guest
                WHERE guest_id='".$member_code."'
                ");
            list($guest_id, $guest_code, $guest_expire) = mysql_fetch_row($q);
           
            if (!$guest_id ||
               ($guest_code != '' && $guest_code != $security_code) ||
               ($guest_expire > 0 && $guest_expire - $unix_timestamp < 0)) {
            
                //if wrong security code
                $t->assign('guest_page', 'newsletter.php');
                $t->display('add_guest_failed.html');
                exit;
                
            } else {

                $q = $db->query("
                    UPDATE {$db->config[prefix]}newsletter_guest
                    SET security_code='', securitycode_expire=''
                    WHERE guest_id='".$guest_id."'
                    ");

            }
            
            $q = $db->query("
                SELECT COUNT(*)
                FROM {$db->config[prefix]}newsletter_guest_subscriptions
                WHERE guest_id='".$member_code."'
                AND security_code='".$db->escape($security_code)."'
                AND (UNIX_TIMESTAMP(securitycode_expire) - $unix_timestamp) > 0
                ");
            $r = mysql_fetch_row($q);
            
            if ($r[0] > 0){
                
                //delete old (confirmed) subscriptions
                $q = $db->query("
                    DELETE FROM {$db->config[prefix]}newsletter_guest_subscriptions
                    WHERE guest_id='".$member_code."'
                    AND (security_code='' OR security_code IS NULL)
                    ");
                //activate new subscriptions
                $q = $db->query("
                    UPDATE {$db->config[prefix]}newsletter_guest_subscriptions
                    SET security_code='', securitycode_expire=''
                    WHERE guest_id='".$member_code."'
                    AND security_code='".$db->escape($security_code)."'
                    AND (UNIX_TIMESTAMP(securitycode_expire) - $unix_timestamp) > 0
                    ");
                
            }

            $t->display('add_guest_complete.html');
            //html_redirect("newsletter.php", false, _TPL_NEWSLETTER_INFO_SAVED, _TPL_NEWSLETTER_INFO_UPDATED);
            exit;
            
        }
             
        //check guest
        $guest = $db->get_guest_by_email($vars['e']);
        if (count($guest) == 0 || !$guest['guest_id']) {

            //check required input vars
            if (count($vars['tr']) == 0){
                $errors[] = _TPL_NEWSLETTER_REQUIRED_THREAD;
            }
        
            if (!strlen($vars['n'])){
                $errors[] = _TPL_NEWSLETTER_REQUIRED_NAME;
            }
            if (!strlen($vars['e']) || !check_email($vars['e'])){
                $errors[] = _TPL_NEWSLETTER_REQUIRED_EMAIL;
            }
        
        
            if ($errors){
                $t->assign('error', $errors);
                show_guest_form($vars);
                return;
            }

            //add guest
            $q = $db->query($s = "
                INSERT INTO {$db->config['prefix']}newsletter_guest
                (guest_id,guest_name,guest_email,security_code,securitycode_expire)
                VALUES (null, '".$db->escape($vars['n'])."', '".$db->escape($vars['e'])."', '".$db->escape($security_code)."', '$securitycode_expire')
            ");
            $guest_id = mysql_insert_id($db->conn);
            
        } else {
            
            $guest_id = $guest['guest_id'];
            if($security_code)
            $db->query($s = "
                UPDATE {$db->config['prefix']}newsletter_guest
                set guest_name='".$db->escape($vars['n'])."',security_code='".$db->escape($security_code)."',securitycode_expire='$securitycode_expire'
                WHERE
                guest_id='$guest_id'");
            
        }

        if (count($vars['tr']) > 0) {
            if ($config['dont_confirm_guests'])
                $db->delete_guest_threads($guest_id);
            $db->add_guest_threads($guest_id, $vars['tr'], $security_code, $securitycode_expire);
        }

        if (!$config['dont_confirm_guests'] && $vars['s'] == '') {
            
            //send a confirmation email
            $t->assign('name', htmlentities($vars['n']));
            $t->assign('link',"$config[root_url]/newsletter.php?a=add_guest&s=".$guest_id.":".$security_code);
    
            $et = & new aMemberEmailTemplate();
            $et->name = "verify_guest";
            $t->assign('config', $config);
            $et->lang = guess_language();
            // load and find templated
            if (!$et->find_applicable()){
                trigger_error("Cannot find applicable e-mail template for [{$et->name},{$et->lang},{$et->product_id},{$et->day}]", E_USER_WARNING);
                exit;
            }
            global $_AMEMBER_TEMPLATE;
            $_AMEMBER_TEMPLATE['text'] = $et->get_smarty_template();
            $parsed_mail = $t->fetch('memory:text');
            unset($_AMEMBER_TEMPLATE['text']);
            mail_customer($vars['e'], $parsed_mail,
                null, null, null, false,
                $vars['n']);
             
            $t->display('add_guest_ok.html');
            exit;
            
        }
        
    }
    
    $t->display('add_guest_complete.html');
    //html_redirect("newsletter.php", false, _TPL_NEWSLETTER_INFO_SAVED, _TPL_NEWSLETTER_INFO_UPDATED);
    exit;

}

///////////////////////// MAIN /////////////////////////////////////////
$vars = get_input_vars();
if (isset($vars['start'])) $start = $vars['start'];

switch ($vars['a']) {
    case 'archive':
        show_archive();
        break;
    case 'add_guest':
        add_guest();
        break;
    default:
        show_guest_form();
        break;
}

///////////////////////////////////////////////////

?>
