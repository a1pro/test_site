{* This file is to be included into amember/templates/thanks.html *}

{php}
if (!$GLOBALS['_ga_tracked']) :
$GLOBALS['_ga_tracked'] = 1;
{/php}

<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>



{if ! $sale}{* Track Visit *}
<script type="text/javascript">
    var pageTracker = _gat._getTracker("{/literal}{$config.google_analytics}{literal}");
    pageTracker._trackPageview();
</script>
{else}{* Track Sale *}
{php}
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
{/php}{* End of PHP code, don't touch above *}

<script type="text/javascript" smarty="smarty">
  var pageTracker = _gat._getTracker("{$config.google_analytics}");
  pageTracker._trackPageview();
  pageTracker._addTrans(
    "{$payment.payment_id}",                         // Order ID
    "",                                              // Affiliation
    "{$total}",                                      // Total
    "{$payment.tax_amount}",                         // Tax
    "",                                              // Shipping
    "{$user.city|escape:"javascript"}",                                // City
    "{$user.state|escape:"javascript"}",                               // State
    "{$user.country|escape:"javascript"}"                              // Country
  );
{foreach from=$receipt_products item=p}
  pageTracker._addItem(
    "{$payment.payment_id}",                         // Order ID
    "{$p.product_id}",                         // SKU
    "{$p.title|escape:"`"}",                                  // Product Name 
    "",                             // Category
    "{$p.subtotal}",                                    // Price
    "1"                                         // Quantity
  );
{/foreach}
  pageTracker._trackTrans();
</script>
{/if}
{php}endif{/php}