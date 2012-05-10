<?php
if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alexander Smith
*      Email: alexander@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: WebAffair payment plugin
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

add_paysystem_to_list(
array(
            'paysys_id'   => 'webaffair',
            'title'       => $config['payment']['webaffair']['title'] ? $config['payment']['webaffair']['title'] : "WebAffair",
            'description' => $config['payment']['webaffair']['description'] ? $config['payment']['webaffair']['description'] : "Pay by credit card/debit card - Visa/Mastercard",
            'public'      => 1,
            'built_in_trials' => 1
        )
);

class payment_webaffair extends payment {
    function do_payment($payment_id, $member_id, $product_id,$price, $begin_date, $expire_date, &$vars){
        global $config, $db;
		$amount=sprintf("%d",$price*100);
		$parm="merchant_id=".$this->config["merchant"]." merchant_country=fr amount=$amount currency_code=978";
		$parm.=" pathfile=".$this->config["pathfile"]." transaction_id=$payment_id";
		$parm.=" normal_return_url=$config[root_url]/thanks.php";
		$parm.=" cancel_return_url=$config[root_url]/cancel.php";
		$parm="$parm automatic_response_url=$config[root_url]/plugins/payment/webaffair/ipn.php";

		$result=exec($this->config["path_bin"]." $parm");
		$tableau = explode ("!", "$result");
		$code = $tableau[1];
		$error = $tableau[2];
		$message = $tableau[3];
		$t = &new_smarty();
		if ($code=="" && $error=="")
		{
			$output = "<BR><CENTER>erreur appel request</CENTER><BR>executable request non trouve ".$this->config["path_bin"];
		}
		elseif ($code != 0){
			$output = "<center><b><h2>Erreur appel API de paiement.</h2></center></b><br><br><br>message erreur : $error <br>";
		}
		else{
			$output = "<br><br>$error<br>$message <br>";
		}
		$t->assign('output',$output);
		$t->display(dirname(__FILE__).'/webaffair.html');
        exit();
    }
}
?>
