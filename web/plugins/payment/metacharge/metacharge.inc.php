<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Metacharge payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 1866 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*
*/


function metacharge_get_dump($var){
//dump of array
$s = "";
foreach ((array)$var as $k=>$v)
    $s .= "$k => $v<br />\n";
return $s;
}
		    
		    
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
    'read docs for explanation, leave empty to not use trial'
    );

add_product_field('trial1_price',
    'Trial 1 Price',
    'money',
    'set 0 for free trial'
    );
add_product_field('trial2_days',
    'Trial 2 Duration',
    'period',
    'read docs for explanation, leave empty to not use trial'
    );

add_product_field('trial2_price',
    'Trial 2 Price',
    'money',
    'set 0 for free trial'
    );
add_product_field('trial3_days',
    'Trial 3 Duration',
    'period',
    'read docs for explanation, leave empty to not use trial'
    );

add_product_field('trial3_price',
    'Trial 3 Price',
    'money',
    'set 0 for free trial'
    );
add_product_field(
            'metacharge_currency', 'Metacharge Currency',
            'select', 'currency for Metacharge gateway',
            '',
            array('options' => array(
                ''  => 'USD',
                'GBP'  => 'GBP',
                'EUR'  => 'EUR',
                'JPY'  => 'JPY',
                'AUD'  => 'AUD',
            ))
);

add_paysystem_to_list(
array(
            'paysys_id'   => 'metacharge',
            'title'       => $config['payment']['metacharge']['title'] ? $config['payment']['metacharge']['title'] : _PLUG_PAY_METACHARGE_TITLE,
            'description' => $config['payment']['metacharge']['description'] ? $config['payment']['metacharge']['description'] : _PLUG_PAY_METACHARGE_DESC,
            'recurring'   => 1,
            'public'      => 1,
            'built_in_trials' => 1
        )
);

function metacharge_get_price($price){
    // A decimal value representing the transaction amount in the currency specified in the strCurrency field, using a point (.) as the separator.
    // Include no other separators, or non-numeric characters.
    $price = str_replace(",", ".", $price);
    $price = preg_replace("/[^0-9\.]/i", "", $price); // only digits and point
    $price = sprintf('%.3f', $price); // float(8,3)
    return $price;
}

function metacharge_get_period($days, $field=''){
    // For scheduled payments based upon this transaction, the interval between payments,
    // given as XY where X is a number (1-999) and Y is “D” for days, “W” for weeks or “M” for months.
    $days = strtolower(trim($days));
    if (preg_match('/^(\d+)(d|w|m|y)$/', $days, $regs)) {
        $count = $regs[1];
        $period = $regs[2];
        if ($period == 'd'){
            return sprintf("%03d", $count) . "D";
        } elseif ($period == 'w'){
            return sprintf("%03d", $count) . "W";
        } elseif ($period == 'm'){
            return sprintf("%03d", $count) . "M";
        } elseif ($period == 'y'){
            return sprintf("%03d", $count * 12) . "M";
        } else {
            fatal_error(_PLUG_PAY_METACHARGE_FERROR2);
        }
    } elseif (preg_match('/^\d+$/', $days)) 
        return sprintf("%03d", $days) . "D";
    else 
        fatal_error(sprintf(_PLUG_PAY_METACHARGE_FERROR3, $field, $days));
}

class payment_metacharge extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        
        $orig_product_id = $product_id;
        if (is_array($product_id))
             $product_id = $product_id[0];
        $product = & get_product($product_id);
        if (count($orig_product_id)>1)
            $product->config['title'] = $config['multi_title'];
        $u  = $db->get_user($member_id);
        $vars = array(
            'intInstID'     => $this->config['installation_id'],
            'strCartID'     => $payment_id, // char(192)
            'strCurrency'   => 'USD', // char(3) The 3-letter ISO code for the currency in which this payment is to be made.
            'strDesc'       => substr($product->config['title'], 0, 192), // char(192)
            'strEmail'      => substr($u['email'], 0, 100),
            'strCardHolder' => substr($u['name_f'] . ' ' . $u['name_l'], 0, 20)
	    );
	if ($config['use_address_info'])
	    $vars += array(
        	'strAddress'    => substr($u['street'], 0, 255),
        	'strCity'       => substr($u['city'], 0, 40),
        	'strState'      => substr($u['state'], 0, 40),
        	'strCountry'    => substr($u['country'], 0, 2), // char(2) The 2-letter ISO code for the purchaser’s country.
        	'strPostcode'   => substr($u['zip'], 0, 15)
    	    );

        if ($this->config['testing']){
            $vars['intTestMode'] = 1;
            // If included, indicates a test purchase.
            // A VISA card with card number 1234123412341234 should be used on the payment page.
            // Values: 0=equivalent to field omitted (payment is live), 1=all payments are successful, 2=all payments fail.
            // Banks are not involved in test payments.
        }
        if ($product->config['metacharge_currency'])
            $vars['strCurrency'] = $product->config['metacharge_currency'];

        if ($product->config['is_recurring']){
            $p = $db->get_payment($payment_id);

            $vars['intRecurs'] = '1'; // For scheduled payments, indicates if scheduled payments should recur. Values: 0=no, 1=yes.
            
            if ($product->config['rebill_times'])
                $vars['intCancelAfter'] = intval($product->config['rebill_times']); // Cancel a subscription after this many successful payments.
            
            $has_trials = false;
            if ($product->config['trial1_days'] && $product->config['trial1_price']){
                $has_trials = true;
                $vars['fltSchAmount1'] = metacharge_get_price($product->config['trial1_price']);
                $vars['strSchPeriod1'] = metacharge_get_period($product->config['trial1_days'], 'trial1_days');

                $p['expire_date'] = $product->get_expire($begin_date, 'trial1_days');
                $p['amount'] = $product->config['trial1_price'];
                $db->update_payment($payment_id, $p);
            }
            
            if ($has_trials && $product->config['trial2_days'] && $product->config['trial2_price']){
                $vars['fltSchAmount2'] = metacharge_get_price($product->config['trial2_price']);
                $vars['strSchPeriod2'] = metacharge_get_period($product->config['trial2_days'], 'trial2_days');
            }
            
            if ($has_trials && $product->config['trial3_days'] && $product->config['trial3_price']){
                $vars['fltSchAmount3'] = metacharge_get_price($product->config['trial3_price']);
                $vars['strSchPeriod3'] = metacharge_get_period($product->config['trial3_days'], 'trial3_days');
            }

//            if (!$has_trials){
                $vars['fltSchAmount'] = metacharge_get_price($price);
                $vars['strSchPeriod'] = metacharge_get_period($product->config['expire_days'], 'expire_days'); // char(4)
//            }
            
        }
        
	$vars['fltAmount'] = metacharge_get_price($price);
	
	$db->log_error("METACHARGE SENT: " . metacharge_get_dump($vars));
        

        $t = & new_smarty();
        $t->template_dir = dirname(__FILE__);
        $t->assign(array(
            'vars'          => $vars
        ));
        $t->display('metacharge.html');
        exit();


    }

    function validate_thanks(&$vars){
        $vars['payment_id'] = intval($vars['strCartID']);
        return '';
    }
    
}
?>
