<?php
include "../../../config.inc.php";
$this_config = $plugin_config['payment']['realex_redirect'];

function get_dump($var){
//dump of array
    $s = "";
    foreach ((array)$var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

$vars = get_input_vars();
$db->log_error("RealEx Redirect DEBUG:<br />".get_dump($vars));

$merchant_id = $vars['MERCHANT_ID'];
$pnref       = $vars['PASREF'];
$result      = $vars['RESULT'];
$message     = $vars['MESSAGE'];
$payment_id  = intval($vars['ORDER_ID']);
$cvnresult   = $vars['CVNRESULT'];


$hash = $vars['TIMESTAMP'] . "." . $merchant_id . "." . $payment_id . "." .
        $result . "." . $message . "." . $pnref . "." . $vars['AUTHCODE'];

$response_valid = false;
if ($vars['MD5HASH']){
    $hash = md5($hash);
    $hash = $hash . "." . $this_config['secret'];
    $hash = md5($hash);
    if ($vars['MD5HASH'] == $hash) $response_valid = true;
} else {
    $hash = sha1($hash);
    $hash = $hash . "." . $this_config['secret'];
    $hash = sha1($hash);
    if ($vars['SHA1HASH'] == $hash) $response_valid = true;
}

if ($response_valid && $result == '00' && $merchant_id == $this_config['merchant_id']){

    $payment = $db->get_payment($payment_id);

    $err = $db->finish_waiting_payment($payment_id, 'realex_redirect',
            $pnref, $payment['amount'], $vars);

    if ($err){
        $db->log_error("finish_waiting_payment error: $err");
        echo "ERROR";
        exit;
    }

    echo "OK";

} else {    echo "NOT VALID";
}

?>