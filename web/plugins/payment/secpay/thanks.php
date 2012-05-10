<?php
require_once("../../../config.inc.php");
$pl = & instantiate_plugin('payment', 'secpay');
$vars = $_REQUEST;
$pl->handle_postback($vars);
?>