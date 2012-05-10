<?php

    include "../../../config.inc.php";
    
    if(strpos(__FILE__, ':') !== false) {
    	$path_delimiter = ';';
    }
    else {
    	$path_delimiter = ':';
    }
    $api_path = dirname(__FILE__).'/api';
    ini_set('include_path', ini_get('include_path').$path_delimiter.$api_path);

	//include needed files
	require_once("ThinMPI.php");
	require_once("AcquirerStatusRequest.php");
	
	//Create StatusRequest
	$data = & new AcquirerStatusRequest();

	//Create ThinMPI instance and ...
	$rule = new ThinMPI();

	//Set parameters
	$data -> setMerchantID( $rule->conf['MERCHANTID'] );
	$data -> setSubID( $rule->conf['SUBID'] );
	$data -> setAuthentication( $rule->conf['AUTHENTICATIONTYPE'] ); // Currently only "RSA_SHA1" is implemented. (mandatory)


    $vars = $_POST ? $_POST : $_GET;
    foreach ($vars as $k => $v) {
        if (get_magic_quotes_gpc())
            $vars[$k] = $v = stripslashes($v);
    }

    $txn_id         = $vars['trxid'];

    $payment_id = get_payment_by_data('receipt_id', $txn_id);
    if (!$payment_id){
        print "Payment not found";
        $db->log_error("Payment not found for transaction #".$txn_id);
        exit();
    }
    $payment = $db->get_payment($payment_id);

    $invoice        = $payment_id;
    $payment_gross  = $payment['amount'];

    
    //$db->log_error("ideal DEBUG<br />\n".get_dump($vars));
	
	//$transID = $_GET['trxid'];
	$transID = $txn_id;
	
	$transID = str_pad($transID, 16, "0");
	$data -> setTransactionID( $transID  );
	
	//... and process request
	//$rule = new ThinMPI();
	$result = $rule->ProcessRequest( $data );
	
	if(!$result->isOK())
	{
		//StatusRequest failed, let the consumer click to try again
		print("Status kon niet worden opgehaald, klik <a href=\"\" onclick=\"javascript:window.location.reload()\">hier</a> om het nogmaals te proberen<br>");
		print("Foutmelding van iDEAL: ");
		$Msg = $result->getErrorMessage();
		print("$Msg<br>");
	}
	else if(!$result->isAuthenticated())
	{
		//Transaction failed, inform the consumer
		print("Uw bestelling is helaas niet betaald, probeer het nog eens");
		print("<br /><a href=\"javascript:location.reload();\">Please reload this page to update a payment status.</a>");
	}
	else
	{
		print("<br />Bedankt voor uw bestelling");
		$transactionID = $result->getTransactionID();
		//Here you should retrieve the order from the database, mark it as "payed"
		//and display the result to your customer.

		print("<br />De bestelling is betaald en wordt naar u opgestuurd");

        // process payment
        $err = $db->finish_waiting_payment($invoice, 'ideal', $txn_id, $payment_gross, $vars);
        if ($err)
            ideal_error("finish_waiting_payment error: $err");
        
        $t = & new_smarty();

        if ($payment_id){
            $pm = $db->get_payment($payment_id);
            $t->assign('payment', $pm);
            if ($pm) {
                $t->assign('product', $db->get_product($pm['product_id']));
                $t->assign('member', $db->get_user($pm['member_id']));
            }
            if (!($prices = $pm['data'][0]['BASKET_PRICES'])){
                $prices = array($pm['product_id'] => $pm['amount']);
            }
            $pr = array();
            $subtotal = 0;
            foreach ($prices as $product_id => $price){
                $v  = $db->get_product($product_id);
        //        $v['price'] = $price;
                $subtotal += $v['price'];
                $pr[$product_id] = $v;
            }
            $t->assign('subtotal', $subtotal);
            $t->assign('total', array_sum($prices));
            $t->assign('products', $pr);
        }
        $t->display("thanks.html");
        
        
    }

function get_dump($var){
//dump of array
    $s = "";
    foreach ((array)$var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function ideal_error($msg){
    global $txn_id, $invoice;
    global $vars;
    fatal_error("iDeal ERROR: $msg (Details: transID: $txn_id, payment_id: $invoice)");// ."\n".get_dump($vars));
}

function get_payment_by_data($data_name = '', $data_value = ''){
    global $db;
    $payment_id = 0;
    
    $q = $db->query($s = "SELECT payment_id
        FROM {$db->config['prefix']}payments
        WHERE receipt_id = '".$db->escape($data_value)."'
        ");
    $r = mysql_fetch_assoc($q);
    $payment_id = $r['payment_id'];
    
    if ($payment_id == 0){

        $q = $db->query($s = "SELECT
            payment_id, data
            FROM {$db->config['prefix']}payments
            ");
        
        while ($r = mysql_fetch_assoc($q)){
            $r['data'] = $db->decode_data($r['data']);
            if ($r['data'][$data_name] == $data_value){
                $payment_id = $r['payment_id'];
                break; // payment found. break while loop
            }
        }
        
    }
    
    return $payment_id;
}

/*
<html>
<head>

<script language="JavaScript" type="text/JavaScript">
function refresh()
{
	window.location.reload()
}
</script>
</head>

<body>


</body>
</html>
*/
?>
