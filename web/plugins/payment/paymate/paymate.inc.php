<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alexander Smith
*      Email: alexander@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Paymate payment plugin
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


add_product_field(
            'is_recurring', 'Recurring Billing',
            'select', 'should user be charged automatically<br />
             when subscription expires',
            '',
            array('options' => array(
                '' => 'No',
                1  => 'Yes'
            ))
);
add_product_field('paymate_currency',
	'Paymate Currency',
	'select',
	'valid only for Paymate processing.<br /> You should not change it<br /> if you use
	another payment processors',
	'',
	array('options' => array(
		'AUD' => 'AUD',
		'USD' => 'USD',
		'GBP' => 'GBP',
		'EUR' => 'EUR'))
	);
add_product_field('paymate_period',
	'Paymate recurring period',
	'select',
	'valid only for Paymate processing.',
	'',
	array('options' => array(
		'7 days' => 'weekly',
		'14 days' => 'fortnightly',
		'30 days' => 'monthly'))
	);

add_paysystem_to_list(
array(
            'paysys_id'   => 'paymate',
            'title'       => $config['payment']['paymate']['title'] ? $config['payment']['paymate']['title'] : "Paymate",
            'description' => $config['payment']['paymate']['description'] ? $config['payment']['paymate']['description'] : "Pay by credit card/debit card - Visa/Mastercard",
            'recurring'   => 1,
            'public'      => 1,
            'built_in_trials' => 1
        )
);


class payment_paymate extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        global $config, $db;
        $orig_product_id = $product_id;
        if (is_array($product_id))
             $product_id = $product_id[0];
        $product = & get_product($product_id);
		$currency=$product->config['paymate_currency'];
        if (count($orig_product_id)>1)
		{
			$currency="AUD";
            $product->config['title'] = $config['multi_title'];
		}
		if(!$currency)$currency="AUD";
        $u  = $db->get_user($member_id);
        $vars = array(
            'mid'   => $this->config['login'],
			'amt'			=> sprintf("%.2f",$price),
			'amt_editable'			=> "N",
			'ref'				=> $payment_id,
            'return'      => $config['root_url']."/plugins/payment/paymate/thanks.php",
            'pmt_sender_email' => $u['email'], 
            'pmt_contact_firstname' => $u['name_f'], 
            'pmt_contact_surname' => $u['name_l'],
			'currency' => $currency,
			);

/*        if ($product->config['is_recurring']){
            $p = $db->get_payment($payment_id);

            $vars += array(
            'ap_timeunit'=> alertpay_get_interval_unit($product->config['expire_days'], 'expire_days'), 
            'ap_periodlength'=> alertpay_get_interval_mult($product->config['expire_days'], 'expire_days'),
            );
			$vars[ap_purchasetype] = 'Subscription';

        }*/
		$payment = $db->get_payment($payment_id);
		$log[]=$vars;
		foreach ($log as $v)
			$payment['data'][] = $v;
		$db->update_payment($payment_id, $payment);

        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
		header("Location: https://www.paymate.com/PayMate/ExpressPayment?$vars");
        exit();
    }
}

?>
