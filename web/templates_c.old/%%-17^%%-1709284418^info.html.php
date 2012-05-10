<?php /* Smarty version 2.6.2, created on 2010-08-09 22:42:28
         compiled from admin/info.html */ ?>
<?php $this->assign('title', 'Version Info'); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/header.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<center>
<br /><h3><?php echo $this->_tpl_vars['title']; ?>
</h3>

<table><tr><td style='font-size: 1.0em;' nowrap>
<ul>
    <li>Script Version: <b><?php echo $this->_tpl_vars['config']['version']; ?>
</b>
</ul>
</td></tr></table><br /><br />

<?php phpinfo(1|4|8|16|32) ?>


<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/footer.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
