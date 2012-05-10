<?php

  include "../../../config.inc.php";
  $this_config = $plugin_config['payment']['safepay'];

  $passPhraseLocal = $this_config['secret_id'];
  $confirmUsing = "curl"; // values: "curl" or "socket"

function get_dump($var){
//dump of array
    $s = "";
    foreach ((array)$var as $k=>$v)
        $s .= "$k => $v<br />\n";
    return $s;
}

function safepay_error($msg){
    global $order_id, $payment_id, $db;
    global $vars;
    $db->log_error("SAFEPAY ERROR: $msg<br />\n".get_dump($vars));
    die($msg);
}

function safepay_get_begin_date($member_id, $product_id){
    global $db;
    $payments = & $db->get_user_payments(intval($member_id));
    $date = date('Y-m-d');
    foreach ($payments as $p){
        if (($p['product_id'] == $product_id) &&
            ($p['expire_date'] > $date) &&
            ($p['completed'] > 0)
            ) 
            $date = $p['expire_date'];
    }
    list($y,$m,$d) = split('-', $date);
    $date = date('Y-m-d', mktime(0,0,0,$m, $d, $y));
    return $date;
}

$vars = get_input_vars();

$db->log_error("safepay DEBUG: " . get_dump($vars));

  ///////////////////////////////////////////////////////////////////////


  function confirmTransaction(){
    global $db, $confirmUsing, $confirmScript, $confirmationID, $transactionID, $totalAmount;
    $confirmScriptFull = $confirmScript["host"] . "/" . $confirmScript["path"];

    if(isset($confirmationID) && is_numeric($confirmationID) && $confirmationID > 0){
      $data = "confirmID=$confirmationID&trid=$transactionID&amount=$totalAmount";
      if($confirmUsing == "curl"){
        
        $answer = get_url($confirmScriptFull, $data);
        
        /*
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $confirmScriptFull);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $answer = curl_exec($ch);
        $curlErr = curl_error($ch);
        if(strlen($curlErr) > 0) safepay_error("CURL ERROR on <$confirmScriptFull>: " . $curlErr . "\n");
        curl_close($ch);
        */
        
        $db->log_error("Confirmation script answer: " . $answer . "\n"); //not an error
        if(strlen($answer) > 0 && strpos($answer, "SUCCESS") !== false) $answer = 1;
        else $answer = 0;
      }
      elseif($confirmUsing == "socket"){
        if($confirmScript["ssl"]){
          $port = "443";
          $ssl = "ssl://";
        }
        else $port = "80";
        $fp = @fsockopen($ssl . $confirmScript["host"], $port, $errnum, $errstr, 30); 
        if(!$fp){
          safepay_error("SOCKET ERROR! $errnum: $errstr\n");
          $answer = 0;
        } 
        else{ 
          fputs($fp, "POST {$confirmScript[path]} HTTP/1.1\r\n"); // PATH
          fputs($fp, "Host: {$confirmScript[host]}\r\n");         // HOST
          fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n"); 
          fputs($fp, "Content-length: ".strlen($data)."\r\n"); 
          fputs($fp, "Connection: close\r\n\r\n"); 
          fputs($fp, $data . "\r\n\r\n"); 
          while(!feof($fp)) $answer .= @fgets($fp, 1024);
          fclose($fp); 
          $db->log_error("Confirmation script answer: " . $answer . "\n"); //not an error
          if(strlen($answer) > 0 && strpos($answer, "SUCCESS") !== false) $answer = 1;
          else $answer = 0;
        }
      }
    }
    else $answer = 1;
    return $answer;
  }

  $itestmode            = $vars["itestmode"];           // TEST MODE FLAG: 'on' for TEST mode, 'off' or empty for REAL TRANSACTION mode
  $_ipn_act             = $vars["_ipn_act"];                    // type: '_ipn_payment' for purchases and '_ipn_subscription' for subscriptions
  $result               = $vars["result"];                      // result code (1 - success, 0 - failure)
  $receiver             = $vars["ireceiver"];           // your SPS username
  $payer                = $vars["ipayer"];                      // buyer's SPS username
  $amount               = $vars["iamount"];                     // service / product cost
  $passPhrase           = $vars["passPhrase"];          // the MD5 hash of your secret passphrase (Note: MD5 Hash code will be all UPPERCASE!)
  $itemName             = $vars["itemName"];                    // product/service name
  $itemNum              = $vars["itemNum"];                     // product/service id
  $itemDescr            = $vars["idescr"];                      // product/service description
  $custom1              = $vars["custom1"];                     // payment_id
  $custom2              = $vars["custom2"];
  $custom3              = $vars["custom3"];
  $custom4              = $vars["custom4"];
  $custom5              = $vars["custom5"];
  
  $payment_id                           = $custom1;
  $pm = $db->get_payment($payment_id);

  if($_ipn_act == '_ipn_payment' || !isset($_ipn_act) || strlen($_ipn_act) == 0){
    
    $itemQuantity         = $vars["iquantity"];         // product/service quantity
    
    if($itestmode != 'on'){                                                             // if REAL TRANSACTION MODE, getting some specific only for this mode variables
      
      $confirmationID     = $vars["confirmID"];         // confirmation ID
      $transactionID      = $vars["tid"];                               // transaction ID
      
    }
    // shipping / physical product delivery: 
    //    1 - no physically delivered product
    //    2 - optional physical delivery
    //    3 - shipping required
    $deliveryRequirement  = $vars["idelivery"];           
    
    if($deliveryRequirement == '2' || $deliveryRequirement == '3'){             // shipping information (only if delivery required)
      $shippingAddress  = $vars["ishaddress"];
      $shippingCity     = $vars["ishcity"];
      $shippingState    = $vars["ishstate"];
      $shippingZip      = $vars["ishzip"];
      $shippingCountry  = $vars["ishcountry"];
    }
  }
  elseif($_ipn_act == '_ipn_subscription'){
    $cycleLength          = $vars["cycleLength"];
    $cycles               = $vars["cycles"];
    $trialPeriod          = $vars["trialPeriod"];
    $trialAmount          = $vars["trialAmount"];
    $trialCycles          = $vars["trialCycles"];
    if($itestmode != 'on'){
      $transactionID      = $vars["tid"];
    }
  }

  $confirmScript = array(
    "host" => "https://www.safepaysolutions.com",
    "path" => "index.php",
    "ssl" => 1 // 1 for https, 0 for http
  );

  $error = false;

  $db->log_error("\n\nEXECUTION TIME: " . date("m/d/y h:i:s") . "\n"); //not an error

  // if the result code is 1 then payment succesfull, but don't forget 
  // to check the secret passphrase (if it is defined in your SPS backoffice)
  if($result == 1 && ($_ipn_act == '_ipn_payment' || !isset($_ipn_act) || strlen($_ipn_act) == 0)){
    // checking secret passphrase 
    if(isset($passPhraseLocal) && (strlen($passPhraseLocal) > 0) && (strtoupper(md5($passPhraseLocal)) != $passPhrase)){
      safepay_error(_PLUG_PAY_SAFEPAY_ERROR);
      $error = true;
    }
    // checking amount format 
    if(!is_numeric($amount) || $amount < 0){
      safepay_error(sprintf(_PLUG_PAY_SAFEPAY_ERROR2, $amount));
      $error = true;
    }
    // checking quantity format 
    if(!is_numeric($itemQuantity) || $itemQuantity < 0){
      safepay_error(sprintf(_PLUG_PAY_SAFEPAY_ERROR3, $itemQuantity));
      $error = true;
    }
    if($itestmode != 'on' && (!isset($transactionID) || strlen($transactionID) == 0 || !preg_match("/^([0-9]){8}-([0-9]){1,}$/", $transactionID))){
      safepay_error(sprintf(_PLUG_PAY_SAFEPAY_ERROR4, $transactionID));
      $error = true;
    }

    // checking finished, POST data looks good 
    if($error === false){
      // calculation total amount
      $totalAmount = $amount*$itemQuantity;
      if($itestmode != 'on'){
        $confirmed = confirmTransaction();
        if($confirmed == 1){
          // everything is ok, payment is good
          $db->log_error("Everything looks ok, money on your SPS account (purchase)\n"); //not an error

                                // process payment
                                $err = $db->finish_waiting_payment($payment_id, 'safepay', $pnref, $amount, $vars);
                                if ($err) safepay_error("finish_waiting_payment error: $err");
                                $_GET['payment_id'] = $_POST['payment_id'] = $payment_id;
                                include '../../../thanks.php';

        }
        else{
          // transaction not confirmed, money is not on your SPS account
          safepay_error(_PLUG_PAY_SAFEPAY_ERROR5);

        }
      }
      else{
        // everything is ok, TEST TRANSACTION, actual charged amount is $0.00!!!
        $db->log_error("Everything is ok. TEST PURCHASE!\n"); //not an error

      }
    }
  }
  elseif($result == 1 && $_ipn_act == '_ipn_subscription'){
    // checking secret passphrase 
    if(isset($passPhraseLocal) && (strlen($passPhraseLocal) > 0) && (strtoupper(md5($passPhraseLocal)) != $passPhrase)){
      safepay_error(_PLUG_PAY_SAFEPAY_ERROR);
      $error = true;
    }
    // checking amount format 
    if(!is_numeric($amount) || $amount < 0){
      safepay_error(_PLUG_PAY_SAFEPAY_ERROR2);
      $error = true;
    }

    // checking finished, POST data looks good 
    if($error === false){
      if($itestmode != 'on'){
        // everything is ok, subscription is good
        $db->log_error("Everything looks ok (subscription)\n"); //not an error

                                if ($pm){
                                    if ($pm['completed']){ // this is completed, we should add new one
                                        $product = get_product($pm['product_id']);
                                        $beg_date = safepay_get_begin_date($pm['member_id'], $pm['product_id']);
                                        list($y,$m,$d) = split('-', $beg_date);
                                        $beg_date1 = date('Y-m-d', mktime(0,0,0,$m, $d, $y) + 3600 * 24);
                                        $payment_id = $db->add_waiting_payment(
                                            $pm['member_id'], 
                                            $pm['product_id'], 
                                            $pm['paysys_id'], 
                                            $amount, 
                                            $beg_date1, 
                                            $product->get_expire($beg_date1),
                                            array('ORIG_ID' => $payment_id)
                                        );
                                    } 
                                }
                        // process payment
                        $err = $db->finish_waiting_payment($payment_id, 'safepay', $pnref, $amount, $vars);
                        if ($err) safepay_error("finish_waiting_payment error: $err");
                        $_GET['payment_id'] = $_POST['payment_id'] = $payment_id;
                        include '../../../thanks.php';

      }
      else{
        // everything is ok, TEST TRANSACTION, actual charged amount is $0.00!!!
        $db->log_error("Everything is ok. TEST SUBSCRIPTION!\n"); //not an error

      }
    }
  }
  else{
    safepay_error(sprintf(_PLUG_PAY_SAFEPAY_ERROR6, $result));
  }


?>
