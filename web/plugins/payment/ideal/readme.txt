Testing configuration
---------------------
#Default values enables you to test the example
iDeal Merchant ID           = 005010700
iDeal subID                 = 0
Private Key File Location   = priv.pem
Private Key Password        = passwd
Certificate File Location   = cert.cer



Security configuration
----------------------

The following steps have to be completed, in order to create a private/public key:
1. Download the openssl library to your computer (http://www.openssl.org).
   There are a number of binaries for different operating systems available
   (see http://www.openssl.org/docs/apps/req.html for more info
   about the certificate request and the certificate generating utility).
2. Generate a RSA private key using a self-chosen password for [privateKeyPass] (without the brackets):

openssl genrsa -des3 –out priv.pem -passout pass:[privateKeyPass] 1024

3. Create a new Certificate based on this private key (using the same password):

openssl req -x509 -new -key priv.pem -passin pass:[privateKeyPass] -days 3650 -out cert.cer

4. Copy the private key and the certificate file into the directory security
5. Edit the following entries in the configuration file config.conf,
   using the data from the steps 2 and 3 above
   - privateKey
   - privateKeyPass
   - privateCert


Templates configuration
-----------------------

Edit file /amember/templates/signup.html
add this rows:

{foreach from=$config.plugins.payment item=pp}
    {if $pp eq 'ideal'}
&lt;tr&gt;
    &lt;th&gt;Kies hier uw bank *&lt;/th&gt;
    &lt;td&gt;
    &lt;select name="issuerID" id="issuerID" size="1"&gt;
    {html_options options=$config.ideal_options selected=$smarty.request.issuerID}
    &lt;/select&gt;
    &lt;/td&gt;
&lt;/tr&gt;
    {/if}
{/foreach}

before rows:

&lt;tr&gt;
    &lt;th width="40%"&gt;#_TPL_SIGNUP_NAME# *&lt;br /&gt;
    &lt;div class="small"&gt;#_TPL_SIGNUP_NAME_1#&lt;/div&gt;&lt;/th&gt;
    &lt;td nowrap="nowrap"&gt;&lt;input type="text" name="name_f" value="{$smarty.request.name_f|escape}" size="15" /&gt;
        &lt;input type="text" name="name_l" value="{$smarty.request.name_l|escape}" size="15" /&gt;
    &lt;/td&gt;
&lt;/tr&gt;
