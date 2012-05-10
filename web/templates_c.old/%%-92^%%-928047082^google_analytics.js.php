<?php /* Smarty version 2.6.2, created on 2010-08-09 23:20:08
         compiled from google_analytics.js */ ?>
<?php require_once(_SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'google_analytics.js', 49, false),)), $this); ?>

<?php 
if (!$GLOBALS['_ga_tracked']) :
$GLOBALS['_ga_tracked'] = 1;
 ?>

<!--<?php echo ' --><script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script><!--{literal} '; ?>
-->



<?php if (! $this->_tpl_vars['sale']): ?><!--<?php echo ' --><script type="text/javascript">
    var pageTracker = _gat._getTracker("';  echo $this->_tpl_vars['config']['google_analytics'];  echo '");
    pageTracker._trackPageview();
</script><!--{literal} '; ?>
-->
<?php else:  
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
<script type="text/javascript" smarty="smarty">
  var pageTracker = _gat._getTracker("<?php echo $this->_tpl_vars['config']['google_analytics']; ?>
");
  pageTracker._trackPageview();
  pageTracker._addTrans(
    "<?php echo $this->_tpl_vars['payment']['payment_id']; ?>
",                         // Order ID
    "",                                              // Affiliation
    "<?php echo $this->_tpl_vars['total']; ?>
",                                      // Total
    "<?php echo $this->_tpl_vars['payment']['tax_amount']; ?>
",                         // Tax
    "",                                              // Shipping
    "<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['city'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'javascript') : smarty_modifier_escape($_tmp, 'javascript')); ?>
",                                // City
    "<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['state'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'javascript') : smarty_modifier_escape($_tmp, 'javascript')); ?>
",                               // State
    "<?php echo ((is_array($_tmp=$this->_tpl_vars['user']['country'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'javascript') : smarty_modifier_escape($_tmp, 'javascript')); ?>
"                              // Country
  );
<?php if (count($_from = (array)$this->_tpl_vars['receipt_products'])):
    foreach ($_from as $this->_tpl_vars['p']):
?>
  pageTracker._addItem(
    "<?php echo $this->_tpl_vars['payment']['payment_id']; ?>
",                         // Order ID
    "<?php echo $this->_tpl_vars['p']['product_id']; ?>
",                         // SKU
    "<?php echo ((is_array($_tmp=$this->_tpl_vars['p']['title'])) ? $this->_run_mod_handler('escape', true, $_tmp, "`") : smarty_modifier_escape($_tmp, "`")); ?>
",                                  // Product Name 
    "",                             // Category
    "<?php echo $this->_tpl_vars['p']['subtotal']; ?>
",                                    // Price
    "1"                                         // Quantity
  );
<?php endforeach; unset($_from); endif; ?>
  pageTracker._trackTrans();
</script><!--<?php echo ' '; ?>
-->
<?php endif; ?>
<?php endif ?>