<b>mod_auth_mysql plugin</b>

This plugin will maintain table of active members for mod_auth_mysql 
Apache module ( http://www.diegonet.com/support/mod_auth_mysql.shtml ). 

INSTALLATION
 1. Please create users table as described in the mod_auth_mysql manual.

 CREATE TABLE user_info (
  username CHAR(30) NOT NULL,
  passwd CHAR(20) NOT NULL,
  groups CHAR(10),
  PRIMARY KEY (user)
 );


 2. Enable this plugin at aMember CP -> Setup -> Plugins
 3. Go to aMember CP -> Setup -> mod_auth_mysql and configure this plugin.
In your .htaccess files you can use the following lines:

<ul>
<li><i>to allow access for all active customers</i>
<div style="color: #a00000">
Require valid-user 
</div>
</li>

<li><i>to allow access for customers of products #1 and/or product #3</i>
<div style="color: #a00000">
Require group PRODUCT_1 PRODUCT_3
</div>
With this code, only customers who have active subscription to
membership types #1 and/or #3 will be allowed to enter into directory.
You can always see actual product numbers at aMember CP -> Edit Products.
</li>
</ul>
You may also want to add line like:
<div style="color: #a00000">
ErrorDocument 401 /401.htm
</div>
to show page http://www.yoursite.com/401.htm for unauthorized 
customer and redirect them to signup page.
