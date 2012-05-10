<b>HTPASSWD protect plugin</b>

Password protect directories with this plugin.
It generates .htpasswd and .htgroup files in directory 
{$config.root_dir}/data. You should put the following code into 
directory you want to protect.

<b>If you have only one level of access, use the following .htaccess file:</b>
<div style="color: #a00000">
AuthType Basic
AuthName "Members Only"
AuthUserFile {$config.data_dir}/.htpasswd
AuthGroupFile {$config.data_dir}/.htgroup
Require valid-user
</div>

<b>To allow access to directory for customers of specific products only,
use the following code.</b>
<div style="color: #a00000">
AuthType Basic
AuthName "Members Only"
AuthUserFile {$config.data_dir}/.htpasswd
AuthGroupFile {$config.data_dir}/.htgroup
Require group PRODUCT_1 PRODUCT_3
</div>
With this code, only customers who have active subscription to
membership types #1 and/or #3 will be allowed to enter into directory.

You may want to add line like:
<div style="color: #a00000">
ErrorDocument 401 /401.htm
</div>
to show page http://www.yoursite.com/401.htm for unauthorized 
customer and redirect them to signup page.
