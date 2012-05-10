<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: google checkout payment plugin
*    FileName $RCSfile$
*    Release: 3.1.9PRO ($Revision: 4234 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

include "../../../config.inc.php";
$this_config = $plugin_config['payment']['google_checkout'];

require_once($config['root_dir'].'/plugins/payment/google_checkout/gCheckout.php');

if (!isset($_SERVER['PHP_AUTH_USER'])) { // HTTP Authentication
    header('WWW-Authenticate: Basic realm="aMember Google Checkout"');
    header('HTTP/1.0 401 Unauthorized');
    $err = "Error. Google Checkout ResponseHandler - username is not entered";
    $db->log_error($err);
    exit();
}

// checking name and password
if( ($_SERVER['PHP_AUTH_USER'] != $this_config["merchant_id"])  
 || ($_SERVER['PHP_AUTH_PW']  != $this_config["merchant_key"])){
    header('WWW-Authenticate: Basic realm="aMember Google Checkout"');
    header('HTTP/1.0 401 Unauthorized');
    $err = "Error. Google Checkout ResponseHandler - Incorrect username or password entered";
    $db->log_error($err . " (".$_SERVER['PHP_AUTH_USER'].":".$_SERVER['PHP_AUTH_PW'].")");
    exit();
}


$checkout_xml_schema = 'http://checkout.google.com/schema/2';
$gCheckout = new gCheckout($this_config["merchant_id"], $this_config["merchant_key"], $cart_expires = '', 
                           $checkout_xml_schema, $this_config["currency"], $this_config["debug"], $this_config["sandbox"], $this_config["allow_create"]);


// Retrieve the XML sent in the HTTP POST request to the ResponseHandler
$xml_response = $HTTP_RAW_POST_DATA;

// Get rid of PHP's magical escaping of quotes 
if (get_magic_quotes_gpc()) {
    $xml_response = stripslashes($xml_response);
}

// Log the XML received in the HTTP POST request
//$gCheckout->LogMessage($xml_response, $debug_only_msg = true);

/*
 * Call the ProcessXmlData function. The ProcessXmlData will route 
 * the XML data to the function that handles the particular type
 * of XML message contained in the POST request.
 */
$gCheckout->ProcessXmlData($xml_response);

/// resend postback if necessary
if ($this_config['resend_postback'])
    foreach (preg_split('/\s+/', $this_config['resend_postback']) as $url){
        $url = trim($url);
        $purl = parse_url($url);
        if ($url == '' || $_SERVER['PHP_SELF'] == $purl['path']) continue;
        $response = $gCheckout->GetCurlResponse($xml_response, $url, $this_config['merchant_id'], $this_config['merchant_key'], $add_headers = true);
        $logfile = '';
        $gCheckout->LogMessage("Google Checkout Resend Postback to: " . $url, $debug_only_msg = false);
    }

?>
