<?php /* Smarty version 2.6.2, created on 2010-11-16 14:38:18
         compiled from ../plugins/protect/amail_aweber/thanks.amail_aweber.inc.html */ ?>

<?php 

$member = $this->_tpl_vars['member'];

$this->_tpl_vars['thismemberid'] = $member['member_id'];

 ?>

<?php if ($this->_tpl_vars['payment'] && $this->_tpl_vars['payment']['member_id'] == $this->_tpl_vars['thismemberid'] && ! $this->_tpl_vars['member']['unsubscribed']): ?>
  <div id="thanksmail">
  <table cellpadding="5" align="center" width="60%">
    <tr>
      <td align="left">

        <h2>But Wait <?php echo $this->_tpl_vars['member']['name_f']; ?>
!  You're Not Done Yet...</h2>

        <p>Hello <?php echo $this->_tpl_vars['member']['name_f']; ?>
, we need you to confirm your email address (<?php echo $this->_tpl_vars['member']['email']; ?>
) in order for you to receive your email subscriptions. Please check your email in a few moments.</p>

        <ol>
          <li>
            <p>
              You will first receive a verification email asking you to verify your email and give permission to be added to our mailing list. This email will come from:
            </p><p>
              &quot;<?php echo $this->_tpl_vars['config']['site_title']; ?>
 [<?php echo $this->_tpl_vars['config']['admin_email_from']; ?>
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

            <p><span style="background-color: #FFFF00">If you don't receive a verification email,</span></strong> either your email provider is blocking our email, or your email reader is blocking our email. Please check your junk mail settings and folders to make sure you allow email from &quot;<?php echo $this->_tpl_vars['config']['admin_email_from']; ?>
&quot;.</p>

          </li><li>

            <p>As soon as you verify your email, you will receive your email subscriptions. What's more, you will be placed on our special &quot;insiders&quot; list where you'll receive web site updates, advance notice of special offers, and more.</p>

          </li>
        </ol>

        <p>Oh, and one more thing. Why do we send a request for verification by email? We do this to ensure that others don't use this form to send you unwanted email. It's to protect your privacy.</p>

        <p>
          Best regards always,<br />
          &nbsp;<br />
          <?php echo $this->_tpl_vars['config']['admin_email_name']; ?>

        </p>

      </td>
    </tr>
  </table>
  </div>
  <hr />
  <br />
<?php endif; ?>
