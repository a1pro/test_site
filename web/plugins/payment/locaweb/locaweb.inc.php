<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alexander Smith
*      Email: alexander@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Locaweb payment plugin
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
            'paysys_id'   => 'locaweb',
            'title'       => $config['payment']['locaweb']['title'] ? $config['payment']['locaweb']['title'] : "Locaweb",
            'description' => $config['payment']['locaweb']['description'] ? $config['payment']['locaweb']['description'] : "Credit card payments",
            'public'      => 1,
            'built_in_trials' => 1
        )
);


class payment_locaweb extends payment {
	function GerarTid ()
	{
		$shopid=$this->config[''];
		$pagamento=$this->config[''];
		$shopid_formatado = substr($shopid, 4, 5);		
		$hhmmssd = date("His").substr(sprintf("%0.1f",microtime()),-1);
		$datajuliana = sprintf("%03d",(date("z")+1));
		$dig_ano = substr(date("y"), 1, 1);
		return $shopid_formatado.$dig_ano.$datajuliana.$hhmmssd.$pagamento;
	}
	
    function do_payment($payment_id, $member_id, $product_id,$price, $begin_date, $expire_date, &$vars){
	
	}
    function send_payment($payment_id, $member_id, $product_id,$price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        if (is_array($product_id)) $product_id = $product_id[0];
        $product = & get_product($product_id);
        if (count($orig_product_id)>1) $product->config['title'] = $config['multi_title'];
        $u  = $db->get_user($member_id);
        $vars = array(
            'identificacao'   => $this->config['identificacao'],
            'ambiente'	=> 'producao', 
            'modulo'	=> 'CCTYPE',
            'operacao'	=> 'Pagamento',
            'tid'		=> $this->GerarTid(),
			'orderid'	=> $payment_id,
			'order'		=> $config['root_url'],
			'price'		=> $price,
			'damount'	=> $member_id
        );

        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);
        header("Location: https://comercio.locaweb.com.br/comercio.comp?$vars");
        exit();
    }
}

?>
