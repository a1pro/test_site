<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alexander Smith
*      Email: alexander@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: AlertPay payment plugin
*    FileName $RCSfile$
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/


add_paysystem_to_list(
array(
            'paysys_id'   => 'mollie',
            'title'       => $config['payment']['mollie']['title'] ? $config['payment']['mollie']['title'] : "Mollie",
            'description' => $config['payment']['mollie']['description'] ? $config['payment']['mollie']['description'] : "payment by phone",
            'public'      => 1,
            'built_in_trials' => 1
        )
);


class payment_mollie extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
	$_SESSION['mollie_id']=$this->config['id'];
	$_SESSION['mollie_amount']=$price;
	$_SESSION['mollie_data']=serialize(array(
		"payment_id"=>$payment_id,
		"member_id"=>$member_id,
		"product_id"=>$product_id,
		"price"=>$price));
        header("Location: $config[root_surl]/plugins/payment/mollie/pay.php");
        exit();
}
}

?>
