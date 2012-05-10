<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alexander Smith
*      Email: alexander@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: realex_redirect payment plugin
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

class payment_realex_redirect extends amember_payment {
    var $title = 'RealEx';
    var $description = 'Redirect';
    var $fixed_price=0;
    var $recurring=0;
//    var $supports_trial1=0;
    var $built_in_trials=0;

    function do_bill($amount, $title, $products, $u, $invoice){
        global $config, $db, $plugin_config;

        $product = $products[0];
        $payment = $db->get_payment($invoice);

        $this_config   = $plugin_config['payment']['realex_redirect'];

        $vars = array(
            'merchant_id'      => $this_config['merchant_id'],
            'order_id'         => $invoice,
            'account'          => $this_config['account'],
            'amount'           => $amount,
            'currency'         => $product['realex_redirect_currency'] ? $product['realex_redirect_currency'] : 'USD',
            'timestamp'        => date("YmdHis"),
            'comment1'         => $product['title'],
            'cust_num'         => $payment['member_id'],
            'auto_settle_flag' => '1'
        );

        $hash = $vars['timestamp'] . "." . $vars['merchant_id'] . "." . $vars['order_id'] . "." . $vars['amount'] . "." . $vars['currency'];
        $hash = md5($hash);
        $hash = $hash . "." . $this_config['secret'];
        $hash = md5($hash);

        $vars['md5hash'] = $hash;

    	$t = & new_smarty();
		$t->assign('vars', $vars);
		$t->display(str_replace("c:\\", '/', dirname(__FILE__).'/form.html'));
		exit();
    }

    function init(){
        parent::init();
        add_product_field(
            'realex_redirect_currency', 'RealEx Redirect Currency',
            'select', 'currency for RealEx Redirect gateway',
            '',
            array('options' => array(
                'EUR' => 'Euro',
                'GBP' => 'Pound Sterling',
                'USD' => 'US Dollar',
                'SEK' => 'Swedish Krona',
                'CHF' => 'Swiss Franc',
                'HKD' => 'Hong Kong Dollar',
                'JPY' => 'Japanese Yen'
            ))
        );
    }

}
instantiate_plugin('payment', 'realex_redirect');
