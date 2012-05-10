<?php
require_once("../../../config.inc.php");

$_product_id = array('ONLY_LOGIN');
require($config['plugins_dir']['protect'] . '/php_include/check.inc.php');


$pl = & instantiate_plugin('payment', 'gtbill');
$vars = get_input_vars();
$p = $db->get_payment($vars['pid']);

$mid = $p['member_id'];
if ($p['payment_id'] && $mid == $_SESSION['_amember_id']){
    $u = $db->get_user($mid);
    $plc = $pl->config;

    // find unique Username for cancelation
    $username = $u['login'];
    foreach ($p['data'] as $pdata){
	if (!is_array($pdata))
	    continue;
	if ($pdata['action'] == 'Add')
	    $username = $pdata['Username'];
    }

    $product = & get_product($p['product_id']);
    $begin_date = $p['begin_date'];
    $expire_date = $product->get_expire($begin_date);
    
    //$product = $dbb->get_product($p['product_id']);
    //$price = $product['price'];
    //$duration = get_days($product['expire_days']) * 3600 * 24;
    //$expire_date = date('Y-m-d', time() + $duration);
    //$p['expire_date'] = $expire_date;
    
    $p['data']['CANCELLED'] = 1;
    $p['data']['CANCELLED_AT'] = strftime($config['time_format'], time());
    $db->update_payment($p['payment_id'], $p);

} else {
    $db->log_error ("Error pid=[".$vars['pid']."], payment_id=[".$p['payment_id']."], _amember_id=[".$_SESSION['_amember_id']."]");
    echo _TPL_FATAL_ERROR_TITLE . ". " . _TPL_FATAL_ERROR_CONTACT . $config['admin_email'];
    exit;
}

function get_days($orig_period){
    $ret = 0;
    if (preg_match('/^\s*(\d+)\s*([y|Y|m|M|w|W|d|D]{0,1})\s*$/', $orig_period, $regs)){
	$period = $regs[1];
	$period_unit = $regs[2];
	if (!strlen($period_unit)) $period_unit = 'd';
	$period_unit = strtoupper($period_unit);

	switch ($period_unit){
	    case 'Y':
		$ret = $period * 365;
		break;
	    case 'M':
		$ret = $period * 30;
		break;
	    case 'W':
		$ret = $period * 7;
		break;
	    case 'D':
		$ret = $period;
		break;
	    default:
		fatal_error(sprintf("Unknown period unit: %s", $period_unit));
	}
    } else {
	fatal_error("Incorrect value for expire days: ".$orig_period);
    }
    return $ret;
}

?>
<html>
<head>
<title>GTBill Cancel Membership</title>
<meta HTTP-EQUIV="Content-Type" CONTENT="text/html">
<meta HTTP-EQUIV="Cache-Control" CONTENT="no cache">
<meta HTTP-EQUIV="Pragma" CONTENT="no cache">
<meta HTTP-EQUIV="Expires" CONTENT="0">
</head>
<body OnLoad="AutoSubmitForm();">
Username: <?php echo $u['login']; ?>
<form action="https://billing.gtbill.com/service/cancelmembership.aspx" method="post" name="CancelForm">
<input type="hidden" name="MerchantID" value="<?php echo $plc['merchant_id']; ?>"/>
<input type="hidden" name="SiteID" value="<?php echo $plc['site_id']; ?>">
<input type="hidden" name="Username" value="<?php echo $username; ?>"/>
</form>
<script language="Javascript">
<!--
function AutoSubmitForm() {
document.CancelForm.submit();
}
//-->
</script>
<noscript>
<center>
<input type="submit" name="continue" value="Cancel Membership">
</center>
</noscript>
</form>
</body>
</html>