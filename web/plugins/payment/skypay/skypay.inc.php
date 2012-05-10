<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

global $config;
require_once($config['root_dir']."/plugins/payment/skypay/skypay.php");

class payment_skypay extends amember_payment {
	var $title = _PLUG_PAY_SKYPAY_TITLE;
    var $description = _PLUG_PAY_SKYPAY_DESC;

	function do_bill($amount, $title, $products, $u, $invoice){
			global $config;
			$url = "$config[root_surl]/plugins/payment/skypay/pay.php";
			$var = "payment_id=$invoice&member_id={$u['member_id']}";
			header("Location: ".$url."?$var");
			exit;
	}

    function init(){
        parent::init();
        add_product_field(
            'skypay_currency', 'Skyppay Currency',
            'select', 'currency for Skypay gateway',
            '',
            array('options' => array(
									'GBP'	=> 'GBP',
									'EUR'	=> 'EUR',
									'USD'	=> 'USD',
									'JPY'	=> 'JPY',
									'AUD'	=> 'AUD',
									)
				)
        );
		add_member_field(
			'street2',
			'Billing Street Address 2',
			'text',
			"",
			'',
			array('hidden_anywhere' => 1)
		);
    }

}

instantiate_plugin('payment', 'skypay');
?>