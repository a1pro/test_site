<?php 
require_once("../../../config.inc.php");

$pl = & instantiate_plugin('payment', 'quickpay');
$vars = get_input_vars();
$pl->process_postback($vars);
?>
