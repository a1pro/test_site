<?php /* Smarty version 2.6.2, created on 2010-11-16 06:30:51
         compiled from fatal_error.html */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'fatal_error.html', 17, false),)), $this); ?>
<?php if (@constant('_TPL_FATAL_ERROR_TITLE')): ?>
    <?php $this->assign('title', @constant('_TPL_FATAL_ERROR_TITLE'));  else: ?>
    <?php $this->assign('title', 'An Error has occured');  endif;  $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<div id="center">
<div id="d_over_iframe"></div>
<div id="d_over_iframe"></div>

<!--MAIN CONTEINER-->
<div id="main_t">
<table><tr><td><ul>
<?php if (is_array ( $this->_tpl_vars['error'] )):  if (count($_from = (array)$this->_tpl_vars['error'])):
    foreach ($_from as $this->_tpl_vars['e']):
?>
<li><span style="color: red; font-weight: bold;"><?php if ($this->_tpl_vars['is_html']):  echo $this->_tpl_vars['e'];  else:  echo ((is_array($_tmp=$this->_tpl_vars['e'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp));  endif; ?></span></li>
<?php endforeach; unset($_from); endif;  else: ?>
<span style="color: red; font-weight: bold;"><?php if ($this->_tpl_vars['is_html']):  echo $this->_tpl_vars['error'];  else:  echo ((is_array($_tmp=$this->_tpl_vars['error'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp));  endif; ?></span>
<?php endif; ?>
<ul></td></tr></table>


<br /><br />
Please contact webmaster: <a href="mailto:<?php echo ((is_array($_tmp=$this->_tpl_vars['admin_email'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"><?php echo ((is_array($_tmp=$this->_tpl_vars['admin_email'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
</a>.
</div>
</div>
</div>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>