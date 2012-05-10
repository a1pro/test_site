=== Plugin: aMail for AWeber ===
Author: Ken Gary at Kencinnus.com
Current Version: 3.2.3.2
Upgrade Link: http://kencinnus.com/downloads/amail_aweber/
Requires at least: aMember Pro v3.2.3
Tested up to: aMember Pro v3.2.3


== Description ==

The aMail Plugin will help you sync up your AWeber lists with your 
aMember products and keep your members subscribed and unsubscribed automatically.

NOTE: Be sure the words on your membership site tell your members that they 
      are going to be subscribed to an email list in a prominent location 
      during the signup process.  

      
== Instructions ==

These instructions assume you know how to create the lists you want for your 
products/members at AWeber and have already done so.

 1. Set up some custom fields for your AWeber list to receive member data from 
    aMember.
 
    a. Visit AWeber and make sure the correct list is your current list.

    b. Hover over My List and click on the Custom Fields selection.  
    
    c. Add the custom fields that you need to sync up with aMember.

       Do not let subscriber update Username, Password or SubscribeIP.

       Whether or not they can change the rest is up to you.

       You do not have to use the exact same names...but why not?
       
       Add these fields (optionally):
    
    	Product     
    	Amount

    	SubscribeIP 
    	
    	MemberID    &lt;-- If you choose to send signup add these three
    	Username
    	Password
    	
    	Street      &lt;-- If you choose to send address add these five
    	City
    	State
    	Zip
    	Country

    	Phone       &lt;-- If you choose to send additional add them here
    	.
    	.
    	.
    	
        NOTE: The order you first enter the fields above is not important,
              but once you have these set, never, ever change the order!              
              It is OK to add more later under these but do not change
              the order no matter what you do.
        
    d. Repeat steps a-c for every list you are going to sync with aMember.
        
 2. Set up the email parser in AWeber.
 
    a. Visit AWeber and make sure the correct list is your current list.
    
    b. Hover over My List and click on the Email Parser selection.
    
    c. Scroll down to the bottom of the page and find the Custom Parsers 
       section. 
       
    d. Click on the add new link next to Custom Parsers.
       
    e. Change the Description to something like 
    
       aMember: mywebsite
    
    f. Change the Trigger Rule to this rule exactly and leave it to match on
       headers:
   
          Subject:\s+aMail for AWeber Subscribe Parser 
       
    g. Rule 1 and Rule 2 are already set for Email and Name and those do not 
       have to be changed.

       THE REST OF THESE RULES ARE OPTIONAL (Case and spacing must match):

    h. Set Rule  3: \n[&gt;\s]*Subscribe IP:\s+(.+?)\n
          Store In: SubscribeIP
      
    i. Set Rule  4: \n[&gt;\s]*MemberID:\s+(.+?)\n
          Store In: MemberID
    
    j. Set Rule  5: \n[&gt;\s]*Username:\s+(.+?)\n
          Store In: Username
    
    k. Set Rule  6: \n[&gt;\s]*Password:\s+(.+?)\n
          Store In: Password
    
    l. Set Rule  7: \n[&gt;\s]*Street:\s+(.+?)\n
          Store In: Street
         
    m. Set Rule  8: \n[&gt;\s]*City:\s+(.+?)\n
          Store In: City
         
    n. Set Rule  9: \n[&gt;\s]*State:\s+(.+?)\n
          Store In: State
         
    o. Set Rule 10: \n[&gt;\s]*Zip:\s+(.+?)\n
          Store In: Zip
         
    p. Set Rule 11: \n[&gt;\s]*Country:\s+(.+?)\n
          Store In: Country
         
    q. Set Rule 12: \n[&gt;\s]*phone:\s+(.+?)\n
          Store In: Phone
          
    r. You could also Optionally parse out the product and amount that is 
       (almost) always included in the signup email.

       Set Rule 13: \n[&gt;\s]*Product:\s+(.+?)\n
          Store In: Product
          
       Set Rule 14: \n[&gt;\s]*Amount:\s+(.+?)\n
          Store In: Amount
          
    s. Save the email parser settings.  This should take you back to the list 
       of email parsers.
    
    t. Check the box in front of the new aMember: mywebsite parser.  
       This turns it on.
    
       NOTE: If you run multiple versions of aMember for different niches on 
             different sites but you have one AWeber account you must create a 
             new parser for each site.  But you can enable one parser for every 
             AWeber list that syncs up with every product in one instance of 
             aMember.
             
    u. Repeat steps a, b and s for every list being managed by this aMember.
    
 3. Meanwhile, back on your own server in the aMember admin panel...
 
    Upload the AWeber plugin files to the     <strong>amember/plugins/protect/amail_aweber/</strong> 
    folder.

 4. Enable the aMail for AWeber plugin at 
 
    <strong>aMember CP -&gt; Setup/Configuration -&gt; Plugins</strong>

 5. Configure the aMail for AWeber plugin at 
 
    <strong>aMember CP -&gt; Setup/Configuration -&gt; aMail for AWeber</strong>

    a. <strong>Debug?</strong> - 

       It also writes debug statements to the error log.

    b. <strong>Primary Default AWeber List Name</strong> - 

       Decide which list at AWeber will be your primary default list and enter 
       it in the field provided.
      
       Every member will be added to this default member list when they 
       purchase any of your products UNLESS you configure products with their 
       own AWeber list as described next.
       
    c. <strong>Send Signup Information?</strong> -

       Check this box if you want the member's username and password to be 
       included in the subscribe email sent to AWeber for their email parser to
       read.  It can be useful if you want to make them confirm through AWeber 
       before they get their userid or password that you have auto-generated by 
       aMember.  You can include the values in their first message from AWeber.
       
       NOTE: Please realize these fields will never get updated in aWeber again.
       
    d. <strong>Send Address Information?</strong> -

       Check this box if you want the member's street, city, state, zip and 
       country information included in the subscribe email sent to AWeber for
       their email parser to read.

       NOTE: Please realize these fields will never get updated in aWeber again.

    e. <strong>Send Additional Fields?</strong> -

       Whatever additional fields you set up in aMember that you also want to 
       send to AWeber should be entered as a comma separated list here. Use the 
       internal name, not the display name when entering them.
       
       For Example:
       
           1. You add an additional field in aMember called "phone."
           
           2. You enter "phone" into the Send Additional Fields box.
           
           3. You add a custom field in AWeber called "Phone."
           
           4. You set up the email parser to look for "phone:" and store that
              value into the "Phone" field.

       NOTE: Please realize these fields will never get updated in aWeber again.
       
    f. <strong>CC Admin?</strong> -

       Check this box when you want a copy of the email that is sent to AWeber 
       for subscribe/unsubscribe also sent to your admin email address.
       
    g. <strong>Do Not Unsubscribe?</strong> - 

       If you do not want this plugin to ever, under any circumstances send a
       remove request to AWeber no matter what then check this box.  This 
       defeats the purpose of using this plugin to keep AWeber in sync with 
       your Newsletter unsubscribe flag and it may confuse your members.
       Subscribes still happen.
       
    h. <strong>Do Not Subscribe OR Unsubscribe?</strong> - 

       There may be times that you need to debug or experiment with something 
       and you want to leave the functionality of this plugin turned on but
       you do not want it to actually subscribe or unsubscribe your members
       at AWeber while you play around with something.  
       
       You can check this box and all of the subscribes/unsubscribes that would 
       have normally gone to AWeber will now go to the admin email 
       address instead, even if you do not have the CC Admin field turned on.  
       
       When you are done you should think about using the aMember Rebuild DB 
       function if you want your changes you made while it was in test mode to 
       actually take.  Since those changes did not happen then your aMember 
       database and the AWeber lists may not be in sync.  
       
    i. <strong>Resend Subscribes/Unsubscribes During Rebuild Database?</strong>
    
       Normally you will leave this unchecked.  If it is checked then it will
       resend every single subscribe or unsubscribe email to AWeber for each-
       and-every one of your members.  
       
       DO CHECK THIS: When you first install the plugin and you want to get all
       of your old members subscribed to your lists in AWeber.  It is also good
       to use once when you are switching from some other autoresponder to 
       AWeber.
     
       But after that you should leave this setting unchecked except in rare
       occassions.
       
       WHY?  Because after 30 days AWeber deletes all of the subscribers who
       never confirmed the first time.  If you have this checked and it resends
       the subscribe emails then they would get another confirmation request 
       email and you would basically be spamming them.
       
    j. <strong>Thank You Page Title</strong>
    
       This will be the title on the thank you page after they purchase.
       
       It is useful to grab their attention and make them read the opt-in
       message below.
       
    k. <strong>Thank You Page Message</strong>
    
       You can enter HTML into this field that will be displayed on the top of
       the thank you page after someone purchases.
       
       This is useful for instructing your members on the importance of opting
       into your AWeber list.  It should contain specific examples and 
       instructions for what to look for in their email inbox.
       
       You can use a limited amount of variable substitution in this template:
       
       [[first_name]]	&lt;-- gets replaced with first name of member
       [[last_name]]    &lt;-- last name of member
       [[email]]        &lt;-- email of member
       [[admin_email]]  &lt;-- email of admin (the from address)
       [[admin_name]]   &lt;-- name of admin (as configured in your settings)
       [[site_name]]    &lt;-- name of your site (as configured)
       
    l. Save aMail plugin settings.
    
    m. You will need to configure an email parser for this list at AWeber as 
       described above.

 6. Copy the pre-packaged modifications from the plugin folder to the proper
    locations within your aMember installation.
    
    NOTE: This assumes you do not have any other customizations to these
          files.  If you do then you will have to merge your updates with
          these updates to make it work the exact way that you want it to.

    a. Copy {$config.root_dir}/plugins/protect/amail_aweber/amember/plugins/db/mysql/mysql.inc.php
       to   {$config.root_dir}/plugins/db/mysql/mysql.inc.php
    
    b. Copy {$config.root_dir}/plugins/protect/amail_aweber/amember/templates/signup.html 
       to   {$config.root_dir}/templates/signup.html

    c. Copy {$config.root_dir}/plugins/protect/amail_aweber/amember/member.php 
       to   {$config.root_dir}/member.php

    d. Copy {$config.root_dir}/plugins/protect/amail_aweber/amember/paysys.php 
       to   {$config.root_dir}/paysys.php

OR...

   IF you do want to make the changes manually to your own versions of these 
   files then the the next four items contain the instructions...
       
 7. Update your /templates/signup.html page to always include the newsletter
    checkbox.  This is what allows AWeber subscriptions or not for each member.
    
    NOTE: Nothing will work without this! It is also something you will have to
          do each time you upgrade aMember.
    
    a. Edit your /amember/templates/signup.html template file and locate these 
       lines:
    
       {if $newsletter_threads &gt; 0}
       &lt;tr&gt;
           &lt;th&gt;<strong>#_TPL_SIGNUP_NEWSLETTERS_SUBSCRIBE#</strong>&lt;br /&gt;
           &lt;div class="small"&gt;#_TPL_SIGNUP_NEWSLETTERS_SUBSCRIBE_1#&lt;/div&gt;
           &lt;/th&gt;
           &lt;td&gt;&lt;input type="checkbox" name="to_subscribe" value="1"
           {if $smarty.request.to_subscribe}checked="checked"{/if} /&gt;
           &lt;/td&gt;
       &lt;/tr&gt;
       {/if}
       
    b. Replace them with these:
    
       &lt;tr&gt;
         &lt;th&gt;
           &lt;strong&gt;#_TPL_SIGNUP_NEWSLETTERS_SUBSCRIBE#&lt;/strong&gt;&lt;br /&gt;
           &lt;div class="small"&gt;#_TPL_SIGNUP_NEWSLETTERS_SUBSCRIBE_1#&lt;/div&gt;
         &lt;/th&gt;&lt;td&gt;
           &lt;input type="checkbox" name="to_subscribe" value="1" checked="checked" /&gt;
           #_TPL_SIGNUP_NEWSLETTERS_SUBSCRIBE_2#
         &lt;/td&gt;
       &lt;/tr&gt;
       
       NOTE: You can edit your /amember/languages/en-custom.php and add this
             line to make it say anything you want after the checkbox:
             
       define ('_TPL_SIGNUP_NEWSLETTERS_SUBSCRIBE_2', 'Your password will be emailed to you.');
       
    c. Save your changes.
    
    d. Upload /templates/signup.html to your server.
       
    e. Use the /amember/languages/en-custom.php to change the words so they
       are suitable for your site.

 8. Update your /plugins/db/mysql/mysql.inc.php file to properly configure the
    unsubscribe flag based on the checkbox on the signup page.
    
    a. Edit your /plugins/db/mysql/mysql.inc.php file and locate these lines:
    
           function add_pending_user($vars){
               _amember_get_iconf_d();
               $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
               global $member_additional_fields;
               $data = array();
       
               if (!strlen($vars['pass']))
                   $vars['pass'] = $vars['pass0'];
                   
    b. Right below them add these lines:
    
       // Begin Mod for aMail Plugin
       $vars['unsubscribed'] = (empty($vars['to_subscribe'])) ? 1 : 0;
       // End Mod for aMail Plugin

    c. Save your changes.
    
    d. Upload /plugins/db/mysql/mysql.inc.php to your server.

 9. Update your member page so that when a member changes the Newsletter 
    Unsubscribe checkbox it keeps their AWeber subscriptions in sync.
    
    a. Edit file /amember/member.php
    
    b. Find function 'update_subscriptions' and after...
    
          if (!$vars['unsubscribe']){

              $q = $db->query($s = "
                  UPDATE {$db->config['prefix']}members
                  SET unsubscribed=0
                  WHERE member_id=$member_id
              ");
              $db->add_member_threads($member_id, $vars['threads']);

          } else {

              $q = $db->query($s = "
               UPDATE {$db->config['prefix']}members
                  SET unsubscribed=1
                  WHERE member_id=$member_id
              ");

          }
    
       insert...
    
          //
          // Begin Mod for aMail Plugin...
          //
          $newmember = $db->get_user($member_id);
          $oldmember = $newmember;
          $oldmember['unsubscribed'] = ($newmember['unsubscribed']) ? 0 : 1;
          plugin_subscription_updated($member_id,$oldmember,$newmember);
          //
          // End Mod for aMail Plugin
          //
    
    c. Save your changes.
    
    d. Upload /amember/member.php to your server.
    
    NOTE: The changes in steps 6, 7 and 8 will have to be re-done every time 
          you upgrade aMember.

10. Modify the paysys.inc.php file so members are not added as unsubscribed.

    a. Edit file /amember/paysys.inc.php
    
    b. Find function 'create_new_payment' and after...
    
          foreach(array('name_f', 'name_l', 'email', 'street', 'city', 'zip', 'country','state') as $v){
	      $member[$v] = $this->get_value_from_vars($v, $vars);
	  }

       insert...
       
         $member['to_subscribe'] = 1;  // mod added for aMail
	   
    c. Save your changes
    
    d. Upload /amember/paysys.inc.php to your server.

11. Make sure your /templates/thanks.html page has NOT been modified
    
    NOTE: As of version 3.2.3 there is no longer any need to modify the
          thanks.html template so if you have done so before, make sure
          you have the standard template from aMember core files.

12. OPTIONAL: If you would like a particular product to subscribe members to a 
    particular AWeber list that is different than your default member list then 
    you can follow these instructions.  

    a. Visit <strong>aMember CP -&gt; Manage Products -&gt; Edit</strong>.

    b. Enter the AWeber list name into the Product's AWeber Listname field.
    
    c. Save product settings.

    d. You will need to configure an email parser for each list at AWeber as 
       described above.
       
    e. Repeat steps a-d for every product that has it's own list at AWeber.
    
       NOTE: You can add multiple listnames by separating them with commas.
             The member will get subscribed to all of the lists when they 
             purchase the product.  However, in most cases you do not have to 
             do this. It is far better to use the AWeber automation rules to 
             add members to a second list when they subscribe to a first list.
             
       NOTE: If you set a product to "nolist" then when members purchase that
             product they will not be subscribed to any list, including the
             plugin default list.
    
13. OPTIONAL: Set up the thank you page for your AWeber Confirmed Opt-In:

    a. Visit AWeber and make sure the correct list is your current list.
   
    b. Under the My Lists tab choose the Confirmed Opt-In page.
   
    c. Enter this into the Confirmation Success Page field:
   
       {$config.root_url}/plugins/protect/amail_aweber/thanks.php
       
    d. Do NOT check the box next to Pass subscriber info (for personalizing this page.)!
       
    e. Repeat steps a-d for every AWeber list you use on this site.
       
14. OPTIONAL: Use aMail thank you page in any forms you use for your AWeber 
    lists outside of aMember.  Yes, that's right! BONUS FUNCTIONALITY!
 
    a. Visit AWeber and make sure the correct list is your current list.
    
    b. Click on the Web Forms tab.
    
    c. Create or edit your web form and ente this into the Thank You Page field:
    
       {$config.root_url}/plugins/protect/amail_aweber/thanks.php
       
    d. Check the box next to Forward Variables.
    
    e. Enter one of these into the Already Subscribed Page field
       (unless you have a better page):
    
       {$config.root_url}/plugins/protect/amail_aweber/thanks.php
       
       - or -
       
       {$config.root_url}/member.php
       
    f. Save your web form.
    
    g. Repeat steps a-f for every AWeber list you use on this site.
    
       NOTE: If you want to change the way this page looks you can edit
             /amember/plugins/protect/amail_aweber/thanks.html
 
15. OPTIONAL: You might want to include the member's personal information in 
    the first followup email.
 
    This is great if you set aMember to generate passwords and your members 
    cannot get that until after they verify through AWeber.
    
    a. In the navigation tabs hover over messages and click on Follow Up.
    
    b. Edit the first message to include something like this:
    
        {!firstname_fix}

        Here are your access details for for your membership -

	    Your User ID: {!custom username}  
	    Your Password: {!custom password}
	
        Your personal details we have on record are -

            Name:        {!name_fix}
            Email:       {!email}            
            Address :    {!custom street} 
            City :       {!custom city}  
            State:       {!custom state} 
            Zip:         {!custom zip}
            Country:     {!custom country}
            Phone:       {!custom phone}

            You signed up from the IP Address:  {!custom subscribeip}
            
        You can log-on to your member pages at:
            
            {$config.root_url}/member.php
        
        On that page is a link to your Profile which you can use to change
        your password. 	
        
        {!signature}
        
    g. Repeat steps a-b for every AWeber list you sync with aMember.

16. Use the AWeber automation rules to automatically subscribe/unsubscribe 
    members from one list to another list to suit your business rules.  This 
    can be handy for prospects and customers.  For example you could create two 
    lists in AWeber, one for members and one for prospects.  You can create one
    free lifetime product in AMember and all of the rest of your products cost 
    the member money. You set the default aMail plugin values to the AWeber 
    list you created for members and you leave the AWeber fields blank on all 
    of your products that cost money but you fill in the fields for the free 
    product with the values for your AWeber prospect list.  Customers who sign 
    up for your free product get put on your prospect list.  Customers who 
    purchase one of your other products get put on your members list.  In 
    AWeber you can set up an automation rule that will take a person off of 
    your prospect list when they subscribe to your member list.
   
17. A Word About Unsubscribes/Resubscribes with AWeber:

    Whenever one of your members is subscribed to one of your lists and either
    they go to their member page and uncheck the newsletter box or an admin
    edits their record and sets them to unsubscribed this plugin will tell
    AWeber to remove them from whatever of your lists that they are currently 
    subscribed to in AWeber.  You can see those on their member record in the
    admin panel.
    
    Once they are removed from a list there is not much the plugin can do to
    resubscribe them.  That's just how AWeber works.
    
    The member themselves can use the link in their AWeber emails to subscribe,
    unsubscribe or resubscribe to your list.  When they do that then there is
    nothing we can do about making sure the data in aMember matches AWeber. But
    the subscriber themselves can get back on your list through AWeber directly.
    
    I recommend you copy-and-paste your AWeber form code into the member.php
    page above the newsletter code so they can re-subscribe using the regular
    form if they want to.  That just provides another option to get a member
    back who may have gotten off of the list.
    
    Another thing you may try when members are marked removed in AWeber is to
    go to AWeber and delete all of those records.  If you clean your AWeber
    list of all of the subscribers who have been removed by the plugin and then
    do the aMember database rebuild, it may have a chance of fixing the records 
    of those of your members who checked your unsubscribe box, changed their 
    minds and unchecked it again.  You do run the risk of resubscribing people
    who unsubscribed themselves directly through AWeber, but they do not have
    to re-confirm.  You have to make your own mind up about this.  
    
    Just know that once your member unsubscribes themselves or an admin 
    unsubscribes them it cannot be reversed by this plugin.  It may look like 
    it was in aMember but the resubscribe will not do anything at AWeber when a
    subscriber there is already marked as removed.
 
18. TIPS

    If you accidentally unsubscribe one of your subscribers you can manually
    reverse this on their record in AWeber.  (The plugin cannot do this.)
    
    a. Go to AWeber and sign in.
    
    b. Pick the list they were accidentally unsubscribed from.
    
    c. Click on Subscribers -> Search
    
    d. Search for and find their record (probably by their email address).
    
       You will see that they are unubscribed because it will have a time
       and date stamp in the Stopped column.
       
    e. Click on their email address to open their record.
    
    f. Uncheck the box next to Stopped.
    
    g. Click on the Save button and save your update.
    
    Now they will be back on your list!
    
    NOTE: You should NOT do this if THEY unsubscribed themselves!
 
 
== TROUBLE SHOOTING  ==

    AWeber will not even allow an email to reach their email parser if any of 
    the following conditions are true:
    
    a. The reverse DNS on your aMember site is not set up correctly at your ISP.
       If it cannot validate that the email address you are sending them an 
       email from is good, it will just get deleted on their servers and never
       reach their email parser.
       
    b. The email address you are using as your outgoing email address in aMember
       has to either be a real email account set up on your server or at least
       have a valid forward rule set up for it to forward it to a valid email
       address.
       
       Do not use an email address from Gmail or anything that is not at the
       same domain that aMember is running from on your server since your 
       server is sending the email.
       
    c. The email address you are using as your outgoing email address in aMember
       has to match the email address you set in AWeber on your list settings in
       the From Address field. 
       
       In other words the same email address has to be used in 3 places:
       
       1. AMember Outgoing Email Address.  (Does not hurt to be same as admin.)
       
       2. AWeber From Address in List Settings (on each list you integrate).
       
       3. The Trigger Rule on your AWeber email parser.
       
    d. In aMember, the E-Mail Sender Name cannot have any periods or other non
       alpha-numeric characters.  If it says "Kencinnus.com" it will not work.
       Spaces are OK (but a period screws up the Trigger Rule on the parser).
       
    e. You might be on a shared server where someone else is sending out SPAM
       and they have gotten your IP put on a black list that AWeber blocks.
       You will need to check this and work with AWeber to remove the block or
       you may have to ask your host to move your site or move to a new host
       company.
    
    f. Testing:
    
       If you have everything set up and it is still not adding subscribers to 
       your list you can test the email parser with the email you receive when 
       someone subscribes and you have the cc admin option turned on in aMail.   
       
       1. If you are receiving that email that says *** New Subscriber in the
          subject then you know your server is capable of sending email. It
          is sending a copy of that same email to AWeber.  So...
          
       2. Edit the email and copy the headers to the clipboard.
       
       3. Go to AWeber -> List Settings -> Email Parser and at the bottom 
          of the page under Test Active Parsers is a form you can enter your 
          headers into and test.  
          
          If you do this and it actually can pick a subscriber out of the copy 
          you got sent then something is wrong as described above.  
          
          The most likely scenerio is the reverse DNS which for some reason is 
          becoming a more common thing that ISPs do not set up by default for 
          some reason.  Just ask them and they will do it for you.
          
    f. And...oh yeah...make sure the email parser is actually enabled and active.
       
       True story.
   
    g. If you are using ClickBank in front of aMember see item 10.

    
== Changelog ==

= 3.2.3.2 =
* Fixed a bug with sending additional fields if SQL.

= 3.2.3.1 =
* Fixed a bug with get_user

= 3.2.3.0 = 
* Made sure everything works with 3.2.3
* Changed the thank you page logic so no template needs to be updated.

= 3.1.9.0 = 
* Made sure everything works with 3.1.9

= 3.1.8.8 = 
* Added cron logic to unsubscribe expired users after xx days

= 3.1.8.7 = 
* Changed rebuild function to assume local subscribe/unsubscribe state is valid.
* Will not re-send subscribes/unsubscribes that it has already sent to AWeber.

= 3.1.8.6 = 
* Changed rebuild function to not resend subscribes/unsubscribes unless told to via option.

= 3.1.8.5 = 
* Fixed bug in remove routine that did not properly remove subscribers when they are deleted.

= 3.1.8.4 = 
* Fixed a bug with the database rebuild routine. Now it access every member.

= 3.1.8.3 = 
* Added capability to pass member id to AWeber.

= 3.1.8.2 = 
* Switched to mail_customer function and used outgoing admin email.
* Email parser at AWeber should now match OUTGOING EMAIL ADDRESS!

= 3.1.8.1 = 
* Add subscribers at finish_waiting_payment instead of subscription_added.
* If a product is set to "nolist" then do not add subscribers to any list.

= 3.1.8.0 = 
* Tested with aMember version 3.1.8

= 3.1.7.5 = 
* Changed revision naming rules to match aMembers current level

= 1.5 = 
* Changed the amail_aweber_deleted function to ignore recurring products.

= 1.4 = 
* Removed periods from site titles so the from trigger rule works at AWeber.
* Added a hook in member.php to capture when they change their unsubscribed flag themselves.

= 1.3 = 
* Added more integration with unsubscribed field in member record.

= 1.2 = 
* Added checkboxes to control what fields are submitted to AWeber, and CC admin.

= 1.1 =
* Changed from submitting the form with curl to sending an email for the AWeber email parser.

= 1.0 = 
* Added login and password paramaters being passed to AWeber.
* Also added Street, City, Zip, State and Phone
* Original Version.

   
== Availability ==

   http://kencinnus.com/plugins/integration/amail/


== Copyright ==

   Copyright (C) 2010 Ken Gary http://kencinnus.com/
                      All Rights Reserved
                    
   This file may not be distributed by anyone outside of Kencinnus, LLC except
   authorized contractors as specified.

   This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
   THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
   PURPOSE.

   For aMail plugin support (to report bugs and request new features) visit:
   
       http://kencinnus.com/contact/
