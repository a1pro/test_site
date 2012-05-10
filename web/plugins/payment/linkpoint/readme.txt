-----

Versions

Version 2.6 of the LinkPoint PHP Wrapper (LPHP) and
version 5.4.1 (01may2001) of the LinkPoint LBIN executable binary 
were used in conjunction with this aMember plugin.

-----

LinkPoint Payment Plugin
Configuration Steps


(1.) Upload your LinkPoint key file and PHP wrapper files according
     to the "LinkPoint PHP Wrapper Installation" directions below.

(2.) Enable and configure plugin at "aMember CP -> Setup -> Plugins".

(3.) Confiure the plugin at "aMember CP -> Setup -> LinkPoint".

(4.) If you want to enable automatic recurring billing, follow the
     instructions below for "Cron Job Configuration."

-----

LinkPint PHP Wrapper
Installation Instructions

(1.) Upload your LinkPoint key file (e.g. 123456.pem) to your cgi-bin,
     chmod it 444.

-----

Cron Job Configuration
Instructions

This is used in a situation where you want to automatically process 
recurring billing for specific products.  In order for this to work, 
you need to:

(1.) Enable "Use External Cron" in the Setup -> Advanced settings of 
     the Admin Control Panel.


(2.) Add a cron job listing of: 

     1 * * * * /usr/bin/lynx -source http://yourdomain/amember/cron.php
     
     The line above should be added, and it should all appear on a 
     single line.  To add the cron job, you must use the command 
     "crontab -e" and the vi editor.  Simply add the above line to 
     the text file then save/exit.  If you are not familiar with cron 
     jobs or proper use of the vi text editor, it is highly 
     recommended to consult your sever administrator for assitance.
     Before you add the above cron job, you will want to change the 
     URL to match your domain name and correct the patch to your new 
     aMember directory.

-----
