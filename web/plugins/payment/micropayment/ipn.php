<?php
require_once("../../../config.inc.php");

$pl = & instantiate_plugin('payment', 'micropayment');
$vars = get_input_vars();
$pl->handle_postback($vars);

?>
