<?php /* Smarty version 2.6.2, created on 2010-11-16 17:33:06
         compiled from /home/getaudio/public_html/members/plugins/protect/amail_aweber/thanks.html */ ?>

<?php if ($this->_tpl_vars['vars']['from']): ?>
  <?php $this->assign('title', "But wait! You're not done yet..."); ?>
  <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
  <div id="thanksmail">
  <table cellpadding="5" align="center" width="60%">
    <tr>
      <td align="left">

        <p>Hello <?php echo $this->_tpl_vars['vars']['name']; ?>
, we need you to confirm your email address (<?php echo $this->_tpl_vars['vars']['from']; ?>
) in order for you to receive your userid and password. Please check your email in a few moments.</p>

        <ol>
          <li>
            <p>
              You will first receive a verification email asking you to verify your email and give permission to be added to our mailing list. This email will come from:
            </p><p>
              &quot;<?php echo $this->_tpl_vars['config']['admin_email_name']; ?>
 [<?php echo $this->_tpl_vars['config']['admin_email']; ?>
]&quot;
            </p><p>
              And it will look something like this:
            </p>

            <!-- Start Sample Email Div -->
            <div style="border:1px solid black;width:500px;margin:0 auto 0 auto;padding:20px;font-family:'Courier New', Courier, monospace;font-size:12px;">
              <p>
                <u><strong>Subject</strong></u><br />
                Response Required, confirm your request for information from <?php echo $this->_tpl_vars['config']['site_title']; ?>
.
              </p><p>
                <strong><u>Message</u></strong><br />
              </p><p>
                We received your request for information from <?php echo $this->_tpl_vars['config']['site_title']; ?>
<br />
                Before we begin sending you the information you<br />
                requested, we want to be certain we have your permission.<br />
                ------------------------------------------------ <br />
                CONFIRM BY VISITING THE LINK BELOW: <br />
                <br />
                https://www.aweber.com/z/c/?xxxxxxxx <br />
                <br />
                Click the link above to give us permission to send you<br />
                information. It's fast and easy! If you cannot click the<br />
                full URL above, please copy and paste it into your web<br />
                browser. <br />
                -------------------------------------------------- <br />
                If you do not want to confirm, simply ignore this message.  <br />
                <br />
                Thank You, <br />
                <br />
                <?php echo $this->_tpl_vars['config']['site_title']; ?>

              </p>
            </div>
            <!-- End Sample Email Div -->

            <p><strong>Click on the verification link in that email when you receive it.</strong></p>

            <p><span style="background-color: #FFFF00">If you don't receive a verification email,</span></strong> either your email provider is blocking our email, or your email reader is blocking our email. Please check your junk mail settings and folders to make sure you allow email from &quot;<?php echo $this->_tpl_vars['config']['admin_email']; ?>
&quot;.</p>

          </li><li>

            <p>As soon as you verify your email, you will receive your userid and password for the site. What's more, you will be placed on our special &quot;insiders&quot; list where you'll receive web site updates, advance notice of special offers, and more.</p>

          </li>
        </ol>

        <p>Oh, and one more thing. Why do we  send a request for verification by email? We do this to ensure that others don't use this form to send you unwanted email. It's to protect your privacy.</p>

        <p>
          Best regards always,<br />
          &nbsp;<br />
          <?php echo $this->_tpl_vars['config']['admin_email_name']; ?>

        </p>

      </td>
    </tr>
  </table>
  </div>
  <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
  else: ?>
  <?php $this->assign('title', "Thanks for verifying your email address!"); ?>
  <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
  <div id="center">
<div id="d_over_iframe"></div>
<div id="d_over_iframe"></div>

<!--MAIN CONTEINER-->
<div id="main_t">
  <div id="thanksmail">
  <table cellpadding="5" align="center" width="60%">
    <tr>
      <td align="left">
        <div><strong>Thanks for verifying your email address.</strong> </div>
        <p>Click <strong><a href="<?php echo $this->_tpl_vars['config']['root_url']; ?>
/login.php">HERE</a></strong> to be taken
        to the <a href="<?php echo $this->_tpl_vars['config']['root_url']; ?>
/login.php"><?php echo $this->_tpl_vars['config']['site_title']; ?>
</a>
        login page where you can login.</p>
      </td>
    </tr>
  </table>
  </div>
  </div>
</div>

  <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
  endif; ?>
