<?php
include "../../../config.inc.php";
$this_config = $plugin_config['payment']['bidpay'];
$vars = get_input_vars();

if (!$vars['ReferenceNumber']){

    // Request Seller Token

    if ($this_config['testmode'])
        $url = "https://sandbox.bidpay.com/SellerToken.aspx";
    else
        $url = "https://bidpay.com/SellerToken.aspx";

    $return_url = $config['root_url']."/plugins/payment/bidpay/" . basename($_SERVER['PHP_SELF']);

    $vars = array(
        'ApiUsername' => $this_config['username'],
        'ReferenceNumber' => '999',
        'ReturnURLAccept' => $return_url,
        'ReturnURLReject' => $return_url
        );

    $vars1 = array();
    foreach ($vars as $kk=>$vv){
        $v = urlencode($vv);
        $k = urlencode($kk);
        $vars1[] = "$k=$v";
    }
    $vars = join('&', $vars1);

    header ("Location: " . $url . "?" . $vars);
    exit;

} else {

    if ($vars['SellerToken']){        echo "Seller Token is: " . $vars['SellerToken'];    } else {    	echo "Error";    }
}

?>