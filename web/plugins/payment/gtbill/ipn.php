<?php
require_once("../../../config.inc.php");

$pl = & instantiate_plugin('payment', 'gtbill');
$vars = get_input_vars();
$vars['Password'] = "******";
$pl->handle_postback($vars);

?>