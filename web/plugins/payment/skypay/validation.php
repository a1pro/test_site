<?php
if(!isset($_POST['Result']) || $_POST['Result'] != 'SUCCESS'){
	header("Location:/");
	exit;
}


require_once '../../../config.inc.php';
require_once dirname(__FILE__).'/skypay.php';
/*                                                                          */
/* Validation module to handle the Skypay transaction response callback		*/
/* It gets the response sent by skypay and records it - updates the payment	*/
/*                                                                          */
$skypay = new Skypay($config['payment']['skypay']);

// CLEAN POST BEFORE PROCESSING
$postData = $skypay->cleanPost($_POST);

$skypay->skypayDebug("Callback Response: " . print_r($postData,1));

$skypay->recordResponse($postData);