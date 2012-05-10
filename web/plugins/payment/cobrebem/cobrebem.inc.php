<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
 
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: cobrebem payment plugin
*    FileName $RCSfile$
*    Release: 3.1.8PRO ($Revision: 3498 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


global $config;

require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

class payment_cobrebem extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('cobrebem', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;                            
        return cc_core_get_cancel_link('cobrebem', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['cobrebem']['title'] ? $config['payment']['cobrebem']['title'] : "Cobrebem",
            'description' => $config['payment']['cobrebem']['description'] ? $config['payment']['cobrebem']['description'] : "Credit card payments",
//            'phone' => 2,
            'code' => 1
        );
    }
	function from_res($name,$str)
	{
		$pos=@strpos($str,$name.">");
		if(!($pos===false))
		{
			$pos+=(strlen($name)+1);
			$res=@substr($str,$pos,@strpos($str,"</$name>",$pos)-$pos);
			return trim($res);
		}
		else
		return "";
	}
    function run_transaction($vars,$path="APC"){
        foreach ($vars as $kk=>$vv){
            $v = urlencode($vv);
            $k = urlencode($kk);
            $vars1[] = "$k=$v";
        }
        $vars1 = join('&', $vars1);
        if ($this->config['testing'])
			$url="https://teste.aprovafacil.com/cgi-bin/APFW/{$this->config['login']}/$path";
		else
			$url="https://aprovafacil.com/cgi-bin/APFW/{$this->config['login']}/$path";
        $ret = cc_core_get_url($url, $vars1);
		//echo htmlentities($ret);
		$res = array(
            'TransacaoAprovada' => $this->from_res("TransacaoAprovada",$ret),
            'ResultadoSolicitacaoAprovacao' => $this->from_res("ResultadoSolicitacaoAprovacao",$ret),
            'CodigoAutorizacao' => $this->from_res("CodigoAutorizacao",$ret),
            'Transacao' => $this->from_res("Transacao",$ret),
            'CartaoMascarado' => $this->from_res("CartaoMascarado",$ret),
            'NumeroDocumento' => $this->from_res("NumeroDocumento",$ret),
            'ComprovanteAdministradora' => $this->from_res("ComprovanteAdministradora",$ret)
        );
		if(!$res['TransacaoAprovada']) $res['html'] = htmlentities($ret);
        return $res;
    }
    function void_transaction($pnref, &$log){
        $vars = array("Transacao" => $pnref);
        $vars_l = $vars;
        $log[] = $vars_l;
        $res = $this->run_transaction($vars,"CAN");
        $log[] = $res;
        return $res;
    }
    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, 
        $currency, $product_description, 
        $charge_type, $invoice, $payment){
        global $config;
        $log = array();
        //////////////////////// cc_bill /////////////////////////

        $vars = array(
            "NumeroDocumento"    => "amember_cobrebem_plugin",
            "ValorDocumento"  => sprintf("%.2f",$amount),
            "QuantidadeParcelas" => "01",
			"NumeroCartao" => $cc_info['cc_number'],
			"MesValidade" => $cc_info['cc_expire_Month'],
			"AnoValidade" => $cc_info['cc_expire_Year'],
			"CodigoSeguranca" => $cc_info['cc_code'],
			"PreAutorizacao" => "N",
			"EnderecoIPComprador" => $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR']			
        );
        
        // prepare log record
        $vars_l = $vars;
		$vars_l['NumeroCartao'] = preg_replace('/./', '*', $vars['NumeroCartao']);
		$vars_l['CodigoSeguranca'] = preg_replace('/./', '*', $vars['CodigoSeguranca']);
        $log[] = $vars_l;
        /////
        $res = $this->run_transaction($vars);
        $log[] = $res;

        if ($res['TransacaoAprovada'] == 'True'){
            if ($charge_type == CC_CHARGE_TYPE_TEST)
                $this->void_transaction($res['Transacao'], $log);
            return array(CC_RESULT_SUCCESS, "", $res['Transacao'], $log);
        } elseif ($res['TransacaoAprovada'] == 'False') {
            return array(CC_RESULT_DECLINE_PERM, $res['ResultadoSolicitacaoAprovacao'], "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, $res['ResultadoSolicitacaoAprovacao'], "", $log);
        }
    }
}

function cobrebem_get_member_links($user){
    return cc_core_get_member_links('cobrebem', $user);
}

function cobrebem_rebill(){
    return cc_core_rebill('cobrebem');
}
                                        
cc_core_init('cobrebem');
?>
