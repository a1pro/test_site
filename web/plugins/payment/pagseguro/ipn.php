<?php
if(!$_REQUEST['VendedorEmail'])
{
	$paysys_id = 'pagseguro';
	include "../../../thanks.php";

}
else
{
	require_once("../../../config.inc.php");        
	$pl = & instantiate_plugin('payment', 'pagseguro');
	$pl->process_postback(get_input_vars());
}
?>
