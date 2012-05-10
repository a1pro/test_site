<?php /* Smarty version 2.6.2, created on 2010-08-11 00:30:28
         compiled from error.inc.html */ ?>
<?php if ($this->_tpl_vars['error']): ?>
<a name="e">&nbsp;</a>
<table class="errmsg" <?php if ($this->_tpl_vars['style'] != ""): ?>style="$style"<?php endif; ?> summary="Errors">
<tr><td><ul>
<?php if (count($_from = (array)$this->_tpl_vars['error'])):
    foreach ($_from as $this->_tpl_vars['e']):
?>
<li><?php echo $this->_tpl_vars['e']; ?>
</li>
<?php endforeach; unset($_from); endif; ?>
</ul></td></tr></table>
<br />
<?php endif; ?>