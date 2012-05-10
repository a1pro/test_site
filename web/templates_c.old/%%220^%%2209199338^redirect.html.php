<?php /* Smarty version 2.6.2, created on 2010-11-16 07:54:58
         compiled from redirect.html */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'redirect.html', 3, false),)), $this); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html><head>
<title><?php echo $this->_tpl_vars['title']; ?>
</title>
<meta http-equiv="Refresh" CONTENT="1; URL=<?php echo ((is_array($_tmp=$this->_tpl_vars['url'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
</head>
<body>
<br /><br />
<table height="90%" width="70%" border="0" align="center"><tr valign="middle" align=center><td>
<b><?php echo $this->_tpl_vars['text']; ?>
</b><br />
<br />
<font size=1 face="Verdana"><a href="<?php echo ((is_array($_tmp=$this->_tpl_vars['url'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">#_TPL_REDIRECT_CLICKHERE#</a></font>


</td></tr></table>
</body></html>