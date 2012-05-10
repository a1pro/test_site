<?php 
/*
* 
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Info / PHP
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 3918 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/
$avoid_timeout = 1;

include "../config.inc.php";
$t = new_smarty();
include "login.inc.php";
ignore_user_abort(true);
@set_time_limit(0);


check_lite();
admin_check_permissions('email');

function get_email_types(){
    global $db, $config;
    $res = array();
    $res['all']      = '* All Users (pending, active and expired)';
    $res['active']     = '* Active Users (paid and not-expired)';
    $res['pending']  = '* Pending Users (never paid)';
    $res['expired']  = '* Expired Users (paid and expired)';
    if ($config['use_affiliates'])
        $res['aff']  = '* Affiliates';
    $products = $db->get_products_list();
    foreach ($products as $p){
        $id = $p['product_id'];
        $n  = $p['title'];
        $res["PRODUCT-$id"] = "Active users of '$n'";
    }
    
    $newsletter_threads = $db->get_newsletter_threads();
    while ( list($id, $n) = each ($newsletter_threads)){
        $res["NEWSLETTER-$id"] = "Newsletter '$n'";
    }
    
    return $res;
}

function get_target_users($start, $count, &$total){
    global $db, $vars;

    $emails = array();
    $output = array();
    $total = 0;
    $skipped = 0;
    $today = date('Y-m-d');
    
    $email_types = array();
    $email_types = array_unique((array)$vars['email_type']);
    
    //newsletter threads
    $threads = array();
    foreach ($email_types as $email_type){
        if (preg_match('/^NEWSLETTER-(\d+)$/', $email_type, $regs)){
            $threads[] = $regs[1];
        }
    }
    $vars['newsletter_thread'] = $threads;

    /*
    if (count($threads) > 0) {
        while ( list(, $thread_id) = each($threads)) {
            $q = $db->query($s = "
                 SELECT blob_available_to AS available_to
                 FROM {$db->config['prefix']}newsletter_thread
                 WHERE thread_id = '$thread_id'
                ");
            $tr = mysql_fetch_assoc($q);
            $available_to = $tr['available_to'];
            $available_to = explode (",", $available_to);
            $email_types = array_merge ($email_types, $available_to);
        }
        $email_types = array_unique($email_types);
     }
     */
    
    // products selected
    $product_ids = array();

    // products active and expired
    $active_product_ids = array();
    $expired_product_ids = array();

    foreach ($email_types as $email_type){
        if (preg_match('/^PRODUCT-(\d+)$/', $email_type, $regs)){
            $product_ids[] = $regs[1];
        }

        if (preg_match('/^active_product-(\d+)$/', $email_type, $regs)){
            $active_product_ids[] = $regs[1];
        }
        if (preg_match('/^expired_product-(\d+)$/', $email_type, $regs)){
            $expired_product_ids[] = $regs[1];
        }
    }
    if ($product_ids){
        $q = $db->query($s = "
            SELECT DISTINCT u.*
            FROM {$db->config['prefix']}members u 
                LEFT JOIN {$db->config['prefix']}payments p 
                ON (p.member_id=u.member_id)
            WHERE p.completed > 0 AND p.product_id IN (".join(',',$product_ids).") 
            AND p.begin_date <= NOW() AND p.expire_date >= NOW()
            AND IFNULL(u.unsubscribed,0) = 0
            GROUP BY u.member_id
        ");
        while ($u = mysql_fetch_assoc($q)){
            if ($emails[$u['email']] || !check_email($u['email'])) continue;
            // make output
            if ($skipped <= $start-1)  {  
                // skip, wait for $start
                $skipped++;
            } elseif (count($output) >= $count) {
                //skip - we had necessary records
            } else {
                $u['data'] = unserialize($u[data]);
                $output[] = $u;
            }
//            print "skipped=$skipped;start=$start;count=$count;co=".count($output).";total=$total<br />";
            $total++;
            $emails[$u['email']]++;
        }
    }


    if (count($active_product_ids) > 0 || count($expired_product_ids) > 0){
            $where_active = "";
            $where_expired = "";
            if (count($active_product_ids) > 0) {
                $where_active = "( p.product_id IN (".join(',',$active_product_ids).") AND p.expire_date >= NOW() )";
            }
            if (count($expired_product_ids) > 0) {
                $where_expired = "( p.product_id IN (".join(',',$expired_product_ids).") AND p.expire_date < NOW() )";
            }
            $where = "";
            if ($where_active != '') {
                $where .= $where_active;
            }
            if ($where_expired != '') {
                if ($where != '') $where .= " OR ";
                $where .= $where_expired;
            }
            if ($where != '') $where = " AND (" . $where . ")";
            
        $q = $db->query($s = "
            SELECT DISTINCT u.*
            FROM {$db->config['prefix']}members u 
                LEFT JOIN {$db->config['prefix']}payments p 
                ON (p.member_id=u.member_id)
            WHERE p.completed > 0 AND p.begin_date <= NOW()
            $where
            AND IFNULL(u.unsubscribed,0) = 0
            GROUP BY u.member_id
        ");
        while ($u = mysql_fetch_assoc($q)){
            if ($emails[$u['email']] || !check_email($u['email'])) continue;
            // make output
            if ($skipped <= $start-1)  {  
                // skip, wait for $start
                $skipped++;
            } elseif (count($output) >= $count) {
                //skip - we had necessary records
            } else {
                $u['data'] = unserialize($u[data]);
                $output[] = $u;
            }
//            print "skipped=$skipped;start=$start;count=$count;co=".count($output).";total=$total<br />";
            $total++;
            $emails[$u['email']]++;
        }
    }



    //process member & guest subscriptions
    if (count($threads) > 0) {

        $q = $db->query($s = "
            SELECT u.*
            FROM {$db->config['prefix']}members AS u 
            LEFT JOIN {$db->config['prefix']}newsletter_member_subscriptions AS nms
            ON nms.member_id = u.member_id
            WHERE 1
            AND nms.thread_id IN (".join(',',$threads).")
            AND IFNULL(u.unsubscribed,0) = 0
            GROUP BY u.member_id
        ");
/*
        $q = $db->query($s = "
            SELECT DISTINCT u.*
            FROM {$db->config['prefix']}newsletter_member_subscriptions AS nms
            LEFT JOIN {$db->config['prefix']}members AS u 
            ON nms.member_id = u.member_id
            LEFT JOIN {$db->config['prefix']}payments AS p 
            ON (p.member_id=u.member_id)
            WHERE p.completed > 0
            AND nms.thread_id IN (".join(',',$threads).")
            AND p.begin_date <= NOW() AND p.expire_date >= NOW()
            AND IFNULL(u.unsubscribed,0) = 0
            GROUP BY u.member_id
        ");
*/
        while ($u = mysql_fetch_assoc($q)){
            $skip = false;
            foreach ($threads as $thread_id){
                if (!$db->is_thread_available_to_member ($thread_id, $u['member_id']))
                    $skip = true;
            }
            if ($skip) continue;
            
            if ($emails[$u['email']] || !check_email($u['email'])) continue;
            // make output
            if ($skipped <= $start-1)  {  
                // skip, wait for $start
                $skipped++;
            } elseif (count($output) >= $count) {
                //skip - we had necessary records
            } else {
                $u['data'] = unserialize($u[data]);
                $output[] = $u;
            }
            $total++;
            $emails[$u['email']]++;
        }

        $q = $db->query($s = "
             SELECT DISTINCT ng.guest_email AS email, ng.guest_name AS name_f
             FROM {$db->config['prefix']}newsletter_guest_subscriptions AS ngs
             LEFT JOIN {$db->config['prefix']}newsletter_guest AS ng 
             ON ng.guest_id = ngs.guest_id
             WHERE ngs.thread_id IN (".join(',',$threads).")
        ");
        while ($g = mysql_fetch_assoc($q)){
             if ($emails[$g['email']] || !check_email($g['email'])) continue;
             $g['is_guest'] = '1';

             if ($skipped <= $start-1)  {  
                 // skip, wait for $start
                 $skipped++;
             } elseif (count($output) >= $count) {
                 //skip - we had necessary records
             } else {
                  $output[] = $g;
             }

             $total++;
             $emails[$g['email']]++;
         }
     }
    
    
    
    /// seek for all, paid, expired, pending
    $where_conds = array();
    foreach ($email_types as $email_type){
        switch ($email_type){
            case 'all': 
                $where_conds[] = " 1 AND email_verified >=0 "; break;
            case 'guest': 
                   $q = $db->query($s = "
                        SELECT DISTINCT ng.guest_email AS email, ng.guest_name AS name_f
                        FROM {$db->config['prefix']}newsletter_guest ng 
                   ");
                   while ($g = mysql_fetch_assoc($q)){
                        if ($emails[$g['email']] || !check_email($g['email'])) continue;
                        $g['is_guest'] = '1';
                    if (count($output) >= $count) {
                    } else {
                        $output[] = $g;
                    }
                    $total++;
                    $emails[$g['email']]++;
                     }
                break;
            case 'active' : 
                $where_conds[] = "u.status = 1";
                break;
            case 'expired':
                $where_conds[] = "u.status = 2";
                break;
            case 'pending':
                $where_conds[] = "u.status = 0 AND email_verified >=0 ";
                break;
            case 'aff':
                $where_conds[] = "u.is_affiliate > 0";
                break;
        }
    }
    if ($where_conds){
        $where_conds = join(' OR ', $where_conds);    
        if ($where_conds)
            $where_conds = " AND ( $where_conds ) ";
        $q = $db->query($s = "
            SELECT DISTINCT u.*
            FROM {$db->config['prefix']}members u 
            WHERE IFNULL(u.unsubscribed,0) = 0 $where_conds
        ");
        while ($u = mysql_fetch_assoc($q)){
            if ($emails[$u['email']] || !check_email($u['email'])) continue;
            if ($skipped <= $start-1)  {  
                $skipped++;
            } elseif (count($output) >= $count) {
            } else {
                $u['data'] = unserialize($u[data]);
                $output[] = $u;
            }
            $total++;
            $emails[$u['email']]++;
        }
    }
    return $output;
}

function get_email_message(&$vars, &$user){
    global $t, $config;
    global $_AMEMBER_TEMPLATE;
    $_AMEMBER_TEMPLATE['text'] = preg_replace('/\{([^$])/', '&#123;\\1', $vars['text']);
    $user['name_f'] = strip_tags($user['name_f']);
    $user['name_l'] = strip_tags($user['name_l']);
    $user['name'] = trim ($user['name_f'] . ' ' . $user['name_l']);

    $t->assign('user', $user);
    $t->assign('config', $config);    
    return $vars['is_html'] ? $t->fetch('memory:text') : wordwrap($t->fetch('memory:text'));
}
function get_email_subject(&$vars, &$user){
    global $t, $config;
    global $_AMEMBER_TEMPLATE;
    $_AMEMBER_TEMPLATE['text'] = preg_replace('/\{([^$])/', '&#123;\\1', $vars['subj']);
    $user['name_f'] = strip_tags($user['name_f']);
    $user['name_l'] = strip_tags($user['name_l']);
    $user['name'] = trim ($user['name_f'] . ' ' . $user['name_l']);
    $t->assign('user', $user);
    $t->assign('config', $config);    
    return wordwrap($t->fetch('memory:text'));
}

function get_email_to(&$vars, &$user){
     return '"'.trim (preg_replace('/[^A-Za-z_\.-]+/', ' ', $user['name_f'] . ' ' . $user['name_l'])).'" '.'<' . $user['email'] . '>';
}

//////////////// display ///////////////////////////////////////////

function display_form(){
    global $t, $config, $vars;    
    $t->assign('email_types', get_email_types());
    $t->assign('config', $config);
    $t->assign('vars', $vars);
    $t->assign('files', serialize($vars['files']));
    $uploaded_files = array();
    foreach ((array)$vars['files'] as $f){
        $uploaded_files[] = "$f[name] ($f[size] KB)";
    }
    if ($uploaded_files)
        $t->assign('uploaded_files', $uploaded_files);
    $t->display("admin/email.html");
}

function display_preview(){
    global $t, $config, $db, $vars;

    $users = get_target_users(0, 1, $total);
    $user  = $users[0];

    $tt = $vars['text'];
    if (isset($user['is_guest']) && $user['is_guest'] == '1') $is_guest = '1'; else $is_guest = '0';
    if (count($vars['newsletter_thread']) > 0) $is_newsletter='1'; else $is_newsletter='0';
    
    $vars['text'] = add_unsubscribe_link($user['email'], $vars['text'], $vars['is_html'], $is_guest, $is_newsletter);
    
    $preview = array(
        'text' => 
        $vars['is_html'] ? 
        get_email_message($vars, $user) :
        htmlspecialchars(get_email_message($vars, $user), ENT_NOQUOTES ) ,
        'subj' => htmlspecialchars(get_email_subject($vars, $user), ENT_NOQUOTES ),
        'to'   => htmlspecialchars(get_email_to($vars, $user), ENT_NOQUOTES ),
        'is_html' => intval($vars['is_html']),
        'files'   => serialize($vars['files'])
    );
    $vars['text'] = $tt;
    
    $t->assign('email_types', $email_types=get_email_types());
    $t->assign('vars', $vars);
    $t->assign('svars', serialize($vars));
    $t->assign('preview', $preview);
    $t->assign('total_members', $total);
    $t->assign('to_archive', 1);
    $t->assign('to_send', 1);
    $uploaded_files = array();
    foreach ((array)$vars['files'] as $f){
        $uploaded_files[] = "$f[name] ($f[size] KB)";
    }
    if ($uploaded_files)
        $t->assign('uploaded_files', $uploaded_files);
    $t->display('admin/email_preview.html');

    $_SESSION['amember_send_mails'] = $vars;
}

function send_mails(){
    global $t, $config, $db, $vars;

    $sess_vars = $_SESSION['amember_send_mails'];
    if ($vars['to_archive'] == '1') {
        //add a message to archive
        $threads = "";
        if (count($sess_vars['newsletter_thread']) > 0) $threads = "," . implode (",", $sess_vars['newsletter_thread']) . ",";
        
        $q = $db->query($s = "
            INSERT INTO {$db->config['prefix']}newsletter_archive
            (archive_id,threads,subject,message,add_date,is_html)
            VALUES
            (null, '$threads', '".$db->escape(get_email_subject($vars, $user))."', '".$db->escape(get_email_message($vars, $user))."', NOW(), '".$db->escape($vars['is_html'])."')
        ");
        
   }
    
    if ($vars['to_send'] == '1') {
        //send a messages
        
        $start =  intval($vars['start']);
        $count =  50 ; // 50 emails per page call
        ////////////////////////////////////////////
        $vars = $sess_vars;
        
        $users = get_target_users($start, $count, $total);
        if ($start == 0)
            admin_log("Broadcast E-Mail Message [{$vars[subj]}] sent to $total users");
    
        // send emails to all users 
        $attachments = $vars['files'];
        foreach ($users as $user){
            $preview = array(
                'text' => get_email_message($vars, $user),
                'subj' => get_email_subject($vars, $user),
                'to'   => get_email_to($vars, $user),
                'is_html' => $vars['is_html']
            );
            
            if (isset($user['is_guest']) && $user['is_guest'] == '1') $is_guest = '1'; else $is_guest = '0';
            if (count($sess_vars['newsletter_thread']) > 0) $is_newsletter='1'; else $is_newsletter='0';
            
            mail_customer($preview['to'], $preview['text'], $preview['subj'], 
                $preview['is_html'], $attachments, $add_unsubscribe=1, '', $is_guest, $is_newsletter);

        }
            
        $newstart = $start + $count;
        $left = $total - $newstart;
        if (!$users || ($left <= 0)){
            $x = $start + count($users);
            clean_attachments();
            unset($_SESSION['amember_send_mails']);
            admin_html_redirect("email.php?count=$x&action=sent",  
                    "Sending emails (finished)", "Sending emails to users ... cleanup operations");
        } else {
            admin_html_redirect("email.php?start=$newstart&action=send&to_send=1",  
                "Sending emails (please don't close browser window)", "Sending emails to users ".($start+1)."-$newstart ($total total, $left e-mails left)");
        }
    } else {
            unset($_SESSION['amember_send_mails']);
            admin_html_redirect("email.php",  
                    "Sending emails (finished)", "Sending emails to users ... cleanup operations");
    }
}

function display_sent(){
    global $t, $vars;

    $t->assign('count', $vars['count']);
    $t->display('admin/email_sent.html');
}

function clean_attachments(){
    global $config;
    foreach ((array)$_SESSION['amember_send_mails']['files'] as $f)
        unlink($f['tmp_name']);

    $dir = opendir($d = $config['root_dir']."/data/");
    while (($file = readdir($dir)) !== false) {
        if (preg_match("/^upload\-/", $file))
            unlink($d . $file);
    }  
    closedir($dir);
}

function process_uploaded_files(&$vars){
    global $db, $config;
    srand(time());
    for ($i=0;$i<=1;$i++){
        if ($_FILES['file']['size'][$i]){ // file uploaded
            if (!move_uploaded_file($tmp=$_FILES['file']['tmp_name'][$i], 
                $new = $config['root_dir']."/data/upload-".rand(10000,99999).'-'.basename($tmp)))
                fatal_error("Unable to move downloaded file: $tmp to $new",0);
            $vars['files'][] = array(
                'tmp_name' => $new,
                'type'     => $_FILES['file']['type'][$i],
                'name'     => basename($_FILES['file']['name'][$i]),
                'size'     => sprintf("%.2f", $_FILES['file']['size'][$i] / 1024)
            );
        }
    }
}


//////////////////// main ////////////////////////////////////////
$t->register_resource("memory", array("memory_get_template",
                                       "memory_get_timestamp",
                                       "memory_get_secure",
                                       "memory_get_trusted"));

$vars = get_input_vars();
if ($vars['files'])
    $vars['files'] = unserialize($vars['files']);
if ($vars['upload'] || $_FILES['file']['size'][0] || $_FILES['file']['size'][1] ){
    process_uploaded_files($vars);
    if ($vars['upload'])
        $vars['action'] = '';
}
if ($vars['back']){
    $vars = unserialize($vars['vars']);
    $vars['action'] = '';
}
switch ($vars['action']){
    case 'preview':
        display_preview();
        break;
    case 'send':
        check_demo();
        send_mails();
        break;
    case 'sent':
        display_sent();
        break;
    default: 
        $_SESSION['send_emails'] = array();
        display_form();
        break;
}

?>
