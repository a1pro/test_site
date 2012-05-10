<?php
class Am_Form_Setup_Global extends Am_Form_Setup
{
    function __construct()
    {
        parent::__construct('global');
        $this->setTitle(___('Global'))
            ->setComment('');
        $this->data['help-id'] = 'Setup/Global';
    }
    function validateCurl($val){
        if (!$val) return;
        exec("$val http://www.yahoo.com/ 2>&1", $out, $return);
        if ($return)
            return "Couldn't execute '$val http://www.yahoo.com/'. Exit code: $return, $out";
    }
    function initElements()
    {
         $this->addElement('text', 'site_title', array (
           'size' => 40,
         ), array('help-id' => '#Site Title'))
             ->setLabel(___('Site Title'));
         
         
         $this->addElement('static', null, null, array('help-id' => '#Root Url and License Key'))->setContent(
             '<div><a href="' . Am_Controller::escape(REL_ROOT_URL) . '/admin-license" target="_top">'
             . ___('change')
             . '</a></div>')->setLabel(___('Root Url and License Keys'));
         
         $this->addText('flowplayer_license')->setLabel(___("FlowPlayer License Key\nyou may get your key in %smembers area%s",
                '<a href="http://www.amember.com/amember/member?flowplayer_key=1">', '</a>'))
             ->addRule('regex', ___('Value must be alphanumeric'), '/^[a-zA-Z0-9]*$/');

         $this->addElement('select', 'theme', null, array('help-id' => '#User Pages Theme'))
             ->setLabel(___('User Pages Theme'))
             ->loadOptions(Am_View::getThemes('user'));

         $this->addElement('select', 'admin_theme')
             ->setLabel(___('Admin Pages Theme'))
             ->loadOptions(Am_View::getThemes('admin'));
/*
         if (!extension_loaded("curl")){
             $el = $this->addElement('text', 'curl')
                 ->setLabel(___('cURL executable file location', "you need it only if you are using payment processors<br />
                 like Authorize.Net or PayFlow Pro<br />
                 usually valid path is /usr/bin/curl or /usr/local/bin/curl"));
             $el->default = '/usr/bin/curl';
             $el->addRule('callback2', 'error', array($this, 'validateCurl'));
         }
*/
         $fs = $this->addElement('fieldset', '##02')
             ->setLabel(___('Signup Form Configuration'));

//         $this->addElement('advcheckbox', 'generate_login')
//             ->setLabel(___('Generate Login', 'should aMember generate username for customer?'));

         $this->setDefault('login_min_length', 5);
         $this->setDefault('login_max_length', 16);

         $loginLen = $fs->addGroup()->setLabel(___('Username length'));
         $loginLen->addInteger('login_min_length')->setLabel('min');
         $loginLen->addInteger('login_max_length')->setLabel('max');

         $fs->addElement('advcheckbox', 'login_disallow_spaces')
             ->setLabel(___('Do not allow spaces in username'));

         $fs->addElement('advcheckbox', 'login_dont_lowercase')
             ->setLabel(___("Do not lowercase username\n".
                    "by default, aMember automatically lowercases entered username\n".
                    "here you can disable this function"));

//         $fs->addElement('advcheckbox', 'generate_pass')
//             ->setLabel(___('Generate Password', 'should aMember generate password for customer?'));
//
         $this->setDefault('pass_min_length', 6);
         $this->setDefault('pass_max_length', 25);
         $passLen = $fs->addGroup()->setLabel(___('Password Length'));
         $passLen->addInteger('pass_min_length')->setLabel('min');
         $passLen->addInteger('pass_max_length')->setLabel('max');

         $fs = $this->addElement('fieldset', '##03')
             ->setLabel(___('Miscellaneous'));

         $this->setDefault('admin.records-on-page', 10);
         $fs->addElement('text', 'admin.records-on-page')
                 ->setLabel(___('Records per page (for grids)'));


         $this->setDefault('currency', 'USD');
         $currency = $fs->addElement('select', 'currency', array (
           'size' => 1,
         ))
             ->setLabel(___("Base Currency\n". 
                 "base currency to be used for reports and affiliate commission.\n".
                 "It could not be changed if there are any invoices in database")
             )
             ->loadOptions(Am_Currency::getFullList());
         if (Am_Di::getInstance()->db->selectCell("SELECT COUNT(*) FROM ?_invoice"))
             $currency->toggleFrozen(true);
    }
}

class Am_Form_Setup_Plugins extends Am_Form_Setup
{
    function __construct()
    {
        parent::__construct('plugins');
        $this->setTitle(___('Plugins'))
            ->setComment('');
    }
    function getPluginsList($folders)
    {
        $ret = array();
        foreach ($folders as $folder)
            foreach (scandir($folder) as $f)
            {
                if ($f[0] == '.') continue;
                $path = "$folder/$f";
                if (is_file($path) && preg_match('/^(.+)\.php$/', $f, $regs)) {
                    $ret[ $regs[1] ] = $regs[1];
                } elseif (is_dir($path)) {
                    if (is_file("$path/$f.php"))
                        $ret[$f] = $f;
                }
            }
        return $ret;
    }
    function initElements()
    {
         /* @var $bootstrap Bootstrap */
         $modules = 
            $this->addMagicSelect('modules')
            ->setLabel(___('Enabled Modules'));
         
         $this->setDefault('modules', array());
         
         foreach (Am_Di::getInstance()->modules->getAvailable() as $module)
         {
             $fn = APPLICATION_PATH . '/' . $module . '/module.xml';
             if (!file_exists($fn)) continue;
             $xml = simplexml_load_file($fn);
             if (!$xml) continue;
             $modules->addOption($module . ' - ' . $xml->desc, $module);
         }
         $plugins = self::getPluginsList(Am_Di::getInstance()->plugins_payment->getPaths());
         array_remove_value($plugins, 'free');
         $this->addMagicSelect('plugins.payment')
             ->setLabel(___('Payment Plugins')."\n".
                    ___("plugins that process credit cards on your website\n".
                        "will appear in the list once you enable [cc] module above"))
             ->loadOptions($plugins);
         
         $this->setDefault('plugins.payment', array());

         $this->addMagicSelect('plugins.protect')
             ->setLabel(___('Integration Plugins'))
             ->loadOptions(self::getPluginsList(Am_Di::getInstance()->plugins_protect->getPaths()));
         
         $this->setDefault('plugins.protect', array());
         
         $this->addMagicSelect('plugins.misc')
             ->setLabel(___('Other Plugins'))
             ->loadOptions(self::getPluginsList(Am_Di::getInstance()->plugins_misc->getPaths()));
         
         $this->setDefault('plugins.misc', array());
    }
    public function beforeSaveConfig(Am_Config $before, Am_Config $after) 
    {
        // Do the same for plugins;
        foreach(array('modules', 'payment', 'misc', 'protect') as $type)
        {
            $configKey = $type == 'modules' ? 'modules' : ('plugins.'.$type);
            $b = (array)$before->get($configKey);
            $a = (array)$after->get($configKey);
            $enabled = array_filter(array_diff($a, $b), 'strlen');
            $disabled = array_filter(array_diff($b, $a), 'strlen');
            $pm = Am_Di::getInstance()->plugins[$type];
            foreach ($disabled as $plugin)
            {
                if ($pm->load($plugin))
                    try {
                        $pm->get($plugin)->deactivate();
                    } catch(Exception $e) {
                        Am_Di::getInstance()->errorLogTable->logException($e);
                        trigger_error("Error during plugin [$plugin] deactivation: " . get_class($e). ": " . $e->getMessage(), E_USER_WARNING);
                    }
                // Now clean config for plugin;
                $after->set($pm->getConfigKey($plugin), array());
            }
            foreach ($enabled as $plugin)
            {
                if ($pm->load($plugin))
                {
                    $class = $pm->getPluginClassName($plugin);
                    try {
                        call_user_func(array($class, 'activate'), $plugin, $type);
                    } catch(Exception $e) {
                        Am_Di::getInstance()->errorLogTable->logException($e);
                        trigger_error("Error during plugin [$plugin] activattion: " . get_class($e). ": " . $e->getMessage(),E_USER_WARNING);
                    }
                }
            }
        }
        Am_Di::getInstance()->config->set('modules', $modules = $after->get('modules', array()));
        Am_Di::getInstance()->app->dbSync(true, $modules);
        $after->save();
    }
}

class Am_Form_Setup_Email extends Am_Form_Setup
{
    function __construct()
    {
        parent::__construct('email');
        $this->setTitle(___('E-Mail'))
            ->setComment('');
    }
    
    function checkSMTPHost($val){
        $res = ($val['email_method'] == 'smtp') ?
            (bool)strlen($val['smtp_host']) : true;
        
        if (!$res) {
            $elements = $this->getElementsByName('smtp_host');
            $elements[0]->setError('SMTP Hostname is required if you have enabled SMTP method');
        }
        
        return $res;
    }
    
    function initElements()
    {
         $this->addElement('text', 'admin_email', array (
           'size' => 50,
         ))
             ->setLabel(___("Admin E-Mail Address\n". 
                 "used to send email notifications to admin\n".
                 "and as default outgoing address")
             )
             ->addRule('callback', ___('Please enter valid e-mail address'), array('Am_Validate', 'email'));

         $this->addElement('text', 'admin_email_from', array (
           'size' => 50,
         ))
             ->setLabel(___(
                 "Outgoing Email Address\n".
                 "used as From: address for sending e-mail messages\n".
                 "to customers. If empty, [Admin E-Mail Address] is used"
             ))
             ->addRule('callback', ___('Please enter valid e-mail address'), array('Am_Validate', 'empty_or_email'));

         $this->addElement('text', 'admin_email_name', array (
           'size' => 40,
         ))
             ->setLabel(___(
             "E-Mail Sender Name\n" . 
             "used to display name of sender in outgoing e-mails"
                 ));

         $fs = $this->addElement('fieldset', '##19')
             ->setLabel(___('E-Mail System Configuration'));

         $fs->addElement('select', 'email_method')
             ->setLabel(___(
                 "Email Sending method\n" .
                 "PLEASE DO NOT CHANGE if emailing from aMember works"))
             ->loadOptions(array(
                 'mail' => ___('Internal PHP mail() function (default)'),
                 'smtp' => ___('SMTP'),
                 'disabled' => ___('Disabled')
             ));

         $fs->addElement('text', 'smtp_host')
             ->setLabel(___('SMTP Hostname'));       
         $this->addRule('callback', ___('SMTP Hostname is required if you have enabled SMTP method'), array($this, 'checkSMTPHost'));
         
         $fs->addElement('integer', 'smtp_port')
             ->setLabel(___('SMTP Port'));
         $fs->addElement('select', 'smtp_security')
             ->setLabel(___('SMTP Security'))
             ->loadOptions(array(
                 ''     => 'None',
                 'ssl'  => 'SSL',
                 'tls'  => 'TLS',
             ));
         $fs->addElement('text', 'smtp_user')
             ->setLabel(___('SMTP Username'));
         $fs->addElement('password', 'smtp_pass')
             ->setLabel(___('SMTP Password'));

         
         $test = ___("Test E-Mail Settings");
         $em = ___("E-Mail Address to Send to");
         $se = ___("Send Test E-Mail");
         $fs->addStatic('email_test')->setContent(<<<CUT
&nbsp;&nbsp;&nbsp;
<span style="color: red; font-weight: bold;">$test</span>
$em <input type='text' name='email' size=30 />
<input type='button' name='email_test_send' value='$se' />
<div id='email-test-result' style='display:none'></div>
CUT
             );

         $se = ___("Sending Test E-Mail...");
         $this->addElement('script')->setScript(<<<CUT
$(function(){
    $("#row-email_test-0 .element-title").hide();
    $("#row-email_test-0 .element").css({ 'margin-left' : '0px'});

    $("input[name='email_test_send']").click(function(){
        var btn = $(this);
        var vars = btn.parents('form').serialize();

        var dialogOpts = {
              modal: true,
              bgiframe: true,
              autoOpen: true,
              height: 400,
              width: 600,
              draggable: true,
              resizeable: true
           };

        var savedVal = btn.val();
        btn.val("$se").prop("disabled", "disabled");

        $.post(window.rootUrl + "/admin-email/test", btn.parents("form").serialize(), function(data){
            $("#email-test-result").html(data).dialog(dialogOpts);
            btn.val(savedVal).prop("disabled", "");
        });

    });

    $("#email_method-0").change(function(){
        var is_smtp = $(this).val() == 'smtp';
        $(".row[id*='smtp_']").toggle(is_smtp);
    }).change();
});
CUT
);

         $this->setDefault('email_log_days', 0);
         $fs->addElement('text', 'email_log_days', array (
           'size' => 6,
         ))
             ->setLabel(___('Log Outgoing E-Mail Messages for ... days'));

         $fs->addElement('advcheckbox', 'email_queue_enabled')
             ->setLabel(___('Use E-Mail Throttle Queue'));
         $fs->addScript()->setScript(<<<CUT
$(function(){
    $("#email_queue_enabled-0").change(function(){
        $("#email_queue_period-0").closest(".row").toggle(this.checked);
        $("#email_queue_limit-0").closest(".row").toggle(this.checked);
    }).change();
});
CUT
         );

         $fs->addElement('select', 'email_queue_period')
             ->setLabel(___(
                 "Allowed E-Mails Period\n" .
                 "choose if your host is limiting e-mails per day or per hour"))
             ->loadOptions(
                 array (
                   3600 => 'Hour',
                   86400 => 'Day',
                 )
         );

         $this->setDefault('email_queue_limit', 100);
         $fs->addInteger('email_queue_limit', array (
           'size' => 6,
         ))
             ->setLabel(___(
                 "Allowed E-Mails Count\n" .
                 "enter number of emails allowed within the period above"));

         $fs = $this->addElement('fieldset', '##10')
             ->setLabel(___('Validation messages to customer'));

         $fs->addElement('email_link', 'verify_email_signup')
             ->setLabel(___('Verfiy E-Mail Address On Signup Page'));
         
         $fs->addElement('email_link', 'verify_email_profile')
             ->setLabel(___('Verfiy New E-Mail Address On Profile Page'));

         $fs = $this->addElement('fieldset', '##11')
             ->setLabel(___('Signup Messages'));
         
         $fs->addElement('email_checkbox', 'registration_mail')
             ->setLabel(___("Send Registration E-Mail\n". 
                 "once customer completes signup form (before payment)"));
         
         /*         
         $fs = $this->addElement('fieldset', '##11')
             ->setLabel(___('Messages to customer after signup', ''));

         $fs->addElement('email_select', 'send_pending_email', array (
           'multiple' => 'multiple',
         ))
             ->setLabel(___('Send Pending E-Mail', 'send email to user when he does payment<br />
             in aMember (and it is not completed yet). It is suitable<br />
             ONLY FOR OFFLINE payment methods to keep reminder for customer<br />
             how to make payment. This email comes to user IMMEDIATELY<br />

             after clicking Signup button<br />
              '))
             ->loadOptions(
                 array (
                   '' => '*** Disabled (default) ***',
                   'manual' => 'Manual',
                   'cc_demo' => 'CC Demo',
                   'free' => 'Free Signup',
                   'paypal' => 'PayPal',
                 )
         );

         $fs->addElement('email_select', 'send_pending_admin', array (
           'multiple' => 'multiple',
         ))
             ->setLabel(___('Send Pending E-Mail to Admin', 'send email to admin when user creates payment<br />
             in aMember (and it is not completed yet). It is suitable<br />
             ONLY FOR OFFLINE payment methods so admin may contact customers<br />
             This email comes to user IMMEDIATELY after clicking Signup button<br />
             prior any actual payment<br />

              '))
             ->loadOptions(
                 array (
                   '' => '*** Disabled (default) ***',
                   'manual' => 'Manual',
                   'cc_demo' => 'CC Demo',
                   'free' => 'Free Signup',
                   'paypal' => 'PayPal',
                 )
         );

         $fs = $this->addElement('fieldset', '##12')
             ->setLabel(___('Not-Completed Payment Notifications', ''));

         //SPECIAL: before_text: ////'number of days when above notification must be send. <br />
         ////    <i>1</i> means 1 day after payment<br />
         ////    <i>2</i> means 2 days after payment<br/>'
         $fs->addElement('email_with_days', 'mail_not_completed')
             ->setLabel(___('Enable "Not-Completed Payment" Notification', 'send email to user when his subscription is pending<br />
             and no completed subscriptions created for this customer yet'));

*/
         $fs = $this->addElement('fieldset', '##13')
             ->setLabel(___('Messages to customer after payment'));
         
         $fs->addElement('email_checkbox', 'send_signup_mail')
             ->setLabel(___("Send Signup E-Mail\n". 
                 "once FIRST subscripton is completed"));

         $fs->addElement('email_checkbox', 'send_payment_mail')
             ->setLabel(___("E-Mail Payment Receipt to user\n". 
                 'every time payment is received'));

         $fs->addElement('email_checkbox', 'send_payment_admin')
             ->setLabel(___("Admin Payment Notifications\n". 
                 "to admin once payment is received"));

         $fs = $this->addElement('fieldset', '##15')
             ->setLabel(___('E-Mails by User Request'));
/*
         $fs->addElement('email_checkbox', 'mail_cancel_member')
             ->setLabel(___('Send Cancel Notifications to User', 'send email to member when he cancels recurring subscription.<br />

             It works only for payment processors which works like Authorize.Net<br />
             or PayFlow Pro'));

         $fs->addElement('email_checkbox', 'mail_cancel_admin')
             ->setLabel(___('Send Cancel Notifications to Admin', 'send email to admin when recurring subscription<br />
             cancelled by member. It works only for payment processors<br />
             which works like Authorize.Net or PayFlow Pro'));
*/
         $fs->addElement('email_link', 'send_security_code')
             ->setLabel(___("Remind Password to Customer"));
/*
         $fs = $this->addElement('fieldset', '##17')
             ->setLabel(___('E-Mail Messages on rebilling event', ''));

         $fs->addElement('email_checkbox', 'cc_rebill_failed')
             ->setLabel(___('Credit Card Rebill Failed', 'if credit card rebill failed, user will receive<br/>
             the following e-mail message.<br/>
             It works for payment processors like Authorize.Net and PayFlow Pro only
             '));

         $fs->addElement('email_checkbox', 'cc_rebill_failed_admin')
             ->setLabel(___('Credit Card Rebill Failed to Admin', 'if credit card rebill failed, admin will receive<br/>
             the following e-mail message.<br/>

             It works for payment processors like Authorize.Net and PayFlow Pro only
             '));

         $fs->addElement('email_checkbox', 'cc_rebill_success')
             ->setLabel(___('Credit Card Rebill Successfull', 'if credit card rebill was sucessfull, user will receive<br/>
             the following e-mail message.<br/>
             It works for payment processors like Authorize.Net and PayFlow Pro only
             '));

         $fs->addElement('email_checkbox', 'card_expires')
             ->setLabel(___('Credit Card Expiration Notice', 'if saved customer credit card expires soon, user will receive<br/>
             the following e-mail message.<br/>
             It works for payment processors like Authorize.Net and PayFlow Pro only
             '));
*/
         $fs = $this->addElement('fieldset', '##16')
             ->setLabel(___('E-Mails by Admin Request'));
         
         $fs->addElement('email_link', 'send_security_code_admin')
             ->setLabel(___('Remind Password to Admin'));
         
         
         $fs = $this->addElement('fieldset', '##18')
             ->setLabel(___('Miscellaneous'));

         $fs->addElement('advcheckbox', 'disable_unsubscribe_link')
             ->setLabel(___('Do not include E-Mail Footer into e-mails'));

         $fs->addTextarea('unsubscribe_html', array('cols'=>70, 'rows'=>6))
             ->setLabel(___("HTML E-Mail Footer\n" .
                 "%link% will be replaced to actual unsubscribe URL"));
         $this->setDefault('unsubscribe_html', Am_Mail::UNSUBSCRIBE_HTML);

         $fs->addTextarea('unsubscribe_txt', array('cols'=>70, 'rows'=>6))
             ->setLabel(___("Text E-Mail Footer\n" .
                 "%link% will be replaced to actual unsubscribe URL"));
         $this->setDefault('unsubscribe_txt', Am_Mail::UNSUBSCRIBE_TXT);

//         $fs->addElement('text', 'copy_admin_email', array (
//           'size' => 50,
//         ))
//             ->setLabel(___("Send copy of all Admin notifications', 'will be used to send copy of email notifications to admin<br/>
//                     you can specify more then one email separated by comma: <br/>
//
//                     test@email.com,test1@email.com,test2@email.com
//             '))
//             ->addRule('callback', 'Please enter valid e-mail address', array('Am_Validate', 'empty_or_email'));
    }
}

class Am_Form_Setup_Pdf extends Am_Form_Setup
{
    function __construct()
    {
        parent::__construct('pdf');
        $this->setTitle(___('PDF Invoice'))
            ->setComment('');
    }
    function initElements()
    {

         $this->addElement('advcheckbox', 'send_pdf_invoice')
             ->setLabel(___(
                 "Enable PDF invoice\n" . 
                 "attach invoice file (.pdf) to Payment Receipt email"));

         $this->addElement('advradio', 'invoice_format')
             ->setLabel(___('Paper format'))
             ->loadOptions(array(
                 Am_Pdf_Invoice::PAPER_FORMAT_LETTER => ___('USA (Letter)'),
                 Am_Pdf_Invoice::PAPPER_FORMAT_A4 => ___('European (A4)')
             ));

         $this->setDefault('invoice_format', Am_Pdf_Invoice::PAPER_FORMAT_LETTER);

         $upload = $this->addElement('upload', 'invoice_custom_template',
                 array(), array('prefix'=>'invoice_custom_template')
             )->setLabel(___("Custom PDF template for invoice (optional)")
             )->setAllowedMimeTypes(array(
             'application/pdf'
         ));
         
         $this->setDefault('invoice_custom_template', '');

         $jsOptions = <<<CUT
{
    onChange : function(filesCount) {
        if (filesCount) {
            $('fieldset#template-custom-settings').show();
            $('fieldset#template-generated-settings').hide();
        } else {
            $('fieldset#template-custom-settings').hide();
            $('fieldset#template-generated-settings').show();
        }
    }
}
CUT;

         $upload->setJsOptions(
            $jsOptions
         );


         $fsCustom = $this->addElement('fieldset', 'template-custom')
             ->setLabel(___('Custom template settings'))
             ->setId('template-custom-settings');

         $this->setDefault('invoice_skip', 150);
         $fsCustom->addElement('text', 'invoice_skip')
             ->setLabel(___(
                 "Top margin\n".
                 "How much [pt] skip from top of template before start to output invoice\n". 
                 "1 pt = 0.352777 mm"));

         $fsGenerated = $this->addElement('fieldset', 'template-generated')
             ->setLabel(___("Auto-generated template settings"))
             ->setId('template-generated-settings');

         $invoice_logo = $fsGenerated->addElement('upload', 'invoice_logo', array(),
                 array('prefix'=>'invoice_logo')
         )->setLabel(___("Company Logo for Invoice\n". 
             "it must be png/jpeg/tiff file (%s)", '200&times;100 px'))
         ->setAllowedMimeTypes(array(
             'image/png', 'image/jpeg', 'image/tiff'
         ));
         
         $this->setDefault('invoice_logo', '');

         $fsGenerated->addElement('textarea', 'invoice_contacts', array (
           'rows' => 5, 'style'=>"width:90%"
         ))
         ->setLabel(___("Invoice Contact information\n" . 
             "included at top"));

         $fsGenerated->addElement('textarea', 'invoice_footer_note', array (
           'rows' => 5, 'style'=>"width:90%"
         ))
         ->setLabel(___("Invoice Footer Note\n" . 
             "will be included at bottom"));

         $script = <<<CUT
(function($){
    $(function() {
        function change_template_type(obj) {
            if ($(obj).val()) {
                $('fieldset#template-custom-settings').show();
                $('fieldset#template-generated-settings').hide();
            } else {
                $('fieldset#template-custom-settings').hide();
                $('fieldset#template-generated-settings').show();
            }
        }

        change_template_type($('input[name=invoice_custom_template]'));

        if (!$('input[name=send_pdf_invoice]').prop('checked')) {
            $('input[name=send_pdf_invoice]').closest('.row').nextAll().not('script').hide();
            $('input[name=send_pdf_invoice]').closest('form').find('input[type=submit]').closest('.row').show();
        }

        $('input[name=send_pdf_invoice]').bind('change', function(){
            if (!$(this).prop('checked')) {
                $(this).closest('.row').nextAll().not('script').hide()
                $(this).closest('form').find('input[type=submit]').closest('.row').show();
            } else {
                $(this).closest('.row').nextAll().not('script').show();
                change_template_type($('input[name=invoice_custom_template]'));
            }
        })
    });
})(jQuery)
CUT;
         $this->addElement('script', 'script')
                 ->setScript(
                    $script
                 );

    }
}

class Am_Form_Setup_Advanced extends Am_Form_Setup
{
    function __construct()
    {
        parent::__construct('advanced');
        $this->setTitle(___('Advanced'))
            ->setComment('');
        $this->data['help-id'] = 'Setup/Advanced';
    }
    
    function checkBackupEmail($val){
        $res = $val['email_backup_frequency'] ?
            Am_Validate::email($val['email_backup_address']) : true;
        
        if (!$res) {
            $elements = $this->getElementsByName('email_backup_address');
            $elements[0]->setError(___('This field is required'));
        }

        return $res;
    }
    
    function initElements()
    {
         $this->addElement('advcheckbox', 'use_cron', null, array('help-path' => 'Cron'))
             ->setLabel(___("Use External Cron"));
         
         $gr = $this->addGroup()->setLabel(___(___('Maintenance Mode'), ___('put website offline, making it available for admins only')));
         $gr->addCheckbox('', array('id' => 'maint_checkbox', 
             'data-text' => ___('Site is temporarily disabled for maintenance')));
         $gr->addTextarea('maintenance', array('id' => 'maint_textarea', 'rows'=>3, 'cols'=>80));
         $gr->addScript()->setScript(<<<CUT
$(function(){
    var checkbox = $('#maint_checkbox');
    var textarea = $('#maint_textarea');
    $('#maint_checkbox').click(function(){
        textarea.toggle(checkbox.prop('checked'));
        if (textarea.is(':visible')) 
        {
            textarea.val(checkbox.data('text'));
        } else {
            checkbox.data('text', textarea.val());
            textarea.val('');
        }
    });
    checkbox.prop('checked', !!textarea.val());
    textarea.toggle(checkbox.is(':checked'));
});
CUT
         );
         
         
         $this->addElement('advcheckbox', 'use_user_groups')
             ->setLabel(___("Use User Groups"));
         
         $gr = $this->addGroup()->setLabel(___("Clear Access Log"));
         $gr->addElement('advcheckbox', 'clear_access_log');
         $gr->addStatic()->setContent(sprintf('<span class="clear_access_log_days">%s</span>', ___("after")));
         $gr->addElement('integer', 'clear_access_log_days');
         $gr->addStatic()->setContent(sprintf('<span class="clear_access_log_days">%s</span>', ___("days")));

         $this->setDefault('multi_title', ___('Membership'));
         $this->addElement('text', 'multi_title')
             ->setLabel(___("Multiple Order Title\n".
                 "when user ordering multiple products,\n".
                 "display the following on payment system\n".
                 "instead of product name"));

         $fs = $this->addElement('fieldset', '##3')
             ->setLabel(___('E-Mail Database Backup'));
         
         $fs->addElement('select', 'email_backup_frequency')
                 ->setLabel(___('Email Backup Frequency'))
                 ->setId('select-email-backup-frequency')
                 ->loadOptions(array(
             '0' => 'Disabled',
             'd' => 'Daily',
             'w' => 'Weekly'    
         ));
         
         $di = Am_Di::getInstance();
         $backUrl = $di->config->get('root_url') . '/backup/cron/k/' . $di->app->getSiteHash('backup-cron', 10);
         
         $text = ___("It is required to setup a cron job to trigger backup generation");
         $html = <<<CUT
<div id="email-backup-note-text">
</div> 
<div id="email-backup-note-text-template" style="display:none">         
    $text <br />
    <strong>%EXECUTION_TIME% /usr/bin/curl $backUrl</strong><br />
</div>
CUT;
         
         $fs->addHtml('email_backup_note')->setHtml($html);
         
         $fs->addElement('text', 'email_backup_address')
                 ->setLabel(___('E-Mail Backup Address'));
                 
         $this->addRule('callback', 'Email is required if you have enabled Email Backup Feature', array($this, 'checkBackupEmail'));
         
         $script = <<<CUT
(function($) {
    function toggle_frequency() {
        if ($('#select-email-backup-frequency').val() == '0') {
            $("input[name=email_backup_address]").closest(".row").hide();
        } else {
            $("input[name=email_backup_address]").closest(".row").show();
        }
        
        switch ($('#select-email-backup-frequency').val()) {
            case 'd' :
                $('#email-backup-note-text').empty().append(
                    $('#email-backup-note-text-template').html().
                        replace(/%FREQUENCY%/, 'daily').
                        replace(/%EXECUTION_TIME%/, '15 0 * * *')
                )
                $('#email-backup-note-text').closest('.row').show();
                break;
            case 'w' :
                $('#email-backup-note-text').empty().append(
                    $('#email-backup-note-text-template').html().
                        replace(/%FREQUENCY%/, 'weekly').
                        replace(/%EXECUTION_TIME%/, '15 0 * * 1')
                )
                $('#email-backup-note-text').closest('.row').show();
                break;
            default:
                $('#email-backup-note-text').closest('.row').hide();
        }
    }
    
    toggle_frequency();
    
    $('#select-email-backup-frequency').bind('change', function(){
        toggle_frequency();
    })

})(jQuery)
CUT;
         
         $this->addScript('script-backup')->setScript($script);
         
         $fs = $this->addElement('fieldset', '##5')
             ->setLabel(___('Advanced Options'));
/*
         $fs->addElement('advcheckbox', 'manually_approve')
             ->setLabel(___('Manually Approve New Members', 'manually approve all new members (first payment)<br />
             don\'t enable it if you have huge members base already - all old<br />
             members become not-approved
             '));

         $fs->addElement('advcheckbox', 'product_paysystem')
             ->setLabel(___('Assign paysystem to product', '
             if you enable this option, you will get select in product<br />
             options. You will be allowed to choose a payment system to<br />
             be used with this product. Don\'t enable this option along<br />
             with "Select Multiple Product" or be very careful!<br />

             Usually this option is not very useful.
             '));

         $fs->addElement('select', 'limit_renewals')
             ->setLabel(___('Limit Renewals', '
             don\'t allow members to order new subscriptions,<br />
             when they already have active subscriptions.<br />
             Please be aware - in some situations, enabling<br />
             of this option will make impossible for user<br />
             to use your service without interruption.<br />
             All these options means that there is already<br />

             another ACTIVE subscription.
             '))
             ->loadOptions(
                 array (
                   '' => 'Don\'t limit - recommended',
                   1 => 'Disallow subscription for the same product',
                   2 => 'Disallow subscription for the same renewal group',
                   3 => 'Disallow if there is any other active subscription',
                 )
         );
*/

         $fs->addElement('advcheckbox', 'dont_check_updates')
             ->setLabel(___("Disable checking for aMember updates"));

         $fs->addElement('advcheckbox', 'quickstart-disable')
             ->setLabel(___("Disable QuickStart wizard"));
         
         $fs->addElement('advcheckbox', 'am3_urls')
             ->setLabel(___("Use aMember3 compatible urls\n".
                      "Enable old style urls (ex.: signup.php, profile.php)\n".
                      "Usefull only after upgrade from aMember v3 to keep old links working.\n"
                      ));
         
         
         $fs->addSelect('session_storage')
             ->setLabel(___("Session Storage"))
             ->loadOptions(array(
                 'db' => ___('aMember Database (default)'),
                 'php' => ___('Standard PHP Sessions'),
             ));

         $js = <<<CUT
(function() {
//    function toggle_clear_access_log() {
//        $('.clear_access_log_days, #clear_access_log_days-0').toggle(!$('input[name=clear_access_log]').prop('checked'));
//    }
//    $(function(){
//        toggle_clear_access_log();
//        $('input[name=clear_access_log]').change(function(){
//            toggle_clear_access_log()
//        })
//    })
})(jQuery)
CUT;

         $this->addScript('script')->setScript($js);
    }
}

class Am_Form_Setup_Loginpage extends Am_Form_Setup
{
    function __construct()
    {
        parent::__construct('loginpage');
        $this->setTitle(___('Login Page'))
            ->setComment('');
    }
    function initElements()
    {
        $gr = $this->addGroup()
         ->setLabel(___("Redirect after login\n".
             "where customer redirected after successful\n".
             "login at %s", '<b>'.ROOT_URL . '/login</b>'));
        $sel = $gr->addSelect('protect.php_include.redirect_ok', 
            array('size' => 1, 'id' => 'redirect_ok-sel'), array('options' => array(
                'first_url' => ___('First available protected url'),
                'last_url' => ___('Last available protected url'),
                'member' => ___('Membership Info Page'),
                'url' => ___('Fixed Url'),
            )));
        $txt = $gr->addText('protect.php_include.redirect_ok_url', 
            array('size' => 40, 'style'=>'display:none', 'id' => 'redirect_ok-txt'));
        $this->setDefault('protect.php_include.redirect_ok_url', ROOT_URL);
        $gr->addScript()->setScript(<<<CUT
$(function(){
    $("#redirect_ok-sel").change(function(){
        $("#redirect_ok-txt").toggle($(this).val() == 'url');
    }).change();
});
CUT
        );

         $this->addElement('text', 'protect.php_include.redirect')
         ->setLabel(___("Redirect after logout\n".
             "enter full URL, starting from http://\n".
              "keep empty for redirect to site homepage"));

         $this->addElement('advcheckbox', 'protect.php_include.remember_login')
             ->setLabel(___("Remember Login\n".
                 "remember username/password in cookies"));

         $this->addElement('advcheckbox', 'protect.php_include.remember_auto')
             ->setLabel(___("Always Remember\n". 
                 "if set to Yes, don't ask customer - always remember"));

         $this->setDefault('protect.php_include.remember_period', 60);
         $this->addElement('integer', 'protect.php_include.remember_period')
             ->setLabel(___("Remember period\n" .
                 "cookie will be stored for ... days"));

         $this->addElement('advcheckbox', 'auto_login_after_signup')
             ->setLabel(___('Automatically login customer after signup'));
         
         $this->setDefault('login_session_lifetime', 120);
         $this->addElement('integer', 'login_session_lifetime')
             ->setLabel(___("User session lifetime (minutes)\n". 
                            "default - 120"));

         $gr = $this->addGroup()
             ->setLabel(___("Account Sharing Prevention"));

         $gr->addStatic()->setContent(___('if customer uses more than'));
         $gr->addElement('integer', 'max_ip_count', array('size' => 4));
         $gr->addStatic()->setContent(___('IP within'));
         $gr->addElement('integer', 'max_ip_period', array('size' => 5));
         $gr->addStatic()->setContent(___('minutes, %sdo the following%s', '<br />', '<br />'));
         $gr->addElement('select', 'max_ip_actions', array('multiple'=>'multiple', 'class'=>'magicselect'))
             ->loadOptions(
                 array (
                   'disable-user'     => 'Disable customer account',
                   'email-admin' => 'Email admin regarding account sharing',
                 )
         );
         
         $gr = $this->addGroup()
             ->setLabel(___('Bruteforce Protection'));
         $this->setDefault('bruteforce_count', '5');
         $gr->addStatic()->setContent(___('if user enters wrong password'));
         $gr->addElement('integer', 'bruteforce_count', array('size' => 4));
         $gr->addStatic()->setContent(___('times within'));
         $this->setDefault('bruteforce_delay', '120');
         $gr->addElement('integer', 'bruteforce_delay', array('size'=>5));
         $gr->addStatic()->setContent(___('minutes, he will be forced to wait until next try'));
   }
}

class Am_Form_Setup_Language extends Am_Form_Setup
{
    function __construct()
    {
        parent::__construct('language');
        $this->setTitle(___('Languages'));
    }
    function initElements()
    {
        $this->addElement('advcheckbox', 'lang.display_choice')
            ->setLabel(___('Display Language Choice'));
        $list = Am_Di::getInstance()->languagesListUser;
        
        $sel = $this->addElement('select', 'lang.enabled', array('multiple'=> 'multiple', 'class' => 'magicselect'))
            ->setLabel(___("Available Locales\ndefines both language and date/number formats"));
        $sel->loadOptions($list);
        
        $this->setDefault('lang.default', 'en');
        $sel = $this->addElement('select', 'lang.default', array())
            ->setLabel(___('Default Locale'));
        $sel->loadOptions(array('' => '== '.___('Please Select').' ==') + $list);
    }
}

class Am_Form_Setup_Theme extends Am_Form_Setup
{
    protected $themeId;
    public function __construct($themeId)
    {
        $this->themeId = $themeId;
        parent::__construct('themes-'.$themeId);
    }
    public function prepare()
    {
        parent::prepare();
        $this->addFieldsPrefix('themes.'.$this->themeId.'.');
    }
}

class Am_Form_Setup_Recaptcha extends Am_Form_Setup
{
    function __construct()
    {
        parent::__construct('recaptcha');
        $this->setTitle(___('ReCaptcha'));
    }
    function initElements()
    {
        $this->addText("recaptcha-public-key", array('size'=>50))->setLabel("<a href='http://www.google.com/recaptcha' target='_blank'>ReCaptcha</a> Public Key")
            ->addRule('required', 'This field is required');
        $this->addText("recaptcha-private-key", array('size'=>50))->setLabel("<a href='http://www.google.com/recaptcha' target='_blank'>ReCaptcha</a> Private Key")
            ->addRule('required', 'This field is required');
    }
    
    function getReadme(){
        return <<<CUT
<b>reCaptcha configuration</b>
Complete instructions can be found here: 
<a href='http://www.amember.com/docs/Setup/ReCaptcha' target='_blank'>http://www.amember.com/docs/Setup/ReCaptcha</a>

Use Forms Editor in order to add recaptcha field to signup/renewal page: 
<a href='%root_url%/admin-saved-form'>%root_url%/admin-saved-form</a>

CUT;
        
    }
}
