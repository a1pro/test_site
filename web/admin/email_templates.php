<?php
/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Info / PHP
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 4958 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

include "../config.inc.php";
$t = new_smarty();
include "login.inc.php";
admin_check_permissions('setup');

$EMAIL_TEMPLATES_CONFIG = '
== TAGSET user ==
$user.login - Username
$user.pass  - Password (plain-text)
$user.email - E-Mail
$user.name_f - Customer First Name
$user.name_l - Customer Last Name
$user.member_id - Customer Internal ID#
$user.street - Customer Street
$user.city - Customer City
$user.state - Customer State
$user.zip - Customer ZIP
$user.country - Customer Country
$user.status - Customer Status (0-pending, 1-active, 2-expired)
$user.is_affiliate - Is customer an affiliate? (0-no, 1-yes)
== TAGSET affiliate ==
$affiliate.login - Username
$affiliate.pass  - Password (plain-text)
$affiliate.email - E-Mail
$affiliate.name_f - Customer First Name
$affiliate.name_l - Customer Last Name
$affiliate.member_id - Customer Internal ID#
$affiliate.street - Customer Street
$affiliate.city - Customer City
$affiliate.state - Customer State
$affiliate.zip - Customer ZIP
$affiliate.country - Customer Country
$affiliate.status - Customer Status (0-pending, 1-active, 2-expired)
$affiliate.is_affiliate - Is customer an affiliate? (0-no, 1-yes)
== TAGSET payment ==
$payment.payment_id - Payment/Subscription Invoice# (and internal id)
$payment.product_id - Payment/Subscription Product#
$payment.begin_date|date_format:$config.date_format - Payment/Subscription Start Date (formatted)
$payment.expire_date|date_format:$config.date_format - Payment/Subscription Expiration Date (formatted)
$payment.completed - Is invoice paid? (0-no, 1-yes)
$payment.receipt_id - Payment/Subscription Receipt# (coming from payment system)
$payment.amount - Payment Total
$payment.tax_amount - Tax total in the payment
== TAGSET product ==
$product.title - Product Title
$product.description - Product Description
$product.price - Product Price
$product.price_group - Product Price Group
== TAGSET config ==
$config.site_title - Site Title
$config.root_url - Root URL of the script
$config.root_surl - Secure Root URL of the script (https:// if configured)
$config.currency|default:"$" - Configured default currency
$config.admin_email_from|default:$config.admin_email - Admin E-Mail
==EMAIL TAGS user,config==
verify_email:email_verify.txt - E-Mail is sent before actual signup to validate customer E-Mail Address
$url - URL to click
==EMAIL TAGS user,config==
verify_email_profile:email_verify_profile.txt - E-Mail is sent when user change email in profile  to validate customer E-Mail Address
$url - URL to click
==EMAIL TAGS user,config,payment==
send_pending_email:pending_mail.txt - E-Mail is sent to user immediately when he clicks Signup button
==EMAIL TAGS user,config,payment==
send_pending_admin:admin/pending_mail.txt - E-Mail is sent to admin when user tries to make payment
==EMAIL TAGS user,config,payment==
mail_not_completed:not_completed_mail.txt - E-Mail is sent to user if payment is not paid configured number of days
==EMAIL TAGS user,config,payment==
send_signup_mail:signup_mail.txt - E-Mail is sent when user completes his first subscription
==EMAIL TAGS user,config,payment==
send_payment_mail:mail_receipt.txt - E-Mail payment receipt to user
==EMAIL TAGS user,config,payment==
send_payment_admin:admin/mail_payment.txt - E-Mail payment receipt to admin
==EMAIL TAGS user,config,payment==
mail_expire:expire_mail.txt - E-Mail to user when his subscription expires
==EMAIL TAGS user,config,payment==
mail_cancel_admin:cc/mail_cancel_admin.txt - E-Mail to admin when user cancels recurring billing
==EMAIL TAGS user,config,payment==
mail_cancel_member:cc/mail_cancel_member.txt - E-Mail to user when he cancels recurring billing
==EMAIL TAGS user,config,payment==
cc_rebill_failed:cc/rebill_failed.txt - E-Mail to user when credit card rebilling failed
$new_expire|date_format:$config.date_format - New Expiration Date, if subscription pro-rated (formatted)
==EMAIL TAGS user,config,payment==
cc_rebill_failed_admin:cc/rebill_failed_admin.txt - E-Mail to admin when credit card rebilling failed
$new_expire|date_format:$config.date_format - New Expiration Date, if subscription pro-rated (formatted)
==EMAIL TAGS user,config,payment==
cc_rebill_success:cc/rebill_success.txt - E-Mail to user when credit card rebilling succesfull
==EMAIL TAGS user,config,payment,product==
card_expires:cc/card_expires.txt - E-Mail to user when his stored credit card expires soon
$expires - Credit card expiration date
==EMAIL TAGS user,config,product==
mail_autoresponder - Automatic periodical E-Mail Message
==EMAIL TAGS user,payment,affiliate,config,product==
aff.mail_sale_admin:admin/aff_sale.txt - Notify Admin regarding affiliate sale
$commission - Commision amount to pay affiliate
==EMAIL TAGS user,payment,affiliate,config,product==
aff.mail_sale_user:aff_sale.txt - Notify Affiliate regarding new sale
$commission - Commision amount to pay affiliate
==EMAIL TAGS user,config==
max_ip_actions:admin/mail_account_sharing.txt - Notify Admin regarding account sharing violation
==EMAIL TAGS user,config==
manually_approve:approval_mail.txt - Notify user that his account is accepted and is awaiting for admin approval
==EMAIL TAGS user,config==
manually_approve:approval_mail.txt - Notify user that his account is accepted and is awaiting for admin approval
==EMAIL TAGS config==
verify_guest:add_guest.txt - Verification e-mail for newsletters subscription (without registration)
$link - URL to click to confirm subscription
$name - Name of the customer
==EMAIL TAGS user,config==
send_pass:sendpass.txt - Send lost password to customer
==EMAIL TAGS user,config==
send_security_code:sendsecuritycode.txt - Send security code to customer to restore password
code - Security Code
hours - Hours to click link';

function display_email(){
    global $vars, $db, $config, $t;
    global $PARSED_EMAIL_TEMPLATES_CONFIG;

    $vars['l'] = get_first($vars['l'], get_default_lang());

    $et = & new aMemberEmailTemplate();
    $et->name = $vars['tpl'];
    $et->lang = $vars['l'];
    $et->product_id = $vars['product_id'];
    $et->day = $vars['day'];
    $et->find_exact();

    if ($vars['reload'] > 0){
        unset($vars['subject']);
        unset($vars['txt']);
        unset($vars['plain_txt']);
        unset($vars['attachments']);
    }
    if ($vars['copy_lang']){
        $e = & new aMemberEmailTemplate();
        $e->name = $vars['tpl'];
        $e->lang = $vars['copy_lang'];
        $e->product_id = $vars['product_id'];
        $e->day = $vars['day'];
        if ($rr=$e->find_exact()){
            $vars['subject'] = $e->subject;
            $vars['txt'] = $e->txt;
            $vars['plain_txt'] = $e->plain_txt;
            $vars['attachments'] = $e->attachments;
        }
    }

    // set encoding for corresponding language
    $lang_record = $GLOBALS['_LANG'][$vars['l']];
    if ($lang_record['encoding'] != ''){
        header("Content-type: text/html; charset=".$lang_record['encoding']);
    }


    $vars['format'] = get_first($vars['format'], $et->format, 'text');
    $vars['subject'] = get_first_set($vars['subject'], $et->subject);
    $vars['txt'] = get_first_set($vars['txt'], $et->txt);
    $vars['plain_txt'] = get_first_set($vars['plain_txt'], $et->plain_txt);
    $vars['attachments'] = split("\n", get_first_set($vars['attachments'], $et->attachments));

    foreach ($vars as $k=>$v)
        $t->assign($k, $v);
    $t->assign('tpl_name', $PARSED_EMAIL_TEMPLATES_CONFIG['emails'][$vars['tpl']]['comment']);

    /// get message tags
    $tags = array();
    foreach ((array)$PARSED_EMAIL_TEMPLATES_CONFIG['emails'][$vars['tpl']]['tagsets'] as $ts){
        $tags = array_merge_recursive($tags, $PARSED_EMAIL_TEMPLATES_CONFIG['tagset'][$ts]);
    }
    foreach ((array)$PARSED_EMAIL_TEMPLATES_CONFIG['emails'][$vars['tpl']]['tags'] as $k => $v){
        $tags[$k] = $v;
    }
    $tags_to_assign = array();
    foreach ($tags as $k=>$v){
        $tags_to_assign[ '{' . $k . '}' ] = '{' . $k . '} - '  . $v;
    }
    $t->assign('tags', $tags_to_assign);

    $options = array();
    $t->assign('lang_options', languages_get_options($for_select=true));
    $t->assign('copy_lang_options', copy_languages_get_options());
    $t->assign('another_day_options', another_day_get_options());
    $t->assign('format_options', array('text' => 'Plain Text (default)',
        'html' => 'HTML E-Mail', 'multipart' => 'Multi-Part (HTML and Text)'
    ));

    $t->assign('back_location', get_back_location());
    $t->display('admin/email_templates.html');
}

function get_back_location(){
    global $vars, $config;
    if ($vars['product_id'])
        return "products.php?action=edit&product_id=$vars[product_id]#$vars[tpl]";
    elseif (preg_match('/^aff\./', $vars['tpl']))
        return "setup.php?notebook=Affiliates#$vars[tpl]";
    elseif ($vars['tpl'] == 'max_ip_actions')
        return "setup.php?notebook=Advanced#$vars[tpl]";
    elseif ($vars['tpl'] == 'manually_approve')
        return "setup.php?notebook=Advanced#$vars[tpl]";
    else
        return "setup.php?notebook=E-Mail#$vars[tpl]";
}

function copy_languages_get_options(){
    global $vars;
    $et = & new aMemberEmailTemplate();
    $et->name = $vars['tpl'];
    $et->product_id = $vars['product_id'];
    $ret = $et->find_languages();
    unset($ret[$vars['l']]);
    return $ret;
}
function another_day_get_options(){
    global $vars;
    $et = & new aMemberEmailTemplate();
    $et->name = $vars['tpl'];
    $et->product_id = $vars['product_id'];
    $ret = array();
    foreach ($et->find_days() as $d)
        $ret[$d] = $d;
    unset($ret[$vars['day']]);
    return $ret;
}

function save_email(){
    global $vars, $db, $config, $t;
    $err = array();

    if ($vars['txt'] == '') return "Error: E-Mail Text is empty";
    if ($vars['l'] == '') return "Error: lang is empty";
    if ($vars['tpl'] == '') return "Error: tpl is empty";
    // handle attachments
    foreach (split("\n", $vars['attachments']) as $k=>$v){
        if ($v == '') { 
            if (is_array($vars['attachments']) && isset($vars['attachments'][$k]))
                unset($vars['attachments'][$k]); 
            continue; 
        };
        $fn = realpath($config['root_dir'] . "/templates/" . $v);
        $tdir = realpath($config['root_dir']."/templates/");
        if (strpos($fn, $tdir) !== 0)
            $err[] = "Attachment filename [$v] is invalid. Please enter just a<br /> filename of file located inside amember/templates/ folder.";
        if (!file_exists($fn))
            $err[] = "Attachment file [$v] is not exists. Please upload file<br /> named \"$v\" into amember/templates/ folder, then press Update button again";
    }
    if ($err)
        return $err;

    $my_tpl = & new aMemberEmailTemplate;
    foreach (array('format','subject','txt','plain_txt',
        'attachments','product_id', 'day') as $f)
            $my_tpl->$f = $vars[$f];
    $my_tpl->name = $vars['tpl'];
    $my_tpl->lang = $vars['l'];
    $my_tpl->attachments = $vars['attachments'];

    // try to find corresponding template
    $et = & new aMemberEmailTemplate;
    $et->name = $my_tpl->name;
    $et->lang = $my_tpl->lang;
    $et->product_id = $my_tpl->product_id;
    $et->day = $my_tpl->day;
    if ($et->find_exact()){
        $my_tpl->email_template_id = $et->email_template_id;
    }
    $my_tpl->save();
    admin_html_redirect("email_templates.php?tpl=$vars[tpl]&l=$vars[l]&product_id=$vars[product_id]&day=$vars[day]", "E-Mail Template Updated", "E-Mail Template has been updated");
    exit();
}

$PARSED_EMAIL_TEMPLATES_CONFIG = array(
    'tagset' => array(),
    'emails' => array(),
);

function parse_email_config(){
    global $EMAIL_TEMPLATES_CONFIG, $PARSED_EMAIL_TEMPLATES_CONFIG;
    $rrrr = preg_split('/^==\s*(.+?)\s*==\s*$/ms', $EMAIL_TEMPLATES_CONFIG, -1, PREG_SPLIT_DELIM_CAPTURE);
    $rrrr = array_map('trim', $rrrr);
    if ($rrrr[0] == '') array_shift($rrrr);
    for ($i=0;$i<count($rrrr);$i+=2){
        $head = $rrrr[$i];
        $body = $rrrr[$i+1];
        $tags = array();
        if (preg_match('/^TAGSET\s+(.+)\s*/', $head, $regs)){ // tagset found
            foreach (preg_split('/[\r\n]+/', $body) as $l){
                if ($l == '') continue;
                list($k,$v) = split(' - ', $l);
                if (($k == '') || ($v == '')){
                    fatal_error("Error in line: $l, no tag keyword or description present, check spaces around dash",0);
                }
                $tags[ trim($k) ] = trim($v);
            }
            $PARSED_EMAIL_TEMPLATES_CONFIG['tagset'][$regs[1]] = $tags;
        } elseif (preg_match('/EMAIL TAGS\s+(.+)\s*/', $head, $regs)){ // email record found
            $tagsets = array_map('trim', split(',', $regs[1]));
            $lines = preg_split('/[\r\n]+/', $body);
            preg_match('/^(.+?) - (.+)/', $x=trim(array_shift($lines)), $regs);
            $em = $regs[1];
            list($em,$filename) = split(':', $em, 2);
            $comment = $regs[2];
            foreach ($lines as $l){
                if ($l == '') continue;
                list($k,$v) = split(' - ', $l);
                if (($k == '') || ($v == '')){
                    fatal_error("Error in line: $l, no tag keyword or description present, check spaces around dash",0);
                }
                $tags[ trim($k) ] = trim($v);
            }
            $PARSED_EMAIL_TEMPLATES_CONFIG['emails'][$em] = array(
                'comment' => $comment,
                'file' => $filename,
                'tagsets' => $tagsets,
                'tags' => $tags,
            );
        } else {
            fatal_error('wrong EMAIL_TEMPLATES_CONFIG definition: ' . $head, 0);
        }
    }
}

function add_email(){
    global $vars, $db, $config, $t;
    $err = array();

    if ($vars['tpl'] == '') return "Error: tpl is empty";
    if ($vars['day'] == '' && isset($vars['day'])) return "Error: DAY is not specified, there must be a number value";

    $my_tpl = & new aMemberEmailTemplate;
    $my_tpl->name = $vars['tpl'];
    $my_tpl->lang = get_default_lang();
    $my_tpl->format = 'text';
    $my_tpl->subject = $vars['tpl'];
    $my_tpl->product_id = $vars['product_id'];
    $my_tpl->day = $vars['day'];

    // try to find corresponding template
    $et = & new aMemberEmailTemplate;
    $et->name = $my_tpl->name;
    $et->lang = $my_tpl->lang;
    $et->product_id = $my_tpl->product_id;
    $et->day = $my_tpl->day;
    if ($et->find_exact() && $et->product_id == $my_tpl->product_id){
        $my_tpl->email_template_id = $et->email_template_id;
        fatal_error("Template is already exists" );
    } else {
        $my_tpl->save();
        admin_html_redirect(get_back_location(), "E-Mail Template Added", "E-Mail Template has been added");
    }
    exit();
}

function del_email(){
    global $vars, $db, $config, $t;
    $err = array();

    if ($vars['tpl'] == '') return "Error: tpl is empty";
    if ($vars['day'] == '' && isset($vars['day'])) return "Error: DAY is not specified, there must be a number value";

    $my_tpl = & new aMemberEmailTemplate;
    $my_tpl->name = $vars['tpl'];
    $my_tpl->product_id = $vars['product_id'];
    $my_tpl->day = $vars['day'];

    $my_tpl->delete_all();

    admin_html_redirect(get_back_location(), "E-Mail Template Deleted", "E-Mail Template has been deleted");    exit();
}

function parse_text_template($text, &$e){
    global $config;
    $subject = '';
    if (preg_match('/^Subject: (.+?)[\n\r]+/im', $text, $args)){
        // found subject in body of message! then save it and remove from
        // message
        $subject = $args[1];
        $text = preg_replace('/(^Subject: .+?[\n\r]+)/im', '', $text);
    }
    $format = 'text';
    if (preg_match('/^Format: (\w+?)\s*$/im', $text, $args)){
        $format = $args[1];
        if (!strcasecmp('MULTIPART', $format)){
            $format = 'multipart';
            $text = preg_replace('/^Format: (\w+?)\s*$/im', '', $text);
        } elseif (!strcasecmp('HTML', $format)){
            $format = 'html';
            $text = preg_replace('/^Format: (\w+?)\s*$/im', '', $text);
        } elseif (!strcasecmp('TEXT', $format)){
            $format = 'text';
            $text = preg_replace('/^Format: (\w+?)\s*$/im', '', $text);
        }
    }
    $attachments = array();
    if (preg_match_all('/^Attachment: (.+?)\s*[\n\r]+/im', $text, $args)){
        foreach ($args[1] as $fname){
            $fname_orig = str_replace('..', '', $fname);
//            if ($fname[0] != '/')
            $fname = $config['root_dir'] . '/templates/' . $fname_orig;
            if (!file_exists($fname)){
                print("<li style='color:red;font-weight:bold;'>Email attachment file : '$fname' is not exists - check your e-mail templates</li>");
                continue;
            } elseif (!is_readable($fname)){
                print("<li style='color:red;font-weight:bold;'>Email attachment file : '$fname' is not readable for the script - check your e-mail templates and/or chmod file</li>");
                continue;
            } else
                $attachments[] = $fname_orig;
        }
        $text = preg_replace('/^Attachment: (.+?)\s*[\n\r]+/im', '', $text);
    }
    $e->format = $format;
    if ($subject != '')
        $e->subject = $subject;
    $e->attachments = join("\n", $attachments);
    $e->txt = $text;
}

function convert_templates(){
    global $PARSED_EMAIL_TEMPLATES_CONFIG, $config, $db;

    print <<<CUT
    <html><head><title>Import E-Mail Templates</title></head>
    <body>
    <h3>Import E-Mail Templates (from files to database)</h3>
CUT;

    foreach ($PARSED_EMAIL_TEMPLATES_CONFIG['emails'] as $k => $em){
        if ($em['file'] == '') continue;
        $file = $config['root_dir'] . "/templates/" . $em['file'];
        if (!file_exists($file)){
            print "<li style='color:red;font-weight:bold;'>File [$file] for template [$k] does not exists. Not imported</li>";
            continue;
        }
        $cnt = join('', file($file));
    
        $db->query("DELETE FROM {$db->config[prefix]}email_templates
        WHERE name='$k' AND
        (product_id IS NULL or product_id = 0)
        ");
        
        
        $e = &new aMemberEmailTemplate();
        $e->subject = $k;
        parse_text_template($cnt, $e);
        $e->name = $k;
        $e->lang = get_default_lang();
        if ($k == 'mail_expire'){
            $e->day = $config['mail_expire_days'] ? $config['mail_expire_days'] : 1;
        }
        $e->save();
        print "<li>Template [$em[file]] ($k) has been imported successfully</li>";
    }
    
    // import autoresponders
    $prlist = $db->get_products_list();
    $prlist[] = array(  
        'product_id' => null,
        'autoresponder' => $config['autoresponder'],
    );
    foreach ($prlist as $pr){
        if (!preg_match_all('/^\s*(\d+)\s*\-\s*(.+?)\s*$/m', $pr['autoresponder'], $regs))
            continue;        
        foreach ($regs[1] as $kk=>$k){
            $file = $config['root_dir'] . "/templates/" . ($fff=$regs[2][$kk]);
            if (!file_exists($file)){
                print "<li style='color:red;font-weight:bold;'>File [$file] for autoresponse template [$k] does not exists. Not imported</li>";
                continue;
            }
            $cnt = join('', file($file));

            $where_product_id = ($pr['product_id'] > 0) ? 
                " AND product_id = $pr[product_id] " :
                " AND product_id IS NULL ";
            $dayk = intval($k);
            $db->query("DELETE FROM {$db->config[prefix]}email_templates
            WHERE name='mail_autoresponder' 
                $where_product_id
                AND day = '$dayk'
            ");

            $e = &new aMemberEmailTemplate();
            $e->subject = "Periodical E-Mail Message";
            parse_text_template($cnt, $e);
            $e->name = "mail_autoresponder";
            $e->day = intval($k);
            $e->product_id = $pr['product_id'];
            $e->lang = get_default_lang();
            $e->save();
            print "<li>Autoresponse Template [$fff] (mail_autoresponse-$pr[product_id]-$k) has been imported successfully</li>";
        }
    }
    
print "</body></html>";
}


//////////////////// main ////////////////////////////////////////

$vars = get_input_vars();

if ($vars['a'] == 'convert'){
    parse_email_config();
    convert_templates();
    exit();
}


if (!$vars['tpl'])
    fatal_error("_REQEST[tpl] is empty - fatal error", 0);

if ($vars['config_value'] != ''){
    $vars['config_value'] = ($vars['config_value'] == 'true') ? '1' : '';
    $db->config_set($vars['tpl'], $vars['config_value'], 0);
}
if (($vars['product_setting_key'] != '') && ($vars['product_setting_value'] != '')
      && $vars['product_id']){
    if ($vars['product_setting_value'] == 'true')
        $vars['product_setting_value'] = 1;
    $p = $db->get_product($vars['product_id']);
    $p[$vars['product_setting_key']] = $vars['product_setting_value'];
    $db->update_product($vars['product_id'], $p);
}

if ($vars['a'] == 'add'){
    $err = add_email();
    if ($err){
        $url = get_back_location();
        fatal_error("$err<br />Please return <a href='$url'>back</a> and fix these errors", 0);
    }
} elseif ($vars['a'] == 'del'){
    $err = del_email();
    if ($err){
        $url = get_back_location();
        fatal_error("$err<br />Please return <a href='$url'>back</a> and fix these errors", 0);
    }
} else {
    parse_email_config();
    if (count($vars['attachments'])){
        foreach ((array)$vars['attachments'] as $k=>$v) 
            if ($v == '') {
                if (isset($vars['attachments'][$k]))
                    unset($vars['attachments'][$k]);
            }
        $vars['attachments'] = join("\n", (array)$vars['attachments']);
    }
    if (($vars['another_day']!='') && $vars['reload']){
        $vars['day'] = $vars['another_day'];
    }
    if ($vars['reload']) $vars['save'] = 0;
    if ($vars['save']){
        if ($err = save_email()){
            $t->assign('error', $err);
        }
    }
    display_email();
}
?>
