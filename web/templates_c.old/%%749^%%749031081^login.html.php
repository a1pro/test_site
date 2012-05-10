<?php /* Smarty version 2.6.2, created on 2010-08-09 20:46:51
         compiled from admin/login.html */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'admin/login.html', 12, false),)), $this); ?>
<?php $this->assign('title', "Administrator Log-in"); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/header.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<!--<?php echo ' --><script language="JavaScript">
<!--
if (self.location.href != top.location.href) {
    top.location.href = self.location.href;
}
// -->
</script><!--{literal} '; ?>
-->

<center>
<h3><?php echo ((is_array($_tmp=$this->_tpl_vars['title'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
</h3>


<?php if ($this->_tpl_vars['error']): ?>
<br />
<font color=red><b><?php echo ((is_array($_tmp=$this->_tpl_vars['error'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
</b></font>
<br /><br />
<?php endif; ?>
<br /><br /><br />
<form method="post" action="<?php echo ((is_array($_tmp=$_SERVER['PHP_SELF'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
<table align=center class=vedit>
    <tr>
        <th><b>Login</b></th>
        <td><input type=text name=login value='' size=12></td>
    </tr>
    <tr>
        <th><b>Password</b></th>
        <td><input type=password name=passwd value='' size=12></td>
    </tr>
</table>
<br />
    <input type=hidden name=do_login value=1>
    <input type=submit value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Login&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;">
</form>


<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/footer.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
