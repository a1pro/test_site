<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: ccbill payment plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 1781 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/



add_paysystem_to_list(
array(
            'paysys_id' => 'micropayment',
            'title'     => $config['payment']['micropayment']['title'] ? $config['payment']['micropayment']['title'] : "Micropayment Credit Card",
            'description' => $config['payment']['micropayment']['description'] ? $config['payment']['micropayment']['description'] : "Credit Card Payments",
            'public'    => 1
        )
);

add_product_field(
            'micropayment_project', 'Micropayment Project Code',
            'text', ''
);


$plugins['payment'][] = 'micropayment_debit';
add_paysystem_to_list(
    array(
		'paysys_id' => 'micropayment_debit',
		'title'     => 'Micropayment Debit Card',
		'description' => "Debit Card Payments",
		'public'    => 1
		)
    );


// need to configure products in clickbank and set thanks page to ./thanks.php
class payment_micropayment extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db, $plugin_config;

        $this->config = $config['payment']['micropayment'];

        $product = & $db->get_product($product_id);
        $payment = $db->get_payment($payment_id);
        $member = $db->get_user($member_id);

        $vars = array(
            'project' => $product['micropayment_project'],
            'amount' => intval($price*100),
            'freepaymentid'  => $payment_id
        );
        switch ($payment['paysys_id']){
            case 'micropayment' : $modul = "creditcard"; break;
            case 'micropayment_debit' : $modul = "lastschrift"; break;
            default : $modul = "creditcard";  
        }

        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
		$hash=md5($vars.$this->config['key']);
        header("Location: https://billing.micropayment.de/$modul/event/?$vars&seal=$hash");
        exit();
    }
	
	function handle_postback($vars){
        global $db, $config;
		$this->log_debug($vars);
		if($vars['freepaymentid'])
		{
			$payment=$db->get_payment($vars['freepaymentid']);
			$error = $db->finish_waiting_payment($vars['freepaymentid'],$payment['paysys_id'],$vars['auth'],'', $vars);
			if ($error)
			{
				if($error) $db->log_error("MICROPAYMENT finish_waiting_payment error - $error");
				return "MICROPAYMENT finish_waiting_payment error - $error";
			}
			$sep  = "\n";
			$url  = $config['root_url']."/thanks.php?paysys_id=$payment[paysys_id]&member_id=$payment[member_id]&product_id=$payment[product_id]&payment_id=$payment[payment_id]";
			$response = "status=ok{$sep}url=$url{$sep}target=_top{$sep}forward=1";			
			echo $response;
		};
	}
	
	function log_debug($vars){
        global $db;
        $s = "MICROPAYMENT DEBUG:<br />\n";
        foreach ($vars as $k=>$v)
            $s .= "[$k] => '$v'<br />\n";
        $db->log_error($s);
    }


}

class payment_micropayment_debit extends payment_micropayment {
}


?>
