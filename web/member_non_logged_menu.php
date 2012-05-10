<?php
$base_url = "http://getaudiofromvideo.com/search/";
?>
            <div id="container">

            	<div id="header">
                    <div style="width:200px; height:105px; float:left;"><a href="<?php echo $base_url;?>">
                            <img src="<?php echo $base_url;?>img/blank.png" alt="" /></a>
                    </div>
                </div>

            	<div id="navigation">
			<form action="<?php echo $base_url;?>members/login.php" name="login" method="post" >
                            <ul class="loginMenu">
                            <li id="username"> Username <input name="amember_login" type="text" class="style05" id="username2"/></li>
                            <li id="password"> Password <input name="amember_pass" type="password" class="style05" id="password2"/></li>
                            <li id="submit"> <input name="submit" value="login" src="<?php echo $base_url;?>img/go_button.gif" type="image" id="submit2"/></li>
                            </ul>
                        </form>
                      <br /><br />

                	  <ul class="subnavMenu" style="margin:5px 0px 0px 0px;">
<!-- TODO fix url -->
                      	<li id="register"><a href="<?php echo $base_url;?>members/signup.php">
                        <img src="<?php echo $base_url;?>img/menu/register_off.gif" alt="Register Now" />
                        </a></li>
                      </ul>

                      <br /><br />

                        <div style="margin:14px 0px 0px 0px">

                        <table style="margin-top: -27px;z-index:99;position:absolute" width="100%"  border="0" cellpadding="0" cellspacing="0">
                          <tr>
                          <td width="20%">&nbsp;</td>
                          <td width="975" align="left">

                            <script type="text/javascript">

                                function bookmark_us(url, title){

                                if (window.sidebar) // firefox
                                window.sidebar.addPanel(title, url, "");
                                else if(window.opera && window.print){ // opera
                                var elem = document.createElement('a');
                                elem.setAttribute('href',url);
                                elem.setAttribute('title',title);
                                elem.setAttribute('rel','sidebar');
                                elem.click();
                                }
                                else if(document.all)// ie
                                window.external.AddFavorite(url, title);
                                }
                            </script>

                            <div class="nav" style="font-weight:bold; font-size:14px; ">
                                <ul>
                            <li><a  class="Home"  title="Home" href="<?php echo $base_url;?>index.php"><span>Home</span></a></li>
                            <li><a  title="About Us" href="<?php echo $base_url;?>info/aboutus" ><span>About Us</span></a></li>
                            <li><a  title="FAQ" href="<?php echo $base_url;?>info/faq" ><span>FAQ</span></a></li>
                            <li><a  title="Testimonials" href="<?php echo $base_url;?>info/testimonials" ><span>Testimonials</span></a></li>
                            <li><a  title="Terms" href="<?php echo $base_url;?>info/terms" ><span>Terms of Use</span></a></li>
                            <li><a  title="Privacy Policy" href="<?php echo $base_url;?>info/privacy" ><span>Privacy Policy</span></a></li>
                            <li><a  title="Stats" href="<?php echo $base_url;?>info/statistics" ><span>Stats</span></a></li>
                            <li><a  title="Contact Us" href="<?php echo $base_url;?>info/contact" ><span>Contact Us</span></a></li>
                                </ul>
                            </div><!--//nav-->

                            </td></tr></table>

                            </div>

                </div><!--navigation-->
