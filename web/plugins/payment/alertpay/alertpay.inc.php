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

add_paysystem_to_list(
array(
            'paysys_id'   => 'alertpay',
            'title'       => $config['payment']['alertpay']['title'] ? $config['payment']['alertpay']['title'] : _PLUG_PAY_ALERTPAY_TITLE,
            'description' => $config['payment']['alertpay']['description'] ? $config['payment']['alertpay']['description'] : _PLUG_PAY_ALERTPAY_DESC,
            'recurring'   => 1,
            'public'      => 1,
            'built_in_trials' => 1
        )
);

function alertpay_get_interval_unit($days, $field=''){
    $days = strtolower(trim($days));
    if (preg_match('/^(\d+)(d|w|m|y)$/', $days, $regs)) {
        $count = $regs[1];
        $period = $regs[2];
        if ($period == 'm'){
            return "Month";
        } elseif ($period == 'd'){
            return "Day";
        } elseif ($period == 'w'){
            return "Week";
        } elseif ($period == 'y'){
            return "Year";
        } else {
            fatal_error(_PLUG_PAY_ALERTPAY_FERROR2);
        }
    } elseif (preg_match('/^\d+$/', $days)) 
        return "Day";
    else 
        fatal_error(sprintf(_PLUG_PAY_ALERTPAY_FERROR3, $field, $days));
}



function alertpay_get_interval_mult($days){
    $days = strtolower(trim($days));
    if (preg_match('/^(\d+)(d|w|m|y)$/', $days, $regs)) {
        $count = $regs[1];
        return $count;
    } elseif (preg_match('/^\d+$/', $days)) 
        return $days;
    else 
        fatal_error(sprintf(_PLUG_PAY_ALERTPAY_FERROR4, $field).
            $this->config['product_id'] . ": '$days'" );
}


class payment_alertpay extends payment {
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
            'ap_merchant'   => $this->config['merchant'],
            'ap_purchasetype' => 'Item', 
            'ap_itemname'     => $product->config['title'],
            'ap_currency' => $this->config['currency'],
            'ap_returnurl'      => $config['root_url']."/thanks.php",
	    'ap_amount'			=> $price,
	    'ap_cancelurl'		=> $config['root_url'],
	    'apc_1'				=> $payment_id,
	    'apc_2'				=> $member_id
        );
        if ($this->config['testing']){
            $vars['ap_test'] = 1;
        }

        if ($product->config['is_recurring']){
            $p = $db->get_payment($payment_id);

            $vars += array(
            'ap_timeunit'=> alertpay_get_interval_unit($product->config['expire_days'], 'expire_days'), 
            'ap_periodlength'=> alertpay_get_interval_mult($product->config['expire_days'], 'expire_days'),
            );
/*
            $vars += array(
            'aps_period'=> alertpay_get_interval_unit($product->config['expire_days'], 'expire_days'), 
            'aps_intervals'=> alertpay_get_interval_mult($product->config['expire_days'], 'expire_days'),
            );
*/
			$vars[ap_purchasetype] = 'Subscription';

        }
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://www.alertpay.com/PayProcess.aspx?$vars");
        exit();
    }
}

?>
