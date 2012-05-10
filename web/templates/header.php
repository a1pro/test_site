<?php
$base_url = "http://getaudiofromvideo.com/";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta content="GetAudioFromVideo - the best converter Youtube to mp3 to many formats like MP3, MP4, and 3GP. " name="DESCRIPTION">
<meta content="converter youtube to mp3, converter youtube mp3, convert youtube videos MP3, convert youtube mp3, convert youtube to mp3, convert youtube video mp3, convert youtube into mp3, youtube to mp3, youtube converter, convert youtube" name="KEYWORDS">
<link rel="stylesheet" type="text/css" href="<?php echo $base_url;?>css/style.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $base_url;?>css/template.css" />
<title>GetAudioFromVideo.com | Download Youtube Videos | Convert Youtube to MP3, Mp4, 3GP, AVI, FLV, WMV</title>
<script type="text/javascript">
<!--
function MM_jumpMenu(targ,selObj,restore){ //v3.0
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}
//-->
</script>
<script src="<?php echo $base_url;?>js/service.js" type="text/javascript"></script>
<?php if ($this->session->userdata['is_logged']) {
       if (!$this->session->userdata['has_active_subscriptions']) {
    ?>

        <script type="text/javascript" src="<?php echo $base_url;?>js/cookie.js" ></script>
        <script type="text/javascript" src="<?php echo $base_url;?>js/lock.js"></script>
        <script type="text/javascript">
            setCookie(_CONVER_COUNT_COOKIE,1,365);
        </script>
    <?php } ?>

<?php } else { #guest
?>
    <script src="<?php echo $base_url;?>js/cookie.js" type="text/javascript"></script>
    <script src="<?php echo $base_url;?>/js/lock.js" type="text/javascript" ></script>
<?php }?>

<!-- GOOGLE ANALYTICS CODE START -->
	<script type="text/javascript">

	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-8374148-1']);
	  _gaq.push(['_trackPageview']);

	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ga);
	  })();

	</script>
	<!-- GOOGLE ANALYTICS CODE END -->

<!-- SOCIAL BUTTONS HEADER START -->
<script type="text/javascript">
(function() {
var s = document.createElement('SCRIPT'), s1 = document.getElementsByTagName('SCRIPT')[0];
s.type = 'text/javascript';
s.async = true;
s.src = 'http://widgets.digg.com/buttons.js';
s1.parentNode.insertBefore(s, s1);
})();
</script>

<script type="text/javascript" src="http://w.sharethis.com/button/buttons.js"></script><script type="text/javascript">stLight.options({publisher:'1d71a651-3a41-4306-8b91-b993a133bbd1'});</script>
<!-- SOCIAL BUTTONS HEADER END -->

</head>
<body>

<!-- Aweber Analytics Code Start -->
<script type="text/javascript" src="http://analytics.aweber.com/js/awt_analytics.js?id=6.Sh"></script>
<!-- Aweber Analytics Code End -->

<div id="header">
  <!--Container header-->
  <div id="d_header_banner">
  
    <!--Container all elements header-->
    <div id="d_header_content">
      <!--Registration form-->
      <div id="d_register_content">
<?php
    if(!$this->session->userdata['is_logged']){ ?>
        <div id="d_register_form">
            <form action="<?php echo $base_url;?>members/login.php" method="POST" name="login" title="Registration">
                <label class="text_reg">Sign in GetAudioFromVideo</label>
                <div id="input_reg">
                            <p align="center">
                            <input name="amember_login" type="text" value="UserName" size="35" maxlength="80" class="input_reg_cl" />
                            </p>
                </div>
                <div id="input_reg">
                    <p align="center">
                            <input name="amember_pass" type="password" size="35" maxlength="80" class="input_reg_cl" />
                            </p>
                            </div>
                    <a href="<?php echo $base_url;?>members/login.php" class="forgot_pass">Forgot password?</a>
           </form>
        </div>
        <div id="d_register_button_login">
            <a class="button_l" onclick="submit_login();"><span>Login</span></a>

        </div>
        <div id="d_register_button_reg"><a href="<?php echo $base_url;?>members/signup.php" class="button_r"><span>Registration</span></a></div>
    <?php }?>
      </div>
        <div id="d_menu">
            <div id="d_menu_con1"><a href="<?php echo $base_url;?>index.php" class="button_m"><span>Home</span></a></div>
            <div id="d_menu_con2"><a href="<?php echo $base_url;?>info/aboutus" class="button_m"><span>About Us</span></a></div>
            <div id="d_menu_con3"><a href="<?php echo $base_url;?>info/faq" class="button_m"><span>FAQ</span></a></div>
            <div id="d_menu_con4"><a href="<?php echo $base_url;?>info/terms" class="button_m"><span>Terms of Use</span></a></div>
            <div id="d_menu_con5"><a href="<?php echo $base_url;?>info/statistics" class="button_m"><span>Stats</span></a></div>
            <div id="d_menu_con6"><a href="<?php echo $base_url;?>info/contact" class="button_m"><span>Contact Us</span></a></div>
      </div>
    </div>
  </div>
</div>
</div>
