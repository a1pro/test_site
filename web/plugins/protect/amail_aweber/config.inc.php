<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) die("Direct access to this location is not allowed");

/**
 *
 * Copyright (C) 2010 Kencinnus, LLC. All rights reserved.
 *
 * This file may not be distributed by anyone outside of
 * Kencinnus, LLC or authorized contractors as specified.
 *
 * Purchasers of this plugin can modify it for the site
 * it is installed on.
 *
 * This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
 * THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE.
 *
 * All Support Questions regarding this plugin should be sent to http://kencinnus.com/contact
 *
 * (See amail_aweber.inc.php for revision history.)
 *
 **/

$notebook_page = 'aMail for AWeber';

config_set_notebook_comment($notebook_page, 'aMail for AWeber v3.2.3.2');

if (file_exists($rm = dirname(__FILE__)."/readme.txt")) config_set_readme($notebook_page, $rm);

add_config_field('protect.amail_aweber.debug',
                 'Debug?',
                 'checkbox',
                 'Write debug statements to log file.',
                 $notebook_page,
                 ''
                );

add_config_field('protect.amail_aweber.listname',
                 'Primary Default AWeber List Name',
                 'text',
                 'e.g.: If your list is yourlistname@aweber.com then just enter <strong>yourlistname</strong>.',
                 $notebook_page,
                 ''
                );

add_config_field('protect.amail_aweber.sendsignup',
                 'Send Signup Information?',
                 'checkbox',
                 'Do you want the member username and password sent to AWeber?',
                 $notebook_page,
                 ''
                );


add_config_field('protect.amail_aweber.sendaddress',
                 'Send Address Information?',
                 'checkbox',
                 'Do you want the member street, city, state, zip and country information sent to AWeber?',
                 $notebook_page,
                 ''
                );

add_config_field('protect.amail_aweber.sendcustom',
                 'Send Additional Fields?',
                 'text',
                 'Do you want the additional fields you set up for members to be sent to AWeber?<br />Include a comma separated list of internal field names here.',
                 $notebook_page,
                 ''
                );


add_config_field('protect.amail_aweber.ccadmin',
                 'CC Admin?',
                 'checkbox',
                 'Do you want a copy of every email sent to AWeber also sent to your admin email address?',
                 $notebook_page,
                 ''
                );

add_config_field('protect.amail_aweber.ccguest',
                 'CC Guest?',
                 'text',
                 'Do you want a copy of every email sent to AWeber also sent to someone else?',
                 $notebook_page,
                 ''
                );

add_config_field('protect.amail_aweber.noremove',
                 'Do Not Unsubscribe?',
                 'checkbox',
                 'If you do NOT want to keep AWeber in sync with the aMember unsubscribed flags check this box.',
                 $notebook_page,
                 ''
                );

add_config_field('protect.amail_aweber.donotsend',
                 'Do Not Subscribe OR Unsubscribe?',
                 'checkbox',
                 'If you do NOT want to send AWeber ANY emails check this box.<br />Subscribe/Unsubscribe emails will go to admin instead.',
                 $notebook_page,
                 ''
                );

add_config_field('protect.amail_aweber.rebuilddb',
                 'Do Database Rebuild?',
                 'checkbox',
                 'Leave this unchecked unless you want to resend all subscribes/unsubscribes during Rebuild DB command.<br />WARNING: It will re-subscribe people who did not confirm over 30 days ago!',
                 $notebook_page,
                 ''
                );

add_config_field('protect.amail_aweber.docron',
                 'Do Cron Unsubscribes?',
                 'checkbox',
                 'Do you want to unsubscribe expired members when the daily cron job is run?',
                 $notebook_page,
                 ''
                );

add_config_field('protect.amail_aweber.thanksheader',
                 'Thank You Page Template',
                 'header','',$notebook_page
                );

add_config_field('protect.amail_aweber.thankstitle',
                 'Thank You Page Title',
                 'text',
                 'This will be the title on the thank you page to get them to look at the opt-in information.',
                 $notebook_page,'','','',
                 array('default' => 'But wait! You\'re not done yet...')
                );


$buffer = '

<div id="thanksmail">
  <table cellpadding="5" align="center" width="60%">
    <tr>
      <td align="left">

        <h2>But Wait [[first_name]]!  You\'re Not Done Yet...</h2>

        <p>Hello [[first_name]], we need you to confirm your email address ([[email]]) in order for you to receive your email subscriptions. Please check your email in a few moments.</p>

        <ol>
          <li>
            <p>
              You will first receive a verification email asking you to verify your email and give permission to be added to our mailing list. This email will come from:
            </p><p>
              &quot;[[site_title]] [[[admin_email]]]&quot;
            </p><p>
              And it will look something like this:
            </p>

            <!-- Start Sample Email Div -->
            <div style="border:1px solid black;width:500px;margin:0 auto 0 auto;padding:20px;font-family:\'Courier New\', Courier, monospace;font-size:12px;">
              <p>
                <u><strong>Subject</strong></u><br />
                Response Required, confirm your request for information from [[site_title]].
              </p><p>
                <strong><u>Message</u></strong><br />
              </p><p>
                We received your request for information from [[site_title]]<br />
                Before we begin sending you the information you<br />
                requested, we want to be certain we have your permission.<br />
                ------------------------------------------------ <br />
                CONFIRM BY VISITING THE LINK BELOW: <br />
                <br />
                https://www.aweber.com/z/c/?xxxxxxxx <br />
                <br />
                Click the link above to give us permission to send you<br />
                information. It\'s fast and easy! If you cannot click the<br />
                full URL above, please copy and paste it into your web<br />
                browser. <br />
                -------------------------------------------------- <br />
                If you do not want to confirm, simply ignore this message.  <br />
                <br />
                Thank You, <br />
                <br />
                [[site_title]]
              </p>
            </div>
            <!-- End Sample Email Div -->

            <p><strong>Click on the verification link in that email when you receive it.</strong></p>

            <p><span style="background-color: #FFFF00">If you don\'t receive a verification email,</span></strong> either your email provider is blocking our email, or your email reader is blocking our email. Please check your junk mail settings and folders to make sure you allow email from &quot;[[admin_email]]&quot;.</p>

          </li><li>

            <p>As soon as you verify your email, you will receive your email subscriptions. What\'s more, you will be placed on our special &quot;insiders&quot; list where you\'ll receive web site updates, advance notice of special offers, and more.</p>

          </li>
        </ol>

        <p>Oh, and one more thing. Why do we send a request for verification by email? We do this to ensure that others don\'t use this form to send you unwanted email. It\'s to protect your privacy.</p>

        <p>
          Best regards always,<br />
          &nbsp;<br />
          [[admin_name]]
        </p>

      </td>
    </tr>
  </table>
  </div>
  <hr />
  <br />

';

add_config_field('protect.amail_aweber.thanksmessage',
                 'Thank You Page Message',
                 'textarea',
                 "Enter the HTML for the opt-in message on the thank you page.<br />You can use variable substitution.  See below.",
                 $notebook_page,'','','',array('store_type' => 1,'default'=>$buffer)
                );

?>
