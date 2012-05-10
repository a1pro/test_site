<?php /* Smarty version 2.6.2, created on 2010-08-09 23:20:08
         compiled from index.html */ ?>
<?php $this->assign('title', $this->_tpl_vars['config']['site_title']); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

#_TPL_INDEX_IF_YOU_ARE_REGISTERED|<a href="member.php">|</a>#.
#_TPL_INDEX_IF_YOU_ARE_NOT_REGISTERED|<a href="signup.php">|</a>#.

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>