<?php /* Smarty version 2.6.2, created on 2010-08-09 23:43:03
         compiled from admin/product.html */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'admin/product.html', 29, false),array('function', 'html_options', 'admin/product.html', 52, false),)), $this); ?>
<?php if ($this->_tpl_vars['add']): ?>
<?php $this->assign('title', "Add Product/Subscription"); ?>
<?php else: ?>
<?php $this->assign('title', "Edit Product/Subscription"); ?>
<?php endif; ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/header.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/js.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<center>
<br /><h3><?php echo $this->_tpl_vars['title']; ?>
</h3>

<a name="e">&nbsp;</a>
<?php if ($this->_tpl_vars['error']): ?>
<table><tr><td>
<?php if (count($_from = (array)$this->_tpl_vars['error'])):
    foreach ($_from as $this->_tpl_vars['e']):
?>
<li><font color=red><b><?php echo $this->_tpl_vars['e']; ?>
</b></font>
<?php endforeach; unset($_from); endif; ?>
</td></tr></table>
<?php endif; ?>


<form method="post" action="products.php#e">
<table class="vedit" width="80%">
<tr>
    <th width=50%><b>Product #</b></th>
    <td> <b><?php echo $this->_tpl_vars['p']['product_id']; ?>
</b></td>
</tr>
<tr>
    <th><b>Title <font color=red>*</font></b><br /><small>Will be displayed to user</small></th>
    <td><input type=text name=title <?php if ($this->_tpl_vars['p']['title']): ?>value="<?php echo ((is_array($_tmp=$this->_tpl_vars['p']['title'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"<?php endif; ?>> </td>
</tr>
<tr>
    <th><b>Description <font color=red>*</font></b><br /><small>Will be displayed to user<br />
    on signup page below the title</small></th>
    <td><textarea rows=5 cols=40 name=description><?php echo ((is_array($_tmp=$this->_tpl_vars['p']['description'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'html') : smarty_modifier_escape($_tmp, 'html')); ?>
</textarea> </td>
</tr>
<tr class="odd">
<td colspan=2 style='text-align: center; font-weight: bold'>Subscription Terms</td>
</tr>
<tr>
    <th><b>Price <font color=red>*</font></b><br />
    <small>Enter only digits (and period, if necessary).<br />
    Do not enter commas ',' or dollar sign '$'</small></th>
    <td><input type=text name=price <?php if ($this->_tpl_vars['p']['price'] != ""): ?>value="<?php echo ((is_array($_tmp=$this->_tpl_vars['p']['price'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"<?php endif; ?>> </td>
</tr>

<?php if (count($_from = (array)$this->_tpl_vars['product_additional_fields'])):
    foreach ($_from as $this->_tpl_vars['f']):
?>
<?php if ($this->_tpl_vars['f']['type'] == 'select'): ?>
<tr>
    <th><b><?php echo $this->_tpl_vars['f']['title']; ?>
</b><br /><small><?php echo $this->_tpl_vars['f']['description']; ?>
</small></th>
    <?php $this->assign('field_name', $this->_tpl_vars['f']['name']); ?>
    <td><select name="<?php echo ((is_array($_tmp=$this->_tpl_vars['field_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" size='<?php echo $this->_tpl_vars['f']['size']; ?>
'>
    <?php echo smarty_function_html_options(array('options' => $this->_tpl_vars['f']['options'],'selected' => $this->_tpl_vars['p'][$this->_tpl_vars['field_name']]), $this);?>

    </select>
    </td>
</tr>
<?php elseif ($this->_tpl_vars['f']['type'] == 'multi_select'): ?>
<tr>
    <th><b><?php echo $this->_tpl_vars['f']['title']; ?>
</b><br /><small><?php echo $this->_tpl_vars['f']['description']; ?>
</small></th>
    <?php $this->assign('field_name', $this->_tpl_vars['f']['name']); ?>
    <td><select name="<?php echo ((is_array($_tmp=$this->_tpl_vars['field_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
[]" size='<?php echo $this->_tpl_vars['f']['size']; ?>
' multiple>
    <?php echo smarty_function_html_options(array('options' => $this->_tpl_vars['f']['options'],'selected' => $this->_tpl_vars['p'][$this->_tpl_vars['field_name']]), $this);?>

    </select>
    </td>
</tr>
<?php elseif ($this->_tpl_vars['f']['type'] == 'textarea'): ?>
<tr>
    <th><b><?php echo $this->_tpl_vars['f']['title']; ?>
</b><br /><small><?php echo $this->_tpl_vars['f']['description']; ?>
</small></th>
    <?php $this->assign('field_name', $this->_tpl_vars['f']['name']); ?>
    <td><textarea name="<?php echo ((is_array($_tmp=$this->_tpl_vars['field_name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" rows=5 cols=30><?php echo $this->_tpl_vars['p'][$this->_tpl_vars['field_name']]; ?>
</textarea>
    </td>
</tr>
<?php elseif ($this->_tpl_vars['f']['type'] == 'checkbox'): ?>
<tr>
    <th><b><?php echo $this->_tpl_vars['f']['title']; ?>
</b><br /><small><?php echo $this->_tpl_vars['f']['description']; ?>
</small></th>
    <?php $this->assign('field_name', $this->_tpl_vars['f']['name']); ?>
    <td><input type="hidden" name="<?php echo $this->_tpl_vars['field_name']; ?>
" value="" />
    <input type="checkbox" style='border-width: 0px' name="<?php echo $this->_tpl_vars['field_name']; ?>
" value=1 <?php if ($this->_tpl_vars['p'][$this->_tpl_vars['field_name']]): ?>checked<?php endif; ?> />
    </td>
</tr>
<?php elseif ($this->_tpl_vars['f']['type'] == 'period'): ?>
<tr>
    <th><b><?php echo $this->_tpl_vars['f']['title']; ?>
</b><br /><small><?php echo $this->_tpl_vars['f']['description']; ?>
</small></th>
    <?php $this->assign('field_name', $this->_tpl_vars['f']['name']); ?>
    <td>
    <?php if ($this->_tpl_vars['field_name'] == 'expire_days'): ?>
    <input type="text" name="<?php echo $this->_tpl_vars['field_name']; ?>
[count]" id="<?php echo $this->_tpl_vars['field_name']; ?>
[count]" 
        value="<?php echo $this->_tpl_vars['p'][$this->_tpl_vars['field_name']]['count']; ?>
" size="12" maxlength="10" />
    <select name="<?php echo $this->_tpl_vars['field_name']; ?>
[unit]" name="<?php echo $this->_tpl_vars['field_name']; ?>
[unit]" size="1" >
    <?php echo smarty_function_html_options(array('options' => $this->_tpl_vars['period_options'],'selected' => $this->_tpl_vars['p'][$this->_tpl_vars['field_name']]['unit']), $this);?>

    </select>
    <?php else: ?>
    <input type="text" name="<?php echo $this->_tpl_vars['field_name']; ?>
[count]" id="<?php echo $this->_tpl_vars['field_name']; ?>
[count]" 
        value="<?php echo $this->_tpl_vars['p'][$this->_tpl_vars['field_name']]['count']; ?>
" size="4"  />
    <select name="<?php echo $this->_tpl_vars['field_name']; ?>
[unit]" name="<?php echo $this->_tpl_vars['field_name']; ?>
[unit]" size="1" >
    <?php echo smarty_function_html_options(array('options' => $this->_tpl_vars['trial_period_options'],'selected' => $this->_tpl_vars['p'][$this->_tpl_vars['field_name']]['unit']), $this);?>

    </select>

    <?php endif; ?>
    </td>
</tr>
<?php elseif ($this->_tpl_vars['f']['type'] == 'header'): ?>
<tr class="odd">
<td colspan=2 style='text-align: center; font-weight: bold'><?php echo $this->_tpl_vars['f']['title']; ?>
</td>
</tr>
<?php elseif ($this->_tpl_vars['f']['type'] == 'hidden'): ?>
<?php else: ?>
<tr>
    <th><b><?php echo $this->_tpl_vars['f']['title']; ?>
</b><br /><small><?php echo $this->_tpl_vars['f']['description']; ?>
</small></th>
    <?php $this->assign('field_name', $this->_tpl_vars['f']['name']); ?>
    <td><input type=text name=<?php echo $this->_tpl_vars['field_name']; ?>
 <?php if ($this->_tpl_vars['p'][$this->_tpl_vars['field_name']] != ""): ?>value="<?php echo ((is_array($_tmp=$this->_tpl_vars['p'][$this->_tpl_vars['field_name']])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"<?php endif; ?>
    <?php if ($this->_tpl_vars['f']['size']): ?>size="<?php echo ((is_array($_tmp=$this->_tpl_vars['f']['size'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"<?php endif; ?>
    id="t_<?php echo ((is_array($_tmp=$this->_tpl_vars['f']['name'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">
    <?php if ($this->_tpl_vars['f']['name'] == 'terms'): ?>
    <div class="small"><b>Default:</b> <?php echo ((is_array($_tmp=$this->_tpl_vars['p']['terms_default'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
<br />
    <i>(will be automatically updated according to change of product settings)</i>
    </div>
    <?php endif; ?>
    </td>
</tr>
<?php endif; ?>
<?php endforeach; unset($_from); endif; ?>

</table>
<!--<?php echo ' --><script language="javascript">
  frm = document.forms[0];
  el = frm.signup_email_checkbox;
  if (el)
    x = (el.checked) ? showLayer(\'signup_email_div\') : hideLayer(\'signup_email_div\');
</script><!--{literal} '; ?>
-->

<br />
<input type=submit value="&nbsp;&nbsp;&nbsp;&nbsp;Save&nbsp;&nbsp;&nbsp;&nbsp;">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=button value="&nbsp;&nbsp;&nbsp;&nbsp;Back&nbsp;&nbsp;&nbsp;&nbsp;" onclick="history.back(-1)">
<input type=hidden name=action value=<?php if ($this->_tpl_vars['add']): ?>add_save<?php else: ?>edit_save<?php endif; ?>>
<input type=hidden name=product_id value="<?php echo ((is_array($_tmp=$this->_tpl_vars['p']['product_id'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
">

<br /><br />
<table class="vedit" width="80%">
<tr class="odd"><td colspan=2 align="center"><b>E-Mail Settings</b></td></tr>
<tr>
    <th width=50%><b>Use Customized Signup E-Mail</b><br />
    <small>use special e-mail message for this product</small></th>
    <td>
    <a name="send_signup_mail"></a>
    <?php if (! $this->_tpl_vars['p']['product_id']): ?>
    <i>Please save new product first, then click Edit to change this option</i>
    <?php else: ?>
    <input type=checkbox name="signup_email_checkbox" onclick="this.checked ? showLayer('signup_email_div') : hideLayer('signup_email_div')"
    <?php if ($this->_tpl_vars['send_signup_mail_tpl']): ?>checked<?php endif; ?>>
    <span id="signup_email_div" style="<?php if (! $this->_tpl_vars['send_signup_mail_tpl']): ?>visibility: hidden<?php endif; ?>">
    <a href="email_templates.php?a=edit&tpl=send_signup_mail&product_id=<?php echo $this->_tpl_vars['p']['product_id']; ?>
">Edit E-Mail Template</a>
    / <a href="email_templates.php?a=del&tpl=send_signup_mail&product_id=<?php echo $this->_tpl_vars['p']['product_id']; ?>
" onclick="return confirm('Are you sure?')">Delete</a>

    </span>
    <?php endif; ?>
    </td>
</tr>
<tr>
    <th><b>Expire Notifications</b><br />
    <small>send email to user when his subscription expires<br />
	email will not be sent for products with recurring billing<br />
     0 - send message in the day of expiration<br />
    -1 - send message one day after expiration<br />
     2 - send message 2 days before expiration<br />
	there can be comma-separated list of values<br />
    </small>
    </th><td valign="top">
    <a name="mail_expire"></a>

    <label for="dme0">
    <input id="dme0" type=radio name="dont_mail_expire" value='' style="border: none;" <?php if ($this->_tpl_vars['p']['dont_mail_expire'] == ""): ?>checked<?php endif; ?> />
    Use default setting (aMember CP->Setup->E-Mail)</label><br />

    <label for="dme1" >
    <input id="dme1" type=radio name="dont_mail_expire" value='1' style="border: none;" <?php if ($this->_tpl_vars['p']['dont_mail_expire'] == '1'): ?>checked<?php endif; ?> />
    Do not email expiration notices for this product (regardless of global setting)</label><br />

    <label for="dme2" >
    <input id="dme2" type=radio name="dont_mail_expire" value='2' style="border: none;" <?php if ($this->_tpl_vars['p']['dont_mail_expire'] == '2'): ?>checked<?php endif; ?> />
    EMail expiration notices for this product (regardless of global setting)</label><br />

    <br />


    <?php if (! $this->_tpl_vars['p']['product_id']): ?>
    <i>Please save new product first, then click Edit to change this option</i>
    <?php elseif (! $this->_tpl_vars['config']['demo']): ?>
    <?php echo $this->_tpl_vars['mail_expire_field']; ?>

    <?php endif; ?>
    </td>
</tr>
<?php if (! $this->_tpl_vars['config']['demo']): ?>
<tr>
    <th><b>Send Automatic Emails</b><br />
    <small>user can receive automatic emails<br />
    after signup. You can setup series of emails<br />
    to be sent.<br />
    0 - message will be sent immediately after purchase<br />
    2 - message will be sent 2 days after purchase<br />
    </small>
    </th><td valign="top">

    <a name="mail_autoresponder"></a>
    <?php if (! $this->_tpl_vars['p']['product_id']): ?>
    <i>Please save new product first, then click Edit to change this option</i>
    <?php else: ?>
    <?php echo $this->_tpl_vars['mail_autoresponder_field']; ?>

    <?php endif; ?>
    </td>
</tr>
<?php endif; ?>

<?php if (! $this->_tpl_vars['config']['demo']): ?>
<tr>
    <th><b>"Not-Completed Payment" Notification</b><br />
    <small>number of days when above notification must be send.<br/>
1 means 1 day after payment<br/>
2 means 2 days after payment<br/>
    </small>
    </th><td valign="top">

    <a name="mail_not_completed"></a>
    <?php if (! $this->_tpl_vars['p']['product_id']): ?>
    <i>Please save new product first, then click Edit to change this option</i>
    <?php else: ?>
    <?php echo $this->_tpl_vars['mail_not_completed_field']; ?>

    <?php endif; ?>
    </td>
</tr>
<?php endif; ?>


</table>


<br />
<input type=submit value="&nbsp;&nbsp;&nbsp;&nbsp;Save&nbsp;&nbsp;&nbsp;&nbsp;">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=button value="&nbsp;&nbsp;&nbsp;&nbsp;Back&nbsp;&nbsp;&nbsp;&nbsp;" onclick="history.back(-1)">


</form>
<?php if (defined ( 'INCREMENTAL_CONTENT_PLUGIN' )):  $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "../plugins/protect/incremental_content/product.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
  endif; ?>
</center>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/footer.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>