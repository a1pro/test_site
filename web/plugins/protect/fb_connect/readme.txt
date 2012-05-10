                   Facebook Connect Notes  v1.4
                     
----------------------------------------------------------------------------
REQUIREMENTS:
  aMember 3.1.2 or later
  PHP Version 5.2.0 or later
  CURL and JSON support (Look for these words in aMember CP -> Version Info)
NB: Step 3 onwards is designed to be read via the amember plugin setup screen
----------------------------------------------------------------------------
INSTALLATION:

1. Copy plugin files to amember/plugins/protect/fb_connect/ folder

2. Enable plugin at aMember CP -> Setup -> Plugins 

***************************************************************************************
* NB: The remaining steps are designed to be read via the amember plugin setup screen *
***************************************************************************************

3. Create a Facebook Application at: http://developers.facebook.com/setup/
   NB: Site URL is {$config.root_url}
   
4. Now edit the settings of your Facebook Application and set the 'Site Domain'
	a. Go here: http://www.facebook.com/developers/apps.php 
	b. Click to 'Edit settings'
	c. Click the 'Web Site' Tab
	d. Set the 'Site Domain' (e.g. amember.com)
    
	
5. Change settings at aMember CP -> Setup -> Facebook Connect

***************************************************************************************
* NB: Step 6 is only needed for aMember versions 3.2.2 or below,                      *
* or where you want custom integration of button in your login.html template          *
***************************************************************************************

6. Update your /templates/login.html page.
    
    This step adds a connect button and information to your login page.
	NB: This is something you will have to do each time you upgrade aMember.
    
    a. Edit your /amember/templates/login.html template file and and insert this line wherever you 
	   would like the Facebook Login Button to appear:
	   
	   {include file="../plugins/protect/fb_connect/login.fb_connect.inc.html"}
	   
	   HINT: to place it just under the login box, insert it just ABOVE these lines:
    
       &lt;br /&gt;
       &lt;p&gt;&#35_TPL_LOGIN_NOT_REGISTERED_YET&#35 &lt;a href="{&#36config.root_url}/
	   {if &#36affiliates_signup}aff_{/if}signup.php"&gt;&#35_TPL_LOGIN_SIGNUP_HERE&#35&lt;/a&gt;&lt;/p&gt;

    b. Save your changes.
    
    c. Upload /amember/templates/login.html to your server.
	
***************************************************************************************
* NB: Step 7 is only needed for aMember versions 3.2.2 or below,                      *
* or where you want custom integration of button in your signup.html template         *
***************************************************************************************

7. Update your /templates/signup.html page.
    
    THIS STEP IS OPTIONAL - it adds a connect button to your signup page.
	NB: This is something you will have to do each time you upgrade aMember.
    
    a. Edit your /amember/templates/signup.html template file and insert this line wherever you 
	   would like the Facebook login button to appear:
    
      {include file="../plugins/protect/fb_connect/signup.fb_connect.inc.html"}
       
    b. Save your changes.
    
    c. Upload /amember/templates/login.html to your server.
	
***************************************************************************************
*            NB: Step 8 is only needed for aMember versions 3.2.2 or below            *
***************************************************************************************

8. AMEMBER VERSIONS 3.2.2 AND BELOW ONLY: Update your /templates/header.html page.
    
    a. Edit your /amember/templates/header.html template file and locate this 
       line:
    
       &lt;body&gt;
    
    b. Insert this line below:
       
       {include file="../plugins/protect/fb_connect/header.fb_connect.inc.html"}
       
    c. Find this line:
    
    	&lt;html xmlns="http&#58//www.w3.org/1999/xhtml"&gt;
    	
    d. Change it to:
    
    	&lt;html xmlns="http&#58//www.w3.org/1999/xhtml" xmlns:fb="http&#58//www.facebook.com/2008/fbml"&gt;
       
    e. Save your changes.
    
    f. Upload /amember/templates/header.html to your server.
	

<strong>Advanced notes / Troubleshooting:</strong>
1) You can optionally edit your Facebook application and add logos etc to it here:
http://www.facebook.com/developers/apps.php

2) Make sure that your 'Redirect After Logout' setting in the PHP Include plugin doesn't
redirect to the login page or any aMember protected page, otherwise Facebook will simply
log the member back in again if member tries logging out with the aMember logout option.
If this is a problem on your site, use the 'Force Facebook Logout' option.

3) If you get an <em>'Invalid argument: The Facebook Connect cross-domain receiver 
URL must have the application's Connect URL'</em> message, this means that you have
not set your Site URL correctly. If so:
	a. Go here: http://www.facebook.com/developers/apps.php 
	b. 'Edit settings' -> 'Web Site' Tab
	d. Set the 'Site URL' to: {$config.root_url}
	
4) Facebook allows only one Site Domain per application, so if your aMember install
uses SSL on a shared certificate (i.e. SSL is not on your own domain), you will need
to set the secure url as your Facebook Application Connect URL and make sure that
ALL links to amember use the secure url:
{$config.root_surl}

5) Some other plugins (e.g. Drupal) may cause the aMember database connection
to be overwritten and this may stop this plugin installing itself correctly. If
the 'SQL Field Installed' option above is not automatically ticked, please
disable all other plugins temporarily to allow this plugin to install. As soon
as the SQL field is installed, you can re-enable the other plugins.

----------------------------------------------------------------------------

 Facebook Connect
 Copyright 2010 (c) R Woodgate
                    All Rights Reserved

----------------------------------------------------------------------------
