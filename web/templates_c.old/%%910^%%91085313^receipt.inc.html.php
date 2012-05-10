<?php /* Smarty version 2.6.2, created on 2010-11-16 14:38:18
         compiled from receipt.inc.html */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'default', 'receipt.inc.html', 32, false),array('modifier', 'string_format', 'receipt.inc.html', 32, false),)), $this); ?>

<?php //{* do not edit lines from these to ending /php! }
$p = $this->_tpl_vars['payment'];
global $db;
$subtotal = 0;

if (!$p['data']['0']['BASKET_PRICES'])
    $p['data']['0']['BASKET_PRICES'] = array($p['product_id'] => $p['amount']);
    
foreach ($p['data']['0']['BASKET_PRICES'] as $pid => $price){
    $pr = $db->get_product($pid);
    $pr['subtotal'] = $pr['trial1_price'] ? $pr['trial1_price'] : $pr['price'];
    $subtotal += $pr['subtotal'];
    $receipt_products[$pid] = $pr;
}
$this->assign('receipt_products', $receipt_products);
$this->assign('subtotal', $subtotal);
$this->assign('total', array_sum($p['data']['0']['BASKET_PRICES']));
 ?>
<table class="receipt">
<tr>
    <th>#_TPL_THX_PRDTITLE#</th>
    <th style="width: 10%; text-align: right;">#_TPL_THX_PRICE#</th>
</tr>
<?php if (count($_from = (array)$this->_tpl_vars['receipt_products'])):
    foreach ($_from as $this->_tpl_vars['p']):
?>
<tr>
    <td><?php echo $this->_tpl_vars['p']['title']; ?>
</td>
    <td style="text-align: right"><?php echo ((is_array($_tmp=@$this->_tpl_vars['config']['currency'])) ? $this->_run_mod_handler('default', true, $_tmp, "$") : smarty_modifier_default($_tmp, "$"));  echo ((is_array($_tmp=$this->_tpl_vars['p']['subtotal'])) ? $this->_run_mod_handler('string_format', true, $_tmp, "%.2f") : smarty_modifier_string_format($_tmp, "%.2f")); ?>
</td>
</tr>
<?php endforeach; unset($_from); endif; ?>
<tr>
    <td class="total"><strong>#_TPL_THX_SUBTOTAL#</strong></td>
    <td class="total" style="text-align: right"><strong><?php echo ((is_array($_tmp=@$this->_tpl_vars['config']['currency'])) ? $this->_run_mod_handler('default', true, $_tmp, "$") : smarty_modifier_default($_tmp, "$"));  echo ((is_array($_tmp=$this->_tpl_vars['subtotal'])) ? $this->_run_mod_handler('string_format', true, $_tmp, "%.2f") : smarty_modifier_string_format($_tmp, "%.2f")); ?>
</strong></td>
</tr>
<?php if ($this->_tpl_vars['payment']['data']['COUPON_DISCOUNT'] != ""): ?>
<tr>
    <td><strong>#_TPL_THX_DISCOUNT#</strong></td>
    <td style="text-align: right"><strong><?php echo ((is_array($_tmp=@$this->_tpl_vars['config']['currency'])) ? $this->_run_mod_handler('default', true, $_tmp, "$") : smarty_modifier_default($_tmp, "$"));  echo ((is_array($_tmp=$this->_tpl_vars['payment']['data']['COUPON_DISCOUNT'])) ? $this->_run_mod_handler('string_format', true, $_tmp, "%.2f") : smarty_modifier_string_format($_tmp, "%.2f")); ?>
</strong></td>
</tr>
<?php endif;  if ($this->_tpl_vars['payment']['tax_amount'] != ""): ?>
<tr>
    <td><strong>#_TPL_THX_TAX#</strong></td>
    <td style="text-align: right"><strong><?php echo ((is_array($_tmp=@$this->_tpl_vars['config']['currency'])) ? $this->_run_mod_handler('default', true, $_tmp, "$") : smarty_modifier_default($_tmp, "$"));  echo ((is_array($_tmp=$this->_tpl_vars['payment']['tax_amount'])) ? $this->_run_mod_handler('string_format', true, $_tmp, "%.2f") : smarty_modifier_string_format($_tmp, "%.2f")); ?>
</strong></td>
</tr>
<?php endif; ?>
<tr>
    <td class="total"><strong>#_TPL_THX_TOTAL#</strong></td>
    <td class="total" style="text-align: right"><strong><?php echo ((is_array($_tmp=@$this->_tpl_vars['config']['currency'])) ? $this->_run_mod_handler('default', true, $_tmp, "$") : smarty_modifier_default($_tmp, "$"));  echo ((is_array($_tmp=$this->_tpl_vars['total'])) ? $this->_run_mod_handler('string_format', true, $_tmp, "%.2f") : smarty_modifier_string_format($_tmp, "%.2f")); ?>
</strong></td>
</tr>
</table>