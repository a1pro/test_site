<?php /* Smarty version 2.6.2, created on 2010-08-09 23:45:22
         compiled from admin/product_saved.html */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'admin/product_saved.html', 5, false),)), $this); ?>
<?php $this->assign('title', 'Information has been saved'); ?>
<?php $this->assign('text', 'Product Info Updated'); ?>
<html><head>
<title><?php echo $this->_tpl_vars['title']; ?>
</title>
<meta http-equiv="Refresh" CONTENT="3; URL=<?php echo ((is_array($_tmp=$this->_tpl_vars['url'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
</head>
<body>
<br /><br />
<table height="25%" width="70%" border="0" align="center"><tr valign="middle" align=center><td>
<b><?php echo $this->_tpl_vars['text']; ?>
</b><br />
<br />
<font size=1 face="Verdana"><a href="<?php echo ((is_array($_tmp=$this->_tpl_vars['url'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">Click here if you do not
want to wait any longer (or if your browser does not automatically forward you).</a>  
</p>

</td></tr></table>
</body></html>