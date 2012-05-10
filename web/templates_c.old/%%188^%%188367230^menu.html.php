<?php /* Smarty version 2.6.2, created on 2010-08-09 20:46:57
         compiled from admin/menu.html */ ?>
<html>
<head>
    <title>aMember Control Panel</title>
    <base target="right">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->_tpl_vars['config']['root_surl']; ?>
/templates/css/admin.css" />
</head>
<body bgcolor=#f0f0e0 leftmargin=0 marginwidth=0 topmargin=3>
<div style='text-align: center; font-weight: bold; font-size: 11pt;'><a href="<?php echo $this->_tpl_vars['config']['root_url']; ?>
/admin/index.php?page=blank">aMember CP</div>
<hr width="98%" />
<?php echo $this->_tpl_vars['menu_html']; ?>


<center><form method=get target=right action="q.php" style="clear: none;">
    <input type=text name=q id="q" size=10 class=small title="Enter part of username, email or name">
    <input type=submit value="Lookup" class=small>
</form></center>

<ul style='margin-left: 0.6em; padding: 0;'>    
<li type=square><a target=_blank href="http://manual.amember.com/">aMember Pro Manual</a>
<li type=square><a target=_blank href="http://www.amember.com/site_add.php">List your site for free</a>
<li type=square><a target=_blank href="http://www.amember.com/forum/forumdisplay.php?f=18">Write a testimonial</a>
<?php if (is_lite ( )): ?>
<li type=square><a target=_blank href="http://www.cgi-central.net/scripts/amember/lite_upgrade.php"><font color=red><b>Upgrade to aMember Pro</b></font></a>
<?php endif; ?>
</ul>
</body>
</html>