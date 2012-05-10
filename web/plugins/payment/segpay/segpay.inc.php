<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    FileName $RCSfile$
*    Release: 2.4.0PRO ($Revision: 5024 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
                                                                     *
*/

add_paysystem_to_list(
array(
            'paysys_id'   => 'segpay',
            'title'       => 'segpay',
            'description' => 'pay with credit cards using Visa, MC, Discover, JCB',
            'recurring'    => 1,
            'public'      => 1,
            'fixed_price' => 1,
        )
);

add_product_field(
            'is_recurring', 'Recurring Billing?',
            'select', 'should user be charged automatically<br>
             when subscription expires',
            '',
            array('options' => array(
                '' => 'No',
                1  => 'Yes'
            ))
);

add_product_field(
            'segpay_pid', 'segpay Package ID',
            'text', 'you must create the same package<br>
             in segpay and enter its number here',
             'validate_segpay_pid'
);

add_product_field(
            'segpay_id', 'segpay ID',
            'text', 'you must create the same product<br>
             in segpay and enter its number here',
             'validate_segpay_id'
);



class payment_segpay extends payment 
{

    function do_not_recurring_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars)
    {
        global $config;
        global $db;
        $product = & get_product($product_id);

        $product_id = $product->config['segpay_id'];
        $package_id = $product->config['segpay_pid'];
        $u = & $db->get_user(intval($member_id));

        $vars = array(
              'x-eticketid'   => $package_id.':'.$product_id,
              'x-auth-link'   => "$config[root_url]/login.php",
              'x-auth-text'   => "GO TO YOUR LOGIN PAGE",
              'username'      => $u['login'],
              'password'      => $u['pass'],
			  'x-billnamefirst' => $u['name_f'],
			  'x-billnamelast'   => $u['name_l'],
			  'x-billemail'   => $u['email'],
              'payment_id'    => $payment_id
        );
        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $varsx = join('&', $vars1);

        header("Location: https://secure2.segpay.com/billing/poset.cgi?".$varsx);
        exit();
    }

    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars)
    {

        global $config;
        global $db;
        $product = & get_product($product_id);
        if(!$product->config['is_recurring'])
            return $this->do_not_recurring_payment($payment_id, $member_id, $product_id,
                 $price, $begin_date, $expire_date, $vars);
        $product_id = $product->config['segpay_id'];
        $package_id = $product->config['segpay_pid'];
        $u = & $db->get_user(intval($member_id));

        $varsx = array
        (
              'x-eticketid'   => $package_id.':'.$product_id,
              'x-auth-link'   => "$config[root_url]/login.php",
              'x-auth-text'   => "GO TO YOUR LOGIN PAGE",
              'username'      => $u['login'],
              'password'      => $u['pass'],
			  'x-billnamefirst' => $u['name_f'],
			  'x-billnamelast'   => $u['name_l'],
			  'x-billemail'   => $u['email'],
              'payment_id'    => $payment_id
        );

        $vars1 = array();
        foreach ($varsx as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $varsx = join('&', $vars1);

        header("Location: https://secure2.segpay.com/billing/poset.cgi?".$varsx);
        return '';
    }

    function log_debug($vars)
    {
        global $db;
        $s = "segpay DEBUG:<br>\n";
        foreach ($vars as $k=>$v)
            $s .= "[$k] => '$v'<br>\n";
        $db->log_error($s);
    }


    function process_thanks(&$vars)
    {
            global $db;
            $this->log_debug($vars);
            if ($vars) echo 'SUCCESS';
            global $db;
            $payment_id = intval($vars['payment_id']);
            if ($vars['approved'] != 'Yes'){
                /// log to payment record
                $p = $db->get_payment($payment_id);
                $p['data'][] = $vars;
                $db->update_payment($payment_id, $p);
                // exit
                $t = & new_smarty();
                $t->display("cancel.html");
                exit();
            }
            //refund or delete
            if($vars['trantype']=='credit' || $vars['action']=='delete')
            {
            	if(!$vars['purchase_id']) return;
            	$p_id = $db->query_one("SELECT payment_id from {$db->config['prefix']}payments where receipt_id='$vars[purchase_id]'");
            	if(!$p_id) return;
            	$p = $db->get_payment($p_id);
                $p['data'][] = $vars;
                $p['data']['CANCELLED'] = 1;
                $p['data']['CANCELLED_AT'] = strftime($config['time_format'], time());

            	$p['expire_date'] = date('Y-m-d', time() - 3600 * 24 ); //yesterday date
            	$db->update_payment($p_id, $p);
            }
            //
            else
            {
				$err = $db->finish_waiting_payment($payment_id,
						'segpay',
						$vars['trans_id'],
						 '', $vars);
				if ($err)
					return "finish_waiting_payment error: $err";
				$p = $db->get_payment($payment_id);
				$GLOBALS['vars']['payment_id'] = $payment_id;
				$GLOBALS['vars']['member_id'] = $p['member_id'];
				$product = & get_product($p['product_id']);
				if ($product->config['is_recurring'] && $vars['payment_id'])
				{
					////// set expire date to infinite
					////// admin must cancel it manually!
					$p['expire_date'] = '2012-12-31';
					$db->update_payment($payment_id, $p);
				}
            }
    }

}

function validate_segpay_id(&$p, $field)
{
    if ((intval($p->config[$field]) <= 0)) {
        return "You MUST enter segpay Product ID";
    }
    return '';
}

function validate_segpay_pid(&$p, $field)
{
    if ((intval($p->config[$field]) <= 0)) {
        return "You MUST enter segpay Package ID";
    }
    return '';
}

?>