<?php 
require_once "../../../config.inc.php";

function getAddressDetails($member, $payment, $vars=array(), $errors=array()){
    global $t, $config;
//print_r($config); exit;
	$t->assign('config', $config);

    $fields = array('name_f', 'name_l', 'street', 'street2', 'city', 'zip', 'country');
    $address = array();
//	try to prefill address details
    foreach ($fields as $field){
        $v = $vars[$field]; // from POST
        if (!isset($vars[$field])){
            if (!$v){
				$v = $member[$field]; // from the member table
			}
            if (!$v && isset($member['data'][$field])){
				$v = $member['data'][$field]; // from the data in member table
			}
        }
        $address[$field] = $v;
    }
	$t->assign('address', $address);
    $t->assign('error', $errors);
    $t->assign('member', $member);
    $t->assign('payment', $payment);
    $t->display(dirname(__FILE__)."/templates/skypay_address.html");
}

function validateAddress($vars){
    $errors = array();
    if (!strlen($vars['name_f']))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR9;
    if (!strlen($vars['name_l']))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR10;
    if (!strlen($vars['street']))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR14;
    if (!strlen($vars['city']))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR15;
    if (!strlen($vars['zip']))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR17;
    if (!strlen($vars['country']))
        $errors[] = _PLUG_PAY_CC_CORE_ERROR18;
    return $errors;
}


/* GETTING THE ADDRESS, REGISTERING TRANSACTION etc*/
$t = & new_smarty();
$vars = get_input_vars();

settype($vars['member_id'], 'integer');
if (!$vars['member_id']){ // member unspecified
	fatal_error(_PLUG_PAY_CC_CORE_FERROR2, 0);
}
$member = $db->get_user($vars['member_id']);

if($config['auto_login_after_signup']){ // otherwise login not in session yet
	if(!$_SESSION['_amember_login']){ // member not logged in
		fatal_error(_PLUG_PAY_SKYPAY_FERROR2, 0);
	}
	if($member['login'] != $_SESSION['_amember_login']){ // different member in session
		fatal_error(_PLUG_PAY_SKYPAY_FERROR3, 0);
	}
}

settype($vars['payment_id'], 'integer');
if (!$vars['payment_id']){ // payment unspecified
	fatal_error(_PLUG_PAY_CC_CORE_FERROR3);
}
$payment = $db->get_payment($vars['payment_id']);

if ($payment['member_id'] != $vars['member_id']){ // payment doesn't match member
	fatal_error(_PLUG_PAY_CC_CORE_FERROR4);
}
if($payment['completed']){ // paid already
	fatal_error(sprintf(_PLUG_PAY_CC_CORE_FERROR5, "<a href='$config[root_url]/member.php'>","</a>"), 0);
}

if($vars['register_transaction']){ // means we are back after the address form
	$errors = validateAddress($vars);

	if(!$errors){
		// save billing address details within payment
		$payment_log = $db->get_payment($vars['payment_id']);
		$billingAddress = array();
		$fields = array('name_f', 'name_l', 'street', 'street2', 'city', 'zip', 'country');
		foreach ($fields as $v){
			$billingAddress[$v] = $vars[$v];
		}
		$payment_log['data']['billing'] = $billingAddress;
		$db->update_payment($payment_log['payment_id'], $payment_log);
		
		$payment = $db->get_payment($vars['payment_id']);
		require_once(dirname(__FILE__).'/skypay.php');
		$skypay = new Skypay($config['payment']['skypay']);
		$skypay->hookPayment($payment);
	}else{
		getAddressDetails($member, $payment, $vars, $errors);
	}
}else{
	getAddressDetails($member, $payment, $vars);
}