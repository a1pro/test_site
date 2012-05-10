<?php

if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

global $config;
require_once($config['root_dir']."/plugins/payment/cc_core/cc_core.inc.php");

function realex_get_dump($var){
//dump of array
    $s = "";
    foreach ($var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

class payment_realex extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){
        return cc_core_do_payment('realex', $payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, $vars);
    }
    function get_cancel_link($payment_id){
        global $db;
        return cc_core_get_cancel_link('realex', $payment_id);
    }
    function get_plugin_features(){
        return array(
            'title' => $config['payment']['realex']['title'] ? $config['payment']['realex']['title'] : _PLUG_PAY_REALEX_TITLE,
            'description' => $config['payment']['realex']['description'] ? $config['payment']['realex']['description'] : _PLUG_PAY_REALEX_DESC,
            'code' => 2,
            'type_options' => array(
                'VISA' => 'Visa/Delta',
                'MC' => 'Mastercard',
                'SWITCH' => 'Switch/Solo',
                'AMEX' => 'American Express',
                'LASER' => 'Laser'
    		),
            'name_f' => 2,
	    'maestro_solo_switch' => 1,
            'currency' => array(
                'EUR' => 'Euro',
                'GBP' => 'Pound Sterling',
                'USD' => 'US Dollar',
                'SEK' => 'Swedish Krona',
                'CHF' => 'Swiss Franc',
                'HKD' => 'Hong Kong Dollar',
                'JPY' => 'Japanese Yen'
            )
        );
    }

    function parse_response($ret = ''){
    	$res = array();
    	$fields = array('merchantid', 'orderid', 'authcode', 'result', 'cvnresult', 'message', 'pasref', 'sha1hash', 'md5hash');

    	foreach ($fields as $field){
    	    if (preg_match("/<".$field.">([^<]*)<\/".$field.">/i", $ret, $matches)){
    		$res[$field] = $matches[1];
    	    }
    	}
    	if (preg_match("/<response timestamp=\"(.*)\">/i", $ret, $matches))
    	    $res['timestamp'] = $matches[1];

    	return $res;
    }

    function run_transaction($vars){

        $request = "<request timestamp=\"".$vars['timestamp']."\" type=\"".$vars['auth_type']."\">
            <merchantid>".$vars['merchantid']."</merchantid>
        ";

        if ($vars['account'])
            $request .= "<account>".$vars['account']."</account>";

        $request .= "
            <orderid>".$vars['orderid']."</orderid>
            <amount currency=\"".$vars['currency']."\">".$vars['amount']."</amount>
            <card>
                <number>".$vars['number']."</number>
                <expdate>".$vars['expdate']."</expdate>
                <chname>".$vars['chname']."</chname>
                <type>".$vars['type']."</type>
        ";

        if ($vars['issueno'] && $vars['type'] == 'SWITCH')
            $request .= "<issueno>".$vars['issueno']."</issueno>";

        if ($vars['cvn'])
            $request .= "
                <cvn>
                    <number>".$vars['cvn']."</number>
                    <presind>1</presind>
                </cvn>
            ";

        $request .= "
            </card>
            <autosettle flag=\"1\" />
            <comments>
                <comment id=\"1\">".$vars['comment']."</comment>
            </comments>
            <tssinfo>
                <custnum>".$vars['member_id']."</custnum>
                <prodid>".$vars['product_id']."</prodid>
                <varref>".$vars['payment_id']."</varref>
                <custipaddress>".$vars['ip']."</custipaddress>
                <address type=\"billing\">
                    <code>".$vars['zip']."</code>
                    <country>".$vars['country']."</country>
                </address>
                <address type=\"shipping\">
                    <code>".$vars['zip']."</code>
                    <country>".$vars['country']."</country>
                </address>
            <country>".$vars['country']."</country>
            </tssinfo>
        ";

        if ($vars['md5hash'])
            $request .= "<md5hash>".$vars['md5hash']."</md5hash>";
        else
            $request .= "<sha1hash>".$vars['sha1hash']."</sha1hash>";

        $request .= "
        </request>
        ";

        $url = "https://epage.payandshop.com/epage-remote.cgi";
	    $ret = cc_core_get_url($url, $request);

        $res = $this->parse_response($ret);
        global $db;
        $db->log_error("RealEx RESPONSE:<br />".realex_get_dump($res));

        $hash = $res['timestamp'] . "." . $res['merchantid'] . "." . $res['orderid'] . "." .
                $res['result'] . "." . $res['message'] . "." . $res['pasref'] . "." . $res['authcode'];

        $response_valid = false;
        if ($res['md5hash']){
            $hash = md5($hash);
            $hash = $hash . "." . $this_config['secret'];
            $hash = md5($hash);
            if ($res['md5hash'] == $hash) $response_valid = true;
        } else {            $hash = sha1($hash);
            $hash = $hash . "." . $this_config['secret'];
            $hash = sha1($hash);
            if ($res['sha1hash'] == $hash) $response_valid = true;
        }

        if ($response_valid){
            if ($res['result'] == '00')
                $return['RESULT']    = 'Approved';
            else
                $return['RESULT']    = 'Declined';
        } else {
            $return['RESULT'] = 'Invalid';
        }

        $return['RESPMSG']   = $res['message'];
        $return['PNREF']     = $res['pasref'];
        $return['CVV_VALID'] = $res['cvnresult'];
        $return['TRANSID']   = $res['authcode'];

        return $return;
    }

    function void_transaction($pnref, &$log, $transid, $vars, $cc){
    	srand(time());

        $vars['auth_type'] = 'void';
        $vars['pasref']    = $pnref;
        $vars['authcode']  = $transid;

        $vars_l = $vars;

        $vars_l['number'] = $cc;
        if ($vars['cvn'])
            $vars_l['cvn'] = preg_replace('/./', '*', $vars['cvn']);

        $log[] = $vars_l;

        $db->log_error("RealEx DEBUG:<br />".realex_get_dump($vars_l));
        $res = $this->run_transaction($vars);
        $log[] = $res;
        return $res;
    }

    /*************************************************************
      cc_bill - do real cc bill
    ***************************************************************/
    function cc_bill($cc_info, $member, $amount, $currency, $product_description, $charge_type, $invoice, $payment){

        global $db, $config, $plugin_config;

        $this_config   = $plugin_config['payment']['realex'];
        $product = $db->get_product($payment['product_id']);

        $log = array();
        //////////////////////// cc_bill /////////////////////////

        srand(time());
        $auth_type = 'auth';
        if ($charge_type == CC_CHARGE_TYPE_TEST){
            $amount = "1.00";
            $auth_type = 'auth';
        }

        if ($cc_info['cc_name_f'] == ''){
            $cc_info['cc_name_f'] = $member['name_f'];
            $cc_info['cc_name_l'] = $member['name_l'];
        }

        $vars = array(
            'timestamp'    => date("YmdHis"),
            'auth_type'    => $auth_type,
            'merchantid'   => $this_config['merchant_id'],
            'account'      => $this_config['account'],
            'orderid'      => $payment['payment_id'] . '-' . rand(100, 999),
            'currency'     => $currency ? $currency : 'EUR',
            'amount'       => intval($amount * 100),
            'number'       => $cc_info['cc_number'],
            'expdate'      => $cc_info['cc-expire'],
            'chname'       => $cc_info['cc_name_f'] . " "  . $cc_info['cc_name_l'],
            'type'         => $cc_info['cc_type'],
            'issueno'      => $cc_info['cc_issuenum'],
            'cvn'          => $cc_info['cc_code'],
            'comment'      => $product['title'],
            'member_id'    => $member['member_id'],
            'product_id'   => $payment['product_id'],
            'payment_id'   => $payment['payment_id'],
            'ip'           => $member['remote_addr']  ? $member['remote_addr'] : $_SERVER['REMOTE_ADDR'],
            'zip'          => $cc_info['cc_zip'],
            'country'      => $cc_info['cc_country']
            );

        $hash = $vars['timestamp'] . "." . $vars['merchantid'] . "." . $vars['orderid'] . "." . $vars['amount'] . "." . $vars['currency'] . "." . $vars['number'];
        $hash = md5($hash);
        $hash = $hash . "." . $this_config['secret'];
        $hash = md5($hash);

        //$vars['sha1hash']  = $hash;
        $vars['md5hash']   = $hash;

        // prepare log record
        $vars_l = $vars;

        $vars_l['number'] = $cc_info['cc'];
        if ($vars['cvn'])
            $vars_l['cvn'] = preg_replace('/./', '*', $vars['cvn']);

        $log[] = $vars_l;
        /////
        $db->log_error("RealEx DEBUG:<br />".realex_get_dump($vars_l));

        $res = $this->run_transaction($vars);
        $log[] = $res;

        if (preg_match("/Approved/i", $res['RESULT'])){
            if ($charge_type == CC_CHARGE_TYPE_TEST){
                $this->void_transaction($res['PNREF'], $log, $res['TRANSID'], $vars, $cc_info['cc']);
    	    }
            return array(CC_RESULT_SUCCESS, "", $res['PNREF'], $log);
        } elseif (preg_match("/Declined/i", $res['RESULT'])) {
            return array(CC_RESULT_DECLINE_PERM, ($res['RESPMSG'] ? $res['RESPMSG'] : $res['RESULT']), "", $log);
        } else {
            return array(CC_RESULT_INTERNAL_ERROR, ($res['RESPMSG'] ? $res['RESPMSG'] : $res['RESULT']), "", $log);
        }
    }
}

function realex_get_member_links($user){
    return cc_core_get_member_links('realex', $user);
}

function realex_rebill(){
    return cc_core_rebill('realex');
}

cc_core_init('realex');
?>