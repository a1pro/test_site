                   OpenID Notes  v1.1
                     
----------------------------------------------------------------------------
REQUIREMENTS:
  aMember 3.1.2 or later
  PHP Version 5.2.0 or later
  CURL support (Look for this word in aMember CP -> Version Info)
NB: Step 4 onwards is designed to be read via the amember plugin config screen
----------------------------------------------------------------------------
INSTALLATION:

1. Copy plugin files to amember/plugins/protect/openid/ folder

2. Enable plugin at aMember CP -> Setup -> Plugins 

3. Change settings at aMember CP -> Setup -> OpenID

4. Update your /templates/header.html page.
    
    NB: This is something you will have to do each time you upgrade aMember.
    
    a. Edit your /amember/templates/header.html template file and locate this 
       line:
    
       &lt;/head&gt;
    
    b. Insert this line ABOVE:
       
       {include file="../plugins/protect/openid/header.openid.inc.html"}
       
    d. Save your changes.
    
    e. Upload /amember/templates/header.html to your server.
	
7. Update your /templates/login.html page.
    
    NB: This is something you will have to do each time you upgrade aMember.
    
    a. Edit your /amember/templates/login.html template file and and insert this line wherever you 
	   would like the OpenID options to appear:
    
       {include file="../plugins/protect/openid/login.openid.inc.html"}
	   
	   Hint, ABOVE this line is usually a good place:
	   &lt;br /&gt;
       &lt;p&gt;&#35_TPL_LOGIN_NOT_REGISTERED_YET&#35 &lt;a href="{&#36config.root_url}/
	   {if &#36affiliates_signup}aff_{/if}signup.php"&gt;&#35_TPL_LOGIN_SIGNUP_HERE&#35&lt;/a&gt;&lt;/p&gt;
    
    b. Save your changes.
    
    c. Upload /amember/templates/login.html to your server.
	
8. OPTIONAL: Update your /templates/signup.html page.
    
    NB: This is something you will have to do each time you upgrade aMember.
    
    a. Edit your /amember/templates/signup.html template file and insert this line wherever you 
	   would like the OpenID options to appear:
       
       {include file="../plugins/protect/openid/signup.openid.inc.html"}
       
    b. Save your changes.
    
    c. Upload /amember/templates/signup.html to your server.
	

----------------------------------------------------------------------------

 OpenID
 Copyright 2010 (c) R Woodgate
                    All Rights Reserved

----------------------------------------------------------------------------
