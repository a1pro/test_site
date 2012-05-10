<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");

if(strpos(__FILE__, ':') !== false) {
	$path_delimiter = ';';
}
else {
	$path_delimiter = ':';
}
$api_path = dirname(__FILE__).'/api';
ini_set('include_path', ini_get('include_path').$path_delimiter.$api_path);

require_once("ThinMPI.php");
require_once("DirectoryRequest.php");
require_once("DirectoryResponse.php");
require_once("AcquirerTrxRequest.php");
require_once("AcquirerStatusRequest.php");

add_paysystem_to_list(
array(
            'paysys_id' => 'ideal',
            'title'     => $config['payment']['ideal']['title'] ? $config['payment']['ideal']['title'] : _PLUG_PAY_IDEAL_TITLE,
            'description' => $config['payment']['ideal']['description'] ? $config['payment']['ideal']['description'] : sprintf(_PLUG_PAY_IDEAL_DESC, '<a href="http://www.ideal.com" target=_blank>', '</a>'),
            'public'    => 1
        )
);

class payment_ideal extends payment {
    function do_payment($payment_id, $member_id, $product_id,
            $price, $begin_date, $expire_date, &$vars){

        global $config, $db;
        $products = $product_id;
        $orig_product_id = $product_id;
        if (is_array($product_id))
             $product_id = $product_id[0];

        $product = & get_product($product_id);
        if (count($orig_product_id)>1)
            $product->config['title'] = $config['multi_title'];

        $member = $db->get_user($member_id);

    	//Put information from form in variables
    	$amount = $price * 100; //Multiply amount by 100 to remove decimals

    	$issuerID = $_POST['issuerID'];
    	if (!$issuerID)
    	    $issuerID = $_GET['issuerID'];
	if (!$issuerID)
	    $issuerID = $payment['data']['issuerID'];
    	
    	if($issuerID==0)
    	{
    		//print("Kies uw bank uit de lijst om met iDEAL te betalen<br>");
    		//exit();
    		$db->log_error ('Kies uw bank uit de lijst om met iDEAL te betalen');
    		return 'iDeal payment error. Please contact site administrator.';
    	}
    	//Create ThinMPI instance
    	$rule = new ThinMPI();

    	//Create TransactionRequest
    	$data = & new AcquirerTrxRequest();
    	
    	//Set parameters for TransactionRequest
    	$data->setIssuerID($issuerID);
    	$data->setMerchantReturnURL( $rule->conf['MERCHANTRETURNURL'] ); 
    	$data->setPurchaseID( $payment_id  );
    	$data->setAmount($amount );
    	$data->setCurrency( $rule->conf['CURRENCY'] );
    	$data->setExpirationPeriod( $rule->conf['EXPIRATIONPERIOD'] );
    	$data->setLanguage( $rule->conf['LANGUAGE'] );
    	
    	//$description = $rule->conf['DESCRIPTION'];
    	//$description = $product->config['description'];
    	$description = substr($product->config['title'], 0, 32);
    	$data->setDescription( $description );
    	
    	$data->setEntranceCode( $rule->conf['ENTRANCECODE'] );
    	//$data->setAcqURL( $rule->conf['ACQUIRERURL'] );
    	
    	$result = new AcquirerTrxResponse();
    	
    	//Process Request
    	$result = $rule->ProcessRequest( $data );
    	
    	if($result->isOK()){
    		
    		$transactionID = $result->getTransactionID();
    		//Here you should store the transactionID along with the order (in the database
    		//of your webshop system) so you can later retrieve the order with the 
    		//transactionID.
    		$payment = $db->get_payment($payment_id);
    		$payment['receipt_id'] = $transactionID;
    		$db->update_payment($payment_id, $payment);
    
    		//Get IssuerURL en decode it
    		$ISSURL = $result->getIssuerAuthenticationURL();
    		$ISSURL = html_entity_decode($ISSURL);
    	
    		//Redirect the browser to the issuer URL
            html_redirect($ISSURL, '', 'Please wait', 'Please wait');
            exit();
            
    	} else {
    	    
    		//TransactionRequest failed, inform the consumer
    		//print("Er is helaas iets misgegaan. Foutmelding van iDEAL:<br>");
    		$Msg = $result->getErrorMessage();
    		$db->log_error ('Er is helaas iets misgegaan. Foutmelding van iDEAL: ' . $Msg);
    		return 'iDeal payment error. Please contact site administrator.';
    		
    	}

    }
}


function ideal_get_issuers_list(){
    global $db, $config;
    
	//Here comes the interesting part: the Directory Request itself.
	//Create a directory request
	$data = & new DirectoryRequest();
	//Set parameters for directory request
	
	//Create thinMPI instance
	$rule = new ThinMPI();

	//Process directory request
	//print_r ($data);
	$result = $rule->ProcessRequest($data);
	//print_r ($result);
	
	if(!$result->isOK()){
		$Msg = $result->getErrorMessage();
	    $db->log_error ("Er is op dit moment geen betaling met iDEAL mogelijk.<br />Foutmelding van iDEAL: $Msg<br />");
	} else 	{
		//Get issuerlist
		$issuerArray = $result->getIssuerList();
		if(count($issuerArray) == 0){
		    $db->log_error ("Lijst met banken niet beschikbaar, er is op dit moment geen betaling met iDEAL mogelijk.");
		} else {
			//Directory request succesful and at least 1 issuer
			for($i=0;$i<count($issuerArray);$i++){
				if($issuerArray[$i]->issuerList == "Short"){
					$issuerArrayShort[]=$issuerArray[$i];
				} else {
					$issuerArrayLong[]=$issuerArray[$i];
				}
			}
    		//Create a selection list
    		$ideal_issuers = array();

			//Create an option tag for every issuer
			for($i=0;$i<count($issuerArrayShort);$i++){
				$issuer = array();
				$issuer['issuerID'] = $issuerArrayShort[$i]->issuerID;
				$issuer['issuerName'] = $issuerArrayShort[$i]->issuerName;
				$ideal_issuers['short'][] = $issuer;
			}
			for($i=0;$i<count($issuerArrayLong);$i++){
				$issuer = array();
				$issuer['issuerID'] = $issuerArrayLong[$i]->issuerID;
				$issuer['issuerName'] = $issuerArrayLong[$i]->issuerName;
				$ideal_issuers['long'][] = $issuer;
			}
			
    		$db->config_set('ideal_issuers', $ideal_issuers, 1);
		}
	}
}

if (!$config['ideal_issuers']){
    ideal_get_issuers_list();
}

$config['ideal_options'] = array();
$config['ideal_options'][] = "Kies uw bank...";

if ($config['ideal_issuers']['short']){
    foreach ($config['ideal_issuers']['short'] as $short){
        $key = $short['issuerID'];
        $config['ideal_options'][$key] = $short['issuerName'];
    }
}

if ($config['ideal_issuers']['long']){
    $config['ideal_options'][] = "---Overige banken---";
    foreach ($config['ideal_issuers']['long'] as $long){
        $key = $long['issuerID'];
        $config['ideal_options'][$key] = $long['issuerName'];
    }
}

setup_plugin_hook('daily', 'ideal_get_issuers_list');
?>
