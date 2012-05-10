<?php
if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

// config items for e-mail messages
function email_select_get(&$field, &$vars){
    global $db, $config;
    $ftype = $field['type'];
    if ($ftype == 'multi_select') $multi=1;
    $fname = $field['name'];
    if (!isset($vars[$fname]))
        $val = $field['params']['default'];
    else
        $val = $vars[$fname];
    $options = "";
    foreach ($field['params']['options'] as $k=>$v){
        $k = htmlspecialchars($k, ENT_QUOTES);
        $v = htmlspecialchars($v, ENT_QUOTES);
        $sel = ($multi ? in_array($k, (array)$val) : $val == $k) ? 'selected' : '';
        $options .= "<option value=\"$k\" $sel>$v";
    }

    $multiple = $multi ? 'multiple' : '';
    $fname = $multi ? $fname."[]" : $fname;
    $size = $multi ? min(10, count($field['params']['options'])) : 1;

    $tpl = $field['params']['email_template'] ? $field['params']['email_template'] : $field['name'];        
    $edit_link = "<a href='email_templates.php?a=edit&tpl=$tpl'>Edit E-Mail Template</a>";
    
    if ($multi) $edit_link = "<br />". $edit_link;
    return "<tr>
        <th><b>{$field[title]}</b><br /><small>{$field[desc]}</small></th>
        <td>
    <a name='{$fname}'></a>
    <select name='$fname' size=$size $multiple>
    $options
    </select><br />
    $edit_link
    </td></tr>
     ";
}

// config items for e-mail messages
function email_linkonly_get(&$field, &$vars){
    global $db, $config;

    $tpl = $field['params']['email_template'] ? $field['params']['email_template'] : $field['name'];        
    $edit_link = "<a href='email_templates.php?a=edit&tpl=$tpl'>Edit E-Mail Template</a>";
    
    return "<tr>
        <th><b>{$field[title]}</b><br /><small>{$field[desc]}</small></th>
        <td>
    <a name='{$fname}'></a>
    $edit_link
    </td></tr>
     ";
}

function email_checkbox_get(&$field, &$vars){
    global $db, $config;
    $ftype = $field['type'];
    $fname = $field['name'];
    if (!isset($vars[$fname]))
        $val = $field['params']['default'];
    else
        $val = $vars[$fname];
    $tpl = $field['params']['email_template'] ? $field['params']['email_template'] : $field['name'];        
    $edit_link = "<a href='email_templates.php?a=edit&tpl=$tpl'>Edit E-Mail Template</a>";
    $checked = $val ? 'checked' : '';

    return "<tr>
        <th><b>{$field[title]}</b><br /><small>{$field[desc]}</small></th>
        <td><input type='hidden' name='$fname' value='' />
        <input style='border-width: 0px;' type='checkbox' name='$fname' value='1' $checked />
        $edit_link
        <br />{$field['params']['before_text']}
        </td></tr>
     ";
}

function email_checkbox_days_get(&$field, &$vars){
    global $db, $config;
    global $db, $config;
    $ftype = $field['type'];
    $fname = $field['name'];
    if (!isset($vars[$fname]))
        $val = $field['params']['default'];
    else
        $val = $vars[$fname];
    $checked = $val ? 'checked' : '';

    $text_input = "";
    $et = & new aMemberEmailTemplate();
    $et->name = $fname;
    foreach ($et->find_days() as $day){
        $edit_link = "email_templates.php?a=edit&tpl=$field[name]&day=$day";
        $del_link  = "email_templates.php?a=del&tpl=$field[name]&day=$day";
        $text_input .= "
        <input type='text' size=3 class='small' value='$day' disabled />
        - <a href='$edit_link'>Edit E-Mail Template</a>
        / <a href='$del_link' onclick='return confirm(\"Are you sure?\")'>Delete</a><br />";

    }
    $text_input .= <<<CUT
    <input type="text" name='{$fname}_days_add' size=3 class="small" />
    <input type="button" onclick="window.location='email_templates.php?a=add&tpl=$field[name]&day='+this.form.{$fname}_days_add.value+'&config_value='+this.form.{$fname}[1].checked"
    value="Add E-Mail Template" />
CUT;
    return "<tr>
        <th><b>{$field[title]}</b><br /><small>{$field[desc]}</small></th>
        <td><input type='hidden' name='$fname' value='' />
        <input style='border-width: 0px;' type='checkbox' name='$fname' value='1' $checked />
        <a name='{$fname}'></a>
        Enable $field[title]<br />
        {$field['params']['before_text']}
        $text_input
        </td></tr>
     ";

}

function email_method_get(&$field, &$vars){
    global $db, $config;

    $mail_checked       = '';
    $smtp_checked       = '';
    $sendmail_checked   = '';

    if ($vars['email_method'] == 'mail')        $mail_checked       = 'CHECKED';
    if ($vars['email_method'] == 'smtp')        $smtp_checked       = 'CHECKED';
    if ($vars['email_method'] == 'sendmail')    $sendmail_checked   = 'CHECKED';
    return "<tr>
        <th><b>{$field[title]}</b><br /><small>{$field[desc]}</small></th>
        <td>
        <script language=JavaScript>
        <!--
        function openlink(url, w, h, s) {
             if (s == null) {s = 'no'}
             if (s == 1) {s = 'yes'}
             if (s == 0) {s = 'no'}
             win = window.open('','popup','width='+ w+ ',height=' + h + ',scrollbars=' + s + ',status=1,resizeable=0');
             win.location.href = url;
             win.focus();
             if (win.opener == null) win.opener = window;
        }
        function disable_inputs() {

            form = document.forms[0];
            bgColor = 'silver';
            if (form.email_method[0].checked) {
                //form.smtp_host.disabled=true;
                //form.sendmail_path.disabled=true;
                form.sendmail_path.readOnly=true;
                form.smtp_host.readOnly=true;
                form.smtp_user.readOnly=true;
                form.smtp_pass.readOnly=true;
                form.smtp_port.readOnly=true;
                form.smtp_security.readOnly=true;
                form.smtp_host.style.backgroundColor = bgColor;
                form.smtp_user.style.backgroundColor = bgColor;
                form.smtp_pass.style.backgroundColor = bgColor;
                form.smtp_port.style.backgroundColor = bgColor;
                form.smtp_security.style.backgroundColor = bgColor;
                form.sendmail_path.style.backgroundColor = bgColor;
            }
            if (form.email_method[1].checked) {
                //form.smtp_host.disabled=false;
                //form.sendmail_path.disabled=true;
                form.sendmail_path.readOnly=true;
                form.sendmail_path.style.backgroundColor = bgColor;
                form.smtp_host.readOnly=false;
                form.smtp_user.readOnly=false;
                form.smtp_pass.readOnly=false;
                form.smtp_port.readOnly=false;
                form.smtp_security.readOnly=false;
                form.smtp_host.style.backgroundColor = '';
                form.smtp_user.style.backgroundColor = '';
                form.smtp_pass.style.backgroundColor = '';
                form.smtp_port.style.backgroundColor = '';
                form.smtp_security.style.backgroundColor = '';

            }
            if (form.email_method[2].checked) {
                //form.smtp_host.disabled=true;
                //form.sendmail_path.disabled=false;
                form.sendmail_path.readOnly=false;
                form.sendmail_path.style.backgroundColor = '';
                form.smtp_host.readOnly=true;
                form.smtp_user.readOnly=true;
                form.smtp_pass.readOnly=true;
                form.smtp_port.readOnly=true;
                form.smtp_security.readOnly=true;
                form.smtp_host.style.backgroundColor = bgColor;
                form.smtp_user.style.backgroundColor = bgColor;
                form.smtp_pass.style.backgroundColor = bgColor;
                form.smtp_port.style.backgroundColor = bgColor;
                form.smtp_security.style.backgroundColor = bgColor;
            }
        }
        function do_test_email() {
            form = document.forms[0];
            email = form.test_email.value;
            sendmail_path = form.sendmail_path.value;
            smtp_host = form.smtp_host.value;
            smtp_user = form.smtp_user.value;
            smtp_pass = form.smtp_pass.value;
            smtp_port = form.smtp_port.value;
            smtp_security = form.smtp_security.options[form.smtp_security.selectedIndex].value;

            url = '{$config[root_url]}/admin/email_test.php?e=' + email;
            if (form.email_method[0].checked) url = url + '&m=mail';
            if (form.email_method[1].checked) url = url + '&m=smtp&h=' + smtp_host + '&u=' + smtp_user + '&pass='+ smtp_pass + '&port=' +smtp_port + '&s='+smtp_security;
            if (form.email_method[2].checked) url = url + '&m=sendmail&p=' + sendmail_path;

            openlink(url, 400, 300, 1);
        }
        function validate_email_method() {
            form = document.forms[0];

            if (form.email_method[1].checked) {
                if (form.smtp_host.value.match('^[ ]*$')) {
                    alert ('Please fill a smtp host field.');
                    form.smtp_host.focus();
                    return false;
                }
            }
            if (form.email_method[2].checked) {
                if (form.sendmail_path.value.match('^[ ]*$')) {
                    alert ('Please fill a sendmail path field.');
                    form.sendmail_path.focus();
                    return false;
                }
            }

            return true;
        }
        form = document.forms[0];
        form.onsubmit = 'return validate_email_method()';
        -->
        </script>
        <table class=vedit>
        <tr><td colspan=3><label for='em0'><input id='em0' type=radio name=email_method value=mail $mail_checked style=\"border:0px;\" onclick=\"disable_inputs()\">  Internal PHP mail() function (default)</label></td></tr>
        <tr><td rowspan=5><label for='em1'><input id='em1' type=radio name=email_method value=smtp $smtp_checked style=\"border:0px;\" onclick=\"disable_inputs()\"> SMTP</label> </td>
    	    <td>User:</td><td><input type=text name=\"smtp_user\" value=\"".$vars['smtp_user']."\" size=20 maxlength=255></td></tr>
    	    <tr><td>Pass:</td><td><input type=text name=\"smtp_pass\" value=\"".$vars['smtp_pass']."\" size=20 maxlength=255></td></tr>
    	    <tr><td>Host:</td><td><input type=text name=\"smtp_host\" value=\"".$vars['smtp_host']."\" size=20 maxlength=255></td></tr>
    	    <tr><td>Port:</td><td><input type=text name=\"smtp_port\" value=\"".$vars['smtp_port']."\" size=4 maxlength=255></td></tr>
    	    <tr><td>Security:</td><td><select name='smtp_security'>
    					<option value='' ".(!$vars['smtp_security']?"selected":"").">None</option>
    					<option value='ssl' ".($vars['smtp_security']=='ssl'?"selected":"").">SSL</option>
    					<option value='tls' ".($vars['smtp_security']=='tls'?"selected":"").">TLS</option>
    				    </select></td></tr>
        <tr><td><label for='em2'><input id='em2' type=radio name=email_method value=sendmail $sendmail_checked style=\"border:0px;\" onclick=\"disable_inputs()\"> SendMail path </label></td><td colspan=2><input type=text name=\"sendmail_path\" value=\"".$vars['sendmail_path']."\" size=20 maxlength=255></td></tr>
	</table>
        <br />
        Test this settings<br />
        Email: <input type=text name=\"test_email\" value=\"".$vars['test_email']."\" size=20 maxlength=255> <input type=button value=Send onclick=\"do_test_email()\">
        <script language=JavaScript>
        <!--
        disable_inputs();
        -->
        </script>
        </td></tr>
     ";
}

function email_method_set(&$field, &$vars, &$db_vars){
    global $db, $config;
    $db->config_set('email_method', $db->escape($vars['email_method']), 0);
    $db->config_set('smtp_host', $db->escape($vars['smtp_host']), 0);
    $db->config_set('smtp_user', $db->escape($vars['smtp_user']), 0);
    $db->config_set('smtp_pass', $db->escape($vars['smtp_pass']), 0);
    $db->config_set('smtp_port', $db->escape($vars['smtp_port']), 0);
    $db->config_set('smtp_security', $db->escape($vars['smtp_security']), 0);
    $db->config_set('sendmail_path', $db->escape($vars['sendmail_path']), 0);
    $db->config_set('test_email', $db->escape($vars['test_email']), 0);
}

add_config_field('admin_email', 'Admin Email',
    'text', "will be used to send email notifications to admin
    ",
    'E-Mail',
    'validate_email_address', '', '', 
    array('size' => 50));
add_config_field('admin_email_from', 'Outgoing Email Address',
    'text', "will be used as From: address for sending e-mail messages<br />
    to customers. If empty, 'Admin EMail' will be used for this goal",
    'E-Mail',
    'validate_email_address', '', '', 
    array('size' => 50));
add_config_field('admin_email_name', 'E-Mail Sender Name',
    'text', "enter name of sender. It will be displayed for all messages<br />
    that aMember sends",
    'E-Mail',
    '', '', '', 
    array('size' => 40));

add_config_field('##19', 'E-Mail System Configuration',
    'header', '', 'E-Mail');
add_config_field('email_methods',  'Email send method',
    'text', "PLEASE DO NOT CHANGE IT<br />if emailing from aMember already works",
    'E-Mail',
    '','email_method_get','email_method_set',
    array(
    ));

add_config_field('##10', 'Messages to customer before signup',
    'header', '', 'E-Mail');
add_config_field('verify_email', 'Verify E-Mail Address On Signup',
    'select', "verify email address entered by customer,<br />
    aMember will send email to<br />
    specified email and user will be able to continue signup<br />
    only after clicking a link in the email message",
    'E-Mail',
    '','email_checkbox_get','',
    array(
    ));

add_config_field('verify_email_profile', 'Verfiy E-Mail Address on Profile',
    'select', "verify email address entered by customer on Profile page,<br />
    aMember will send email to
    specified email address. <br/>User will have to click link from message <br/>in order to approve email change.",
    'E-Mail',
    '','email_checkbox_get','',
    array(
    ));


add_config_field('##11', 'Messages to customer after signup',
    'header', '', 'E-Mail');
if (!is_lite()){
$pp = array('' => '*** Disabled (default) ***');
foreach (get_paysystems_list() as $p)
    $pp[$p['paysys_id']] = $p['title'];
add_config_field('send_pending_email',  'Send Pending E-Mail',
    'multi_select', "send email to user when he does payment<br />
    in aMember (and it is not completed yet). It is suitable<br />
    ONLY FOR OFFLINE payment methods to keep reminder for customer<br />
    how to make payment. This email comes to user IMMEDIATELY<br />
    after clicking Signup button<br />
     ",
    'E-Mail',
    '','email_select_get','',
    array(
        'store_type' => 1,
        'options' => $pp
    ));
add_config_field('send_pending_admin',  'Send Pending E-Mail to Admin',
    'multi_select', "send email to admin when user creates payment<br />
    in aMember (and it is not completed yet). It is suitable<br />
    ONLY FOR OFFLINE payment methods so admin may contact customers<br />
    This email comes to user IMMEDIATELY after clicking Signup button<br />
    prior any actual payment<br />
     ",
    'E-Mail',
    '','email_select_get','',
    array(
        'store_type' => 1,
        'options' => $pp
    ));
add_config_field('##12', 'Not-Completed Payment Notifications',
    'header', '', 'E-Mail');

add_config_field('mail_not_completed',  'Enable "Not-Completed Payment" Notification',
    'select', "send email to user when his subscription is pending<br />
    and no completed subscriptions created for this customer yet",
    'E-Mail',
    '','email_checkbox_days_get','',
    array(
       'before_text' => "number of days when above notification must be send. <br />
    <i>1</i> means 1 day after payment<br />
    <i>2</i> means 2 days after payment<br/>
        "

    ));
}



add_config_field('##13', 'Messages to customer after payment',
    'header', '', 'E-Mail');
add_config_field('send_signup_mail',  'Send Signup E-Mail',
    'select', "send email to user when FIRST
    <br />subscripton is completed",
    'E-Mail',
    '','email_checkbox_get','',
    array(
        'options' => array(
            1 => 'Yes',
            0 => 'No'
        )
    ));
add_config_field('send_payment_mail',  'E-Mail Payment Receipt to user',
    'select', "send email to customer every time when payment is finished<br />",
    'E-Mail',
    '','email_checkbox_get','',
    array(
    ));

add_config_field('send_pdf_invoice',  'PDF invoice',
    'checkbox', "attach invoice file (.pdf) to Payment Receipt email",
    'E-Mail',
    '','',''
    );
add_config_field('invoice_contacts', 'Invoice Contact information',
    'textarea', "will be include to PDF invoice above", 'E-Mail',
    '', '', '',
    array('rows' => 9)
    );

add_config_field('send_payment_admin',  'Admin Payment Notifications',
    'select', "send email to admin when subscription
    completed",
    'E-Mail',
    '','email_checkbox_get','',
    array(
    ));

add_config_field('##14', 'Messages to customer after subscription expiration',
    'header', '', 'E-Mail');
add_config_field('mail_expire',  'Enable Expire Notification',
    'select', "send email to user when his subscription expires<br />
    email will not be sent for products with recurring billing",
    'E-Mail',
    '','email_checkbox_days_get','',
    array(
       'before_text' => "Enter number of days before (example <em>1</em>)<br/>
        or after expiration (for example <em>-2</em>)<br/>
        when expiration message will be sent<br/>
        There can be comma-separated list of values<br/>
        "
    ));

add_config_field('##15', 'E-Mails by User Request',
    'header', '', 'E-Mail');
add_config_field('mail_cancel_member',  'Send Cancel Notifications to User',
    'select', "send email to member when he cancels recurring subscription.<br />
    It works only for payment processors which works like Authorize.Net<br />
    or PayFlow Pro",
    'E-Mail',
    '','email_checkbox_get','',
    array(
    ));
add_config_field('mail_cancel_admin',  'Send Cancel Notifications to Admin',
    'select', "send email to admin when recurring subscription<br />
    cancelled by member. It works only for payment processors<br />
    which works like Authorize.Net or PayFlow Pro",
    'E-Mail',
    '','email_checkbox_get','',
    array(
    ));
add_config_field('send_pass',  'Send Password to Customer',
    'select', "send lost password e-mail to customer.
    Have a look also to <br />aMember CP->Setup->Advanced:Enable Secure Password Reminder",
    'E-Mail',
    '','email_linkonly_get','',
    array(
    ));

add_config_field('##16', '"Autoresponse" Messages',
    'header', '', 'E-Mail');
if (!is_lite()){
add_config_field('mail_autoresponder', 'Send Automatic Emails',
    'checkbox', "user can receive automatic emails after<br />
    signup. You can setup series of emails to be sent.
    ", 'E-Mail',
    '', 'email_checkbox_days_get', '',
    array(
    'before_text' => "Please choose days when e-mail messages must be sent.<br />
    Then use 'Edit E-Mail Template' links to edit messages.<br />
    "));

add_config_field('autoresponder_renew', 'How to Send Automatic Emails after Renewal',
    'select', "When user renews subscription, aMember can<br />
    resend days counter and start emails again from first one<br />
    or aMember can continue old mailing cycle.
    ", 'E-Mail',
    '', '', '',
    array('options' => array(
        ''  => 'Continue old mailing cycle (count days from first payment)',
        '1' => 'Reset days counter to zero, start new cycle'
    )));
}

add_config_field('##17', 'E-Mail Messages on rebilling event',
    'header', '', 'E-Mail');
add_config_field('cc_rebill_failed', 'Credit Card Rebill Failed',
    'select', "if credit card rebill failed, user will receive<br/>
    the following e-mail message.<br/>
    It works for payment processors like Authorize.Net and PayFlow Pro only
    ",
    'E-Mail',
    '','email_checkbox_get','',
    array(
    ));
add_config_field('cc_rebill_failed_admin', 'Credit Card Rebill Failed to Admin',
    'select', "if credit card rebill failed, admin will receive<br/>
    the following e-mail message.<br/>
    It works for payment processors like Authorize.Net and PayFlow Pro only
    ",
    'E-Mail',
    '','email_checkbox_get','',
    array(
    ));
add_config_field('cc_rebill_success', 'Credit Card Rebill Successfull',
    'select', "if credit card rebill was sucessfull, user will receive<br/>
    the following e-mail message.<br/>
    It works for payment processors like Authorize.Net and PayFlow Pro only
    ",
    'E-Mail',
    '','email_checkbox_get','',
    array(
    ));
add_config_field('card_expires', 'Credit Card Expiration Notice',
    'select', "if saved customer credit card expires soon, user will receive<br/>
    the following e-mail message.<br/>
    It works for payment processors like Authorize.Net and PayFlow Pro only
    ",
    'E-Mail',
    '','email_checkbox_get','',
    array(
    ));

add_config_field('##18', 'Miscellaneous',
    'header', '', 'E-Mail');

add_config_field('disable_unsubscribe_link',  'Do not include unsubscribe link in  emails',
    'checkbox', "Link Text can be changed in /amember/templates/unsubscribe_link.inc.html template",
    'E-Mail',
    '','',''
    );
add_config_field('copy_admin_email', 'Send copy of all Admin notifications',
    'text', "will be used to send copy of email notifications to admin<br/>
            you can specify more then one email separated by comma: <br/>
            test@email.com,test1@email.com,test2@email.com
    ",
    'E-Mail',
    'validate_emails', '', '',
    array('size' => 50));


?>