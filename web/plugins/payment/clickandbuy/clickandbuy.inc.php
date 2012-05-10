<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: ClickAndBuy payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 2604 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/

function clickandbuy_get_dump($var){
//dump of array
$s = "";
foreach ((array)$var as $k=>$v)
    $s .= "$k => $v<br />\n";
return $s;
}

class payment_clickandbuy extends amember_payment {
    var $title = _PLUG_PAY_CLICKANDBUY_TITLE;
    var $description = _PLUG_PAY_CLICKANDBUY_DESC;
    var $fixed_price = 0;
    var $recurring = 1;
//    var $supports_trial1 = 0;
    var $built_in_trials = 0;

    function get_rand($length){
        $all_g = "ABCDEFGHIJKLMNOPQRSTWXZ";
        $pass = "";
        srand((double)microtime()*1000000);
        for($i=0;$i<$length;$i++) {
            srand((double)microtime()*1000000);
            $pass .= $all_g[ rand(0, strlen($all_g) - 1) ];
        }
        return $pass;
    }

    function do_bill($amount, $title, $products, $u, $invoice){
        global $config, $db;
        $product = $products[0];

        //list($a, $p, $t, $rebill_times) = $this->build_subscription_params($products, $amount, $u, $invoice);

        $vars = array(
            'price'               => intval($amount * 100),
            'cb_currency'         => $product['clickandbuy_currency'] ? $product['clickandbuy_currency'] : 'USD',
            'cb_content_name_utf' => $title,
            'payment_id'          => $invoice,
            'externalBDRID'       => $invoice.'-'.$this->get_rand(3),
        );

	    $db->log_error("ClickAndBuy Debug: " . clickandbuy_get_dump($vars));

        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        //clickandbuy_subscription_id
        $purchase_code = $product['clickandbuy_subscription_id'] ? $product['clickandbuy_subscription_id'] : $this->config['purchase_code'];
        header("Location: http://premium-" . $purchase_code . ".eu.clickandbuy.com/clickandbuy.php?$vars");
        exit();
    }

    function validate_thanks(&$vars){
        return '';
    }
    function process_thanks(&$vars){
            global $db;
            require_once('lib/nusoap.php');

            if ($vars['result'] != 'success')
                return "Payment failed.";

            $client = new soapclient('http://wsdl.eu.clickandbuy.com/TMI/1.4/TransactionManagerbinding.wsdl',true);
            $err = $client->getError();
            if ($err) {
                return "Error: " . $err;
            }

            $payment_id = intval($vars['payment_id']);
            $pm = $db->get_payment($payment_id);


            $product = & get_product($pm['product_id']);
            if ($product->config['is_recurring']){
        	    ////// set expire date to infinite
		        ////// admin must cancel it manually!
		        $p['expire_date'] = '2012-12-31';
		        $db->update_payment($payment_id, $p);
	        }


            if ($this->config['disable_second_confirmation']){                $err = $db->finish_waiting_payment($pm['payment_id'], 'clickandbuy', $pm['data']['trans_id'], '', $vars);
                if ($err)
                    return "Error: " . $err;
            } else {

                $secondconfirmation = array(
                    'sellerID' => $this->config['seller_id'],
                    'tmPassword' => $this->config['tm_password'],
                    'slaveMerchantID' => '0',
                    'externalBDRID' => $pm['data']['ext_bdr_id']
                    );

                /*Start Soap Request*/
                $result = $client->call(
                    'isExternalBDRIDCommitted',
                    $secondconfirmation,
                    'https://clickandbuy.com/TransactionManager/',
                    'https://clickandbuy.com/TransactionManager/'
                    );

                if ($client->fault) {
                    return "Soap Request Fault [".$client->getError()."]";                } else {                    $err = $client->getError();
                    if ($err) {
                        return "Error " . $err;
                    } else {                        $err = $db->finish_waiting_payment($pm['payment_id'], 'clickandbuy', $pm['data']['trans_id'], '', $vars);
                        if ($err)
                            return "Error " . $err;
                    }
                }
            }

    }

    function init(){
        parent::init();
        add_product_field(
            'clickandbuy_subscription_id', 'ClickAndBuy Purchase Link code (Subscription ID)',
            'text', 'if empty, common code from plugin configuration will be used',
            '','');

        add_product_field(
            'clickandbuy_currency', 'ClickAndBuy Currency',
            'select', 'currency for ClickAndBuy gateway',
            '',
            array('options' => array(
                'USD'  => 'US Dollar',
                'EUR'  => 'Euro',
                'AUD'  => 'Australian Dollar',
                'GBP'  => 'British Pound',
                'CHF'  => 'Swiss Franc',
                'DKK'  => 'Danish Krone',
                'NOK'  => 'Norwegian Krone',
                'SEK'  => 'Swedish Krone',
                'NZD'  => 'New Zealand Dollar',
                'CAD'  => 'Canadian Dollar',
                'MXN'  => 'Mexican Peso',
                'ZAR'  => 'South African Rand',
                'TRY'  => 'New Turkish Lira',
                'JPY'  => 'Japanese Yen',
                'HKD'  => 'Hong Kong Dollar',
                'CNY'  => 'Chinese Yuan',
                'TWD'  => 'Taiwanese Dollar',
                'INR'  => 'Indian Rupee',
                'BRL'  => 'Brazilian Real',
                'KRW'  => 'Korean Won',
                'MYR'  => 'Malaysian Ringgit'
                )
            )
        );
    }
}

instantiate_plugin('payment', 'clickandbuy');