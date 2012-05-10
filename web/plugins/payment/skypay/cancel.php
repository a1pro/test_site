<?php                                                        
include('../../../config.inc.php');

$t = & new_smarty();
$error = '';
$vars = & get_input_vars();

$t->display(dirname(__FILE__)."/templates/cancel.html");
?>