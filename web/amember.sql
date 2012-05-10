###############################################################
#                                                             #
#                aMember MySQL DATABASE DUMP                  #
#                                                             #
#     This file is to use with amember/setup.php only         #
#     It cannot be loaded to MySQL as is.                     #
#                                                             #
###############################################################


# Table structure for table 'access_log'
CREATE TABLE @DB_MYSQL_PREFIX@access_log(
log_id int(11) NOT NULL auto_increment,
member_id int(11) NOT NULL,
time timestamp(14) NOT NULL,
url varchar(255) default NULL,
remote_addr varchar(15) default NULL,
referrer varchar(255) default NULL,
PRIMARY KEY (log_id),
INDEX (member_id, time, remote_addr),
INDEX (time)
)
;

#
# Table structure for table 'aff_clicks'
#
CREATE TABLE @DB_MYSQL_PREFIX@aff_clicks(
log_id int(11) NOT NULL auto_increment,
aff_id int(11) NOT NULL,
time timestamp(14) NOT NULL,
url varchar(255) default NULL,
remote_addr varchar(15) default NULL,
referrer varchar(255) default NULL,
PRIMARY KEY (log_id),
INDEX (aff_id, time, remote_addr),
INDEX (time)
)
;

#
# Table structure for table 'cron_run'
#

CREATE TABLE @DB_MYSQL_PREFIX@cron_run (
id int(11) NOT NULL default '0',
time datetime NOT NULL default '0000-00-00 00:00:00',
PRIMARY KEY (id)
);



#
# Table structure for table 'error_log'
#

CREATE TABLE @DB_MYSQL_PREFIX@error_log (
log_id int(11) NOT NULL auto_increment,
member_id int(11) default '0',
time timestamp(14) NOT NULL,
url varchar(255) default NULL,
remote_addr varchar(15) default NULL,
referrer varchar(255) default NULL,
error text,
PRIMARY KEY (log_id)
) ;



#
# Table structure for table 'members'
#

CREATE TABLE @DB_MYSQL_PREFIX@members (
member_id int(11) NOT NULL auto_increment,
login varchar(32) NOT NULL,
pass varchar(32) default NULL,
email varchar(64) default NULL,
name_f varchar(32) NOT NULL default '',
name_l varchar(32) NOT NULL default '',
street varchar(255) default NULL,
city varchar(255) default NULL,
state varchar(255) default NULL,
zip varchar(255) default NULL,
country varchar(255) default NULL,
is_male smallint(6) default NULL,
added datetime NOT NULL default '0000-00-00 00:00:00',
remote_addr varchar(15) default NULL,
data text NOT NULL,
PRIMARY KEY (member_id),
UNIQUE KEY login (login)
) ;



#
# Table structure for table 'payments'
#

CREATE TABLE @DB_MYSQL_PREFIX@payments (
payment_id int(11) NOT NULL auto_increment,
member_id int(11) NOT NULL ,
product_id int(11) NOT NULL ,
begin_date date NOT NULL ,
expire_date date NOT NULL ,
paysys_id varchar(32) NOT NULL default '',
receipt_id varchar(32) NOT NULL default '',
amount decimal(12,2) NOT NULL default '0.00',
completed smallint(6) default '0',
remote_addr varchar(15) NOT NULL default '',
data text,
time timestamp(14) NOT NULL,
PRIMARY KEY (payment_id),
KEY member_id (member_id)
) ;



#
# Table structure for table 'products'
#

CREATE TABLE @DB_MYSQL_PREFIX@products (
product_id int(11) NOT NULL auto_increment,
title varchar(255) NOT NULL default '',
description text,
price decimal(12,2) default NULL,
data text,
PRIMARY KEY (product_id)
) ; 


#### *** 1.9.1 ***
CREATE TABLE @DB_MYSQL_PREFIX@coupon (
  coupon_id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
  batch_id int(10) unsigned NOT NULL,
  code varchar(32) NOT NULL,
  comment varchar(64),
  discount varchar(32) NOT NULL,
  begin_date date,
  expire_date date,
  locked tinyint(3) unsigned NOT NULL,
  product_id varchar(255),
  use_count int(11),
  member_use_count int(11),
  used_count int(11),
  used_for text,
  data text,
  UNIQUE KEY code (code)
);

CREATE TABLE @DB_MYSQL_PREFIX@config (
  config_id int(11) NOT NULL auto_increment PRIMARY KEY,
  name varchar(64) NOT NULL,
  type smallint(6) default '0',
  value varchar(255) ,
  blob_value blob,
  UNIQUE KEY name (name)
);

CREATE TABLE @DB_MYSQL_PREFIX@aff_commission (
  commission_id int(11) NOT NULL auto_increment PRIMARY KEY,
  aff_id int NOT NULL,
  date DATE NOT NULL,
  amount DECIMAL(12,2),
  record_type char(16) NOT NULL,
  payment_id int NOT NULL,
  receipt_id char(32) NOT NULL,
  product_id int not null,
  is_first int not null,
  payout_id char(32),
  tier smallint default 1,
  INDEX aff_id (aff_id, date),
  UNIQUE KEY payment (payment_id, receipt_id, record_type, tier)
);

CREATE TABLE @DB_MYSQL_PREFIX@folders (
  folder_id int(11) NOT NULL auto_increment,
  path varchar(255) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  method varchar(64) default NULL,
  product_ids varchar(64) NOT NULL default '',
  files_content blob,
  PRIMARY KEY  (folder_id),
  UNIQUE KEY path (path),
  UNIQUE KEY url (url)
);

CREATE TABLE @DB_MYSQL_PREFIX@admin_log (
  log_id int(11) NOT NULL auto_increment,
  dattm datetime,
  admin_id int(11),
  ip varchar(32),
  tablename varchar(32) NOT NULL,
  record_id int(11) NOT NULL,
  message text,
  admin_login varchar(32) default NULL,
  PRIMARY KEY  (log_id)
);


CREATE TABLE @DB_MYSQL_PREFIX@admins (
  admin_id int(11) NOT NULL auto_increment,
  login varchar(32) NOT NULL default '',
  pass varchar(40) NOT NULL default '',
  last_login datetime default NULL,
  last_ip varchar(32) default NULL,
  last_session varchar(32) default NULL,
  email varchar(255) default NULL,
  super_user smallint(6) NOT NULL default '0',
  perms text,
  PRIMARY KEY  (admin_id),
  UNIQUE KEY login (login)
);

#### fill-in config values

REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('root_url', 0, '@ROOT_URL@', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('root_surl', 0, '@ROOT_SURL@', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('admin_email', 0, '@ADMIN_EMAIL@', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('generate_login', 0, '0', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('login_min_length', 0, '4', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('login_max_length', 0, '32', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('generate_pass', 0, '0', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('pass_min_length', 0, '4', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('pass_max_length', 0, '32', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('clear_access_log', 0, '1', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('clear_access_log_days', 0, '7', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('max_ip_count', 0, '5', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('max_ip_period', 0, '1440', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('select_multiple_products', 0, '0', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('multi_title', 0, 'Membership', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('use_coupons', 0, '0', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('date_format', 0, '%m/%d/%Y', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('time_format', 0, '%m/%d/%Y %H:%M:%S', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('send_signup_mail', 0, '1', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('mail_expire', 0, '0', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('mail_expire_days', 0, '1', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('use_address_info', 0, '0', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('unique_email', 0, '1', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('curl', 0, '@CURL_PATH@', '');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('profile_fields', 1, '', 'a:4:{i:0;s:5:"pass0";i:1;s:6:"name_f";i:2;s:6:"name_l";i:3;s:5:"email";}');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('license', 2, '', '@LICENSE@');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('plugins.payment', 1, '', '@PAYMENT_PLUGINS@');
REPLACE INTO @DB_MYSQL_PREFIX@config 
(name,type,value,blob_value) VALUES ('plugins.protect', 1, '', '@PROTECT_PLUGINS@');
REPLACE INTO @DB_MYSQL_PREFIX@config
(name,type,value,blob_value) VALUES ('lang.list', 1, '', 'a:1:{i:0;s:10:"en:English";}');
REPLACE INTO @DB_MYSQL_PREFIX@admins 
    (login, pass, email, super_user)
VALUES
    ('@ADMIN_LOGIN@', '@ADMIN_PASS@', '@ADMIN_EMAIL@', 1);

MODIFY @DB_MYSQL_PREFIX@members FIELD status smallint NOT NULL DEFAULT 0;
MODIFY @DB_MYSQL_PREFIX@members FIELD aff_id int;
MODIFY @DB_MYSQL_PREFIX@members FIELD is_affiliate tinyint(4);
MODIFY @DB_MYSQL_PREFIX@members FIELD aff_payout_type varchar(32);
MODIFY @DB_MYSQL_PREFIX@members FIELD unsubscribed tinyint(4) DEFAULT 0;
MODIFY @DB_MYSQL_PREFIX@members FIELD email_verified tinyint(4) DEFAULT 0;
### add affiliate id field if it don't exists
MODIFY @DB_MYSQL_PREFIX@payments FIELD aff_id int NOT NULL;
### add payer_id field if it don't exists
MODIFY @DB_MYSQL_PREFIX@payments FIELD payer_id VARCHAR(255) NOT NULL;
### add indexes
MODIFY @DB_MYSQL_PREFIX@payments INDEX payer_id (payer_id);
MODIFY @DB_MYSQL_PREFIX@admin_log INDEX al (tablename, record_id);
MODIFY @DB_MYSQL_PREFIX@aff_commission INDEX ac (date);

### 1.9.3RC..
##
MODIFY @DB_MYSQL_PREFIX@coupon FIELD use_count int NOT NULL DEFAULT 0;
MODIFY @DB_MYSQL_PREFIX@coupon FIELD member_use_count int NOT NULL DEFAULT 0;
MODIFY @DB_MYSQL_PREFIX@coupon FIELD used_count int NOT NULL DEFAULT 0;
MODIFY @DB_MYSQL_PREFIX@payments FIELD coupon_id int NOT NULL;
MODIFY @DB_MYSQL_PREFIX@payments INDEX coupon_id (coupon_id);
MODIFY @DB_MYSQL_PREFIX@payments FIELD tm_added datetime NOT NULL;
MODIFY @DB_MYSQL_PREFIX@payments INDEX tm_added (tm_added, product_id);
MODIFY @DB_MYSQL_PREFIX@payments FIELD tm_completed datetime NULL;
MODIFY @DB_MYSQL_PREFIX@payments INDEX tm_completed (tm_completed, product_id);

CREATE TABLE @DB_MYSQL_PREFIX@failed_login (
  failed_login_id int(11) NOT NULL auto_increment,
  ip char(15) NOT NULL,
  login_type int(11) NOT NULL,
  failed_logins int(11) NOT NULL,
  last_failed int(11) NOT NULL,
  PRIMARY KEY  (failed_login_id),
  UNIQUE KEY ip (ip, login_type)
);

## 300 ##
MODIFY @DB_MYSQL_PREFIX@members FIELD security_code VARCHAR(40);
MODIFY @DB_MYSQL_PREFIX@members FIELD securitycode_expire DATETIME;
REPLACE INTO @DB_MYSQL_PREFIX@config (name,type,value) VALUES ('auto_login_after_signup', 0, '1');

CREATE TABLE @DB_MYSQL_PREFIX@newsletter_thread (
  thread_id int(10) unsigned NOT NULL auto_increment,
  title varchar(60) NOT NULL default '',
  description text,
  is_active smallint NOT NULL default 1,
  blob_available_to blob default NULL,
  blob_auto_subscribe blob default NULL,
  PRIMARY KEY  (thread_id)
);

CREATE TABLE @DB_MYSQL_PREFIX@newsletter_archive (
  archive_id int(10) unsigned NOT NULL auto_increment,
  threads varchar(255) NOT NULL default '',
  subject varchar(255) NOT NULL default '',
  message text,
  add_date datetime,
  PRIMARY KEY  (archive_id)
);

CREATE TABLE @DB_MYSQL_PREFIX@newsletter_guest (
  guest_id int(10) unsigned NOT NULL auto_increment,
  guest_name varchar(60) NOT NULL default '',
  guest_email varchar(60) NOT NULL default '',
  PRIMARY KEY  (guest_id)
);


CREATE TABLE @DB_MYSQL_PREFIX@newsletter_guest_subscriptions (
  guest_subscription_id int(10) unsigned NOT NULL auto_increment,
  guest_id int(10) unsigned NOT NULL,
  thread_id int(10) unsigned NOT NULL,
  PRIMARY KEY  (guest_subscription_id)
);

CREATE TABLE @DB_MYSQL_PREFIX@newsletter_member_subscriptions (
  member_subscription_id int(10) unsigned NOT NULL auto_increment,
  member_id int(10) unsigned NOT NULL,
  thread_id int(10) unsigned NOT NULL,
  PRIMARY KEY  (member_subscription_id)
);

CREATE TABLE @DB_MYSQL_PREFIX@email_templates (
  email_template_id int(11) NOT NULL auto_increment,
  name varchar(32) NOT NULL default '',
  lang varchar(16) NOT NULL default '',
  format enum('text','html','multipart') NOT NULL default 'text',
  subject varchar(255) NOT NULL default '',
  txt mediumtext NOT NULL,
  plain_txt mediumtext,
  attachments mediumtext,
  product_id int(11) default NULL,
  `day` int(11) default NULL,
  PRIMARY KEY  (email_template_id)
);

## insert e-mail templates
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (1, 'verify_email', 'en', 'text', '{$config.site_title} - Account Verification', 'Hello {$user.name_f} {$user.name_l},\r\n\r\nYou (or someone else) has just registered an account on {$config.site_title}.\r\nClicking on the link below will activate the account:\r\n{$url}\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (2, 'send_pending_email', 'en', 'text', 'Pending Payment', 'Dear {$name_f} {$name_l},\r\n\r\nThank you for signup. Your payment status is PENDING.\r\nPlease complete payment as described.\r\n\r\n   Your User ID: {$login}  \r\n   Your Password: {$pass}\r\n\r\nYour may log-on to your member pages at:\r\n{$config.root_url}/member.php\r\nand check your subscription status.\r\n\r\nBest Regards,\r\nSite Team\r\n', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (3, 'mail_not_completed', 'en', 'text', '{$config.site_title} - Signup is not finished', 'Dear {$name_f} {$name_l},\r\n\r\nThank you for signup! Unfortunately, you have not finished\r\npayment yet. If you have any troubles with payment,\r\ndon''t hesitate to contact us using the following \r\nemail address: {$config.admin_email}.\r\n\r\nDespite the fact that your payment is not completed,\r\na customer record has been created for you. \r\nYou may login to members area using the following \r\nURL :\r\n  {$config.root_url}/member.php\r\nYou member details are:\r\n   Your User ID: {$login}  \r\n   Your Password: {$pass}\r\n\r\nAfter logging-in, you may use "Add/Renew Subscription" controls\r\nto complete your payment.\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, 1);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (4, 'send_signup_mail', 'en', 'text', '{$config.site_title} - Membership Information', 'Dear {$name_f} {$name_l},\r\n\r\nThank you for subscribing on {$config.site_title}!\r\n\r\n   Your User ID: {$login}  \r\n   Your Password: {$pass}\r\n\r\nLog-on to your member pages at:\r\n{$config.root_url}/member.php\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (5, 'send_payment_mail', 'en', 'text', '{$config.site_title} - Payment Receipt', 'Thank you for your order. You may find order details below:\r\n\r\nYour Invoice Number {$payment.payment_id} from {$payment.tm_completed|date_format:$config.date_format}\r\n\r\n{"Product/Subscription Title"|string_format:"%-50s"} Price\r\n-----------------------------------------------------------\r\n{foreach from=$products item=p}\r\n{$p.title|string_format:"%-50s"} {$config.currency|default:"$"}{$p.price}\r\n{/foreach}\r\n-----------------------------------------------------------\r\n{"Subtotal"|string_format:"%-50s"} {$config.currency|default:"$"}{$subtotal|string_format:"%.2f"}\r\n{if $payment.data.COUPON_DISCOUNT ne "" }\r\n{"Discount"|string_format:"%-50s"} {$config.currency|default:"$"}{$payment.data.COUPON_DISCOUNT|string_format:"%.2f"}\r\n{/if}\r\n{if $payment.data.TAX_AMOUNT ne ""}\r\n{"Tax Amount"|string_format:"%-50s"} {$config.currency|default:"$"}{$payment.data.TAX_AMOUNT|string_format:"%.2f"}\r\n{/if}\r\n-----------------------------------------------------------\r\n{"Total"|string_format:"%-50s"} {$config.currency|default:"$"}{$total|string_format:"%.2f"}\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (6, 'send_payment_admin', 'en', 'text', '{$config.site_title} *** New Payment', 'New payment completed:\r\n    Product:    {$product.title}\r\n    Amount:     {$config.currency|default:"$"}{$payment.amount}\r\n    Period:     {$payment.begin_date|date_format:$config.date_format} - {$payment.expire_date|date_format:$config.date_format}\r\n\r\n  User details:\r\n    Username:   {$user.login}\r\n    Email:      {$user.email}\r\n    Name:       {$user.name_f} {$user.name_l}\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (7, 'mail_expire', 'en', 'text', '{$config.site_title} - Your Subscription Expires', 'Dear {$name_f} {$name_l},\r\n\r\nYour subscription on {$config.site_title} expires  {$payment.expire_date|date_format:"%m/%d/%Y"}.\r\n\r\nPlease log-on to membership information page at:\r\n{$config.root_url}/member.php\r\nand renew your subscription.\r\n\r\nYour login details:\r\n   Your User ID:  {$login}  \r\n   Your Password: {$pass}\r\n\r\nThank you for your attention!\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, 1);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (8, 'mail_cancel_admin', 'en', 'text', '{$config.site_title} - User subscription cancelled', 'User {$user.login}, "{$user.name_f} {$user.name_l}" <{$user.email}>\r\nhas cancelled recurring subscription #{$payment.payment_id} to product\r\n{$product.title}.\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (9, 'mail_cancel_member', 'en', 'text', '{$config.site_title} - Subscription cancelled', 'Dear {$user.name_f} {$user.name_l},\r\n\r\nYour subscription to "{$product.title}" cancelled. Feel free to subscribe \r\nagain, you can do it here:\r\n  {$config.root_url}/member.php\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (10, 'cc_rebill_failed', 'en', 'text', '{$config.site_title} Subscription Renewal Failed', 'Hello {$user.name_f} {$user.name_l},\r\n\r\nYour subscription was not renewed automatically by membership system\r\ndue to payment failure: "{$error}".\r\n\r\n{* If subscription has been pro-rated. *}\r\n{if $new_expire ne "" }\r\nBilling attempt will be automatically repeated {$new_expire|date_format:$config.date_format},\r\nplease add funds to your card or update your credit card information.\r\n{/if}\r\n\r\nYou may update your credit card info here: \r\n{$config.root_url}/member.php\r\n\r\nThank you for attention!\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (11, 'cc_rebill_success', 'en', 'text', '{$config.site_title} Subscription Renewed', 'Hello {$user.name_f} {$user.name_l},\r\n\r\nYour subscription has been renewed automatically by our membership system.\r\nYour credit card was charged on ${$payment.amount}.\r\n\r\nNext renewal date: {$payment.expire_date|date_format:$config.date_format}\r\n\r\nYou may login to membership info page at :\r\n{$config.root_url}/member.php\r\n\r\nThank you!\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (12, 'card_expires', 'en', 'text', 'Credit Card Expiration', 'Hello {$user.name_f} {$user.name_l},\r\n\r\nYour credit card that we have on file for recurring billing will expire\r\non {$expires}. Next recurring billing for "{$product.title}"\r\nis sheduled for {$payment.expire_date|date_format:$config.date_format}.\r\n\r\nTo avoid any interruption of your subscription, please visit page\r\n{$config.root_url}/member.php\r\nand update your credit card information. \r\n\r\nThank you!\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (13, 'aff.mail_sale_admin', 'en', 'text', '** Affiliate Commission', 'Dear admin,\r\n\r\nNew sale has been made with using of affiliate link.\r\nYou may find sale details below:\r\n\r\n----\r\nAffilate: {$affiliate.member_id} / {$affiliate.login} / {$affiliate.email} \r\n          {$affiliate.name_f} {$affiliate.name_l} / {$affiliate.remote_addr}\r\nNew Member: {$user.member_id} / {$user.login} / {$user.email} \r\n          {$user.name_f} {$user.name_l} / {$user.remote_addr}\r\nPayment REF: {$payment.payment_id}\r\nTotal:       {$payment.amount}\r\nProduct ordered: {$product.title}\r\nCommission paid: {$commission}\r\n----\r\n\r\n', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (14, 'aff.mail_sale_user', 'en', 'text', '** Affiliate Sale', 'Dear {$affiliate.name_f} {$affiliate.name_l},\r\n\r\nNew sale has been made by your affiliate link and \r\ncommission credited to your balance. You may find \r\nsale details below:\r\n\r\n----\r\nPayment REF: {$payment.payment_id}\r\nTotal:       {$payment.amount}\r\nProduct ordered: {$product.title}\r\nYour commission: {$commission}\r\n----\r\n\r\n', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (15, 'max_ip_actions', 'en', 'text', '*** Account Sharing Violation Detected', 'There is an automated message from aMember Pro script. \r\nAn account violation has been detected for the following\r\ncustomer:\r\n  User details:\r\n    Username:   {$user.login}\r\n    Email:      {$user.email}\r\n    Name:       {$user.name_f} {$user.name_l}\r\n{if $config.max_ip_actions ne "2"}\r\nCustomer account has been automatically locked.\r\n{/if}\r\n    \r\nIt has reached configured limit of {$config.max_ip_count} IP within\r\n{$config.max_ip_period} minutes. Please login into aMember CP and review\r\nhis access log. \r\n\r\nIf it looks like a script mistake, you may disable sharing violation checking \r\nfor this user in the "User Profile", or disable it globally at aMember CP -> \r\nSetup -> Advanced by setting "Max Number of IP" to something like "99999".\r\n    \r\n--\r\nYour aMember Pro script \r\n{$config.root_url}/admin/', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (16, 'manually_approve', 'en', 'text', '{$config.site_title} Your signup is pending', 'Dear {$name_f} {$name_l},\r\n\r\nThank you for subscribing!\r\n\r\nWe review all payments manually, so your payment \r\nstatus is pending. You will receive email when your\r\naccount will be approved. Thank you for your patience.\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (17, 'verify_guest', 'en', 'text', '{$config.site_title} - Newsletter Subscription', 'Dear {$name},\r\n\r\nYou signed up for a guest subscription on {$config.site_title}.\r\n\r\nTo confirm subscription please follow a link:\r\n    {$link}\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (18, 'send_pass', 'en', 'text', '{$config.site_title} - Lost Password', 'Dear {$name_f} {$name_l},\r\n\r\nYou have requested your member log-in information.\r\n\r\n     Your User ID:  {$login}\r\n\r\n     Your Password: {$pass}\r\n\r\nLog-in to member pages at:\r\n    {$config.root_url}/member.php\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (19, 'send_security_code', 'en', 'text', 'Your Lost Password', 'Dear {$name_f} {$name_l},\r\n\r\nYou have requested your member log-in information.\r\n\r\n     Your User ID:  {$login}\r\n\r\nFollow a link below to change your password:\r\n  {$config.root_url}/sendpass.php?s={$code}\r\n    \r\nThis link will be active {$hours} hour(s).\r\n\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);


MODIFY @DB_MYSQL_PREFIX@newsletter_guest FIELD security_code VARCHAR(40);
MODIFY @DB_MYSQL_PREFIX@newsletter_guest FIELD securitycode_expire DATETIME;
MODIFY @DB_MYSQL_PREFIX@newsletter_guest_subscriptions FIELD security_code VARCHAR(40);
MODIFY @DB_MYSQL_PREFIX@newsletter_guest_subscriptions FIELD securitycode_expire DATETIME;

## 301 ##
MODIFY @DB_MYSQL_PREFIX@payments FIELD tax_amount decimal(12,2) NOT NULL default '0.00';
MODIFY @DB_MYSQL_PREFIX@newsletter_archive FIELD is_html smallint NOT NULL default 0;

MODIFY @DB_MYSQL_PREFIX@folders FIELD product_ids text NOT NULL;

## 308 ##
MODIFY @DB_MYSQL_PREFIX@admins FIELD pass varchar(128) NOT NULL default '';

CREATE TABLE @DB_MYSQL_PREFIX@countries (
    country_id INT NOT NULL auto_increment PRIMARY KEY,
    country char(2) NOT NULL,
    title varchar(64),
    tag int default NULL,
    UNIQUE (country)
);

CREATE TABLE @DB_MYSQL_PREFIX@states (
    state_id int NOT NULL auto_increment PRIMARY KEY,
    state char(12) NOT NULL,
    country char(2) NOT NULL,
    title varchar(64),
    UNIQUE(state),
    INDEX (country)
);

MODIFY @DB_MYSQL_PREFIX@coupon FIELD is_recurring smallint not null default 0;
MODIFY @DB_MYSQL_PREFIX@aff_commission FIELD tier smallint default 1;
MODIFY @DB_MYSQL_PREFIX@aff_commission UNIQUE INDEX payment (payment_id, receipt_id, record_type, tier);
MODIFY @DB_MYSQL_PREFIX@newsletter_member_subscriptions FIELD status smallint NOT NULL default 1;

## 311 ##
MODIFY @DB_MYSQL_PREFIX@countries FIELD status ENUM( 'added', 'changed' ) default NULL;
MODIFY @DB_MYSQL_PREFIX@states FIELD status ENUM( 'added', 'changed' ) default NULL;
MODIFY @DB_MYSQL_PREFIX@states FIELD tag int default 0;


# Table structure for table 'products_links' for incremental content plugin
CREATE TABLE @DB_MYSQL_PREFIX@products_links(
    link_id int(11) NOT NULL auto_increment,
    link_product_id int(11) NOT NULL,
    link_start_delay varchar(10) NOT NULL,
    link_duration varchar(10) NOT NULL,
    link_url varchar(255) default NULL,
    link_title varchar(255) default NULL,
    link_path varchar(255) default NULL,
    link_protected smallint(6) default '0',
    time timestamp(14) NOT NULL,
    PRIMARY KEY (link_id),
    INDEX (link_product_id),
    INDEX (time)
);

MODIFY @DB_MYSQL_PREFIX@products_links FIELD link_group_id int default 0;

# Table structure for table 'products_links_groups'
CREATE TABLE @DB_MYSQL_PREFIX@products_links_groups (
    id int(11) NOT NULL auto_increment,
    title varchar(255) NOT NULL ,
    description varchar(255) default NULL ,
    priority int(11) default '0',
    PRIMARY KEY (id)
);

## 313 ##
CREATE TABLE @DB_MYSQL_PREFIX@rebill_log (
	rebill_log_id int not null auto_increment PRIMARY KEY,
    added_tm datetime not null,
    payment_date date not null,
    payment_id int not null,
	rebill_payment_id int not null,
	amount decimal(12,2) not null,
	status smallint default null,
	status_tm datetime,
    status_msg varchar(255),
	index (payment_id)
);


## 314 ## 

MODIFY @DB_MYSQL_PREFIX@coupon FIELD member_id int not null default 0;

## 319 ##

MODIFY @DB_MYSQL_PREFIX@products_links FIELD link_comment text NOT NULL;
MODIFY @DB_MYSQL_PREFIX@products_links FIELD link_files_path text NOT NULL;

## 320 ##

INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (20, 'verify_email_profile', 'en', 'text', '{$config.site_title} - Email  Verification', 'Hello {$user.name_f} {$user.name_l},\r\n\r\nYou (or someone else) has just changed email address in your account on {$config.site_title}.\r\nClicking on the link below will approve this change:\r\n\r\n{$url}\r\n\r\nPlease note that email address will be changed in your profile  only if you will click on above link.\r\nIf you didn\'t request email address change just disregard this message.\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);
INSERT INTO @DB_MYSQL_PREFIX@email_templates VALUES (21, 'aff.mail_signup_user', 'en', 'text', '{$config.site_title} - Affiliate  Information', 'Dear {$name_f} {$name_l},\r\n\r\nThank you for signup to affiliate programm on {$config.site_title}!\r\n\r\n   Your User ID: {$login}  \r\n   Your Password: {$pass}\r\n\r\nLog-on to your affiliate area at:\r\n{$config.root_url}/aff_member.php\r\nYour affiliate link:\r\n{$config.root_url}/go.php?r={$user.member_id}\r\n--\r\nBest Regards,\r\n{$config.site_title}\r\n{$config.root_url}', '', '', NULL, NULL);


## ALL ##
### keep this last line, don't forget to increase db_version in common.inc.php 
REPLACE INTO @DB_MYSQL_PREFIX@config (name,type,value) VALUES ('db_version', 0, '320');
###