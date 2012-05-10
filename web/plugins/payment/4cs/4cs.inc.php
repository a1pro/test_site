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
*    Release: 3.1.8PRO ($Revision: 3601 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
*/






class payment_4cs extends amember_payment {
    var $title       = "4CS";
    var $description = "Credit card payment";
    var $fixed_price = 0;
    var $recurring   = 0;

    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config;
        global $db;
        $product = & get_product($product_id);

        $vars = array(
            'MERCHKEY' => $this->config['merchkey'],
            'TRANPAGE' => $this->config['tranpage'],
            'TRANTYPE' => 'AUTHPOST',
            'AMT' => sprintf("%.2f",$price),
            'CURR' => $this->config['currency'],
            'INVOICE'        => $payment_id,
            'TRANID'       => "AMEMBER".$payment_id,
            'URLAPPROVED' => $config['root_url']."/thanks.php?member_id=$member_id&$product_id=$product_id&paysys_id=4cs&res=#RC#&fres=#FC#&ac=#APP#&ref=#REF#&tran=#TRANID#&payment_id=#INVOICE#&err=#EM#",
            'URLOTHER' => $config['root_url']."/plugins/payment/4cs/cancel.php",
        );
		$t = &new_smarty();
		$t->assign('vars', $vars);
		$t->display(dirname(__FILE__) . '/iframe.html');
    }
    
    function log_debug($vars){
        global $db;
        $s = "4CS DEBUG:<br />\n";
        foreach ($vars as $k=>$v)
            $s .= "[$k] => '$v'<br />\n";
        $db->log_error($s);
    }
	function message($vars,$message="")
	{
		if($message)
		{
			echo $message;
		}
		else
		{
			echo "Your transaction has been $vars[fres].<br />
			Result code - $vars[res]<br />
			Financial Result Code - $vars[fres]<br />";
			if($vars['err'])
				echo "Error - $vars[err]<br />";
		}
		exit;
	}

    function process_thanks(&$vars){
		global $db;
        $this->log_debug($vars);
		if($vars['res']!="OK" && $vars['fres']!="APPROVED")
		{
			$this->message($vars);
		}
		else
		{
			$err = $db->finish_waiting_payment($vars['payment_id'],'4cs',$vars['tran'],'', $vars);
			if ($err) $this->message($vars,$err);
		}
    }
    function init(){
        parent::init();
    }
}

$pl = & instantiate_plugin('payment', '4cs');
?>
