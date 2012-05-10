<?php /* Smarty version 2.6.2, created on 2010-08-09 23:43:01
         compiled from admin/products.html */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'cycle', 'admin/products.html', 21, false),array('modifier', 'amember_date_format', 'admin/products.html', 28, false),array('modifier', 'default', 'admin/products.html', 34, false),)), $this); ?>
<?php $this->assign('title', "Products/Subscriptions Types List"); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/header.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<center>
<br /><h3><?php echo $this->_tpl_vars['title']; ?>
</h3>

<table class=hedit width=95% border=1 bordercolor=#a0a0a0>
<tr>
    <th>Product #</th>
    <th>Title</th>
    <th>Price</th>
    <th>Duration</th>
    <th>Scope</th>
    <th>Group</th>
    <th>Sort</th>
    <th>Recurr.</th>
    <th>Agr.</th>
    <th>URL</th>
    <th width=5%><font color=606060>Actions</font></th>
</tr>
<?php if (count($_from = (array)$this->_tpl_vars['pl'])):
    foreach ($_from as $this->_tpl_vars['p']):
?>
<tr class=<?php echo smarty_function_cycle(array('values' => "xx,odd"), $this);?>
>
    <td> <a href="products.php?action=edit&product_id=<?php echo $this->_tpl_vars['p']['product_id']; ?>
"><b><?php echo $this->_tpl_vars['p']['product_id']; ?>
</b></a> </td>
    <td> <?php echo $this->_tpl_vars['p']['title']; ?>
 </td>
    <td> <?php echo $this->_tpl_vars['p']['price']; ?>
 </td>
    <td> 
    <?php if ($this->_tpl_vars['p']['expire_days'] == @constant('MAX_SQL_DATE')): ?>LIFETIME
    <?php elseif (strlen ( $this->_tpl_vars['p']['expire_days'] ) == 10): ?>
    <?php echo ((is_array($_tmp=$this->_tpl_vars['p']['expire_days'])) ? $this->_run_mod_handler('amember_date_format', true, $_tmp) : smarty_modifier_amember_date_format($_tmp)); ?>

    <?php else: ?>
    <?php echo $this->_tpl_vars['p']['expire_days']; ?>

    <?php endif; ?> 
    
    </td>
    <td> <?php echo ((is_array($_tmp=@$this->_tpl_vars['p']['scope'])) ? $this->_run_mod_handler('default', true, $_tmp, 'all') : smarty_modifier_default($_tmp, 'all')); ?>
 </td>
    <td> <?php echo ((is_array($_tmp=@$this->_tpl_vars['p']['price_group'])) ? $this->_run_mod_handler('default', true, $_tmp, '0') : smarty_modifier_default($_tmp, '0')); ?>
 </td>
    <td> <?php echo ((is_array($_tmp=@$this->_tpl_vars['p']['order'])) ? $this->_run_mod_handler('default', true, $_tmp, '0') : smarty_modifier_default($_tmp, '0')); ?>
 </td>
    <td> <?php if ($this->_tpl_vars['p']['is_recurring']): ?>Yes<?php else: ?>No<?php endif; ?> </td>
    <td> <?php if ($this->_tpl_vars['p']['need_agreement']): ?>Yes<?php else: ?>No<?php endif; ?> </td>
    <td> <a href="<?php echo $this->_tpl_vars['p']['url']; ?>
" target=_blank><?php echo $this->_tpl_vars['p']['url']; ?>
</a> </td>
    <td nowrap>
            <a href="products.php?action=edit&amp;product_id=<?php echo $this->_tpl_vars['p']['product_id']; ?>
">Edit</a>
            <a href="products.php?action=add&amp;copy_product_id=<?php echo $this->_tpl_vars['p']['product_id']; ?>
">Copy</a>
            <a href="products.php?action=delete&amp;product_id=<?php echo $this->_tpl_vars['p']['product_id']; ?>
" onclick="return confirm('You want to delete product <?php echo $this->_tpl_vars['u']['login']; ?>
?')">Delete</a>
     </td>
</tr>
<?php endforeach; unset($_from); endif; ?>
</table>
<br />
<a href="products.php?action=add">Add New Product</a>
&nbsp;&nbsp;&nbsp;
<a href="products.php?action=reorder">Change order/groups</a>
<br />
<br /><br />
<table width=70% bgcolor=#F0F0F0><tr><td>
<small><b><div style='font-weight: bold;'>NOTES:</b><br />
<li>Deleting existing subscriptions will affect existsing users.
<li>Changing of subscription details such as duration and price
will affect existing recurring subscriptions only if these are
based on "Credit Card" payment plugins (when credit card information
is stored in aMember database, and aMember initializes rebilling.
In case of PayPal and other similar payment processors, changes will 
not affect existing subscibers
</small>
</td><tr></table>
<br />
<a href="mass_subscribe.php">Mass subscribe members</a> <br />
<a href="signup_link_wizard.php">Signup Link Wizard</a>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "admin/footer.inc.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
