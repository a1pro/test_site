<?php /* Smarty version 2.6.2, created on 2010-08-09 20:47:05
         compiled from admin/setup.html */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'admin/setup.html', 10, false),)), $this); ?>
<?php $this->assign('title', 'aMember Pro Configuration'); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/header.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<script type="text/javascript" src="<?php echo $this->_tpl_vars['config']['root_surl']; ?>
/includes/jquery/jquery.js?smarty"></script><!--<?php echo ' '; ?>
-->
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/js.other_db.js", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<?php $this->assign('selected', $this->_tpl_vars['notebook']); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/setup_nb.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<center>
<h3><?php echo $this->_tpl_vars['title']; ?>
 : <?php echo ((is_array($_tmp=$this->_tpl_vars['notebook'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
<br />
<font color=gray size=1><b><?php echo $this->_tpl_vars['notebooks'][$this->_tpl_vars['notebook']]['comment']; ?>
</b></font><br />
</h3>
<a name="e">&nbsp;</a>
<?php if ($this->_tpl_vars['error']): ?>
<table><tr><td>
<?php if (count($_from = (array)$this->_tpl_vars['error'])):
    foreach ($_from as $this->_tpl_vars['e']):
?>
<li><font color=red><b><?php echo ((is_array($_tmp=$this->_tpl_vars['e'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
</b></font>
<?php endforeach; unset($_from); endif; ?>
</td></tr></table>
<?php endif; ?>

<?php if ($this->_tpl_vars['notebook'] == 'Plugins'): ?>
<div align=left style='font-size: xx-small; width: 300px;'>
You may enable <b>cc_demo</b> payment plugin for testing purposes.
Once you have it enabled, go to <b>aMember CP -> Setup -> CC Demo</b> and read readme.
Don't forget to disable it when testing is finished!<br /><br />
</div>
<?php endif; ?>

<form method=post action="setup.php#e">
<table class=vedit>
<?php if (count($_from = (array)$this->_tpl_vars['fields'])):
    foreach ($_from as $this->_tpl_vars['f']):
?>
<?php if ($this->_tpl_vars['f']['type'] == 'header'): ?>
<tr class="odd">
<td colspan=2 style='text-align: center; font-weight: bold'><?php echo $this->_tpl_vars['f']['title']; ?>
</td>
</tr>
<?php elseif (! $this->_tpl_vars['f']['special_edit']): ?>
<tr>
    <th><b><?php echo $this->_tpl_vars['f']['title']; ?>
</b><br /><small><?php echo $this->_tpl_vars['f']['desc']; ?>
</small></th>
    <td><?php echo $this->_tpl_vars['f']['edit']; ?>
</td>
</tr>
<?php else:  echo $this->_tpl_vars['f']['edit'];  endif; ?>
<?php endforeach; unset($_from); endif; ?>
</table>
<br />
<input type=submit value="&nbsp;&nbsp;&nbsp;&nbsp;Save&nbsp;&nbsp;&nbsp;&nbsp;">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=button value="&nbsp;&nbsp;&nbsp;&nbsp;Back&nbsp;&nbsp;&nbsp;&nbsp;" onclick="history.back(-1)">
<input type=hidden name=notebook value="<?php echo ((is_array($_tmp=$this->_tpl_vars['notebook'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
<input type=hidden name=save value=1>
</form>

<?php if ($this->_tpl_vars['readme'] != ""): ?>
<table bgcolor=#e0f0f0><tr><td>
<pre style='font-size: 9pt; text-align: left;'><?php echo $this->_tpl_vars['readme']; ?>
</pre>
</td></tr></table>
<?php endif; ?>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/footer.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>