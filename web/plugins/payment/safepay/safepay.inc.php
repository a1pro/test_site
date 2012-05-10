<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");


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
add_product_field('trial1_days',
    'Trial 1 Duration',
    'period',
    'read PayPal docs for explanation, leave empty to not use trial'
    );

add_product_field('trial1_price',
    'Trial 1 Price',
    'money',
    'set 0 for free trial'
    );

add_paysystem_to_list(
array(
            'paysys_id' => 'safepay',
            'title'     => $config['payment']['safepay']['title'] ? $config['payment']['safepay']['title'] : _PLUG_PAY_SAFEPAY_TITLE,
            'description' => $config['payment']['safepay']['description'] ? $config['payment']['safepay']['description'] : sprintf(_PLUG_PAY_SAFEPAY_DESC, '<a href="http://www.safepaysolutions.com" target=_blank>', '</a>'),
            'recurring'   => 1,
            'public'    => 1,
            'built_in_trials' => 1
        )
);

function get_date_day_diff($start_date, $end_date) {
   $days = 0;
   if ($start_date < $end_date) {
       $date1 = $start_date;
       $date2 = $end_date;
   } else {
       $date1 = $end_date;
       $date2 = $start_date;
   } while ($date1 + 86400 < $date2) { // only different dates
       //$thedate = getdate($date1); 
       //if (($thedate["wday"] != '0') and ($thedate["wday"] != '6'))  //Skip saturday or sunday.
         $days++;
       $date1 += 86400; // Add a day.
   } 
   return $days;
}

class payment_safepay extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $products = $product_id;
        $orig_product_id = $product_id;
        if (is_array($product_id))
             $product_id = $product_id[0];

        $product = & get_product($product_id);
        if (count($orig_product_id)>1)
            $product->config['title'] = $config['multi_title'];

        $member = $db->get_user($member_id);

        if (preg_match('/^\d\d\d\d-\d\d-\d\d$/', $product->config['start_date']))
            $begin_date = $product->config['start_date']; /// fixed start date
        else                
            $begin_date = date('Y-m-d');

        if (true || $product->config['is_recurring']) { // only subscriptions (for reason of Trials), if not recurring will use cycles == 1
                $vars = array(
                                        "_ipn_act"      => "_ipn_subscription",
                                        "fid"           => "",
                                        "itestmode"     => $this->config['testmode'] ? "on" : "off",
                                        "notifyURL"     => $config['root_url']."/plugins/payment/safepay/ipn.php",
                                        "returnURL"     => sprintf("%s/thanks.php?member_id=%d&product_id=%d", $config['root_url'], $member_id, $product_id),
                                        "cancelURL"     => $config['root_url']."/cancel.php",
                                        "notifyEml"     => $this->config['notifyEml'],
                                        "iowner"        => $this->config['owner'],
                                        "ireceiver"     => $this->config['owner'],
                                        "iamount"       => sprintf('%.2f', $price),
                                        "itemName"      => $product->config['title'],
                                        "itemNum"       => "1",
                                        "idescr"        => $product->config['description'],
                                        
                                        "cycleLength"   => $product->config['expire_days'] ? get_date_day_diff( time(), strtotime( $product->get_expire( $begin_date, 'expire_days' ) ) ) : '0',
                                        "cycles"        => $product->config['is_recurring'] ? "0" : "1", //$product->config['rebill_times'],
                                        "trialPeriod"   => $product->config['trial1_days'] ? get_date_day_diff( time(), strtotime( $product->get_expire( $begin_date, 'trial1_days' ) ) ) : '0',
                                        "trialCycles"   => "1",
                                        "trialAmount"   => $product->config['trial1_price'],
                                        
                                        "idelivery"     => "1",
                                        "iquantity"     => "1",
                                        "imultiplyPurchase" => "n",
                                        "custom1"       => $payment_id,
                                        "custom2"       => "",
                                        "custom3"       => "",
                                        "custom4"       => "",
                                        "custom5"       => "",
                                        "colortheme"    => "",
                                        );
                                
                  } else { // DISABLED! (no Trials support here)
                        
                $vars = array(
                                        "_ipn_act"              => "_ipn_payment",
                                        "fid"                   => "",
                                        "itestmode"     => $this->config['testmode'] ? "on" : "off",
                                        "notifyURL"     => $config['root_url']."/plugins/payment/safepay/ipn.php",
                                        "returnURL"     => sprintf("%s/thanks.php?member_id=%d&product_id=%d", $config['root_url'], $member_id, $product_id),
                                        "cancelURL"     => $config['root_url']."/cancel.php",
                                        "notifyEml"     => $this->config['notifyEml'],
                                        "iowner"                => $this->config['owner'],
                                        "ireceiver"     => $this->config['owner'],
                                        "iamount"       => sprintf('%.2f', $price),
                                        "itemName"      => $product->config['title'],
                                        "itemNum"       => "1",
                                        "idescr"                => $product->config['description'],
                                        "idelivery"     => "1",
                                        "iquantity"     => "1",
                                        "imultiplyPurchase" => "n",
                                        "custom1"       => $payment_id,
                                        "custom2"       => "",
                                        "custom3"       => "",
                                        "custom4"       => "",
                                        "custom5"       => "",
                                        "colortheme"    => "",
                                );
                                
                  }
                
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        html_redirect("https://www.safepaysolutions.com/index.php?$vars",
            '', 'Please wait', 'Please wait');
        exit();
    }
}

?>
