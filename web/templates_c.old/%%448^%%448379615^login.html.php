<?php /* Smarty version 2.6.2, created on 2010-08-11 00:30:28
         compiled from login.html */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'login.html', 10, false),)), $this); ?>
<?php $this->assign('title', @constant('_TPL_LOGIN_PLEASE_LOGIN')); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "error.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<form name="login" method="post" <?php if ($this->_tpl_vars['form_action']): ?>action="<?php echo $this->_tpl_vars['form_action']; ?>
"<?php endif; ?>>

<table class="vedit" >
    <tr>
        <th>#_TPL_LOGIN_USERNAME#</th>
        <td><input type="text" name="amember_login" size="15" value="<?php echo ((is_array($_tmp=$_REQUEST['amember_login'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" /></td>
    </tr>
    <tr>
        <th>#_TPL_LOGIN_PASSWORD#</th>
        <td><input type="password" name="amember_pass" size="15" /></td>
    </tr>
    <?php if ($this->_tpl_vars['this_config']['remember_login'] && ! $this->_tpl_vars['this_config']['remember_auto']): ?>
    <tr>
        <td colspan="2" style='padding:0px; padding-bottom: 2px;'>
<input type="checkbox" name="remember_login" value="1">
<span class="small">#_TPL_LOGIN_REMEMBER_MY_PASSWORD#</span>
        </td>
    </tr>
    <?php endif; ?>
</table>
<input type="hidden" name="login_attempt_id" value="<?php print time(); ?>" />
<?php if ($this->_tpl_vars['redirect_url']): ?>
    <input type="hidden" name="amember_redirect_url" value="<?php echo ((is_array($_tmp=$this->_tpl_vars['redirect_url'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" />
<?php endif; ?>
<br />

<input type="submit" value="&nbsp;&nbsp;&nbsp;#_TPL_LOGIN_BTN_LOGIN#&nbsp;&nbsp;&nbsp;" />&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="button" value="&nbsp;&nbsp;&nbsp;#_TPL_LOGIN_BTN_BACK#&nbsp;&nbsp;&nbsp;" onclick="history.back(-1)" />
</form>

<br />
<p>#_TPL_LOGIN_NOT_REGISTERED_YET# <a href="<?php echo $this->_tpl_vars['config']['root_url']; ?>
/<?php if ($this->_tpl_vars['affiliates_signup']): ?>aff_<?php endif; ?>signup.php">#_TPL_LOGIN_SIGNUP_HERE#</a></p>
<br />

<h3>#_TPL_LOGIN_LOST_PASSWORD#?</h3>
<form name="sendpass" method="post" action="<?php echo $this->_tpl_vars['config']['root_url']; ?>
/sendpass.php">
<table align="center" class="vedit" width="30%">
    <tr>
        <th>#_TPL_LOGIN_ENTER_YOUR_EMAIL_OR_USERNAME#</th>
        <td><input type="text" name="login" size="12" /></td>
    </tr>
</table>
<input type="submit" value="#_TPL_LOGIN_BTN_GET_PASSWORD#" />
</form>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>