PHP_INCLUDE protect plugins.

Add cookie-based login for protected-pages. It can only protect
PHP files, of course. But it very useful to include payed
membership-support to your existsing PHP-based application.
Also it log access to special db table - you able to view it
later via admin interface.
This module also used in member.php to do authentication for 
members. So if you need to change something, you should create new 
plugin, based on this code.

Just include {$config.root_dir}/plugins/php_include/check.inc.php
in your PHP page as follows:
============================================================================
&lt;?php 
$_product_id = array(1,3); // or $_product_id = array(1) if it so
include("{$config.root_dir}/plugins/protect/php_include/check.inc.php"); 

.. your existing PHP code goes here
?&gt;
.. your html still here, if exists
============================================================================

If user is not authorized, this code will display login page :
(template: templates/login.html).

Yout MUST define $_product_id array to work. If you want to only check
access login/password then use it as : 
    $_product_id = array('ONLY_LOGIN');
It only check login/password pair. It will allow access for not-paid or expired members! 
You are warned!

User can use http://www.yoursite.com/aMember_Root/plugins/protect/php_include/logout.php
to logout. You can set location for redirect after logout in admin panel.

