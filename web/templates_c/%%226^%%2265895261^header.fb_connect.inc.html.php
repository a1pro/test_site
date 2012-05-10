<?php /* Smarty version 2.6.2, created on 2010-12-06 00:30:56
         compiled from ../plugins/protect/fb_connect/header.fb_connect.inc.html */ ?>

<?php 
global $db, $plugin_config, $config;

$fb_connect_config = $plugin_config['protect']['fb_connect'];
$fbappid	 = trim($fb_connect_config['appid']);
$fb_login_url = $config['root_url']."/signup.php";
$fb_logout_url = $config['root_url']."/logout.php";

 ?>
<div id="fb-root"></div>
<script type="text/javascript" smarty="smarty">
	<?php echo '
	window.fbAsyncInit = function() {
	FB.init({appId: \'';  echo $fbappid;  echo '\', status: true, cookie: true,
			 xfbml: true});
	
	FB.Event.subscribe(\'auth.sessionChange\', function(response) {
		if (response.session) {
		  // A user has logged in, and a new cookie has been saved
		  document.location.href = "';  echo $fb_login_url;  echo '";
		} else {
		  // The user has logged out, and the cookie has been cleared
		  document.location.href = "';  echo $fb_logout_url;  echo '";
		}
	});
	};
  (function() {
	var e = document.createElement(\'script\');
	e.type = \'text/javascript\';
	e.src = document.location.protocol +
	  \'//connect.facebook.net/en_GB/all.js\';
	e.async = true;
	document.getElementById(\'fb-root\').appendChild(e);
  }());
'; ?>

</script><!--<?php echo ' '; ?>
-->