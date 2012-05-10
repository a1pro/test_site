<?php /* Smarty version 2.6.2, created on 2010-11-16 01:47:48
         compiled from member_header.html */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml" >
<head>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta content="GetAudioFromVideo - the best Youtube converter to many formats like MP3, MP4, and 3GP. " name="DESCRIPTION">
<meta content="converter youtube to mp3, converter youtube mp3, convert youtube videos MP3, convert youtube mp3, convert youtube to mp3, convert youtube video mp3, convert youtube into mp3, youtube to mp3, youtube converter, convert youtube" name="KEYWORDS">
<link rel="shortcut icon" href="<?php echo $this->_tpl_vars['base_url']; ?>
favicon.ico" />
<link rel="stylesheet" type="text/css" href="<?php echo $this->_tpl_vars['base_url']; ?>
css/style.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $this->_tpl_vars['base_url']; ?>
css/template.css" />
<title>Convert Youtube videos to MP3, MP4, 3GP, AVI, FLV, WMV | GetAudioFromVideo.com | Download Youtube Videos</title>


<!--<?php echo ' --><script type="text/javascript" src="/js/jquery.min.js"></script><!--{literal} '; ?>
-->
<!--<?php echo ' --><script src="http://www.cpalead.com/mygateway.php?pub=16797" type="text/javascript"></script><!--{literal} '; ?>
-->
<!--<?php echo ' --><script type="text/javascript">
function MM_jumpMenu(targ,selObj,restore){ //v3.0
  eval(targ+".location=\'"+selObj.options[selObj.selectedIndex].value+"\'");
  if (restore) selObj.selectedIndex=0;
}
</script><!--{literal} '; ?>
-->
<!--<?php echo ' --><script src="/js/service.js" type="text/javascript"></script><!--{literal} '; ?>
-->




    <?php if ($this->_tpl_vars['is_user_logged'] == 1): ?>
       <?php if ($this->_tpl_vars['has_active_subscriptions'] == 0): ?>
		
        <!--<?php echo ' --><script type="text/javascript" src="/js/cookie.js" ></script><!--{literal} '; ?>
-->
        <!--<?php echo ' --><script type="text/javascript" src="/js/lock.js"></script><!--{literal} '; ?>
-->
        <!--<?php echo ' --><script type="text/javascript">
            setCookie(_CONVER_COUNT_COOKIE,1,365);
        </script><!--{literal} '; ?>
-->
		
    <?php endif; ?>

	<?php else: ?>
		
		<!--<?php echo ' --><script src="/js/cookie.js" type="text/javascript"></script><!--{literal} '; ?>
-->
		<!--<?php echo ' --><script src="/js/lock.js" type="text/javascript" ></script><!--{literal} '; ?>
-->
		
	<?php endif; ?>

	<!-- GOOGLE ANALYTICS CODE END -->

<!-- SOCIAL BUTTONS HEADER START -->

<!--<?php echo ' --><script type="text/javascript">
(function() {
var s = document.createElement(\'SCRIPT\'), s1 = document.getElementsByTagName(\'SCRIPT\')[0];
s.type = \'text/javascript\';
s.async = true;
s.src = \'http://widgets.digg.com/buttons.js\';
s1.parentNode.insertBefore(s, s1);
})();
</script><!--{literal} '; ?>
-->

<!--<?php echo ' --><script type="text/javascript" src="http://w.sharethis.com/button/buttons.js"></script><!--{literal} '; ?>
--><!--<?php echo ' --><script type="text/javascript">stLight.options({publisher:\'1d71a651-3a41-4306-8b91-b993a133bbd1\'});</script><!--{literal} '; ?>
-->
<!-- SOCIAL BUTTONS HEADER END -->

</head>
<body>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "../plugins/protect/fb_connect/header.fb_connect.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<!--<?php echo ' --><script type="text/javascript" src="http://analytics.aweber.com/js/awt_analytics.js?id=6.Sh"></script><!--{literal} '; ?>
-->
<!-- Aweber Analytics Code End -->

<!-- FLOATING LIVE SUPPORT DIV START -->
<!--<div id="floatdiv" style="
    position:absolute;
    width:400px;height:80px;
    padding:16px;
    top:10px;
    left:908px;
    z-index:100">
 http://www.LiveZilla.net Chat Button Link Code <a href="javascript:void(window.open('http://67.18.149.236/livezilla/chat.php','','width=590,height=580,left=0,top=0,resizable=yes,menubar=no,location=no,status=yes,scrollbars=yes'))"><img src="http://67.18.149.236/livezilla/image.php?id=01" width="191" height="69" border="0" alt="LiveZilla Live Help"></a><noscript><div><a href="http://67.18.149.236/livezilla/chat.php" target="_blank">Start Live Help Chat</a></div></noscript><!-- http://www.LiveZilla.net Chat Button Link Code --><!-- http://www.LiveZilla.net Tracking Code<div id="livezilla_tracking" style="display:none"></div><!--<?php echo ' --><script type="text/javascript">
<!-- DON\'T PLACE IN HEAD ELEMENT -
var script = document.createElement("script");script.type="text/javascript";var src = "http://67.18.149.236/livezilla/server.php?request=track&output=jcrpt&nse="+Math.random();setTimeout("script.src=src;document.getElementById(\'livezilla_tracking\').appendChild(script)",1);</script><!--{literal} '; ?>
--><!-- http://www.LiveZilla.net Tracking Code
</div>-->
<!-- FLOATING LIVE SUPPORT DIV END -->

<div id="header">
  <!--Container header-->
  <div id="d_header_banner">
    <!--Container all elements header-->
    <div id="d_header_content">
		
      <!--Registration form-->
      <div id="d_register_content" style="width:450px">
		<?php if ($this->_tpl_vars['is_user_logged'] == 0): ?>
        <div id="d_register_form">
            <!--<form action="<?php echo $this->_tpl_vars['base_url']; ?>
members/login.php" method="POST" name="login" title="Registration">-->
			<form action="https://www.getaudiofromvideo.com/members/login.php" method="POST" name="header_login" title="Registration">
                <label class="text_reg">Login to convert Youtube videos</label>
                <div id="input_reg">
                          <p align="center">
                	<input name="amember_login" type="text" value="UserName" size="35" maxlength="80" class="input_reg_cl"
                               title="Enter user name" onfocus="<?php echo 'if(this.value==\'UserName\'){this.value=\'\'}else{this.value=this.value}'; ?>
" onblur="<?php echo 'if(this.value==\'\'){this.value=\'UserName\'}'; ?>
" />
              		</p>
                </div>
                <div id="input_reg">
                    <p align="center">
                	<input name="amember_pass" type="password"
                               onkeypress="<?php echo 'if(event.keyCode == 13){submit_login();}'; ?>
"
                               value="Password" size="35" maxlength="80" class="input_reg_cl" title="Enter you password" onfocus="<?php echo 'if(this.value==\'Password\'){this.value=\'\'}else{this.value=this.value}'; ?>
" onblur="<?php echo 'if(this.value==\'\'){this.value=\'Password\'}'; ?>
" />
              		</p>
                            </div>
                    <a href="<?php echo $this->_tpl_vars['base_url']; ?>
members/login.php" class="forgot_pass">Forgot password?</a>
					<input type="hidden" value="1" name="remember_login">
           </form>
        </div>
		<div id="fb_login_btn" style="float: left; width: 180px; margin-left: 40px;margin-top:-8px">
		<?php 
		$fbuser = fb_connect_get_fbuser();
		if (!$fbuser) {
			echo '<p style="margin:1em 0;text-align:center;">
				<fb:login-button size="medium" autologoutlink="true" perms="email,publish_stream">Signup with Facebook</fb:login-button>
			</p>';
			}
		 ?>
		</div>
        <div id="d_register_button_login">
            <a class="button_l" onclick="submit_login();"><span>Login</span></a>

        </div>
        <div id="d_register_button_reg"><a href="<?php echo $this->_tpl_vars['base_url']; ?>
members/signup.php" class="button_r"><span>Register Now</span></a></div>
    <?php else: ?>
        <div id="d_register_form">
            <p align="center" style="padding-top:20px;padding-bottom:20px;">Hello, <?php echo $this->_tpl_vars['amember_user']['login']; ?>
</p>
            <div id="d_bpl">
            <div id="button_profile"><a class="button_p" href="<?php echo $this->_tpl_vars['base_url']; ?>
members/member.php"><span>my account</span></a></div>
            <div id="button_logout"><a class="button_out" href="<?php echo $this->_tpl_vars['base_url']; ?>
members/logout.php"><span>logout</span></a></div>
            </div>
        </div>
    <?php endif; ?>
		
      </div>
	  
         <!--Container menu navigation-->
          <div id="d_menu">
            <div id="d_menu_con1"><a href="<?php echo $this->_tpl_vars['base_url']; ?>
index.php" class="button_m"><span>Home</span></a></div>
            <div id="d_menu_con2"><a href="<?php echo $this->_tpl_vars['base_url']; ?>
info/aboutus" class="button_m"><span>About Us</span></a></div>
            <div id="d_menu_con3"><a href="<?php echo $this->_tpl_vars['base_url']; ?>
info/faq" class="button_m"><span>FAQ</span></a></div>
            <div id="d_menu_con3"><a href="<?php echo $this->_tpl_vars['base_url']; ?>
info/testimonials" class="button_m"><span>Testimonials</span></a></div>
            <div id="d_menu_con4"><a href="<?php echo $this->_tpl_vars['base_url']; ?>
info/terms" class="button_m"><span>Terms of Use</span></a></div>
            <div id="d_menu_con4"><a href="<?php echo $this->_tpl_vars['base_url']; ?>
info/privacy" class="button_m"><span>Privacy Policy</span></a></div>
            <div id="d_menu_con5"><a href="<?php echo $this->_tpl_vars['base_url']; ?>
info/statistics" class="button_m"><span>Stats</span></a></div>
            <div id="d_menu_con6"><a href="<?php echo $this->_tpl_vars['base_url']; ?>
info/contact" class="button_m"><span>Contact Us</span></a></div>
          </div>
    </div>
	
  </div>
  <div id='goog_trans' style="width:170px;float:right;">
			<div id="google_translate_element"></div>
			<!--<?php echo ' --><script>
			function googleTranslateElementInit() {
			  new google.translate.TranslateElement({
				pageLanguage: \'en\',
				gaTrack: true
			  }, \'google_translate_element\');
			}
			</script><!--{literal} '; ?>
-->
			<!--<?php echo ' --><script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script><!--{literal} '; ?>
-->
		</div>
</div>
</div>
<!--<?php echo ' --><script type="text/javascript" src="/js/floatingwin.js"></script><!--{literal} '; ?>
-->