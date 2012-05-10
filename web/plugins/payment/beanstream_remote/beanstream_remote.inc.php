<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: beanstream_remote payment plugin
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 5012 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

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
add_payment_field('beanstream_rbaccountid', 'Beanstream recugging billing account id',
    'hidden', 'integer value');


add_paysystem_to_list(
array(
            'paysys_id'   => 'beanstream_remote',
            'title'       => $config['payment']['beanstream_remote']['title'] ? $config['payment']['beanstream_remote']['title'] : _PLUG_PAY_BEANSTREAM_TITLE,
            'description' => $config['payment']['beanstream_remote']['description'] ? $config['payment']['beanstream_remote']['description'] : _PLUG_PAY_BEANSTREAM_REM_DESC,
            'recurring'   => 1,
            'public'      => 1,
            'built_in_trials' => 0
        )
);


class payment_beanstream_remote extends payment {
	function get_period($orig_period){
		if (preg_match('/^\s*(\d+)\s*([y|Y|m|M|w|W|d|D]{0,1})\s*$/', 
				$orig_period, $regs)){
			$period = $regs[1];
			$period_unit = $regs[2];
			if (!strlen($period_unit)) $period_unit = 'd';
			$period_unit = strtoupper($period_unit);
			switch ($period_unit){
				case 'Y': 
					if (($period < 1) or ($period > 5)) 
						fatal_error("Period must be in interval 1-5 years");
					break;
				case 'M': 
					if (($period < 1) or ($period > 24)) 
						fatal_error("Period must be in interval 1-24 months");
					break;
				case 'W': 
					if (($period < 1) or ($period > 52)) 
						fatal_error("Period must be in interval 1-52 weeks");
					break;
				case 'D': 
					if (($period < 1) or ($period > 90)) 
						fatal_error("Period must be in interval 1-90 days");
					break;
				default:
					fatal_error( "Unknown period unit: $period_unit");
			}
		} else {
			fatal_error( "Incorrect value for expire days: ".$orig_period );
		}
		return array($period, $period_unit);
	}

    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $db, $config, $t;
        $payment = $db->get_payment($payment_id);
        $member = $db->get_user($member_id);
        $product = $db->get_product($product_id);

        $vars = array(
            'approvedPage'     => $config['root_surl'] . "/thanks.php?payment_id={$payment[payment_id]}",
            'declinedPage'    => $config['root_surl'] . "/plugins/payment/beanstream_remote/cancel.php",
            'errorPage'       => $config['root_surl'] . "/plugins/payment/beanstream_remote/cancel.php",
            'merchant_id'     => $this->config['merchant_id'] ,
            
            'trnOrderNumber' => $payment['payment_id'],
            'trnAmount' => $price,
            'ordEmailAddress' => $member['email'],
            'ordName' => $member['name_f'] . " " . $member['name_l'],
            'trnComments' => $product['title']
        );
        if ($product['is_recurring']){
            list($period, $period_unit) = $this->get_period($product['expire_days']);
            $vars += array(
                'trnRecurring' => 1,
                'rbBillingPeriod' => $period_unit,
                'rbBillingIncrement' => $period
            );
        }
        /////
        $t->assign('header', $config['root_dir'] . '/templates/header.html');
        $t->assign('footer', $config['root_dir'] . '/templates/footer.html');
        $member['name'] = $member['name_f'] . ' ' . $member['name_l'];
        $t->assign('member', $member);
        $t->assign('vars', $vars);

        $t->display($config['root_dir'] . "/plugins/payment/beanstream_remote/beanstream_remote.html");
    }
    
    function get_cancel_link($payment_id){
        global $db,$config;
        $p = $db->get_payment($payment_id);
        if (!$p['data']['CANCELLED'] && $p['data']['beanstream_rbaccountid'])
        	return "{$config[root_surl]}/plugins/payment/beanstream_remote/close.php?"
            ."payment_id=$payment_id&"
            ."member_id={$p[member_id]}";
    }

}
?>
