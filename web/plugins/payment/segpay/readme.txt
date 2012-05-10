            SEGPAY payment plugin configuration
           -------------------------------------

If you already have new SEGPAY account, then there is nothing to change.

CONFIUGURATION OF NEW ACCOUNT 

1. Login into your SEGPAY account:
   https://my.segpay.com/

2. If you've already added Price Points, then Click 
to "Post Back" link, if not - create Price Points at first.

3. Click to "New Post Back Config" button.

4. Enter any Description of you Post Back.
Set
<b>2nd Trans Post URL:</b> {$config.root_url}/plugins/payment/
segpay/thanks.php?action=< action >&approved=< approved >&
stage=< stage >&trans_id=< tranid >&price=< price >&
trantype=< trantype >&trial_price=< ival >&trial_period=< iint >
&rebill_price=< rval >&rebill_period=< rint >&username=< extra username >
&password=< extra password >&payment_id=< extra payment_id >
<b><font color="red">NOTE: type values at < ... > WITHOUT spaces!</font></b>


<b>2nd Trans Post Msg:</b> SUCCESS

<b>Sign-Up Template:</b> email.signup.xml

<b>Cancel Template:</b> email.cancel.xml

Click to "Update" Button.

5. Create Packages -> add your Price Points inside them ->
->set Post Back withing Package configuration to one that you've
created.