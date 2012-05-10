<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

/*
*
*
*     Author: Alexander Smith
*      Email: alexander@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: pagseguro payment plugin
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
            'paysys_id'   => 'pagseguro',
            'title'       => $config['payment']['pagseguro']['title'] ? $config['payment']['pagseguro']['title'] : "PagSecuro",
            'description' => $config['payment']['pagseguro']['description'] ? $config['payment']['pagseguro']['description'] : "Credit Card Payment",
            'public'      => 1,
        )
);

class payment_pagseguro extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $amount, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $orig_product_id = $product_id;
        if (is_array($product_id))
             $product_id = $product_id[0];
        $product = & get_product($product_id);
        if (count($orig_product_id)>1)
            $product->config['title'] = $config['multi_title'];
        $u  = $db->get_user($member_id);
        $vars = array(
            'email_cobranca'   => $this->config['merchant_email'],
            'tipo' => 'CP', 
            'moeda'     => $this->config['currency'],
            'item_id_1' => $payment_id,
            'item_descr_1' => "amember payment for ".$product->config['title'],
			'item_quant_1' => "1",
			'item_valor_1' => str_replace('.', '', sprintf('%.2f', $amount)),
			'image'		=> "btnComprarBR.jpg",
        );

        $vars1 = array();
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars = join('&', $vars1);		
        header("Location: https://pagseguro.uol.com.br/security/webpagamentos/webpagto.aspx?$vars");
        exit();
    }
    function get_dump($var){
        $s = "";
        foreach ($var as $k=>$v)
            $s .= "$k => $v<br />\n";
        return $s;
    }
    function log_debug($vars){
        global $db;
        $s = "PAGSEGURO DEBUG:<br />\n";
        foreach ($vars as $k=>$v)
            $s .= "[$k] => '$v'<br />\n";
        $db->log_error($s);
    }
	
    function postback_error($err,$vars){
        global $db;
        fatal_error("PagSeguro ERROR: $err<br />\n".$this->get_dump($vars));
    }
    function process_postback($vars){
        global $db;
		$this->log_debug($vars);
        $vars1 = $vars;
		$vars1['tipo'] = 'CP';
		$vars1['Comando'] = 'validar';
		$vars1['Token'] = $this->config['token'];
		$vars1['email_cobranca']= $this->config['merchant_email'];
        foreach ($vars1 as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars2[] = "$k=$v";
        }
        $vars2 = join('&', $vars2);
		$res = get_url("https://pagseguro.uol.com.br/Security/NPI/Default.aspx",$vars2);
		if($res!="VERIFICADO"){
            $this->postback_error($res,$vars1);
            return false;
		}			

        // process payment
		if(strtoupper($vars['StatusTransacao'])=='APROVADO')
		{
			$err = $db->finish_waiting_payment($vars['ProdID_1'], "pagseguro",$vars['TransacaoID'], str_replace('.', '', $vars['ProdValor_1']), $vars);
			if ($err) 
				$this->postback_error("finish_waiting_payment error: $err",$vars);
		}
    }
}
?>
