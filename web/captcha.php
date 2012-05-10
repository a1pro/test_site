<?php
define('MEM_DEBUG', 0);
define('TM_DEBUG', 0);
/*
*  Display CAPTCHA image and save it to the session
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Captcha script
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1640 $)
*
* Please direct bug reports,suggestions or feedbacks to the cgi-central support
* http://www.cgi-central.net/support/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*/
require_once 'config.inc.php';
require_once $config['root_dir'] . '/includes/kcaptcha/kcaptcha.php';

# KCAPTCHA configuration file

$captcha_conf = array();
$captcha_conf['alphabet'] = "0123456789abcdefghijklmnopqrstuvwxyz"; # do not change without changing font files!

# symbols used to draw CAPTCHA
//$allowed_symbols = "0123456789"; #digits
$captcha_conf['allowed_symbols'] = "23456789abcdeghkmnpqsuvxyz"; #alphabet without similar symbols (o=0, 1=l, i=j, t=f)

# folder with fonts
$captcha_conf['fontsdir'] = 'fonts';    

# CAPTCHA string length
$captcha_conf['length'] = mt_rand(4,5); # random 5 or 6
//$length = 6;

# CAPTCHA image size (you do not need to change it, whis parameters is optimal)
$captcha_conf['width'] = 122;
$captcha_conf['height'] = 62;

# symbol's vertical fluctuation amplitude divided by 2
$captcha_conf['fluctuation_amplitude'] = 6;

# increase safety by prevention of spaces between symbols
$captcha_conf['no_spaces'] = false;

# show credits
$captcha_conf['show_credits'] = false; # set to false to remove credits line. Credits adds 12 pixels to image height
$captcha_conf['credits'] = ' '; # if empty, HTTP_HOST will be shown

# CAPTCHA image colors (RGB, 0-255)
//$foreground_color = array(0, 0, 0);
//$background_color = array(220, 230, 255);
$captcha_conf['foreground_color'] = array(mt_rand(0,100), mt_rand(0,100), mt_rand(0,100));
$captcha_conf['background_color'] = array(mt_rand(200,255), mt_rand(200,255), mt_rand(200,255));

# JPEG quality of CAPTCHA image (bigger is better quality, but larger file size)
$captcha_conf['jpeg_quality'] = 80;


########## End of CAPTCHA configuration

// Print headers to disable image caching
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


$captcha = new KCAPTCHA($captcha_conf);
$_SESSION['amember_captcha'] = $captcha->getKeyString();

