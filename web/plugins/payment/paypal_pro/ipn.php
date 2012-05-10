<?php
require_once("../../../config.inc.php");

$pl = & instantiate_plugin('payment', 'paypal_pro');
$vars = $_POST;
$pl->handle_postback($vars);

?>
